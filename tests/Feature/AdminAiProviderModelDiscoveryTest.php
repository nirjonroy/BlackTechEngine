<?php

use App\Models\Admin;
use App\Models\AiProvider;
use App\Models\User;
use App\Services\Ai\Gemini\GeminiHttpTransport;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    config()->set('ai.provider_verification_enabled', true);
    config()->set('services.gemini.api_key', 'synthetic-discovery-key');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach (['manage ai models', 'verify ai providers'] as $permission) {
        Permission::firstOrCreate([
            'name' => $permission,
            'guard_name' => 'admin',
        ]);
    }
});

function phase15bDiscoveryAdmin(bool $authorized = true): Admin
{
    $admin = Admin::query()->create([
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('password'),
    ]);

    if ($authorized) {
        $admin->givePermissionTo('verify ai providers');
    }

    $admin->givePermissionTo('manage ai models');

    return $admin;
}

function phase15bGeminiProvider(array $overrides = []): AiProvider
{
    return AiProvider::factory()->create([
        'key' => 'gemini',
        'display_name' => 'Google Gemini',
        'adapter_type' => 'gemini',
        ...$overrides,
    ]);
}

function phase15bDiscoveryPayload(array $overrides = []): array
{
    return [
        'confirm_discovery' => '1',
        ...$overrides,
    ];
}

test('discovery route is an authorized rate limited POST action', function () {
    $route = Route::getRoutes()->getByName('admin.ai-configuration.providers.discovery.store');

    expect($route?->methods())->toBe(['POST'])
        ->and(implode('|', $route?->gatherMiddleware() ?? []))->toContain('auth:admin')
        ->and(implode('|', $route?->gatherMiddleware() ?? []))->toContain('verify ai providers,admin')
        ->and($route?->gatherMiddleware())->toContain('throttle:ai-provider-verification');
});

test('guests web users and unauthorized Admins cannot discover models', function () {
    $provider = phase15bGeminiProvider();
    Http::preventStrayRequests();

    $this->post(route('admin.ai-configuration.providers.discovery.store', $provider), phase15bDiscoveryPayload())
        ->assertRedirectToRoute('admin.login');

    $this->actingAs(User::factory()->create())
        ->post(route('admin.ai-configuration.providers.discovery.store', $provider), phase15bDiscoveryPayload())
        ->assertRedirectToRoute('admin.login');

    $this->actingAs(phase15bDiscoveryAdmin(false), 'admin')
        ->post(route('admin.ai-configuration.providers.discovery.store', $provider), phase15bDiscoveryPayload())
        ->assertForbidden();

    Http::assertNothingSent();
});

test('disabled gate and missing credentials prevent discovery safely', function () {
    $admin = phase15bDiscoveryAdmin();
    $provider = phase15bGeminiProvider();
    Http::preventStrayRequests();

    config()->set('ai.provider_verification_enabled', false);
    $this->actingAs($admin, 'admin')
        ->post(route('admin.ai-configuration.providers.discovery.store', $provider), phase15bDiscoveryPayload())
        ->assertServiceUnavailable();

    config()->set('ai.provider_verification_enabled', true);
    config()->set('services.gemini.api_key', null);
    $this->post(route('admin.ai-configuration.providers.discovery.store', $provider), phase15bDiscoveryPayload())
        ->assertSessionHasErrors(['discovery' => 'Gemini credentials are not configured on the server.']);

    Http::assertNothingSent();
});

test('discovery accepts only the trusted bound Gemini provider and explicit payload', function () {
    $admin = phase15bDiscoveryAdmin();
    $unboundProvider = phase15bGeminiProvider(['adapter_type' => null]);
    Http::preventStrayRequests();

    $this->actingAs($admin, 'admin')
        ->post(route('admin.ai-configuration.providers.discovery.store', $unboundProvider), phase15bDiscoveryPayload())
        ->assertSessionHasErrors('verification');

    $this->post(route('admin.ai-configuration.providers.discovery.store', $unboundProvider), phase15bDiscoveryPayload([
        'endpoint' => 'https://attacker.example/models',
        'api_key' => 'forged',
    ]))->assertSessionHasErrors(['endpoint', 'api_key']);

    Http::assertNothingSent();
});

