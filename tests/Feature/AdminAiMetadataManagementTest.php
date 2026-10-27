<?php

use App\Enums\AiModelCapability;
use App\Models\Admin;
use App\Models\AiConfigurationAudit;
use App\Models\AiModel;
use App\Models\AiProvider;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    config()->set('ai.metadata_writes_enabled', true);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Permission::create([
        'name' => 'manage ai models',
        'guard_name' => 'admin',
    ]);
});

function createAiMetadataAdmin(bool $authorized = true): Admin
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

function aiProviderPayload(array $overrides = []): array
{
    return [
        'key' => 'example-provider',
        'display_name' => 'Example Provider',
        'description' => 'Metadata only provider.',
        'request_timeout_seconds' => 30,
        'retry_limit' => 1,
        ...$overrides,
    ];
}

function aiModelPayload(AiProvider $provider, array $overrides = []): array
{
    return [
        'ai_provider_id' => $provider->id,
        'provider_model_id' => 'models/example-v1',
        'display_name' => 'Example Model',
        'modality' => 'text',
        'capabilities' => [AiModelCapability::TextGeneration->value],
        ...$overrides,
    ];
}

test('the default-off write gate hides actions and blocks direct write routes', function () {
    config()->set('ai.metadata_writes_enabled', false);
    $admin = createAiMetadataAdmin();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.ai-configuration.providers'))
        ->assertOk()
        ->assertSee('Metadata changes are currently unavailable')
        ->assertDontSee(route('admin.ai-configuration.providers.create'), false);

    $this->get(route('admin.ai-configuration.models'))
        ->assertOk()
        ->assertDontSee(route('admin.ai-configuration.models.create'), false);

    $this->get(route('admin.ai-configuration.providers.create'))->assertServiceUnavailable();
    $this->post(route('admin.ai-configuration.providers.store'), aiProviderPayload())->assertServiceUnavailable();
    $this->assertDatabaseCount('ai_providers', 0);
    $this->assertDatabaseCount('ai_configuration_audits', 0);
});

test('guests web users permissionless admins and editors cannot manage AI metadata', function () {
    $provider = AiProvider::factory()->create();
    $model = AiModel::factory()->create(['ai_provider_id' => $provider->id]);

    $this->get(route('admin.ai-configuration.providers.create'))->assertRedirectToRoute('admin.login');
    $this->post(route('admin.ai-configuration.providers.store'), aiProviderPayload())->assertRedirectToRoute('admin.login');

    $this->actingAs(User::factory()->create())
        ->get(route('admin.ai-configuration.models.create'))
        ->assertRedirectToRoute('admin.login');
    $this->assertGuest('admin');

    $permissionlessAdmin = createAiMetadataAdmin(false);
    $this->actingAs($permissionlessAdmin, 'admin')
        ->patch(route('admin.ai-configuration.providers.update', $provider), aiProviderPayload(['lock_version' => 0]))
        ->assertForbidden();

    $editor = createAiMetadataAdmin(false);
    $editor->assignRole(Role::create(['name' => 'Editor', 'guard_name' => 'admin']));
    $this->actingAs($editor, 'admin')
        ->patch(route('admin.ai-configuration.models.update', $model), aiModelPayload($provider, ['lock_version' => 0]))
        ->assertForbidden();
});

