<?php

use App\Enums\AiCapabilitySupport;
use App\Enums\AiModelCapability;
use App\Enums\AiProviderErrorCategory;
use App\Models\AiProvider;
use App\Services\Ai\AiProviderAdapterRegistry;
use App\Services\Ai\AiProviderCredentialResolver;
use App\Services\Ai\Exceptions\AiProviderException;
use App\Services\Ai\Gemini\GeminiHttpTransport;
use App\Services\Ai\Gemini\GeminiProviderAdapter;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;

function geminiCatalogModel(array $overrides = []): array
{
    return [
        'name' => 'models/Gemini-2.5-Flash',
        'baseModelId' => 'gemini-2.5-flash',
        'version' => '2.5',
        'displayName' => 'Gemini 2.5 Flash',
        'description' => 'A synthetic test model.',
        'inputTokenLimit' => 1048576,
        'outputTokenLimit' => 65536,
        'supportedGenerationMethods' => ['generateContent', 'countTokens'],
        'thinking' => true,
        ...$overrides,
    ];
}

function fakeGeminiHttp(mixed $response): void
{
    Http::preventStrayRequests();
    Http::fake([
        GeminiHttpTransport::BASE_URL.'/*' => $response,
    ]);
}

function configuredGeminiAdapter(): GeminiProviderAdapter
{
    config()->set('services.gemini.api_key', 'synthetic-gemini-key-for-tests');

    return app(GeminiProviderAdapter::class);
}

function capturedProviderException(callable $operation): AiProviderException
{
    try {
        $operation();
    } catch (AiProviderException $exception) {
        return $exception;
    }

    throw new RuntimeException('Expected an AI provider exception was not thrown.');
}

test('Gemini adapter tests use isolated application services', function () {
    expect(app()->environment())->toBe('testing')
        ->and(config('database.default'))->toBe('sqlite')
        ->and(config('database.connections.sqlite.database'))->toBe(':memory:')
        ->and(config('cache.default'))->toBe('array')
        ->and(config('session.driver'))->toBe('array');
});

test('adapter registry resolves only the code-owned Gemini adapter', function () {
    $registry = app(AiProviderAdapterRegistry::class);

    expect($registry->resolve('gemini'))->toBeInstanceOf(GeminiProviderAdapter::class)
        ->and(fn () => $registry->resolve('App\\Services\\DangerousAdapter'))->toThrow(
            InvalidArgumentException::class,
            'The requested AI provider adapter is not registered.',
        )
        ->and(fn () => $registry->resolve('https://attacker.example'))->toThrow(InvalidArgumentException::class)
        ->and(AiProvider::SUPPORTED_ADAPTER_TYPES)->toBe(['gemini']);
});

test('credential resolver reports presence without exposing a credential', function () {
    $resolver = app(AiProviderCredentialResolver::class);
    config()->set('services.gemini.api_key', null);

    expect($resolver->hasGeminiCredentials())->toBeFalse();

    config()->set('services.gemini.api_key', ' synthetic-only ');

    expect($resolver->hasGeminiCredentials())->toBeTrue()
        ->and($resolver->geminiApiKey())->toBe('synthetic-only');
});

test('missing credentials fail safely without making an HTTP request', function () {
    config()->set('services.gemini.api_key', null);
    Http::preventStrayRequests();
    $adapter = app(GeminiProviderAdapter::class);

    $verification = $adapter->verifyConnection();
    $exception = capturedProviderException(fn () => $adapter->discoverModels());

    expect($verification->successful)->toBeFalse()
        ->and($verification->errorCategory)->toBe(AiProviderErrorCategory::MissingCredentials)
        ->and($exception->category)->toBe(AiProviderErrorCategory::MissingCredentials)
        ->and($exception->getMessage())->not->toContain('key');
    Http::assertNothingSent();
});

