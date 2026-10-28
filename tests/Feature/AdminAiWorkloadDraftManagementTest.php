<?php

use App\Models\Admin;
use App\Models\AiConfigurationAudit;
use App\Models\AiModel;
use App\Models\AiProvider;
use App\Models\AiWorkloadRoute;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    config()->set('ai.workload_draft_writes_enabled', true);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Permission::create([
        'name' => 'manage ai models',
        'guard_name' => 'admin',
    ]);
});

function createAiWorkloadDraftAdmin(bool $authorized = true): Admin
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

function aiWorkloadDraftPayload(array $overrides = []): array
{
    return [
        'workload_key' => 'content-brief-generation',
        'primary_ai_model_id' => null,
        'fallback_ai_model_id' => null,
        ...$overrides,
    ];
}

test('the independent default-off gate preserves listing and blocks every write entrypoint', function () {
    config()->set('ai.workload_draft_writes_enabled', false);
    $admin = createAiWorkloadDraftAdmin();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.ai-configuration.workload-routes'))
        ->assertOk()
        ->assertSee('Workload draft changes are currently unavailable')
        ->assertDontSee(route('admin.ai-configuration.workload-routes.create'), false);

    $this->get(route('admin.ai-configuration.workload-routes.create'))->assertServiceUnavailable();
    $this->post(route('admin.ai-configuration.workload-routes.store'), aiWorkloadDraftPayload())->assertServiceUnavailable();
    $this->assertDatabaseCount('ai_workload_routes', 0);
    $this->assertDatabaseCount('ai_configuration_audits', 0);
});

test('guests web users permissionless admins and editors cannot manage workload drafts', function () {
    $draft = AiWorkloadRoute::factory()->create();

    $this->get(route('admin.ai-configuration.workload-routes.create'))->assertRedirectToRoute('admin.login');
    $this->post(route('admin.ai-configuration.workload-routes.store'), aiWorkloadDraftPayload())->assertRedirectToRoute('admin.login');

    $this->actingAs(User::factory()->create())
        ->get(route('admin.ai-configuration.workload-routes.edit', $draft))
        ->assertRedirectToRoute('admin.login');
    $this->assertGuest('admin');

    $permissionlessAdmin = createAiWorkloadDraftAdmin(false);
    $this->actingAs($permissionlessAdmin, 'admin')
        ->patch(route('admin.ai-configuration.workload-routes.update', $draft), aiWorkloadDraftPayload(['lock_version' => 0]))
        ->assertForbidden();

    $editor = createAiWorkloadDraftAdmin(false);
    $editor->assignRole(Role::create(['name' => 'Editor', 'guard_name' => 'admin']));
    $this->actingAs($editor, 'admin')
        ->get(route('admin.ai-configuration.workload-routes.create'))
        ->assertForbidden();
});

test('authorized and super admins can render safe draft forms including an honest empty catalog', function () {
    $admin = createAiWorkloadDraftAdmin();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.ai-configuration.workload-routes.create'))
        ->assertOk()
        ->assertSee('No persisted models are available')
        ->assertSee('name="_token"', false)
        ->assertDontSee('name="is_enabled"', false)
        ->assertDontSee('name="settings"', false)
        ->assertDontSee('name="api_key"', false);

    $superAdmin = createAiWorkloadDraftAdmin(false);
    $superAdmin->assignRole(Role::create(['name' => 'Super Admin', 'guard_name' => 'admin']));

    $this->actingAs($superAdmin, 'admin')
        ->get(route('admin.ai-configuration.workload-routes.create'))
        ->assertOk();
});

