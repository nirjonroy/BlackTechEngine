<?php

use App\Enums\AiProviderErrorCategory;
use App\Enums\AiProviderHealthStatus;
use App\Models\Admin;
use App\Models\AiConfigurationAudit;
use App\Models\AiProvider;
use App\Models\User;
use App\Services\Ai\Gemini\GeminiHttpTransport;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    Cache::flush();
    config()->set('ai.metadata_writes_enabled', true);
    config()->set('ai.provider_verification_enabled', true);
    config()->set('services.gemini.api_key', 'synthetic-verification-key');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach (['manage ai models', 'verify ai providers'] as $permission) {
        Permission::firstOrCreate([
            'name' => $permission,
            'guard_name' => 'admin',
        ]);
    }
});

function createProviderVerificationAdmin(bool $canVerify = true, bool $canManageMetadata = true): Admin
{
    $admin = Admin::create([
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('password'),
    ]);

    if ($canVerify) {
        $admin->givePermissionTo('verify ai providers');
    }

    if ($canManageMetadata) {
        $admin->givePermissionTo('manage ai models');
    }

    return $admin;
}

function geminiProvider(array $overrides = []): AiProvider
{
    return AiProvider::factory()->create([
        'key' => 'gemini',
        'display_name' => 'Google Gemini',
        ...$overrides,
    ]);
}

function providerBindingPayload(AiProvider $provider, array $overrides = []): array
{
    return [
        'adapter_identifier' => 'gemini',
        'lock_version' => $provider->lock_version,
        ...$overrides,
    ];
}

function providerVerificationPayload(AiProvider $provider, array $overrides = []): array
{
    return [
        'confirm_verification' => '1',
        'lock_version' => $provider->lock_version,
        ...$overrides,
    ];
}

function fakeSuccessfulGeminiVerification(): void
{
    Http::preventStrayRequests();
    Http::fake([
        GeminiHttpTransport::BASE_URL.'/*' => Http::response([
            'models' => [[
                'name' => 'models/gemini-test',
                'baseModelId' => 'gemini-test',
                'version' => '1',
                'supportedGenerationMethods' => ['generateContent'],
            ]],
        ]),
    ]);
}

test('permission seeding registers verification once for Super Admin and preserves Editor access', function () {
    foreach (['view dashboard', 'create admin', 'edit admin'] as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'admin']);
    }

    $editor = Role::create(['name' => 'Editor', 'guard_name' => 'admin']);
    $editor->givePermissionTo(['view dashboard', 'create admin', 'edit admin']);
    $existingAdmin = createProviderVerificationAdmin(false, false);

    $this->seed(RolePermissionSeeder::class);
    $this->seed(RolePermissionSeeder::class);

    $superAdmin = Role::query()
        ->where('name', 'Super Admin')
        ->where('guard_name', 'admin')
        ->firstOrFail();

    expect(Permission::query()
        ->where('name', 'verify ai providers')
        ->where('guard_name', 'admin')
        ->count())->toBe(1)
        ->and(Permission::query()
            ->where('name', 'verify ai providers')
            ->where('guard_name', 'web')
            ->exists())->toBeFalse()
        ->and($superAdmin->hasPermissionTo('verify ai providers'))->toBeTrue()
        ->and($editor->fresh()->permissions->pluck('name')->sort()->values()->all())->toBe([
            'create admin',
            'edit admin',
            'view dashboard',
        ])
        ->and($existingAdmin->fresh()->roles)->toBeEmpty();
});

test('verification routes use POST auth permission and named throttle middleware', function () {
    $bindingRoute = Route::getRoutes()->getByName('admin.ai-configuration.providers.binding.store');
    $verificationRoute = Route::getRoutes()->getByName('admin.ai-configuration.providers.verification.store');

    expect($bindingRoute?->methods())->toBe(['POST'])
        ->and($verificationRoute?->methods())->toBe(['POST'])
        ->and(implode('|', $bindingRoute?->gatherMiddleware() ?? []))->toContain('auth:admin')
        ->and(implode('|', $bindingRoute?->gatherMiddleware() ?? []))->toContain('verify ai providers,admin')
        ->and(implode('|', $verificationRoute?->gatherMiddleware() ?? []))->toContain('verify ai providers,admin')
        ->and($verificationRoute?->gatherMiddleware())->toContain('throttle:ai-provider-verification');
});

