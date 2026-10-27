<?php

use App\Enums\AiModelAvailabilityStatus;
use App\Enums\AiModelCapability;
use App\Enums\AiModelModality;
use App\Enums\AiProviderHealthStatus;
use App\Models\Admin;
use App\Models\AiConfigurationAudit;
use App\Models\AiModel;
use App\Models\AiProvider;
use App\Models\AiWorkloadRoute;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Permission::create([
        'name' => 'manage ai models',
        'guard_name' => 'admin',
    ]);
});

function createAiConfigurationAdmin(bool $authorized = true): Admin
{
    $admin = Admin::create([
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('password'),
    ]);

    if ($authorized) {
        $admin->givePermissionTo('manage ai models');
    }

    return $admin;
}

function aiConfigurationRouteNames(): array
{
    return [
        'admin.ai-configuration.index',
        'admin.ai-configuration.providers',
        'admin.ai-configuration.models',
        'admin.ai-configuration.workload-routes',
    ];
}

test('guests are redirected to admin login from every AI configuration route', function () {
    foreach (aiConfigurationRouteNames() as $routeName) {
        $this->get(route($routeName))->assertRedirectToRoute('admin.login');
    }
});

test('a regular web user cannot access AI configuration with the web guard', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.ai-configuration.index'))
        ->assertRedirectToRoute('admin.login');

    $this->assertGuest('admin');
});

test('permissionless and editor admins receive forbidden responses', function () {
    $permissionlessAdmin = createAiConfigurationAdmin(false);

    $this->actingAs($permissionlessAdmin, 'admin')
        ->get(route('admin.ai-configuration.index'))
        ->assertForbidden();

    $editor = createAiConfigurationAdmin(false);
    $editor->assignRole(Role::create(['name' => 'Editor', 'guard_name' => 'admin']));

    $this->actingAs($editor, 'admin')
        ->get(route('admin.ai-configuration.providers'))
        ->assertForbidden();
});

test('authorized and super admins can access every AI configuration page', function () {
    $authorizedAdmin = createAiConfigurationAdmin();

    $this->actingAs($authorizedAdmin, 'admin');
    foreach (aiConfigurationRouteNames() as $routeName) {
        $this->get(route($routeName))->assertOk();
    }

    $superAdmin = createAiConfigurationAdmin(false);
    $superAdmin->assignRole(Role::create(['name' => 'Super Admin', 'guard_name' => 'admin']));

    $this->actingAs($superAdmin, 'admin');
    foreach (aiConfigurationRouteNames() as $routeName) {
        $this->get(route($routeName))->assertOk();
    }
});

test('AI configuration catalog routes are read only and use the admin permission middleware', function () {
    foreach (aiConfigurationRouteNames() as $routeName) {
        $route = Route::getRoutes()->getByName($routeName);

        expect($route)->not->toBeNull()
            ->and($route->methods())->toBe(['GET', 'HEAD'])
            ->and($route->gatherMiddleware())->toContain('auth:admin')
            ->and(implode('|', $route->gatherMiddleware()))->toContain('manage ai models');
    }

    expect(Route::getRoutes()->getByName('admin.ai-configuration.workload-routes')->methods())
        ->toBe(['GET', 'HEAD']);
});

test('empty AI configuration tables render honest zero and empty states', function () {
    $admin = createAiConfigurationAdmin();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.ai-configuration.index'))
        ->assertOk()
        ->assertViewHas('metrics', [
            'providers' => 0,
            'enabled_providers' => 0,
            'models' => 0,
            'enabled_models' => 0,
            'workload_routes' => 0,
            'enabled_workload_routes' => 0,
        ])
        ->assertSee('No AI configuration activity yet')
        ->assertDontSee('OpenAI GPT')
        ->assertDontSee('Estimated spend');

    $this->get(route('admin.ai-configuration.providers'))->assertSee('No providers found');
    $this->get(route('admin.ai-configuration.models'))->assertSee('No models found');
    $this->get(route('admin.ai-configuration.workload-routes'))->assertSee('No workload routes found');
});

