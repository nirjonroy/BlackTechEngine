<?php

namespace App\Services;

use App\Enums\AiModelAvailabilityStatus;
use App\Enums\AiModelCapability;
use App\Enums\AiModelModality;
use App\Enums\AiProviderHealthStatus;
use App\Models\Admin;
use App\Models\AiConfigurationAudit;
use App\Models\AiModel;
use App\Models\AiProvider;
use App\Models\AiWorkloadRoute;
use App\Services\Ai\AiContentGenerationManager;
use App\Services\Ai\AiProviderCredentialResolver;
use App\Services\Ai\Gemini\GeminiProviderAdapter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AiConfigurationActivationManager
{
    private const PROVIDER_VERIFICATION_MAX_AGE_HOURS = 24;

    public function __construct(private readonly AiProviderCredentialResolver $credentialResolver) {}

    public function setProviderState(Admin $admin, AiProvider $provider, bool $enable, int $expectedVersion): bool
    {
        return DB::transaction(function () use ($admin, $provider, $enable, $expectedVersion): bool {
            $lockedProvider = AiProvider::query()->lockForUpdate()->findOrFail($provider->getKey());
            $this->ensureCurrentVersion($lockedProvider->lock_version, $expectedVersion, 'provider');

            if ($enable) {
                $this->ensureActivationCanProceed();
                $this->ensureProviderEligible($lockedProvider);

                if ($lockedProvider->is_enabled && $lockedProvider->health_status === AiProviderHealthStatus::Healthy) {
                    return false;
                }

                $lockedProvider->is_enabled = true;
                $lockedProvider->health_status = AiProviderHealthStatus::Healthy;
                $this->saveState($admin, $lockedProvider, 'ai_provider.activated', ['is_enabled', 'health_status']);

                return true;
            }

            return $this->deactivateProvider($admin, $lockedProvider);
        });
    }

    public function setModelState(Admin $admin, AiModel $model, bool $enable, int $expectedVersion): bool
    {
        if (! $enable) {
            return DB::transaction(function () use ($admin, $model, $expectedVersion): bool {
                $lockedModel = AiModel::query()->lockForUpdate()->findOrFail($model->getKey());
                $this->ensureCurrentVersion($lockedModel->lock_version, $expectedVersion, 'model');

                return $this->deactivateModel($admin, $lockedModel);
            });
        }

        $providerId = $model->ai_provider_id;

        return DB::transaction(function () use ($admin, $model, $providerId, $expectedVersion): bool {
            $provider = AiProvider::query()->lockForUpdate()->findOrFail($providerId);
            $lockedModel = AiModel::query()->lockForUpdate()->findOrFail($model->getKey());
            $this->ensureCurrentVersion($lockedModel->lock_version, $expectedVersion, 'model');

            if ($lockedModel->ai_provider_id !== $provider->getKey()) {
                throw ValidationException::withMessages([
                    'lock_version' => 'This model changed after you opened the page. Review the latest state and try again.',
                ]);
            }

            $this->ensureActivationCanProceed();
            $this->ensureProviderEligible($provider, requireEnabled: true);
            $this->ensureModelEligible($lockedModel);

            if ($lockedModel->is_enabled && $lockedModel->availability_status === AiModelAvailabilityStatus::Available) {
                return false;
            }

            $lockedModel->is_enabled = true;
            $lockedModel->availability_status = AiModelAvailabilityStatus::Available;
            $lockedModel->last_verified_at = $provider->last_verified_at;
            $this->saveState(
                $admin,
                $lockedModel,
                'ai_model.activated',
                ['is_enabled', 'availability_status', 'last_verified_at'],
            );

            return true;
        });
    }

    public function setWorkloadState(Admin $admin, AiWorkloadRoute $workloadRoute, bool $enable, int $expectedVersion): bool
    {
        if (! $enable) {
            return DB::transaction(function () use ($admin, $workloadRoute, $expectedVersion): bool {
                $lockedRoute = AiWorkloadRoute::query()->lockForUpdate()->findOrFail($workloadRoute->getKey());
                $this->ensureCurrentVersion($lockedRoute->lock_version, $expectedVersion, 'workload route');

                return $this->deactivateRoute($admin, $lockedRoute);
            });
        }

        $model = $workloadRoute->primaryModel()->first();
        $providerId = $model instanceof AiModel ? (int) $model->ai_provider_id : 0;

        if (! $model instanceof AiModel || $providerId < 1) {
            throw ValidationException::withMessages(['state' => 'Select an approved primary model before activation.']);
        }

        return DB::transaction(function () use ($admin, $workloadRoute, $model, $providerId, $expectedVersion): bool {
            $provider = AiProvider::query()->lockForUpdate()->findOrFail($providerId);
            $lockedModel = AiModel::query()->lockForUpdate()->findOrFail($model->getKey());
            $lockedRoute = AiWorkloadRoute::query()->lockForUpdate()->findOrFail($workloadRoute->getKey());
            $this->ensureCurrentVersion($lockedRoute->lock_version, $expectedVersion, 'workload route');

            if ($lockedRoute->primary_ai_model_id !== $lockedModel->getKey()
                || $lockedModel->ai_provider_id !== $provider->getKey()) {
                throw ValidationException::withMessages([
                    'lock_version' => 'This workload route changed after you opened the page. Review the latest state and try again.',
                ]);
            }

            $this->ensureActivationCanProceed();
            $this->ensureProviderEligible($provider, requireEnabled: true);
            $this->ensureModelEligible($lockedModel, requireEnabled: true);

            if ($lockedRoute->workload_key !== AiContentGenerationManager::WORKLOAD_KEY
                || $lockedRoute->fallback_ai_model_id !== null
                || ! empty($lockedRoute->settings)) {
                throw ValidationException::withMessages([
                    'state' => 'The approved SEO/GEO workload must use the exact workload key, one primary model, no fallback, and no runtime settings.',
                ]);
            }

            if ($lockedRoute->is_enabled) {
                return false;
            }

            $lockedRoute->is_enabled = true;
            $this->saveState($admin, $lockedRoute, 'ai_workload_route.activated', ['is_enabled']);

            return true;
        });
    }

    private function ensureActivationCanProceed(): void
    {
        if (! config('ai.provider_verification_enabled')) {
            throw ValidationException::withMessages([
                'state' => 'Enable the controlled provider-verification gate before approving operational AI configuration.',
            ]);
        }

        if (config('ai.content_generation_enabled')) {
            throw ValidationException::withMessages([
                'state' => 'Disable customer AI generation before changing operational AI configuration.',
            ]);
        }
    }

    private function ensureProviderEligible(AiProvider $provider, bool $requireEnabled = false): void
    {
        $verifiedRecently = $provider->last_verified_at?->gte(now()->subHours(self::PROVIDER_VERIFICATION_MAX_AGE_HOURS)) ?? false;

        if ($provider->key !== GeminiProviderAdapter::IDENTIFIER
            || $provider->adapter_type !== GeminiProviderAdapter::IDENTIFIER
            || ! $verifiedRecently
            || ! $this->credentialResolver->hasGeminiCredentials()
            || ($requireEnabled && (! $provider->is_enabled || $provider->health_status !== AiProviderHealthStatus::Healthy))) {
            throw ValidationException::withMessages([
                'state' => 'The Gemini provider must have server credentials, a trusted adapter binding, and a successful verification within the last 24 hours.',
            ]);
        }
    }

    private function ensureModelEligible(AiModel $model, bool $requireEnabled = false): void
    {
        $requiredCapabilities = [
            AiModelCapability::TextGeneration->value,
            AiModelCapability::StructuredOutput->value,
        ];

        if ($model->modality !== AiModelModality::Text
            || array_diff($requiredCapabilities, $model->capabilities) !== []
            || $model->input_token_limit === null
            || $model->output_token_limit === null
            || ($requireEnabled && (! $model->is_enabled || $model->availability_status !== AiModelAvailabilityStatus::Available))) {
            throw ValidationException::withMessages([
                'state' => 'The approved model must be text-capable, support text generation and structured output, and include discovered token limits.',
            ]);
        }
    }

    private function deactivateProvider(Admin $admin, AiProvider $provider): bool
    {
        $models = AiModel::query()->whereBelongsTo($provider, 'provider')->lockForUpdate()->get();
        $modelIds = $models->modelKeys();
        /** @var Collection<int, AiWorkloadRoute> $routes */
        $routes = $modelIds === []
            ? collect()
            : AiWorkloadRoute::query()
                ->whereIn('primary_ai_model_id', $modelIds)
                ->orWhereIn('fallback_ai_model_id', $modelIds)
                ->lockForUpdate()
                ->get();
        $changed = false;

        foreach ($routes as $route) {
            $changed = $this->deactivateRoute($admin, $route) || $changed;
        }

        foreach ($models as $model) {
            $changed = $this->deactivateModelRecord($admin, $model) || $changed;
        }

        if ($provider->is_enabled) {
            $provider->is_enabled = false;
            $this->saveState($admin, $provider, 'ai_provider.deactivated', ['is_enabled']);
            $changed = true;
        }

        return $changed;
    }

    private function deactivateModel(Admin $admin, AiModel $model): bool
    {
        $routes = AiWorkloadRoute::query()
            ->where('primary_ai_model_id', $model->getKey())
            ->orWhere('fallback_ai_model_id', $model->getKey())
            ->lockForUpdate()
            ->get();
        $changed = false;

        foreach ($routes as $route) {
            $changed = $this->deactivateRoute($admin, $route) || $changed;
        }

        return $this->deactivateModelRecord($admin, $model) || $changed;
    }

    private function deactivateModelRecord(Admin $admin, AiModel $model): bool
    {
        if (! $model->is_enabled) {
            return false;
        }

        $model->is_enabled = false;
        $this->saveState($admin, $model, 'ai_model.deactivated', ['is_enabled']);

        return true;
    }

    private function deactivateRoute(Admin $admin, AiWorkloadRoute $route): bool
    {
        if (! $route->is_enabled) {
            return false;
        }

        $route->is_enabled = false;
        $this->saveState($admin, $route, 'ai_workload_route.deactivated', ['is_enabled']);

        return true;
    }

    /** @param list<string> $changedFields */
    private function saveState(
        Admin $admin,
        AiProvider|AiModel|AiWorkloadRoute $subject,
        string $action,
        array $changedFields,
    ): void {
        $subject->lock_version++;
        $subject->save();

        AiConfigurationAudit::query()->create([
            'acting_admin_id' => $admin->getAuthIdentifier(),
            'subject_type' => $subject::class,
            'subject_id' => $subject->getKey(),
            'action' => $action,
            'request_id' => Str::uuid()->toString(),
            'changes' => ['changed_fields' => $changedFields],
        ]);
    }

    private function ensureCurrentVersion(int $actualVersion, int $expectedVersion, string $subject): void
    {
        if ($actualVersion !== $expectedVersion) {
            throw ValidationException::withMessages([
                'lock_version' => "This {$subject} changed after you opened the page. Review the latest state and try again.",
            ]);
        }
    }
}