test('guests web users metadata-only admins and editors cannot bind or verify providers', function () {
    $provider = geminiProvider(['adapter_type' => 'gemini']);
    Http::preventStrayRequests();

    $this->post(route('admin.ai-configuration.providers.verification.store', $provider), providerVerificationPayload($provider))
        ->assertRedirectToRoute('admin.login');

    $this->actingAs(User::factory()->create())
        ->post(route('admin.ai-configuration.providers.verification.store', $provider), providerVerificationPayload($provider))
        ->assertRedirectToRoute('admin.login');
    $this->assertGuest('admin');

    $metadataAdmin = createProviderVerificationAdmin(false);
    $this->actingAs($metadataAdmin, 'admin')
        ->post(route('admin.ai-configuration.providers.binding.store', $provider), providerBindingPayload($provider))
        ->assertForbidden();

    $editor = createProviderVerificationAdmin(false, false);
    $editor->assignRole(Role::create(['name' => 'Editor', 'guard_name' => 'admin']));
    $this->actingAs($editor, 'admin')
        ->post(route('admin.ai-configuration.providers.verification.store', $provider), providerVerificationPayload($provider))
        ->assertForbidden();

    Http::assertNothingSent();
    $this->assertDatabaseCount('ai_configuration_audits', 0);
});

test('the default-off gate blocks binding and verification without outbound HTTP', function () {
    config()->set('ai.provider_verification_enabled', false);
    $admin = createProviderVerificationAdmin();
    $provider = geminiProvider();
    Http::preventStrayRequests();

    $this->actingAs($admin, 'admin')
        ->post(route('admin.ai-configuration.providers.binding.store', $provider), providerBindingPayload($provider))
        ->assertServiceUnavailable();

    $provider->adapter_type = 'gemini';
    $provider->save();

    $this->post(route('admin.ai-configuration.providers.verification.store', $provider), providerVerificationPayload($provider->fresh()))
        ->assertServiceUnavailable();

    Http::assertNothingSent();
    $this->assertDatabaseCount('ai_configuration_audits', 0);
});

test('an authorized Admin can bind the trusted Gemini adapter transactionally', function () {
    $admin = createProviderVerificationAdmin();
    $provider = geminiProvider();

    $this->actingAs($admin, 'admin')
        ->post(route('admin.ai-configuration.providers.binding.store', $provider), providerBindingPayload($provider))
        ->assertRedirect()
        ->assertSessionHas('status');

    $freshProvider = $provider->fresh();
    $audit = AiConfigurationAudit::query()->sole();

    expect($freshProvider->adapter_type)->toBe('gemini')
        ->and($freshProvider->lock_version)->toBe(1)
        ->and($freshProvider->is_enabled)->toBeFalse()
        ->and($freshProvider->health_status)->toBe(AiProviderHealthStatus::Unverified)
        ->and($audit->action)->toBe('ai_provider.adapter_bound')
        ->and($audit->acting_admin_id)->toBe($admin->id)
        ->and($audit->changes)->toBe([
            'changed_fields' => ['adapter_type'],
            'verification_outcome' => 'trusted_adapter_bound',
        ]);
});

test('binding rejects unrelated providers arbitrary adapters and unexpected operational fields', function () {
    $admin = createProviderVerificationAdmin();
    $provider = AiProvider::factory()->create(['key' => 'unrelated-provider']);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.ai-configuration.providers.binding.store', $provider), providerBindingPayload($provider))
        ->assertSessionHasErrors('adapter_identifier');

    $this->post(route('admin.ai-configuration.providers.binding.store', $provider), providerBindingPayload($provider, [
        'adapter_identifier' => 'App\\Adapters\\InjectedAdapter',
        'base_url' => 'https://attacker.example',
    ]))->assertSessionHasErrors(['adapter_identifier', 'base_url']);

    expect($provider->fresh()->adapter_type)->toBeNull()
        ->and($provider->fresh()->lock_version)->toBe(0);
    $this->assertDatabaseCount('ai_configuration_audits', 0);
});