test('overview metrics and audit activity use real records without exposing change values', function () {
    $admin = createAiConfigurationAdmin();
    $provider = AiProvider::factory()->create(['is_enabled' => true]);
    $model = AiModel::factory()->create(['ai_provider_id' => $provider->id, 'is_enabled' => true]);
    AiWorkloadRoute::factory()->create(['primary_ai_model_id' => $model->id, 'is_enabled' => true]);
    AiConfigurationAudit::factory()->create([
        'acting_admin_id' => $admin->id,
        'subject_type' => AiProvider::class,
        'subject_id' => $provider->id,
        'action' => 'configuration_updated',
        'changes' => [
            'changed_fields' => ['display_name'],
            'summary' => 'DO-NOT-RENDER-THIS-STORED-VALUE',
        ],
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.ai-configuration.index'))
        ->assertOk()
        ->assertViewHas('metrics.providers', 1)
        ->assertViewHas('metrics.models', 1)
        ->assertViewHas('metrics.workload_routes', 1)
        ->assertSee('Configuration Updated')
        ->assertSee('Display Name')
        ->assertDontSee('DO-NOT-RENDER-THIS-STORED-VALUE')
        ->assertDontSee('request_id');
});

test('provider catalog filters sorts and paginates persisted providers safely', function () {
    $admin = createAiConfigurationAdmin();
    AiProvider::factory()->create(['display_name' => 'Zulu Provider', 'health_status' => AiProviderHealthStatus::Healthy, 'is_enabled' => true]);
    AiProvider::factory()->create(['display_name' => 'Alpha Provider', 'health_status' => AiProviderHealthStatus::Unverified, 'is_enabled' => false]);
    AiProvider::factory()->count(10)->create();

    $response = $this->actingAs($admin, 'admin')->get(route('admin.ai-configuration.providers', [
        'search' => 'Zulu',
        'health' => 'healthy',
        'state' => 'enabled',
        'sort' => 'name',
        'per_page' => 10,
    ]));

    $response->assertOk()->assertSee('Zulu Provider')->assertDontSee('Alpha Provider');
    expect($response->viewData('providers')->perPage())->toBe(10);

    $this->get(route('admin.ai-configuration.providers', ['sort' => 'display_name desc; drop table users']))
        ->assertSessionHasErrors('sort');
    $this->get(route('admin.ai-configuration.providers', ['per_page' => 1000]))
        ->assertSessionHasErrors('per_page');

    $this->get(route('admin.ai-configuration.providers', ['health' => 'unverified']))
        ->assertOk()
        ->assertSee('Unverified');
});

test('model catalog filters by provider availability and modality', function () {
    $admin = createAiConfigurationAdmin();
    $selectedProvider = AiProvider::factory()->create(['display_name' => 'Selected Provider']);
    $otherProvider = AiProvider::factory()->create(['display_name' => 'Other Provider']);
    AiModel::factory()->create([
        'ai_provider_id' => $selectedProvider->id,
        'display_name' => 'Selected Text Model',
        'modality' => AiModelModality::Text,
        'availability_status' => AiModelAvailabilityStatus::Available,
        'capabilities' => [AiModelCapability::TextGeneration->value],
    ]);
    AiModel::factory()->create([
        'ai_provider_id' => $otherProvider->id,
        'display_name' => 'Other Image Model',
        'modality' => AiModelModality::Image,
        'availability_status' => AiModelAvailabilityStatus::Unavailable,
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.ai-configuration.models', [
            'provider' => $selectedProvider->id,
            'status' => 'available',
            'modality' => 'text',
        ]))
        ->assertOk()
        ->assertSee('Selected Text Model')
        ->assertSee('Text Generation')
        ->assertDontSee('Other Image Model');
});

test('workload routes show only persisted assignments and omit settings', function () {
    $admin = createAiConfigurationAdmin();
    $provider = AiProvider::factory()->create(['display_name' => 'Routing Provider']);
    $primary = AiModel::factory()->create(['ai_provider_id' => $provider->id, 'display_name' => 'Primary Model']);
    $fallback = AiModel::factory()->create(['ai_provider_id' => $provider->id, 'display_name' => 'Fallback Model']);
    AiWorkloadRoute::factory()->create([
        'workload_key' => 'long-form-content',
        'primary_ai_model_id' => $primary->id,
        'fallback_ai_model_id' => $fallback->id,
        'settings' => null,
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.ai-configuration.workload-routes', ['search' => 'long-form']))
        ->assertOk()
        ->assertSee('Long Form Content')
        ->assertSee('Primary Model')
        ->assertSee('Fallback Model')
        ->assertDontSee('Route settings');
});

test('dynamic catalog and audit output is escaped and navigation is permission aware', function () {
    $unsafeName = '<script>alert("ai")</script>';
    $admin = createAiConfigurationAdmin();
    $provider = AiProvider::factory()->create(['display_name' => $unsafeName]);
    AiModel::factory()->create(['ai_provider_id' => $provider->id, 'display_name' => $unsafeName]);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.ai-configuration.providers'))
        ->assertOk()
        ->assertSee($unsafeName)
        ->assertDontSee($unsafeName, false)
        ->assertSee('href="'.route('admin.ai-configuration.index').'"', false)
        ->assertSee('AI Models &amp; Routing', false);

    $this->get(route('admin.ai-configuration.models'))
        ->assertOk()
        ->assertSee($unsafeName)
        ->assertDontSee($unsafeName, false);

    Permission::create(['name' => 'view dashboard', 'guard_name' => 'admin']);
    $unauthorizedAdmin = createAiConfigurationAdmin(false);
    $unauthorizedAdmin->givePermissionTo('view dashboard');
    $this->actingAs($unauthorizedAdmin, 'admin')
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee('href="'.route('admin.ai-configuration.index').'"', false);
});
