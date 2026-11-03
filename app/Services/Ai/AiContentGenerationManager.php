<?php

namespace App\Services\Ai;

use App\Enums\AiContentDraftStatus;
use App\Enums\AiContentLength;
use App\Enums\AiGenerationAttemptStatus;
use App\Enums\AiModelAvailabilityStatus;
use App\Enums\AiModelCapability;
use App\Enums\AiProviderErrorCategory;
use App\Enums\AiProviderHealthStatus;
use App\Enums\EntitlementKey;
use App\Models\AiContentDraft;
use App\Models\AiGenerationAttempt;
use App\Models\AiModel;
use App\Models\AiProvider;
use App\Models\AiWorkloadRoute;
use App\Models\Brand;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Ai\Exceptions\AiContentGenerationException;
use App\Services\Ai\Exceptions\AiProviderException;
use App\Services\ContentEditorialWorkflow;
use App\Services\Subscriptions\EntitlementService;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

class AiContentGenerationManager
{
    public const WORKLOAD_KEY = 'seo-geo-content-generation';

    public const MAXIMUM_INPUT_TOKENS = 4000;

    public const MAXIMUM_OUTPUT_TOKENS = 2500;

    public const FIRST_GENERATION_SINGLE_USE_KEY = 'first-controlled-gemini-generation';

    public function __construct(
        private readonly AiContentPromptBuilder $promptBuilder,
        private readonly AiProviderAdapterRegistry $adapterRegistry,
        private readonly ContentEditorialWorkflow $editorialWorkflow,
        private readonly EntitlementService $entitlements,
    ) {}

