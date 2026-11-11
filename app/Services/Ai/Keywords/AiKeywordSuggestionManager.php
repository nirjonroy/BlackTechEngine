<?php

namespace App\Services\Ai\Keywords;

use App\Enums\EntitlementKey;
use App\Models\AiWorkloadRoute;
use App\Models\Brand;
use App\Models\BrandKeyword;
use App\Models\UsageEvent;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Ai\AiProviderAdapterRegistry;
use App\Services\Subscriptions\EntitlementService;
use App\Services\Subscriptions\UsageMeter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use JsonException;
use Throwable;

class AiKeywordSuggestionManager
{
    public const WORKLOAD_KEY = 'seo-geo-content-generation';

    public function __construct(
        private readonly AiKeywordPromptBuilder $promptBuilder,
        private readonly AiProviderAdapterRegistry $adapterRegistry,
        private readonly EntitlementService $entitlements,
        private readonly UsageMeter $usageMeter,
    ) {}

    /**
     * Generate keyword and cluster suggestions using Gemini AI.
     *
     * @param  array<string, mixed>  $input
     * @return array{
     *     seed_keyword: string,
     *     clusters: array<int, array{
     *         cluster_name: string,
     *         pillar_keyword: string,
     *         description: string,
     *         keywords: array<int, array{
     *             keyword: string,
     *             search_intent: string,
     *             is_duplicate: bool
     *         }>
     *     }>
     * }
     *
     * @throws ValidationException
     */
    public function suggest(User $user, Workspace $workspace, Brand $brand, array $input): array
    {
        if (! config('ai.content_generation_enabled', false)) {
            throw ValidationException::withMessages([
                'generation' => 'AI keyword suggestion is currently disabled by administrator gate.',
            ]);
        }

        $this->entitlements->assertFeature($workspace, EntitlementKey::ContentStudio);

        $route = AiWorkloadRoute::query()
            ->with('primaryModel.provider')
            ->where('workload_key', self::WORKLOAD_KEY)
            ->where('is_enabled', true)
            ->first();

        $model = $route?->primaryModel;
        $provider = $model?->provider;

        if ($model === null || $provider === null || ! $model->is_enabled || ! $provider->is_enabled) {
            throw ValidationException::withMessages([
                'generation' => 'No active and enabled AI model route is configured for keyword discovery.',
            ]);
        }

        $adapter = $this->adapterRegistry->resolve((string) $provider->adapter_type);
        $idempotencyKey = (string) Str::uuid();

        $usageReservation = $this->usageMeter->reserve(
            $workspace,
            EntitlementKey::AiGenerationAttempts,
            1,
            $idempotencyKey,
            $user,
        );

        $prompt = $this->promptBuilder->build($brand, $input);

        try {
            $result = $adapter->generateStructuredContent(
                $model->provider_model_id,
                $prompt,
                2000,
            );
        } catch (Throwable $e) {
            if ($usageReservation instanceof UsageEvent) {
                $this->usageMeter->releaseMany([$usageReservation], 'keyword_generation_failed');
            }

            throw ValidationException::withMessages([
                'generation' => 'AI keyword generation failed: '.($e->getMessage() ?: 'Provider error.'),
            ]);
        }

        if ($usageReservation instanceof UsageEvent) {
            $this->usageMeter->commit($usageReservation, Brand::class, $brand->getKey());
        }

        return $this->parseAndDecorateResponse($result->content->content, $brand, $input['seed_keyword']);
    }

    /**
     * Parse and validate model JSON output, annotating existing duplicate keywords.
     *
     * @return array{
     *     seed_keyword: string,
     *     clusters: array<int, array{
     *         cluster_name: string,
     *         pillar_keyword: string,
     *         description: string,
     *         keywords: array<int, array{
     *             keyword: string,
     *             search_intent: string,
     *             is_duplicate: bool
     *         }>
     *     }>
     * }
     *
     * @throws ValidationException
     */
    public function parseAndDecorateResponse(string $rawJson, Brand $brand, string $seedKeyword): array
    {
        // Strip markdown code fences if model returned them despite prompt
        $cleaned = trim(preg_replace('/^```(?:json)?\s*|\s*```$/m', '', trim($rawJson)));

        try {
            $decoded = json_decode($cleaned, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw ValidationException::withMessages([
                'generation' => 'AI model returned an unparseable response format.',
            ]);
        }

        if (! is_array($decoded) || ! isset($decoded['clusters']) || ! is_array($decoded['clusters'])) {
            throw ValidationException::withMessages([
                'generation' => 'AI model response missing required clusters structure.',
            ]);
        }

        // Fetch existing normalized keywords for this brand to detect duplicates
        $existingNormalized = $brand->keywords()
            ->pluck('normalized_keyword')
            ->flip()
            ->all();

        $processedClusters = [];

        foreach ($decoded['clusters'] as $clusterData) {
            if (! is_array($clusterData) || empty($clusterData['cluster_name'])) {
                continue;
            }

            $clusterName = trim((string) $clusterData['cluster_name']);
            $pillarKeyword = trim((string) ($clusterData['pillar_keyword'] ?? ''));
            $description = trim((string) ($clusterData['description'] ?? ''));
            $keywordsList = [];

            if (isset($clusterData['keywords']) && is_array($clusterData['keywords'])) {
                foreach ($clusterData['keywords'] as $kwData) {
                    if (! is_array($kwData) || empty($kwData['keyword'])) {
                        continue;
                    }

                    $kwText = trim((string) $kwData['keyword']);
                    $normalized = BrandKeyword::normalize($kwText);
                    $intentVal = strtolower((string) ($kwData['search_intent'] ?? 'informational'));

                    $validIntent = in_array($intentVal, ['informational', 'commercial', 'transactional', 'navigational'], true)
                        ? $intentVal
                        : 'informational';

                    $keywordsList[] = [
                        'keyword' => $kwText,
                        'normalized_keyword' => $normalized,
                        'search_intent' => $validIntent,
                        'is_duplicate' => isset($existingNormalized[$normalized]),
                    ];
                }
            }

            $processedClusters[] = [
                'cluster_name' => $clusterName,
                'pillar_keyword' => $pillarKeyword,
                'description' => $description,
                'keywords' => $keywordsList,
            ];
        }

        $flatSuggestions = [];
        foreach ($processedClusters as $cluster) {
            foreach ($cluster['keywords'] as $kw) {
                $flatSuggestions[] = array_merge($kw, [
                    'cluster_name' => $cluster['cluster_name'],
                    'pillar_keyword' => $cluster['pillar_keyword'],
                ]);
            }
        }

        return [
            'seed_keyword' => $seedKeyword,
            'clusters' => $processedClusters,
            'suggestions' => $flatSuggestions,
        ];
    }
}
