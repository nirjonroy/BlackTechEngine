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
use App\Services\Ai\AiContentGenerationManager;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    config()->set('ai.content_generation_enabled', false);
    config()->set('ai.provider_verification_enabled', true);
    config()->set('services.gemini.api_key', 'synthetic-activation-key');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Permission::create(['name' => 'manage ai models', 'guard_name' => 'admin']);
});

function activationAdmin(string $role = 'Super Admin'): Admin
{
    $admin = Admin::create([
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('password'),
    ]);
    $storedRole = Role::firstOrCreate(['name' => $role, 'guard_name' => 'admin']);
    $storedRole->givePermissionTo('manage ai models');
    $admin->assignRole($storedRole);

    return $admin;
}

function activatableProvider(array $overrides = []): AiProvider
{
    return AiProvider::factory()->create([
        'key' => 'gemini',
        'adapter_type' => 'gemini',
        'last_verified_at' => now(),
        ...$overrides,
    ]);
}

function activatableModel(AiProvider $provider, array $overrides = []): AiModel
{
    return AiModel::factory()->for($provider, 'provider')->create([
        'modality' => AiModelModality::Text,
        'capabilities' => [
            AiModelCapability::TextGeneration->value,
            AiModelCapability::StructuredOutput->value,
        ],
        'input_token_limit' => 32000,
        'output_token_limit' => 8192,
        ...$overrides,
    ]);
}

function activationPayload(int $lockVersion, string $state = 'enabled'): array
{
    return ['state' => $state, 'lock_version' => $lockVersion, 'confirm' => '1'];
}

test('only a Super Admin can change operational AI state', function () {
    $provider = activatableProvider();

    $this->patch(route('admin.ai-configuration.providers.activation.update', $provider), activationPayload(0))
        ->assertRedirectToRoute('admin.login');

    $this->actingAs(User::factory()->create())
        ->patch(route('admin.ai-configuration.providers.activation.update', $provider), activationPayload(0))
        ->assertRedirectToRoute('admin.login');

    $editor = activationAdmin('Editor');

    $this->actingAs($editor, 'admin')
        ->patch(route('admin.ai-configuration.providers.activation.update', $provider), activationPayload(0))
        ->assertForbidden();

    expect($provider->fresh()->is_enabled)->toBeFalse();
    $this->assertDatabaseCount('ai_configuration_audits', 0);
});

test('provider activation requires credentials recent verification and the current version', function () {
    $admin = activationAdmin();
    $provider = activatableProvider(['last_verified_at' => now()->subHours(25)]);

    $this->actingAs($admin, 'admin')
        ->patch(route('admin.ai-configuration.providers.activation.update', $provider), activationPayload(0))
        ->assertSessionHasErrors('state');

    $provider->forceFill(['last_verified_at' => now(), 'lock_version' => 1])->save();

    $this->patch(route('admin.ai-configuration.providers.activation.update', $provider), activationPayload(0))
        ->assertSessionHasErrors('lock_version');

    config()->set('services.gemini.api_key', null);
    $this->patch(route('admin.ai-configuration.providers.activation.update', $provider), activationPayload(1))
        ->assertSessionHasErrors('state');

    expect($provider->fresh()->is_enabled)->toBeFalse();
    $this->assertDatabaseCount('ai_configuration_audits', 0);
});

test('Super Admin activates an eligible provider model and workload in order', function () {
    $admin = activationAdmin();
    $provider = activatableProvider();
    $model = activatableModel($provider);
    $route = AiWorkloadRoute::factory()->create([
        'workload_key' => AiContentGenerationManager::WORKLOAD_KEY,
        'primary_ai_model_id' => $model->id,
        'fallback_ai_model_id' => null,
        'settings' => null,
    ]);

    $this->actingAs($admin, 'admin')
        ->patch(route('admin.ai-configuration.providers.activation.update', $provider), activationPayload(0))
        ->assertRedirect()
        ->assertSessionHasNoErrors();
    $this->patch(route('admin.ai-configuration.models.activation.update', $model), activationPayload(0))
        ->assertRedirect()
        ->assertSessionHasNoErrors();
    $this->patch(route('admin.ai-configuration.workload-routes.activation.update', $route), activationPayload(0))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($provider->fresh()->is_enabled)->toBeTrue()
        ->and($provider->fresh()->health_status)->toBe(AiProviderHealthStatus::Healthy)
        ->and($model->fresh()->is_enabled)->toBeTrue()
        ->and($model->fresh()->availability_status)->toBe(AiModelAvailabilityStatus::Available)
        ->and($model->fresh()->last_verified_at)->not->toBeNull()
        ->and($route->fresh()->is_enabled)->toBeTrue();
    expect(AiConfigurationAudit::query()->orderBy('id')->pluck('action')->all())->toBe([
        'ai_provider.activated',
        'ai_model.activated',
        'ai_workload_route.activated',
    ]);
    expect(AiConfigurationAudit::query()->get()->every(
        fn (AiConfigurationAudit $audit): bool => array_keys($audit->changes) === ['changed_fields'],
    ))->toBeTrue();
});