test('authorized and super admins can render CSRF-protected create and edit forms', function () {
    $admin = createAiMetadataAdmin();
    $provider = AiProvider::factory()->create(['display_name' => 'Existing Provider']);
    $model = AiModel::factory()->create([
        'ai_provider_id' => $provider->id,
        'display_name' => 'Existing Model',
        'capabilities' => [AiModelCapability::StructuredOutput->value],
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.ai-configuration.providers.create'))
        ->assertOk()
        ->assertSee('name="_token"', false)
        ->assertDontSee('name="is_enabled"', false)
        ->assertDontSee('name="adapter_type"', false);

    $this->get(route('admin.ai-configuration.providers.edit', $provider))
        ->assertOk()
        ->assertSee('name="_method" value="PATCH"', false)
        ->assertSee('name="lock_version"', false)
        ->assertSee('Existing Provider');

    $this->get(route('admin.ai-configuration.models.edit', $model))
        ->assertOk()
        ->assertSee('Existing Model')
        ->assertSee('value="structured_output"', false)
        ->assertDontSee('name="availability_status"', false);

    $superAdmin = createAiMetadataAdmin(false);
    $superAdmin->assignRole(Role::create(['name' => 'Super Admin', 'guard_name' => 'admin']));
    $this->actingAs($superAdmin, 'admin')
        ->get(route('admin.ai-configuration.models.create'))
        ->assertOk();
});

test('provider creation normalizes the key and forces safe operational defaults', function () {
    $admin = createAiMetadataAdmin();

    $response = $this->actingAs($admin, 'admin')
        ->post(route('admin.ai-configuration.providers.store'), aiProviderPayload([
            'key' => '  Example Provider API  ',
            'display_name' => '  Example Provider  ',
        ]));

    $provider = AiProvider::query()->sole();
    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.ai-configuration.providers.edit', $provider));
    expect($provider->key)->toBe('example-provider-api')
        ->and($provider->display_name)->toBe('Example Provider')
        ->and($provider->is_enabled)->toBeFalse()
        ->and($provider->getRawOriginal('health_status'))->toBe('unverified')
        ->and($provider->adapter_type)->toBeNull()
        ->and($provider->last_verified_at)->toBeNull()
        ->and($provider->lock_version)->toBe(0);

    $audit = AiConfigurationAudit::query()->sole();
    expect($audit->acting_admin_id)->toBe($admin->id)
        ->and($audit->subject_type)->toBe(AiProvider::class)
        ->and($audit->subject_id)->toBe($provider->id)
        ->and($audit->action)->toBe('ai_provider.created')
        ->and($audit->request_id)->toBeUuid()
        ->and($audit->changes)->toBe(['changed_fields' => [
            'key', 'display_name', 'description', 'request_timeout_seconds', 'retry_limit',
        ]]);
});

test('provider validation rejects duplicates invalid bounds and preserves old input', function () {
    $admin = createAiMetadataAdmin();
    AiProvider::factory()->create(['key' => 'duplicate-provider']);

    $this->actingAs($admin, 'admin')
        ->from(route('admin.ai-configuration.providers.create'))
        ->post(route('admin.ai-configuration.providers.store'), aiProviderPayload([
            'key' => 'Duplicate Provider',
            'display_name' => 'Remember This Name',
            'request_timeout_seconds' => 121,
            'retry_limit' => 6,
        ]))
        ->assertRedirect(route('admin.ai-configuration.providers.create'))
        ->assertSessionHasErrors(['key', 'request_timeout_seconds', 'retry_limit'])
        ->assertSessionHasInput('display_name', 'Remember This Name');

    $this->assertDatabaseCount('ai_providers', 1);
    $this->assertDatabaseCount('ai_configuration_audits', 0);
});

test('forged provider operational and credential fields are rejected without activation', function () {
    $admin = createAiMetadataAdmin();

    $this->actingAs($admin, 'admin')
        ->post(route('admin.ai-configuration.providers.store'), aiProviderPayload() + [
            'is_enabled' => true,
            'health_status' => 'healthy',
            'last_verified_at' => now()->toDateTimeString(),
            'adapter_type' => 'App\\Adapters\\InjectedAdapter',
            'api_key' => 'never-store-this',
            'provider_url' => 'https://attacker.example',
        ])
        ->assertSessionHasErrors([
            'is_enabled', 'health_status', 'last_verified_at', 'adapter_type', 'api_key', 'provider_url',
        ]);

    $this->assertDatabaseCount('ai_providers', 0);
    $this->assertDatabaseCount('ai_configuration_audits', 0);
});

test('provider updates increment the version once and audit only changed field names', function () {
    $admin = createAiMetadataAdmin();
    $provider = AiProvider::factory()->create([
        'display_name' => 'Original Provider',
        'description' => 'Original description',
        'retry_limit' => 1,
    ]);

    $this->actingAs($admin, 'admin')
        ->patch(route('admin.ai-configuration.providers.update', $provider), aiProviderPayload([
            'key' => $provider->key,
            'display_name' => 'Updated Provider',
            'description' => 'Original description',
            'lock_version' => 0,
        ]))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', 'AI provider metadata updated. Operational state was not changed.');

    $freshProvider = $provider->fresh();
    expect($freshProvider->display_name)->toBe('Updated Provider')
        ->and($freshProvider->lock_version)->toBe(1)
        ->and($freshProvider->is_enabled)->toBeFalse()
        ->and($freshProvider->getRawOriginal('health_status'))->toBe('unverified');
    expect(AiConfigurationAudit::query()->sole()->changes)->toBe(['changed_fields' => ['display_name']]);
});

test('provider no-op and stale updates create no audit or overwrite', function () {
    $admin = createAiMetadataAdmin();
    $provider = AiProvider::factory()->create(['display_name' => 'Original Provider']);
    $payload = aiProviderPayload([
        'key' => $provider->key,
        'display_name' => 'Original Provider',
        'description' => $provider->description,
        'request_timeout_seconds' => $provider->request_timeout_seconds,
        'retry_limit' => $provider->retry_limit,
        'lock_version' => 0,
    ]);

    $this->actingAs($admin, 'admin')
        ->patch(route('admin.ai-configuration.providers.update', $provider), $payload)
        ->assertSessionHas('status', 'No provider metadata changes were detected.');

    $this->patch(route('admin.ai-configuration.providers.update', $provider), [...$payload, 'display_name' => 'First Update'])
        ->assertSessionHasNoErrors();
    $this->patch(route('admin.ai-configuration.providers.update', $provider), [...$payload, 'display_name' => 'Stale Update'])
        ->assertSessionHasErrors('lock_version');

    expect($provider->fresh()->display_name)->toBe('First Update')
        ->and($provider->fresh()->lock_version)->toBe(1);
    $this->assertDatabaseCount('ai_configuration_audits', 1);
});

test('model creation preserves the native identifier and forces safe defaults', function () {
    $admin = createAiMetadataAdmin();
    $provider = AiProvider::factory()->create();

    $response = $this->actingAs($admin, 'admin')
        ->post(route('admin.ai-configuration.models.store'), aiModelPayload($provider, [
            'provider_model_id' => '  Vendor/Model-V1  ',
            'capabilities' => ['text_generation', 'structured_output'],
        ]));

    $model = AiModel::query()->sole();
    $response->assertSessionHasNoErrors()->assertRedirect(route('admin.ai-configuration.models.edit', $model));
    expect($model->provider_model_id)->toBe('Vendor/Model-V1')
        ->and($model->ai_provider_id)->toBe($provider->id)
        ->and($model->capabilities)->toBe(['text_generation', 'structured_output'])
        ->and($model->is_enabled)->toBeFalse()
        ->and($model->getRawOriginal('availability_status'))->toBe('unverified')
        ->and($model->last_verified_at)->toBeNull()
        ->and($model->lock_version)->toBe(0);
    expect(AiConfigurationAudit::query()->sole()->changes)->toBe(['changed_fields' => [
        'ai_provider_id', 'provider_model_id', 'display_name', 'modality', 'capabilities',
    ]]);
});

test('model validation rejects nonexistent providers unsupported values and associative capabilities', function () {
    $admin = createAiMetadataAdmin();
    $provider = AiProvider::factory()->create();

    $this->actingAs($admin, 'admin')
        ->post(route('admin.ai-configuration.models.store'), aiModelPayload($provider, [
            'ai_provider_id' => 999999,
            'modality' => 'audio',
            'capabilities' => ['custom_key' => 'text_generation', 'unknown_capability'],
        ]))
        ->assertSessionHasErrors(['ai_provider_id', 'modality', 'capabilities', 'capabilities.0']);

    $this->assertDatabaseCount('ai_models', 0);
    $this->assertDatabaseCount('ai_configuration_audits', 0);
});

test('model identifiers are unique per provider with case-insensitive collision handling', function () {
    $admin = createAiMetadataAdmin();
    $firstProvider = AiProvider::factory()->create();
    $secondProvider = AiProvider::factory()->create();
    AiModel::factory()->create([
        'ai_provider_id' => $firstProvider->id,
        'provider_model_id' => 'Vendor/Model-V1',
    ]);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.ai-configuration.models.store'), aiModelPayload($firstProvider, [
            'provider_model_id' => 'vendor/model-v1',
        ]))
        ->assertSessionHasErrors('provider_model_id');

    $this->post(route('admin.ai-configuration.models.store'), aiModelPayload($secondProvider, [
        'provider_model_id' => 'vendor/model-v1',
    ]))->assertSessionHasNoErrors();

    $this->assertDatabaseCount('ai_models', 2);
});