test('transport uses a fixed versioned HTTPS endpoint and API key header', function () {
    fakeGeminiHttp(Http::response(['models' => []]));
    $adapter = configuredGeminiAdapter();

    $result = $adapter->discoverModels();

    expect($result->complete)->toBeTrue()
        ->and($result->models)->toBeEmpty();
    Http::assertSent(function (Request $request): bool {
        $query = [];
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

        return $request->method() === 'GET'
            && parse_url($request->url(), PHP_URL_SCHEME) === 'https'
            && parse_url($request->url(), PHP_URL_HOST) === 'generativelanguage.googleapis.com'
            && parse_url($request->url(), PHP_URL_PATH) === '/v1beta/models'
            && $query === ['pageSize' => '50']
            && ! str_contains($request->url(), 'synthetic-gemini-key-for-tests')
            && $request->hasHeader('x-goog-api-key', 'synthetic-gemini-key-for-tests');
    });
});

test('connection verification parses a non-generating model catalog response', function () {
    fakeGeminiHttp(Http::response(['models' => [geminiCatalogModel()]]));

    $verification = configuredGeminiAdapter()->verifyConnection();

    expect($verification->successful)->toBeTrue()
        ->and($verification->errorCategory)->toBeNull();
    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'pageSize=1'));
});

test('model discovery preserves native identifiers and documented metadata', function () {
    fakeGeminiHttp(Http::response(['models' => [geminiCatalogModel()]]));

    $result = configuredGeminiAdapter()->discoverModels();
    $model = $result->models[0];

    expect($result->complete)->toBeTrue()
        ->and($result->pagesFetched)->toBe(1)
        ->and($model->resourceName)->toBe('models/Gemini-2.5-Flash')
        ->and($model->providerModelId)->toBe('Gemini-2.5-Flash')
        ->and($model->baseModelId)->toBe('gemini-2.5-flash')
        ->and($model->version)->toBe('2.5')
        ->and($model->displayName)->toBe('Gemini 2.5 Flash')
        ->and($model->inputTokenLimit)->toBe(1048576)
        ->and($model->outputTokenLimit)->toBe(65536)
        ->and($model->supportsThinking)->toBeTrue();
});

test('capability mapping distinguishes supported unsupported and unknown states', function () {
    fakeGeminiHttp(Http::response(['models' => [geminiCatalogModel()]]));

    $knownModel = configuredGeminiAdapter()->discoverModels()->models[0];

    expect($knownModel->capabilitySupport(AiModelCapability::TextGeneration))->toBe(AiCapabilitySupport::Supported)
        ->and($knownModel->capabilitySupport(AiModelCapability::Embeddings))->toBe(AiCapabilitySupport::Unsupported)
        ->and($knownModel->capabilitySupport(AiModelCapability::StructuredOutput))->toBe(AiCapabilitySupport::Unknown)
        ->and($knownModel->capabilitySupport(AiModelCapability::ImageGeneration))->toBe(AiCapabilitySupport::Unknown);
});

test('omitted generation-method metadata remains unknown', function () {
    $model = geminiCatalogModel();
    unset($model['supportedGenerationMethods']);
    fakeGeminiHttp(Http::response(['models' => [$model]]));

    $discoveredModel = configuredGeminiAdapter()->discoverModels()->models[0];

    expect($discoveredModel->generationMethodsKnown)->toBeFalse()
        ->and($discoveredModel->capabilitySupport(AiModelCapability::TextGeneration))->toBe(AiCapabilitySupport::Unknown)
        ->and($discoveredModel->capabilitySupport(AiModelCapability::Embeddings))->toBe(AiCapabilitySupport::Unknown);
});