test('creation normalizes the key permits empty assignments and forces a disabled draft', function () {
    $admin = createAiWorkloadDraftAdmin();

    $response = $this->actingAs($admin, 'admin')
        ->post(route('admin.ai-configuration.workload-routes.store'), aiWorkloadDraftPayload([
            'workload_key' => '  Content Brief Generation  ',
        ]));

    $draft = AiWorkloadRoute::query()->sole();
    $response->assertSessionHasNoErrors()->assertRedirect(route('admin.ai-configuration.workload-routes.edit', $draft));
    expect($draft->workload_key)->toBe('content-brief-generation')
        ->and($draft->primary_ai_model_id)->toBeNull()
        ->and($draft->fallback_ai_model_id)->toBeNull()
        ->and($draft->settings)->toBeNull()
        ->and($draft->is_enabled)->toBeFalse()
        ->and($draft->lock_version)->toBe(0);

    $audit = AiConfigurationAudit::query()->sole();
    expect($audit->acting_admin_id)->toBe($admin->id)
        ->and($audit->subject_type)->toBe(AiWorkloadRoute::class)
        ->and($audit->subject_id)->toBe($draft->id)
        ->and($audit->action)->toBe('ai_workload_route.draft_created')
        ->and($audit->request_id)->toBeUuid()
        ->and($audit->changes)->toBe(['changed_fields' => [
            'workload_key', 'primary_ai_model_id', 'fallback_ai_model_id', 'settings',
        ]]);
});

test('creation accepts genuine distinct model assignments without changing model state', function () {
    $admin = createAiWorkloadDraftAdmin();
    $provider = AiProvider::factory()->create(['display_name' => 'Persisted Provider']);
    $primary = AiModel::factory()->create(['ai_provider_id' => $provider->id, 'display_name' => 'Primary Model']);
    $fallback = AiModel::factory()->create(['ai_provider_id' => $provider->id, 'display_name' => 'Fallback Model']);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.ai-configuration.workload-routes.create'))
        ->assertOk()
        ->assertSee('Persisted Provider')
        ->assertSee('Primary Model')
        ->assertSee('[disabled, unverified]');

    $this->post(route('admin.ai-configuration.workload-routes.store'), aiWorkloadDraftPayload([
        'primary_ai_model_id' => $primary->id,
        'fallback_ai_model_id' => $fallback->id,
    ]))->assertSessionHasNoErrors();

    $draft = AiWorkloadRoute::query()->sole();
    expect($draft->primary_ai_model_id)->toBe($primary->id)
        ->and($draft->fallback_ai_model_id)->toBe($fallback->id)
        ->and($primary->fresh()->is_enabled)->toBeFalse()
        ->and($fallback->fresh()->is_enabled)->toBeFalse();
});

test('validation rejects invalid duplicate and unsupported draft configuration', function () {
    $admin = createAiWorkloadDraftAdmin();
    $provider = AiProvider::factory()->create();
    $model = AiModel::factory()->create(['ai_provider_id' => $provider->id]);
    AiWorkloadRoute::factory()->create(['workload_key' => 'existing-draft']);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.ai-configuration.workload-routes.store'), aiWorkloadDraftPayload([
            'workload_key' => 'Existing Draft',
            'primary_ai_model_id' => $model->id,
            'fallback_ai_model_id' => $model->id,
            'settings' => ['temperature' => 0.7],
            'is_enabled' => true,
            'api_key' => 'never-store-this',
        ]))
        ->assertSessionHasErrors([
            'workload_key', 'fallback_ai_model_id', 'settings', 'is_enabled', 'api_key',
        ]);

    $this->post(route('admin.ai-configuration.workload-routes.store'), aiWorkloadDraftPayload([
        'workload_key' => 'missing-model-draft',
        'primary_ai_model_id' => 999999,
    ]))->assertSessionHasErrors('primary_ai_model_id');

    $this->assertDatabaseCount('ai_workload_routes', 1);
    $this->assertDatabaseCount('ai_configuration_audits', 0);
});

test('meaningful updates increment once while no-op and stale submissions do not mutate or audit', function () {
    $admin = createAiWorkloadDraftAdmin();
    $draft = AiWorkloadRoute::factory()->create(['workload_key' => 'original-draft']);
    $payload = aiWorkloadDraftPayload([
        'workload_key' => 'original-draft',
        'lock_version' => 0,
    ]);

    $this->actingAs($admin, 'admin')
        ->patch(route('admin.ai-configuration.workload-routes.update', $draft), $payload)
        ->assertSessionHas('status', 'No workload draft changes were detected.');

    $this->patch(route('admin.ai-configuration.workload-routes.update', $draft), [
        ...$payload,
        'workload_key' => 'updated-draft',
    ])->assertSessionHasNoErrors();

    $this->patch(route('admin.ai-configuration.workload-routes.update', $draft), [
        ...$payload,
        'workload_key' => 'stale-overwrite',
    ])->assertSessionHasErrors('lock_version');

    expect($draft->fresh()->workload_key)->toBe('updated-draft')
        ->and($draft->fresh()->lock_version)->toBe(1)
        ->and($draft->fresh()->is_enabled)->toBeFalse();
    expect(AiConfigurationAudit::query()->sole()->changes)->toBe(['changed_fields' => ['workload_key']]);
});