test('generic provider metadata updates still reject adapter and operational fields', function () {
    $admin = createProviderVerificationAdmin();
    $provider = geminiProvider();

    $this->actingAs($admin, 'admin')
        ->patch(route('admin.ai-configuration.providers.update', $provider), [
            'key' => 'gemini',
            'display_name' => 'Google Gemini',
            'description' => null,
            'request_timeout_seconds' => 30,
            'retry_limit' => 0,
            'lock_version' => 0,
            'adapter_type' => 'gemini',
            'health_status' => 'healthy',
            'is_enabled' => true,
        ])->assertSessionHasErrors(['adapter_type', 'health_status', 'is_enabled']);

    expect($provider->fresh()->adapter_type)->toBeNull()
        ->and($provider->fresh()->is_enabled)->toBeFalse();
});

test('binding no-op creates no audit or version increment', function () {
    $admin = createProviderVerificationAdmin();
    $provider = geminiProvider(['adapter_type' => 'gemini', 'lock_version' => 4]);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.ai-configuration.providers.binding.store', $provider), providerBindingPayload($provider))
        ->assertRedirect()
        ->assertSessionHas('status');

    expect($provider->fresh()->lock_version)->toBe(4);
    $this->assertDatabaseCount('ai_configuration_audits', 0);
});

test('binding rejects a stale version and rolls back when audit persistence fails', function () {
    $admin = createProviderVerificationAdmin();
    $staleProvider = geminiProvider(['lock_version' => 1]);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.ai-configuration.providers.binding.store', $staleProvider), providerBindingPayload($staleProvider, [
            'lock_version' => 0,
        ]))->assertSessionHasErrors('lock_version');

    expect($staleProvider->fresh()->adapter_type)->toBeNull()
        ->and($staleProvider->fresh()->lock_version)->toBe(1);

    $staleProvider->delete();

    $rollbackProvider = geminiProvider();
    Event::listen('eloquent.creating: '.AiConfigurationAudit::class, function (): never {
        throw new RuntimeException('Synthetic audit failure.');
    });

    $this->post(route('admin.ai-configuration.providers.binding.store', $rollbackProvider), providerBindingPayload($rollbackProvider))
        ->assertSessionHasErrors('binding');

    expect($rollbackProvider->fresh()->adapter_type)->toBeNull()
        ->and($rollbackProvider->fresh()->lock_version)->toBe(0);
    $this->assertDatabaseCount('ai_configuration_audits', 0);
});

test('missing credentials are reported as a boolean-safe failure without outbound HTTP', function () {
    config()->set('services.gemini.api_key', null);
    $admin = createProviderVerificationAdmin();
    $provider = geminiProvider(['adapter_type' => 'gemini']);
    Http::preventStrayRequests();

    $this->actingAs($admin, 'admin')
        ->post(route('admin.ai-configuration.providers.verification.store', $provider), providerVerificationPayload($provider))
        ->assertRedirect()
        ->assertSessionHasErrors(['verification' => 'Gemini credentials are not configured on the server.']);

    Http::assertNothingSent();
    expect($provider->fresh()->last_verified_at)->toBeNull()
        ->and($provider->fresh()->lock_version)->toBe(0)
        ->and(AiConfigurationAudit::query()->sole()->changes['verification_outcome'])->toBe(
            AiProviderErrorCategory::MissingCredentials->value,
        );
});

test('provider page exposes only credential presence and explicit gated actions', function () {
    $admin = createProviderVerificationAdmin();
    $provider = geminiProvider(['adapter_type' => 'gemini']);
    $secret = 'synthetic-super-secret-gemini-value';
    config()->set('services.gemini.api_key', $secret);
    Http::preventStrayRequests();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.ai-configuration.providers'))
        ->assertOk()
        ->assertSee('Configured')
        ->assertSee(route('admin.ai-configuration.providers.verification.store', $provider), false)
        ->assertSee('name="confirm_verification"', false)
        ->assertDontSee($secret);

    Http::assertNothingSent();
});