test('model discovery follows valid pagination without changing request parameters', function () {
    $sequence = Http::sequence()
        ->push(['models' => [geminiCatalogModel()], 'nextPageToken' => 'page-two'])
        ->push(['models' => [geminiCatalogModel([
            'name' => 'models/gemini-embedding-001',
            'baseModelId' => 'gemini-embedding',
            'version' => '001',
            'supportedGenerationMethods' => ['embedContent'],
        ])]]);
    fakeGeminiHttp($sequence);

    $result = configuredGeminiAdapter()->discoverModels();

    expect($result->complete)->toBeTrue()
        ->and($result->pagesFetched)->toBe(2)
        ->and($result->models)->toHaveCount(2);
    Http::assertSentCount(2);
    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'pageToken=page-two')
        && str_contains($request->url(), 'pageSize=50'));
});

test('repeated pagination tokens produce an incomplete safe result', function () {
    $sequence = Http::sequence()
        ->push(['models' => [geminiCatalogModel()], 'nextPageToken' => 'repeated'])
        ->push(['models' => [geminiCatalogModel([
            'name' => 'models/gemini-second',
            'baseModelId' => 'gemini-second',
        ])], 'nextPageToken' => 'repeated']);
    fakeGeminiHttp($sequence);

    $result = configuredGeminiAdapter()->discoverModels();

    expect($result->complete)->toBeFalse()
        ->and($result->incompleteReason)->toBe('malformed_pagination')
        ->and($result->errorCategory)->toBe(AiProviderErrorCategory::MalformedResponse)
        ->and($result->models)->toHaveCount(2);
});

test('discovery stops at its page bound without treating the result as complete', function () {
    $sequence = Http::sequence();

    foreach (range(1, 10) as $page) {
        $sequence->push([
            'models' => [geminiCatalogModel([
                'name' => "models/gemini-page-{$page}",
                'baseModelId' => "gemini-page-{$page}",
            ])],
            'nextPageToken' => 'page-'.($page + 1),
        ]);
    }

    fakeGeminiHttp($sequence);

    $result = configuredGeminiAdapter()->discoverModels();

    expect($result->complete)->toBeFalse()
        ->and($result->pagesFetched)->toBe(10)
        ->and($result->incompleteReason)->toBe('page_limit')
        ->and($result->models)->toHaveCount(10);
    Http::assertSentCount(10);
});

test('a later provider failure returns an incomplete catalog without discarding earlier models', function () {
    $sequence = Http::sequence()
        ->push(['models' => [geminiCatalogModel()], 'nextPageToken' => 'next'])
        ->push(['error' => ['code' => 'service_unavailable', 'message' => 'raw provider detail']], 503);
    fakeGeminiHttp($sequence);

    $result = configuredGeminiAdapter()->discoverModels();

    expect($result->complete)->toBeFalse()
        ->and($result->incompleteReason)->toBe('provider_error')
        ->and($result->errorCategory)->toBe(AiProviderErrorCategory::ProviderUnavailable)
        ->and($result->models)->toHaveCount(1);
});

test('malformed model metadata is rejected without returning provider payloads', function (array $model): void {
    fakeGeminiHttp(Http::response(['models' => [$model]]));

    $exception = capturedProviderException(fn () => configuredGeminiAdapter()->discoverModels());

    expect($exception->category)->toBe(AiProviderErrorCategory::MalformedResponse)
        ->and($exception->getMessage())->toBe('Gemini returned an unexpected model catalog response.')
        ->and($exception->getMessage())->not->toContain(json_encode($model));
})->with([
    'missing required name' => [geminiCatalogModel(['name' => null])],
    'unsafe resource name' => [geminiCatalogModel(['name' => 'publishers/attacker/model'])],
    'missing base model identifier' => [geminiCatalogModel(['baseModelId' => null])],
    'invalid token limit' => [geminiCatalogModel(['inputTokenLimit' => -1])],
    'invalid generation methods' => [geminiCatalogModel(['supportedGenerationMethods' => null])],
    'duplicate generation method' => [geminiCatalogModel(['supportedGenerationMethods' => ['generateContent', 'generateContent']])],
]);

