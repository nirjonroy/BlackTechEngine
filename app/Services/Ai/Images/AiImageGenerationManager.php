<?php

namespace App\Services\Ai\Images;

use App\Enums\AiModelCapability;
use App\Enums\AiModelModality;
use App\Enums\EntitlementKey;
use App\Models\AiContentDraft;
use App\Models\AiContentDraftImage;
use App\Models\AiModel;
use App\Models\AiWorkloadRoute;
use App\Models\Brand;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Ai\AiProviderCredentialResolver;
use App\Services\Ai\Exceptions\AiContentGenerationException;
use App\Services\Subscriptions\EntitlementService;
use App\Services\Subscriptions\UsageMeter;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class AiImageGenerationManager
{
    public const WORKLOAD_KEY = 'seo-featured-image-generation';

    public function __construct(
        private readonly AiImagePromptBuilder $promptBuilder,
        private readonly AiImageStorageService $storage,
        private readonly AiProviderCredentialResolver $credentialResolver,
        private readonly EntitlementService $entitlements,
        private readonly UsageMeter $usageMeter,
    ) {}

    /**
     * Generate and attach a featured image for a draft.
     *
     * @throws AiContentGenerationException
     */
    public function generate(
        User $user,
        Workspace $workspace,
        Brand $brand,
        AiContentDraft $draft,
        ?string $userInstructions = null,
    ): AiContentDraftImage {
        if (! config('ai.image_generation_enabled')) {
            throw new AiContentGenerationException('feature_disabled', 'AI image generation is currently disabled.');
        }

        $this->entitlements->assertFeature($workspace, EntitlementKey::ContentStudio);

        $model = $this->eligibleImageModel();
        $apiKey = $this->credentialResolver->geminiApiKey();

        if (empty($apiKey)) {
            throw new AiContentGenerationException('missing_credentials', 'AI image provider is not configured with valid credentials.');
        }

        $idempotencyKey = (string) Str::uuid();
        $reservation = $this->usageMeter->reserve($workspace, EntitlementKey::AiGenerationAttempts, 1, $idempotencyKey, $user);

        $prompt = $this->promptBuilder->buildPrompt($draft, $userInstructions);
        $altText = $this->promptBuilder->generateAltText($draft);
        $filename = $this->promptBuilder->generateFilename($draft);

        try {
            $binaryData = $this->callImageProvider($model, $apiKey, $prompt);
            $stored = $this->storage->store($binaryData, $brand->getKey(), $filename);
        } catch (Throwable $e) {
            $this->usageMeter->release($reservation, 'image_generation_failed');

            if ($e instanceof AiContentGenerationException) {
                throw $e;
            }

            throw new AiContentGenerationException('provider_failure', "AI image generation failed: {$e->getMessage()}");
        }

        // Deactivate previous active images for this draft
        $draft->featuredImages()->update(['is_active' => false]);

        /** @var AiContentDraftImage $image */
        $image = AiContentDraftImage::query()->create([
            'workspace_id' => $workspace->getKey(),
            'brand_id' => $brand->getKey(),
            'ai_content_draft_id' => $draft->getKey(),
            'ai_model_id' => $model->getKey(),
            'created_by_user_id' => $user->getKey(),
            'prompt' => $prompt,
            'user_instructions' => $userInstructions,
            'image_path' => $stored['path'],
            'file_name' => $stored['file_name'],
            'mime_type' => $stored['mime_type'],
            'file_size_bytes' => $stored['file_size_bytes'],
            'width' => $stored['width'],
            'height' => $stored['height'],
            'alt_text' => $altText,
            'status' => 'completed',
            'is_active' => true,
        ]);

        $this->usageMeter->commit($reservation, AiContentDraftImage::class, $image->getKey());

        return $image;
    }

    /**
     * Resolve and verify that the configured route model supports image generation.
     *
     * @throws AiContentGenerationException
     */
    private function eligibleImageModel(): AiModel
    {
        $workloadKey = (string) config('ai.image_generation_workload', self::WORKLOAD_KEY);

        $route = AiWorkloadRoute::query()
            ->with(['primaryModel.provider'])
            ->where('workload_key', $workloadKey)
            ->where('is_enabled', true)
            ->first();

        if ($route === null || $route->primaryModel === null) {
            throw new AiContentGenerationException(
                'missing_configuration',
                "AI image generation route '{$workloadKey}' is not configured or disabled.",
            );
        }

        $model = $route->primaryModel;

        if (! $model->is_enabled || ! $model->provider?->is_enabled) {
            throw new AiContentGenerationException(
                'model_unavailable',
                "AI image model '{$model->provider_model_id}' or its provider is currently disabled.",
            );
        }

        // Strict verification: model must have Image capability or Image modality
        $capabilities = is_array($model->capabilities) ? $model->capabilities : [];
        $isImageCapable = $model->modality === AiModelModality::Image
            || in_array(AiModelCapability::ImageGeneration->value, $capabilities, true)
            || in_array('image_generation', $capabilities, true);

        if (! $isImageCapable) {
            throw new AiContentGenerationException(
                'unsupported_model',
                "Configured AI model '{$model->provider_model_id}' does not support image generation. Please configure an approved image model (e.g. Imagen 3).",
            );
        }

        return $model;
    }

    /**
     * Call the Google AI Imagen endpoint.
     *
     * @throws AiContentGenerationException
     */
    private function callImageProvider(AiModel $model, string $apiKey, string $prompt): string
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model->provider_model_id}:predict?key={$apiKey}";

        $response = Http::timeout(45)
            ->connectTimeout(5)
            ->acceptJson()
            ->post($url, [
                'instances' => [
                    ['prompt' => $prompt],
                ],
                'parameters' => [
                    'sampleCount' => 1,
                    'aspectRatio' => '16:9',
                ],
            ]);

        if (! $response->successful()) {
            $errorMsg = $response->json('error.message') ?: "HTTP {$response->status()}";
            throw new AiContentGenerationException('provider_error', "Image generation provider returned an error: {$errorMsg}");
        }

        $data = $response->json();
        $base64 = $data['predictions'][0]['bytesBase64Encoded'] ?? null;

        if (empty($base64) || ! is_string($base64)) {
            // Also check alternate Gemini format: generatedImages.0.image.imageBytes
            $base64 = $data['generatedImages'][0]['image']['imageBytes'] ?? null;
        }

        if (empty($base64) || ! is_string($base64)) {
            throw new AiContentGenerationException('malformed_response', 'AI image provider returned an invalid or empty image payload.');
        }

        $binary = base64_decode($base64, true);
        if ($binary === false || strlen($binary) === 0) {
            throw new AiContentGenerationException('malformed_image_data', 'Failed to decode base64 image data from provider.');
        }

        return $binary;
    }
}