test('model and workload activation reject unsafe capabilities and routing', function () {
    $admin = activationAdmin();
    $provider = activatableProvider([
        'is_enabled' => true,
        'health_status' => AiProviderHealthStatus::Healthy,
    ]);
    $model = activatableModel($provider, [
        'capabilities' => [AiModelCapability::TextGeneration->value],
    ]);

    $this->actingAs($admin, 'admin')
        ->patch(route('admin.ai-configuration.models.activation.update', $model), activationPayload(0))
        ->assertSessionHasErrors('state');

    $model->update([
        'capabilities' => [AiModelCapability::TextGeneration->value, AiModelCapability::StructuredOutput->value],
        'is_enabled' => true,
        'availability_status' => AiModelAvailabilityStatus::Available,
        'lock_version' => 1,
    ]);
    $fallback = activatableModel($provider, [
        'is_enabled' => true,
        'availability_status' => AiModelAvailabilityStatus::Available,
    ]);
    $route = AiWorkloadRoute::factory()->create([
        'workload_key' => AiContentGenerationManager::WORKLOAD_KEY,
        'primary_ai_model_id' => $model->id,
        'fallback_ai_model_id' => $fallback->id,
    ]);

    $this->patch(route('admin.ai-configuration.workload-routes.activation.update', $route), activationPayload(0))
        ->assertSessionHasErrors('state');

    expect($model->fresh()->is_enabled)->toBeTrue()
        ->and($route->fresh()->is_enabled)->toBeFalse();
    $this->assertDatabaseCount('ai_configuration_audits', 0);
});

test('deactivating a provider safely disables dependent models and workloads', function () {
    $admin = activationAdmin();
    $provider = activatableProvider([
        'is_enabled' => true,
        'health_status' => AiProviderHealthStatus::Healthy,
    ]);
    $model = activatableModel($provider, [
        'is_enabled' => true,
        'availability_status' => AiModelAvailabilityStatus::Available,
    ]);
    $route = AiWorkloadRoute::factory()->create([
        'workload_key' => AiContentGenerationManager::WORKLOAD_KEY,
        'primary_ai_model_id' => $model->id,
        'is_enabled' => true,
    ]);

    $this->actingAs($admin, 'admin')
        ->patch(route('admin.ai-configuration.providers.activation.update', $provider), activationPayload(0, 'disabled'))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($provider->fresh()->is_enabled)->toBeFalse()
        ->and($model->fresh()->is_enabled)->toBeFalse()
        ->and($route->fresh()->is_enabled)->toBeFalse();
    expect(AiConfigurationAudit::query()->orderBy('id')->pluck('action')->all())->toBe([
        'ai_workload_route.deactivated',
        'ai_model.deactivated',
        'ai_provider.deactivated',
    ]);
});

test('audit failure rolls back the entire activation transaction', function () {
    $admin = activationAdmin();
    $provider = activatableProvider();
    Event::listen('eloquent.creating: '.AiConfigurationAudit::class, function (): never {
        throw new RuntimeException('Synthetic audit failure.');
    });

    $this->actingAs($admin, 'admin')
        ->patch(route('admin.ai-configuration.providers.activation.update', $provider), activationPayload(0))
        ->assertSessionHasErrors('state');

    expect($provider->fresh()->is_enabled)->toBeFalse()
        ->and($provider->fresh()->health_status)->toBe(AiProviderHealthStatus::Unverified)
        ->and($provider->fresh()->lock_version)->toBe(0);
    $this->assertDatabaseCount('ai_configuration_audits', 0);
});