test('malformed JSON is classified without disclosing the response body', function () {
    fakeGeminiHttp(Http::response('secret raw provider response', 200, ['Content-Type' => 'application/json']));

    $exception = capturedProviderException(fn () => configuredGeminiAdapter()->discoverModels());

    expect($exception->category)->toBe(AiProviderErrorCategory::MalformedResponse)
        ->and($exception->getMessage())->not->toContain('secret raw provider response')
        ->and($exception->getMessage())->not->toContain('synthetic-gemini-key-for-tests');
});

test('HTTP failures are classified using validated status and provider codes', function (int $status, ?string $code, AiProviderErrorCategory $category): void {
    $error = ['message' => 'raw provider error with synthetic-gemini-key-for-tests'];

    if ($code !== null) {
        $error['code'] = $code;
    }

    fakeGeminiHttp(Http::response(['error' => $error], $status));

    $exception = capturedProviderException(fn () => configuredGeminiAdapter()->discoverModels());

    expect($exception->category)->toBe($category)
        ->and($exception->getMessage())->not->toContain('raw provider error')
        ->and($exception->getMessage())->not->toContain('synthetic-gemini-key-for-tests');
})->with([
    'invalid request' => [400, 'invalid_request', AiProviderErrorCategory::InvalidRequest],
    'authentication' => [401, 'authentication', AiProviderErrorCategory::AuthenticationFailed],
    'billing' => [402, 'payment_required', AiProviderErrorCategory::BillingRequired],
    'permission' => [403, 'permission_denied', AiProviderErrorCategory::PermissionDenied],
    'missing model' => [404, 'model_not_found', AiProviderErrorCategory::ModelNotFound],
    'rate limit' => [429, 'rate_limit_exceeded', AiProviderErrorCategory::RateLimited],
    'quota' => [429, 'quota_exceeded', AiProviderErrorCategory::QuotaExceeded],
    'server error' => [500, 'api_error', AiProviderErrorCategory::ProviderUnavailable],
    'bad gateway' => [502, null, AiProviderErrorCategory::ProviderUnavailable],
    'unavailable' => [503, 'service_unavailable', AiProviderErrorCategory::ProviderUnavailable],
    'deadline' => [504, 'deadline_exceeded', AiProviderErrorCategory::Timeout],
    'gateway timeout' => [504, null, AiProviderErrorCategory::Timeout],
]);

test('connection failures and timeouts are sanitized', function (string $message, AiProviderErrorCategory $category): void {
    fakeGeminiHttp(Http::failedConnection($message));

    $exception = capturedProviderException(fn () => configuredGeminiAdapter()->discoverModels());

    expect($exception->category)->toBe($category)
        ->and($exception->getMessage())->not->toContain($message)
        ->and($exception->getMessage())->not->toContain('synthetic-gemini-key-for-tests');
})->with([
    'network failure' => ['synthetic DNS failure', AiProviderErrorCategory::NetworkFailure],
    'timeout' => ['cURL error 28: operation timed out with a secret', AiProviderErrorCategory::Timeout],
]);

test('adapter operations do not mutate AI records dispatch jobs or register Admin routes', function () {
    Queue::fake();
    fakeGeminiHttp(Http::response(['models' => [geminiCatalogModel()]]));
    $adminRouteCount = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route): bool => str_starts_with($route->uri(), 'admin/ai-configuration'))
        ->count();

    configuredGeminiAdapter()->verifyConnection();
    configuredGeminiAdapter()->discoverModels();

    $this->assertDatabaseCount('ai_providers', 0);
    $this->assertDatabaseCount('ai_models', 0);
    $this->assertDatabaseCount('ai_workload_routes', 0);
    $this->assertDatabaseCount('ai_configuration_audits', 0);
    expect(collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route): bool => str_starts_with($route->uri(), 'admin/ai-configuration'))
        ->count())->toBe($adminRouteCount);
    Queue::assertNothingPushed();
});