test('authorized discovery renders a complete preview without persisting provider or model state', function () {
    $admin = phase15bDiscoveryAdmin();
    $provider = phase15bGeminiProvider();
    $providerState = $provider->fresh()->getAttributes();
    Http::preventStrayRequests();
    Http::fake([
        GeminiHttpTransport::BASE_URL.'/*' => Http::response([
            'models' => [[
                'name' => 'models/gemini-2.5-flash',
                'baseModelId' => 'gemini-2.5-flash',
                'version' => '2.5',
                'displayName' => 'Gemini 2.5 Flash',
                'description' => 'Synthetic catalog description.',
                'inputTokenLimit' => 1048576,
                'outputTokenLimit' => 65536,
                'supportedGenerationMethods' => ['generateContent', 'countTokens'],
            ], [
                'name' => 'models/text-embedding-004',
                'baseModelId' => 'text-embedding',
                'version' => '004',
                'displayName' => 'Text Embedding 004',
                'supportedGenerationMethods' => ['embedContent'],
            ]],
        ]),
    ]);

    $response = $this->actingAs($admin, 'admin')
        ->post(route('admin.ai-configuration.providers.discovery.store', $provider), phase15bDiscoveryPayload());

    $response->assertOk()
        ->assertViewIs('admin.ai-configuration.providers.discovery')
        ->assertSee('Catalog preview complete.')
        ->assertSee('Gemini 2.5 Flash')
        ->assertSee('generateContent supported')
        ->assertSee('1,048,576 tokens')
        ->assertSee('Not generation-capable')
        ->assertSee('No models were created, updated, enabled, or removed by this preview.');

    expect($provider->fresh()->getAttributes())->toBe($providerState);
    $this->assertDatabaseCount('ai_models', 0);
    $this->assertDatabaseCount('ai_configuration_audits', 0);
    Http::assertSentCount(1);
});

test('multi-page partial discovery is never presented as complete', function () {
    $admin = phase15bDiscoveryAdmin();
    $provider = phase15bGeminiProvider();
    Http::preventStrayRequests();
    Http::fake([
        GeminiHttpTransport::BASE_URL.'/*' => Http::sequence()->push([
            'models' => [[
                'name' => 'models/gemini-first',
                'baseModelId' => 'gemini-first',
                'version' => '1',
                'supportedGenerationMethods' => ['generateContent'],
            ]],
            'nextPageToken' => 'page-two',
        ])
            ->push(['error' => ['code' => 'service_unavailable', 'message' => 'sensitive provider detail']], 503),
    ]);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.ai-configuration.providers.discovery.store', $provider), phase15bDiscoveryPayload())
        ->assertOk()
        ->assertSee('Catalog preview is partial.')
        ->assertSee('Do not treat this preview as the complete Gemini catalog.')
        ->assertSee('gemini-first')
        ->assertDontSee('sensitive provider detail');

    Http::assertSentCount(2);
    $this->assertDatabaseCount('ai_models', 0);
});

test('malformed and authentication failures are sanitized and make no changes', function (array|string $body, int $status, string $message): void {
    $admin = phase15bDiscoveryAdmin();
    $provider = phase15bGeminiProvider();
    Http::preventStrayRequests();
    Http::fake([
        GeminiHttpTransport::BASE_URL.'/*' => Http::response($body, $status),
    ]);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.ai-configuration.providers.discovery.store', $provider), phase15bDiscoveryPayload())
        ->assertSessionHasErrors(['discovery' => $message]);

    $this->assertDatabaseCount('ai_models', 0);
    $this->assertDatabaseCount('ai_configuration_audits', 0);
})->with([
    'malformed response' => ['raw secret response', 200, 'Gemini returned an unexpected model catalog response.'],
    'invalid credentials' => [['error' => ['code' => 'authentication', 'message' => 'raw secret']], 401, 'Gemini rejected the configured server credential.'],
]);

test('provider catalog exposes discovery only as a gated credential-safe action', function () {
    $admin = phase15bDiscoveryAdmin();
    $provider = phase15bGeminiProvider();
    $secret = 'synthetic-discovery-secret-value';
    config()->set('services.gemini.api_key', $secret);
    Http::preventStrayRequests();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.ai-configuration.providers'))
        ->assertOk()
        ->assertSee('Gemini provider setup workflow')
        ->assertSee(route('admin.ai-configuration.providers.discovery.store', $provider), false)
        ->assertSee('name="confirm_discovery"', false)
        ->assertDontSee($secret);

    Http::assertNothingSent();
});