test('successful verification persists only limited evidence after the HTTP request finishes', function () {
    $admin = createProviderVerificationAdmin();
    $provider = geminiProvider(['adapter_type' => 'gemini']);
    Queue::fake();
    fakeSuccessfulGeminiVerification();
    $this->travelTo('2026-10-28 20:00:00');

    $this->actingAs($admin, 'admin')
        ->post(route('admin.ai-configuration.providers.verification.store', $provider), providerVerificationPayload($provider))
        ->assertRedirect()
        ->assertSessionHas('status');

    $freshProvider = $provider->fresh();
    $audit = AiConfigurationAudit::query()->sole();

    expect($freshProvider->last_verified_at?->format('Y-m-d H:i:s'))->toBe('2026-10-28 20:00:00')
        ->and($freshProvider->lock_version)->toBe(1)
        ->and($freshProvider->health_status)->toBe(AiProviderHealthStatus::Unverified)
        ->and($freshProvider->is_enabled)->toBeFalse()
        ->and($audit->action)->toBe('ai_provider.connection_verified')
        ->and($audit->changes)->toBe([
            'changed_fields' => ['last_verified_at'],
            'verification_outcome' => 'models_endpoint_verified',
        ]);
    $this->assertDatabaseCount('ai_models', 0);
    $this->assertDatabaseCount('ai_workload_routes', 0);
    Queue::assertNothingPushed();
    Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
        && parse_url($request->url(), PHP_URL_PATH) === '/v1beta/models'
        && ! str_contains($request->url(), 'synthetic-verification-key'));
});

test('verification performs no database transaction while the provider request is in flight', function () {
    $admin = createProviderVerificationAdmin();
    $provider = geminiProvider(['adapter_type' => 'gemini']);
    $baselineTransactionLevel = DB::transactionLevel();
    $transactionLevel = null;
    Http::preventStrayRequests();
    Http::fake([
        GeminiHttpTransport::BASE_URL.'/*' => function () use (&$transactionLevel) {
            $transactionLevel = DB::transactionLevel();

            return Http::response(['models' => []]);
        },
    ]);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.ai-configuration.providers.verification.store', $provider), providerVerificationPayload($provider))
        ->assertRedirect();

    expect($transactionLevel)->toBe($baselineTransactionLevel);
});

test('a concurrent provider change after HTTP verification rejects stale persistence', function () {
    $admin = createProviderVerificationAdmin();
    $provider = geminiProvider(['adapter_type' => 'gemini']);
    Http::preventStrayRequests();
    Http::fake([
        GeminiHttpTransport::BASE_URL.'/*' => function () use ($provider) {
            AiProvider::query()->whereKey($provider->id)->update([
                'display_name' => 'Changed concurrently',
                'lock_version' => 1,
            ]);

            return Http::response(['models' => []]);
        },
    ]);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.ai-configuration.providers.verification.store', $provider), providerVerificationPayload($provider))
        ->assertSessionHasErrors('lock_version');

    expect($provider->fresh()->display_name)->toBe('Changed concurrently')
        ->and($provider->fresh()->lock_version)->toBe(1)
        ->and($provider->fresh()->last_verified_at)->toBeNull();
    $this->assertDatabaseCount('ai_configuration_audits', 0);
});

test('verification audit failure rolls back timestamp and version changes', function () {
    $admin = createProviderVerificationAdmin();
    $provider = geminiProvider(['adapter_type' => 'gemini']);
    fakeSuccessfulGeminiVerification();
    Event::listen('eloquent.creating: '.AiConfigurationAudit::class, function (): never {
        throw new RuntimeException('Synthetic verification audit failure.');
    });

    $this->actingAs($admin, 'admin')
        ->post(route('admin.ai-configuration.providers.verification.store', $provider), providerVerificationPayload($provider))
        ->assertSessionHasErrors('verification');

    expect($provider->fresh()->last_verified_at)->toBeNull()
        ->and($provider->fresh()->lock_version)->toBe(0)
        ->and($provider->fresh()->health_status)->toBe(AiProviderHealthStatus::Unverified);
    $this->assertDatabaseCount('ai_configuration_audits', 0);
});