test('forged model operational fields are rejected without activation', function () {
    $admin = createAiMetadataAdmin();
    $provider = AiProvider::factory()->create();

    $this->actingAs($admin, 'admin')
        ->post(route('admin.ai-configuration.models.store'), aiModelPayload($provider) + [
            'is_enabled' => true,
            'availability_status' => 'available',
            'last_verified_at' => now()->toDateTimeString(),
            'api_token' => 'never-store-this',
            'pricing' => ['input' => 10],
        ])
        ->assertSessionHasErrors(['is_enabled', 'availability_status', 'last_verified_at', 'api_token', 'pricing']);

    $this->assertDatabaseCount('ai_models', 0);
});

test('model updates handle meaningful no-op and stale submissions transactionally', function () {
    $admin = createAiMetadataAdmin();
    $provider = AiProvider::factory()->create();
    $model = AiModel::factory()->create([
        'ai_provider_id' => $provider->id,
        'display_name' => 'Original Model',
        'capabilities' => ['text_generation'],
    ]);
    $payload = aiModelPayload($provider, [
        'provider_model_id' => $model->provider_model_id,
        'display_name' => 'Original Model',
        'modality' => $model->getRawOriginal('modality'),
        'capabilities' => ['text_generation'],
        'lock_version' => 0,
    ]);

    $this->actingAs($admin, 'admin')
        ->patch(route('admin.ai-configuration.models.update', $model), $payload)
        ->assertSessionHas('status', 'No model metadata changes were detected.');
    $this->patch(route('admin.ai-configuration.models.update', $model), [...$payload, 'display_name' => 'Updated Model'])
        ->assertSessionHasNoErrors();
    $this->patch(route('admin.ai-configuration.models.update', $model), [...$payload, 'display_name' => 'Stale Model'])
        ->assertSessionHasErrors('lock_version');

    expect($model->fresh()->display_name)->toBe('Updated Model')
        ->and($model->fresh()->lock_version)->toBe(1);
    expect(AiConfigurationAudit::query()->sole()->changes)->toBe(['changed_fields' => ['display_name']]);
});