test('audit insertion failure rolls back the draft update and hides sensitive exception details', function () {
    $admin = createAiWorkloadDraftAdmin();
    $draft = AiWorkloadRoute::factory()->create(['workload_key' => 'original-draft']);
    $eventName = 'eloquent.creating: '.AiConfigurationAudit::class;
    Event::listen($eventName, function (): void {
        throw new RuntimeException('Sensitive simulated database detail.');
    });

    try {
        $response = $this->actingAs($admin, 'admin')
            ->from(route('admin.ai-configuration.workload-routes.edit', $draft))
            ->patch(route('admin.ai-configuration.workload-routes.update', $draft), aiWorkloadDraftPayload([
                'workload_key' => 'must-roll-back',
                'lock_version' => 0,
            ]))
            ->assertRedirect(route('admin.ai-configuration.workload-routes.edit', $draft))
            ->assertSessionHasErrors('draft');

        expect($response->getSession()->get('errors')->all())
            ->not->toContain('Sensitive simulated database detail.');
    } finally {
        Event::forget($eventName);
    }

    expect($draft->fresh()->workload_key)->toBe('original-draft')
        ->and($draft->fresh()->lock_version)->toBe(0);
    $this->assertDatabaseCount('ai_configuration_audits', 0);
});

test('enabled routes cannot be changed through draft management', function () {
    $admin = createAiWorkloadDraftAdmin();
    $route = AiWorkloadRoute::factory()->create(['is_enabled' => true]);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.ai-configuration.workload-routes.edit', $route))
        ->assertConflict();

    $this->patch(route('admin.ai-configuration.workload-routes.update', $route), aiWorkloadDraftPayload([
        'workload_key' => $route->workload_key,
        'lock_version' => 0,
    ]))->assertSessionHasErrors('draft');

    expect($route->fresh()->is_enabled)->toBeTrue()
        ->and($route->fresh()->lock_version)->toBe(0);
    $this->assertDatabaseCount('ai_configuration_audits', 0);
});

test('rendered provider and model context is escaped', function () {
    $admin = createAiWorkloadDraftAdmin();
    $provider = AiProvider::factory()->create(['display_name' => '<script>alert("provider")</script>']);
    AiModel::factory()->create([
        'ai_provider_id' => $provider->id,
        'display_name' => '<script>alert("model")</script>',
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.ai-configuration.workload-routes.create'))
        ->assertOk()
        ->assertSee('&lt;script&gt;', false)
        ->assertDontSee('<script>alert("model")</script>', false)
        ->assertDontSee('<script>alert("provider")</script>', false);
});

test('draft routes expose only approved methods and no operational endpoints', function () {
    $expectedMethods = [
        'admin.ai-configuration.workload-routes' => ['GET', 'HEAD'],
        'admin.ai-configuration.workload-routes.create' => ['GET', 'HEAD'],
        'admin.ai-configuration.workload-routes.store' => ['POST'],
        'admin.ai-configuration.workload-routes.edit' => ['GET', 'HEAD'],
        'admin.ai-configuration.workload-routes.update' => ['PATCH'],
    ];

    foreach ($expectedMethods as $routeName => $methods) {
        $route = Route::getRoutes()->getByName($routeName);
        expect($route?->methods())->toBe($methods)
            ->and($route?->gatherMiddleware())->toContain('auth:admin')
            ->and(implode('|', $route?->gatherMiddleware() ?? []))->toContain('manage ai models');
    }

    foreach (['destroy', 'activate', 'execute', 'verify', 'health-test'] as $action) {
        expect(Route::has("admin.ai-configuration.workload-routes.{$action}"))->toBeFalse();
    }
});