test('provider failures remain sanitized and never change verification state', function (int $status, string $code, AiProviderErrorCategory $category): void {
    $admin = createProviderVerificationAdmin();
    $provider = geminiProvider(['adapter_type' => 'gemini']);
    $rawSecret = 'synthetic-verification-key raw provider detail';
    Http::preventStrayRequests();
    Http::fake([
        GeminiHttpTransport::BASE_URL.'/*' => Http::response([
            'error' => ['code' => $code, 'message' => $rawSecret],
        ], $status),
    ]);

    $response = $this->actingAs($admin, 'admin')
        ->post(route('admin.ai-configuration.providers.verification.store', $provider), providerVerificationPayload($provider));

    $response->assertRedirect()->assertSessionHasErrors('verification');
    expect(session('errors')->first('verification'))->not->toContain($rawSecret)
        ->and($provider->fresh()->last_verified_at)->toBeNull()
        ->and($provider->fresh()->lock_version)->toBe(0)
        ->and(AiConfigurationAudit::query()->sole()->changes['verification_outcome'])->toBe($category->value);
})->with([
    'authentication' => [401, 'authentication', AiProviderErrorCategory::AuthenticationFailed],
    'permission' => [403, 'permission_denied', AiProviderErrorCategory::PermissionDenied],
    'rate limit' => [429, 'rate_limit_exceeded', AiProviderErrorCategory::RateLimited],
    'provider unavailable' => [503, 'service_unavailable', AiProviderErrorCategory::ProviderUnavailable],
]);

test('malformed responses are safely classified', function () {
    $admin = createProviderVerificationAdmin();
    $malformedProvider = geminiProvider(['adapter_type' => 'gemini']);
    Http::preventStrayRequests();
    Http::fake([
        GeminiHttpTransport::BASE_URL.'/*' => Http::response('raw malformed secret', 200),
    ]);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.ai-configuration.providers.verification.store', $malformedProvider), providerVerificationPayload($malformedProvider))
        ->assertSessionHasErrors('verification');

    expect(AiConfigurationAudit::query()->latest('id')->firstOrFail()->changes['verification_outcome'])
        ->toBe(AiProviderErrorCategory::MalformedResponse->value);
});

test('network timeouts are safely classified', function () {
    $admin = createProviderVerificationAdmin();
    $timeoutProvider = geminiProvider(['adapter_type' => 'gemini']);
    Http::preventStrayRequests();

    Http::fake([
        GeminiHttpTransport::BASE_URL.'/*' => Http::failedConnection('cURL error 28: synthetic timeout with secret'),
    ]);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.ai-configuration.providers.verification.store', $timeoutProvider), providerVerificationPayload($timeoutProvider))
        ->assertSessionHasErrors('verification');

    expect(AiConfigurationAudit::query()->latest('id')->firstOrFail()->changes['verification_outcome'])
        ->toBe(AiProviderErrorCategory::Timeout->value);
});

test('named rate limiting permits three Admin attempts and blocks the fourth', function () {
    $admin = createProviderVerificationAdmin();
    $provider = geminiProvider(['adapter_type' => 'gemini']);
    RateLimiter::clear('admin-provider-verification:'.$admin->id);
    RateLimiter::clear('provider-verification:'.$provider->id);
    Http::preventStrayRequests();
    Http::fake([
        GeminiHttpTransport::BASE_URL.'/*' => Http::response([
            'error' => ['code' => 'authentication', 'message' => 'synthetic'],
        ], 401),
    ]);

    $this->actingAs($admin, 'admin');

    foreach (range(1, 3) as $_attempt) {
        $this->post(route('admin.ai-configuration.providers.verification.store', $provider), providerVerificationPayload($provider))
            ->assertRedirect();
    }

    $this->post(route('admin.ai-configuration.providers.verification.store', $provider), providerVerificationPayload($provider))
        ->assertTooManyRequests();

    Http::assertSentCount(3);
    $this->assertDatabaseCount('ai_configuration_audits', 3);
});

test('verification POST requires CSRF outside the test environment', function () {
    $admin = createProviderVerificationAdmin();
    $provider = geminiProvider(['adapter_type' => 'gemini']);
    Http::preventStrayRequests();

    $this->actingAs($admin, 'admin');
    $this->app['env'] = 'production';

    $this->post(route('admin.ai-configuration.providers.verification.store', $provider), providerVerificationPayload($provider))
        ->assertStatus(419);

    Http::assertNothingSent();
    expect($provider->fresh()->last_verified_at)->toBeNull();
});
