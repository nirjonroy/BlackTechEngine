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
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

test('AI foundation migrations expose metadata without credential or runtime fields', function () {
    expect(Schema::hasColumns('ai_providers', [
        'id',
        'key',
        'display_name',
        'adapter_type',
        'description',
        'is_enabled',
        'health_status',
        'request_timeout_seconds',
        'retry_limit',
        'last_verified_at',
        'created_at',
        'updated_at',
    ]))->toBeTrue()
        ->and(Schema::hasColumns('ai_models', [
            'id',
            'ai_provider_id',
            'provider_model_id',
            'display_name',
            'modality',
            'capabilities',
            'is_enabled',
            'availability_status',
            'last_verified_at',
            'created_at',
            'updated_at',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('ai_workload_routes', [
            'id',
            'workload_key',
            'primary_ai_model_id',
            'fallback_ai_model_id',
            'is_enabled',
            'settings',
            'created_at',
            'updated_at',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('ai_configuration_audits', [
            'id',
            'acting_admin_id',
            'subject_type',
            'subject_id',
            'action',
            'request_id',
            'changes',
            'created_at',
        ]))->toBeTrue();

    $foundationColumns = collect([
        ...Schema::getColumnListing('ai_providers'),
        ...Schema::getColumnListing('ai_models'),
        ...Schema::getColumnListing('ai_workload_routes'),
        ...Schema::getColumnListing('ai_configuration_audits'),
    ]);

    expect($foundationColumns)->not->toContain(
        'api_key',
        'access_token',
        'authorization_header',
        'base_url',
        'prompt',
        'response',
        'price',
        'token_limit',
    );
});

test('migrations do not populate providers models routes or audits', function () {
    $this->assertDatabaseCount('ai_providers', 0);
    $this->assertDatabaseCount('ai_models', 0);
    $this->assertDatabaseCount('ai_workload_routes', 0);
    $this->assertDatabaseCount('ai_configuration_audits', 0);
});

test('providers normalize unique keys and remain disabled and unverified', function () {
    $provider = AiProvider::create([
        'key' => '  Example Provider  ',
        'display_name' => 'Example Provider',
    ]);

    expect($provider->key)->toBe('example-provider')
        ->and($provider->adapter_type)->toBeNull()
        ->and($provider->is_enabled)->toBeFalse()
        ->and($provider->health_status)->toBe(AiProviderHealthStatus::Unverified)
        ->and($provider->request_timeout_seconds)->toBe(30)
        ->and($provider->retry_limit)->toBe(0)
        ->and($provider->last_verified_at)->toBeNull();

    expect(fn () => AiProvider::create([
        'key' => 'example-provider',
        'display_name' => 'Duplicate',
    ]))->toThrow(QueryException::class);
});

test('providers reject unimplemented adapters and unsafe retry or timeout bounds', function () {
    expect(fn () => AiProvider::make([
        'key' => 'provider-one',
        'display_name' => 'Provider One',
        'adapter_type' => 'generic-http',
    ]))->toThrow(InvalidArgumentException::class)
        ->and(fn () => AiProvider::make([
            'key' => 'provider-two',
            'display_name' => 'Provider Two',
            'request_timeout_seconds' => 121,
        ]))->toThrow(InvalidArgumentException::class)
        ->and(fn () => AiProvider::make([
            'key' => 'provider-three',
            'display_name' => 'Provider Three',
            'retry_limit' => 6,
        ]))->toThrow(InvalidArgumentException::class);
});

test('models belong to providers and default to disabled and unverified', function () {
    $provider = AiProvider::factory()->create();
    $model = AiModel::create([
        'ai_provider_id' => $provider->id,
        'provider_model_id' => ' native/model-v1 ',
        'display_name' => 'Model V1',
        'modality' => AiModelModality::Text,
        'capabilities' => [
            AiModelCapability::TextGeneration->value,
            AiModelCapability::StructuredOutput->value,
        ],
    ]);

    expect($model->provider_model_id)->toBe('native/model-v1')
        ->and($model->provider->is($provider))->toBeTrue()
        ->and($provider->fresh()->models->first()->is($model))->toBeTrue()
        ->and($model->modality)->toBe(AiModelModality::Text)
        ->and($model->capabilities)->toBe(['text_generation', 'structured_output'])
        ->and($model->is_enabled)->toBeFalse()
        ->and($model->availability_status)->toBe(AiModelAvailabilityStatus::Unverified)
        ->and($model->last_verified_at)->toBeNull();
});

test('provider model identifiers are unique within each provider', function () {
    $firstProvider = AiProvider::factory()->create();
    $secondProvider = AiProvider::factory()->create();

    AiModel::factory()->for($firstProvider, 'provider')->create([
        'provider_model_id' => 'model-v1',
    ]);
    AiModel::factory()->for($secondProvider, 'provider')->create([
        'provider_model_id' => 'model-v1',
    ]);

    expect(fn () => AiModel::factory()->for($firstProvider, 'provider')->create([
        'provider_model_id' => 'model-v1',
    ]))->toThrow(QueryException::class);

    $this->assertDatabaseCount('ai_models', 2);
});

test('capabilities require a duplicate-free allowlisted list', function () {
    $baseAttributes = [
        'ai_provider_id' => AiProvider::factory()->create()->id,
        'provider_model_id' => 'model-v1',
        'display_name' => 'Model V1',
        'modality' => AiModelModality::Text,
    ];

    expect(fn () => AiModel::make([
        ...$baseAttributes,
        'capabilities' => ['runtime_url' => 'https://example.com'],
    ]))->toThrow(InvalidArgumentException::class)
        ->and(fn () => AiModel::make([
            ...$baseAttributes,
            'capabilities' => ['arbitrary_execution'],
        ]))->toThrow(InvalidArgumentException::class)
        ->and(fn () => AiModel::make([
            ...$baseAttributes,
            'capabilities' => ['text_generation', 'text_generation'],
        ]))->toThrow(InvalidArgumentException::class);
});

test('workload routes normalize unique keys and allow empty disabled assignments', function () {
    $route = AiWorkloadRoute::create([
        'workload_key' => '  Draft Content Brief  ',
    ]);

    expect($route->workload_key)->toBe('draft-content-brief')
        ->and($route->primary_ai_model_id)->toBeNull()
        ->and($route->fallback_ai_model_id)->toBeNull()
        ->and($route->primaryModel)->toBeNull()
        ->and($route->fallbackModel)->toBeNull()
        ->and($route->is_enabled)->toBeFalse()
        ->and($route->settings)->toBeNull();

    expect(fn () => AiWorkloadRoute::create([
        'workload_key' => 'draft-content-brief',
    ]))->toThrow(QueryException::class)
        ->and(fn () => AiWorkloadRoute::make([
            'workload_key' => 'another-workload',
            'settings' => ['endpoint' => 'https://example.com'],
        ]))->toThrow(InvalidArgumentException::class);
});

test('workload model relationships are restrictive and cannot use the same fallback', function () {
    $primaryModel = AiModel::factory()->create();
    $fallbackModel = AiModel::factory()->create();
    $route = AiWorkloadRoute::factory()->create([
        'primary_ai_model_id' => $primaryModel->id,
        'fallback_ai_model_id' => $fallbackModel->id,
        'settings' => [],
    ]);

    expect($route->primaryModel->is($primaryModel))->toBeTrue()
        ->and($route->fallbackModel->is($fallbackModel))->toBeTrue()
        ->and($primaryModel->primaryWorkloadRoutes->first()->is($route))->toBeTrue()
        ->and($fallbackModel->fallbackWorkloadRoutes->first()->is($route))->toBeTrue()
        ->and($route->settings)->toBe([]);

    expect(fn () => AiWorkloadRoute::factory()->create([
        'primary_ai_model_id' => $primaryModel->id,
        'fallback_ai_model_id' => $primaryModel->id,
    ]))->toThrow(InvalidArgumentException::class)
        ->and(fn () => $primaryModel->delete())->toThrow(QueryException::class)
        ->and(fn () => $primaryModel->provider->delete())->toThrow(QueryException::class);
});

test('configuration audits are sanitized append-only records that preserve history', function () {
    $admin = Admin::create([
        'name' => 'AI Configuration Auditor',
        'email' => 'ai-auditor@example.com',
        'password' => Hash::make('Password123'),
    ]);
    $provider = AiProvider::factory()->create();
    $audit = AiConfigurationAudit::create([
        'acting_admin_id' => $admin->id,
        'subject_type' => AiProvider::class,
        'subject_id' => $provider->id,
        'action' => 'provider_metadata_created',
        'request_id' => (string) Str::uuid(),
        'changes' => [
            'changed_fields' => ['display_name', 'description'],
            'state' => ['from' => null, 'to' => 'disabled'],
        ],
    ]);

    expect($audit->changes)->toBe([
        'changed_fields' => ['display_name', 'description'],
        'state' => ['from' => null, 'to' => 'disabled'],
    ])->and($audit->actingAdmin->is($admin))->toBeTrue()
        ->and($audit->subject->is($provider))->toBeTrue()
        ->and($audit->updated_at)->toBeNull();

    expect(fn () => $audit->update(['action' => 'rewritten']))->toThrow(LogicException::class)
        ->and(fn () => $audit->delete())->toThrow(LogicException::class);

    $provider->delete();
    $admin->delete();

    $this->assertModelExists($audit);
    expect($audit->fresh()->actingAdmin)->toBeNull()
        ->and($audit->fresh()->subject)->toBeNull();
});

test('configuration audits reject sensitive keys payloads and secret-like values', function () {
    $attributes = [
        'action' => 'provider_metadata_updated',
        'changes' => [],
    ];

    expect(fn () => AiConfigurationAudit::make([
        ...$attributes,
        'changes' => ['api_key' => ['from' => null, 'to' => 'secret']],
    ]))->toThrow(InvalidArgumentException::class)
        ->and(fn () => AiConfigurationAudit::make([
            ...$attributes,
            'changes' => ['raw_prompt' => 'Generate private content'],
        ]))->toThrow(InvalidArgumentException::class)
        ->and(fn () => AiConfigurationAudit::make([
            ...$attributes,
            'changes' => ['summary' => 'Bearer a-sensitive-access-token'],
        ]))->toThrow(InvalidArgumentException::class)
        ->and(fn () => AiConfigurationAudit::make([
            ...$attributes,
            'changes' => ['summary' => str_repeat('x', 1001)],
        ]))->toThrow(InvalidArgumentException::class);
});

test('permission seeding is idempotent grants super admin and preserves editor access', function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach (['view dashboard', 'create admin', 'edit admin'] as $permissionName) {
        Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'admin']);
    }

    $editor = Role::create(['name' => 'Editor', 'guard_name' => 'admin']);
    $editor->givePermissionTo(['view dashboard', 'create admin', 'edit admin']);
    $customPermission = Permission::create(['name' => 'review ai audits', 'guard_name' => 'admin']);
    $customRole = Role::create(['name' => 'AI Auditor', 'guard_name' => 'admin']);
    $customRole->givePermissionTo($customPermission);
    $existingAdmin = Admin::create([
        'name' => 'Existing Admin',
        'email' => 'existing-ai-admin@example.com',
        'password' => Hash::make('Password123'),
    ]);

    $this->seed(RolePermissionSeeder::class);
    $this->seed(RolePermissionSeeder::class);

    $superAdmin = Role::query()
        ->where('name', 'Super Admin')
        ->where('guard_name', 'admin')
        ->firstOrFail();

    expect(Permission::query()
        ->where('name', 'manage ai models')
        ->where('guard_name', 'admin')
        ->count())->toBe(1)
        ->and(Permission::query()
            ->where('name', 'manage ai models')
            ->where('guard_name', 'web')
            ->exists())->toBeFalse()
        ->and($superAdmin->hasPermissionTo('manage ai models'))->toBeTrue()
        ->and($editor->fresh()->permissions->pluck('name')->sort()->values()->all())->toBe([
            'create admin',
            'edit admin',
            'view dashboard',
        ])->and($customRole->fresh()->permissions->pluck('name')->all())->toBe(['review ai audits'])
        ->and($existingAdmin->fresh()->roles)->toBeEmpty();
});

test('foundation model operations dispatch no jobs and make no network requests', function () {
    Http::preventStrayRequests();
    Queue::fake();

    $provider = AiProvider::factory()->create();
    AiModel::factory()->for($provider, 'provider')->create();
    AiWorkloadRoute::factory()->create();

    Http::assertNothingSent();
    Queue::assertNothingPushed();
});