    /** @param array<string, mixed> $input */
    public function generate(User $user, Workspace $workspace, Brand $brand, array $input): AiContentDraft
    {
        if (! config('ai.content_generation_enabled')) {
            throw new AiContentGenerationException('feature_disabled', 'AI content generation is currently disabled.');
        }

        $this->entitlements->assertFeature($workspace, EntitlementKey::ContentStudio);

        try {
            $reservation = Cache::lock('ai-content-generation:workspace:'.$workspace->getKey(), 10)
                ->block(1, fn (): array => $this->reserve($user, $workspace, $brand, $input));
        } catch (LockTimeoutException) {
            throw new AiContentGenerationException('request_in_progress', 'Another generation request is already being prepared. Please try again.');
        }

        if ($reservation['draft'] instanceof AiContentDraft) {
            return $reservation['draft'];
        }

        /** @var AiGenerationAttempt $attempt */
        $attempt = $reservation['attempt'];
        /** @var AiModel $model */
        $model = $reservation['model'];

        $inputTokenLimit = min(self::MAXIMUM_INPUT_TOKENS, $model->input_token_limit ?? self::MAXIMUM_INPUT_TOKENS);
        $outputTokenLimit = min(
            self::MAXIMUM_OUTPUT_TOKENS,
            $model->output_token_limit ?? self::MAXIMUM_OUTPUT_TOKENS,
            AiContentLength::from($input['content_length'])->maximumOutputTokens(),
        );

        try {
            $adapter = $this->adapterRegistry->resolve((string) $model->provider->adapter_type);
            $countedInputTokens = $adapter->countInputTokens(
                $model->provider_model_id,
                $reservation['prompt'],
                $outputTokenLimit,
            );

            if ($countedInputTokens > $inputTokenLimit) {
                $this->failAttempt($attempt, 'input_token_limit_exceeded');

                throw new AiContentGenerationException(
                    'input_token_limit_exceeded',
                    'The content brief exceeds the approved AI input limit. Shorten the brief and try again.',
                );
            }

            $result = $adapter->generateStructuredContent(
                $model->provider_model_id,
                $reservation['prompt'],
                $outputTokenLimit,
            );

            if (($result->inputTokens !== null && $result->inputTokens > $inputTokenLimit)
                || ($result->outputTokens !== null && $result->outputTokens > $outputTokenLimit)) {
                $this->failAttempt(
                    $attempt,
                    'provider_token_limit_mismatch',
                    $result->inputTokens,
                    $result->outputTokens,
                );

                throw new AiContentGenerationException(
                    'provider_token_limit_mismatch',
                    'AI generation exceeded an approved token limit, so no draft was saved.',
                );
            }
        } catch (InvalidArgumentException) {
            $this->failAttempt($attempt, 'invalid_configuration');

            throw new AiContentGenerationException('missing_configuration', 'AI generation is not configured yet.');
        } catch (AiProviderException $exception) {
            $this->failAttempt($attempt, $exception->category->value);

            $message = $exception->category === AiProviderErrorCategory::MissingCredentials
                ? 'AI generation is not configured yet.'
                : 'AI generation could not be completed. Please try again later.';

            throw new AiContentGenerationException($exception->category->value, $message);
        }

        try {
            return DB::transaction(function () use ($attempt, $brand, $input, $model, $result, $user, $workspace): AiContentDraft {
                $draft = AiContentDraft::query()->create([
                    ...collect($input)->except('idempotency_key')->all(),
                    'workspace_id' => $workspace->getKey(),
                    'brand_id' => $brand->getKey(),
                    'created_by_user_id' => $user->getKey(),
                    'ai_provider_id' => $model->ai_provider_id,
                    'ai_model_id' => $model->getKey(),
                    'seo_title' => $result->content->seoTitle,
                    'meta_description' => $result->content->metaDescription,
                    'suggested_slug' => $result->content->suggestedSlug,
                    'outline' => $result->content->outline,
                    'content' => $result->content->content,
                    'faq_entries' => $result->content->faqEntries,
                    'internal_link_opportunities' => $result->content->internalLinkOpportunities,
                    'external_research_topics' => $result->content->externalResearchTopics,
                    'verification_notes' => $result->content->verificationNotes,
                    'status' => AiContentDraftStatus::NeedsReview,
                ]);

                $attempt->update([
                    'ai_content_draft_id' => $draft->getKey(),
                    'status' => AiGenerationAttemptStatus::Completed,
                    'input_tokens' => $result->inputTokens,
                    'output_tokens' => $result->outputTokens,
                    'completed_at' => now(),
                ]);

                $this->editorialWorkflow->recordCreation($draft, $user, 'ai_draft_created');

                return $draft;
            });
        } catch (Throwable) {
            try {
                $this->failAttempt($attempt, 'persistence_failure');
            } catch (Throwable) {
                // The controlled response must not expose a secondary storage failure.
            }

            throw new AiContentGenerationException('persistence_failure', 'The generated draft could not be saved safely. Please try again later.');
        }
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{attempt: AiGenerationAttempt|null, model: AiModel|null, prompt: string, draft: AiContentDraft|null}
     */
    private function reserve(User $user, Workspace $workspace, Brand $brand, array $input): array
    {
        $firstGenerationTest = $this->firstGenerationTestEnabled();

        if ($firstGenerationTest) {
            $this->ensureApprovedFirstGenerationTenant($user, $workspace, $brand);
        }

        $existing = AiGenerationAttempt::query()
            ->whereBelongsTo($user)
            ->where('idempotency_key', $input['idempotency_key'])
            ->first();

        if ($existing !== null) {
            if ($existing->status === AiGenerationAttemptStatus::Completed && $existing->draft !== null) {
                return ['attempt' => null, 'model' => null, 'prompt' => '', 'draft' => $existing->draft];
            }

            throw new AiContentGenerationException('duplicate_request', 'This generation request has already been submitted.');
        }

        $dailyLimit = $firstGenerationTest ? 1 : (int) config('ai.content_generation_daily_limit', 20);
        $dailyAttempts = AiGenerationAttempt::query()
            ->where(fn ($query) => $query->whereBelongsTo($user)->orWhereBelongsTo($workspace))
            ->where('created_at', '>=', now()->startOfDay())
            ->count();

        if ($dailyLimit < 1 || $dailyAttempts >= $dailyLimit) {
            throw new AiContentGenerationException('daily_limit_reached', 'The daily AI generation limit has been reached.');
        }

        $model = $this->eligibleModel();
        $prompt = $this->promptBuilder->build($brand, $input);
        try {
            $attempt = AiGenerationAttempt::query()->create([
                'correlation_id' => (string) Str::uuid(),
                'idempotency_key' => $input['idempotency_key'],
                'single_use_key' => $firstGenerationTest ? self::FIRST_GENERATION_SINGLE_USE_KEY : null,
                'workspace_id' => $workspace->getKey(),
                'brand_id' => $brand->getKey(),
                'user_id' => $user->getKey(),
                'ai_model_id' => $model->getKey(),
                'status' => AiGenerationAttemptStatus::Pending,
                'prompt_hash' => hash('sha256', $prompt),
            ]);
        } catch (QueryException $exception) {
            if ($firstGenerationTest && preg_match('/single_use_key|ai_generation_attempts_single_use_key_unique/i', $exception->getMessage()) === 1) {
                throw new AiContentGenerationException(
                    'single_use_consumed',
                    'The controlled Gemini generation attempt has already been used. Further generation remains disabled.',
                );
            }

            throw $exception;
        }

        return compact('attempt', 'model', 'prompt') + ['draft' => null];
    }

    private function eligibleModel(): AiModel
    {
        $route = AiWorkloadRoute::query()
            ->with('primaryModel.provider')
            ->where('workload_key', self::WORKLOAD_KEY)
            ->where('is_enabled', true)
            ->first();
        $model = $route?->primaryModel;
        $provider = $model?->provider;

        $eligible = $model !== null
            && $provider !== null
            && $model->is_enabled
            && $model->availability_status === AiModelAvailabilityStatus::Available
            && in_array(AiModelCapability::TextGeneration->value, $model->capabilities, true)
            && in_array(AiModelCapability::StructuredOutput->value, $model->capabilities, true)
            && $provider->is_enabled
            && $provider->health_status === AiProviderHealthStatus::Healthy
            && is_string($provider->adapter_type)
            && in_array($provider->adapter_type, AiProvider::SUPPORTED_ADAPTER_TYPES, true)
            && (! $this->firstGenerationTestEnabled() || $route->fallback_ai_model_id === null);

        if (! $eligible) {
            throw new AiContentGenerationException('missing_configuration', 'AI generation is not configured yet.');
        }

        return $model;
    }

    private function failAttempt(
        AiGenerationAttempt $attempt,
        string $category,
        ?int $inputTokens = null,
        ?int $outputTokens = null,
    ): void {
        $attempt->update([
            'status' => AiGenerationAttemptStatus::Failed,
            'error_category' => $category,
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'completed_at' => now(),
        ]);
    }

    private function firstGenerationTestEnabled(): bool
    {
        return (bool) config('ai.first_generation_test.enabled', false);
    }

    private function ensureApprovedFirstGenerationTenant(User $user, Workspace $workspace, Brand $brand): void
    {
        $approvedUserId = (int) config('ai.first_generation_test.user_id', 0);
        $approvedWorkspaceId = (int) config('ai.first_generation_test.workspace_id', 0);
        $approvedBrandId = (int) config('ai.first_generation_test.brand_id', 0);

        if ($approvedUserId < 1
            || $approvedWorkspaceId < 1
            || $approvedBrandId < 1
            || $user->getKey() !== $approvedUserId
            || $workspace->getKey() !== $approvedWorkspaceId
            || $brand->getKey() !== $approvedBrandId) {
            throw new AiContentGenerationException(
                'single_use_not_authorized',
                'This account, workspace, and brand are not approved for the controlled Gemini generation test.',
            );
        }
    }
}