test('audit insertion failure rolls back metadata and returns a controlled error', function () {
    $admin = createAiMetadataAdmin();
    $provider = AiProvider::factory()->create(['display_name' => 'Original Provider']);
    $eventName = 'eloquent.creating: '.AiConfigurationAudit::class;
    Event::listen($eventName, function (): void {
        throw new RuntimeException('Sensitive simulated database detail.');
    });

    try {
        $response = $this->actingAs($admin, 'admin')
            ->from(route('admin.ai-configuration.providers.edit', $provider))
            ->patch(route('admin.ai-configuration.providers.update', $provider), aiProviderPayload([
                'key' => $provider->key,
                'display_name' => 'Should Roll Back',
                'description' => $provider->description,
                'request_timeout_seconds' => $provider->request_timeout_seconds,
                'retry_limit' => $provider->retry_limit,
                'lock_version' => 0,
            ]))
            ->assertRedirect(route('admin.ai-configuration.providers.edit', $provider))
            ->assertSessionHasErrors('metadata');

        expect($response->getSession()->get('errors')->all())
            ->not->toContain('Sensitive simulated database detail.');
    } finally {
        Event::forget($eventName);
    }

    expect($provider->fresh()->display_name)->toBe('Original Provider')
        ->and($provider->fresh()->lock_version)->toBe(0);
    $this->assertDatabaseCount('ai_configuration_audits', 0);
});

test('metadata routes expose only approved methods and workload routing remains read only', function () {
    $expectedMethods = [
        'admin.ai-configuration.providers.create' => ['GET', 'HEAD'],
        'admin.ai-configuration.providers.store' => ['POST'],
        'admin.ai-configuration.providers.edit' => ['GET', 'HEAD'],
        'admin.ai-configuration.providers.update' => ['PATCH'],
        'admin.ai-configuration.models.create' => ['GET', 'HEAD'],
        'admin.ai-configuration.models.store' => ['POST'],
        'admin.ai-configuration.models.edit' => ['GET', 'HEAD'],
        'admin.ai-configuration.models.update' => ['PATCH'],
        'admin.ai-configuration.workload-routes' => ['GET', 'HEAD'],
    ];

    foreach ($expectedMethods as $routeName => $methods) {
        $route = Route::getRoutes()->getByName($routeName);
        expect($route?->methods())->toBe($methods)
            ->and($route?->gatherMiddleware())->toContain('auth:admin')
            ->and(implode('|', $route?->gatherMiddleware() ?? []))->toContain('manage ai models');
    }

    foreach (['destroy', 'activate', 'verify'] as $action) {
        expect(Route::has("admin.ai-configuration.providers.{$action}"))->toBeFalse()
            ->and(Route::has("admin.ai-configuration.models.{$action}"))->toBeFalse()
            ->and(Route::has("admin.ai-configuration.workload-routes.{$action}"))->toBeFalse();
    }
});
