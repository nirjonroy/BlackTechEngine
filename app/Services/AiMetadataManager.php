<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\AiConfigurationAudit;
use App\Models\AiModel;
use App\Models\AiProvider;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AiMetadataManager
{
    /**
     * @param  array{key: string, display_name: string, description: string|null, request_timeout_seconds: int, retry_limit: int}  $metadata
     */
    public function createProvider(Admin $admin, array $metadata): AiProvider
    {
        try {
            return DB::transaction(function () use ($admin, $metadata): AiProvider {
                $provider = new AiProvider;
                $provider->key = $metadata['key'];
                $provider->display_name = $metadata['display_name'];
                $provider->description = $metadata['description'];
                $provider->request_timeout_seconds = $metadata['request_timeout_seconds'];
                $provider->retry_limit = $metadata['retry_limit'];
                $provider->adapter_type = null;
                $provider->is_enabled = false;
                $provider->health_status = 'unverified';
                $provider->last_verified_at = null;
                $provider->lock_version = 0;
                $provider->save();

                $this->recordAudit($admin, $provider, 'ai_provider.created', array_keys($metadata));

                return $provider;
            });
        } catch (QueryException $exception) {
            $this->throwProviderUniqueValidationException($exception);
        }
    }

    /**
     * @param  array{key: string, display_name: string, description: string|null, request_timeout_seconds: int, retry_limit: int}  $metadata
     */
    public function updateProvider(Admin $admin, AiProvider $provider, array $metadata, int $expectedVersion): bool
    {
        try {
            return DB::transaction(function () use ($admin, $provider, $metadata, $expectedVersion): bool {
                $lockedProvider = AiProvider::query()->lockForUpdate()->findOrFail($provider->getKey());

                if ($lockedProvider->lock_version !== $expectedVersion) {
                    throw ValidationException::withMessages([
                        'lock_version' => 'This provider changed after you opened the form. Review the latest metadata and try again.',
                    ]);
                }

                $lockedProvider->key = $metadata['key'];
                $lockedProvider->display_name = $metadata['display_name'];
                $lockedProvider->description = $metadata['description'];
                $lockedProvider->request_timeout_seconds = $metadata['request_timeout_seconds'];
                $lockedProvider->retry_limit = $metadata['retry_limit'];
                $changedFields = array_keys($lockedProvider->getDirty());

                if ($changedFields === []) {
                    return false;
                }

                $lockedProvider->lock_version++;
                $lockedProvider->save();
                $this->recordAudit($admin, $lockedProvider, 'ai_provider.metadata_updated', $changedFields);

                return true;
            });
        } catch (QueryException $exception) {
            $this->throwProviderUniqueValidationException($exception);
        }
    }

    /**
     * @param  array{ai_provider_id: int, provider_model_id: string, display_name: string, modality: string, capabilities: list<string>}  $metadata
     */
    public function createModel(Admin $admin, array $metadata): AiModel
    {
        try {
            return DB::transaction(function () use ($admin, $metadata): AiModel {
                $model = new AiModel;
                $model->ai_provider_id = $metadata['ai_provider_id'];
                $model->provider_model_id = $metadata['provider_model_id'];
                $model->display_name = $metadata['display_name'];
                $model->modality = $metadata['modality'];
                $model->capabilities = $metadata['capabilities'];
                $model->is_enabled = false;
                $model->availability_status = 'unverified';
                $model->last_verified_at = null;
                $model->lock_version = 0;
                $model->save();

                $this->recordAudit($admin, $model, 'ai_model.created', array_keys($metadata));

                return $model;
            });
        } catch (QueryException $exception) {
            $this->throwModelUniqueValidationException($exception);
        }
    }

    /**
     * @param  array{ai_provider_id: int, provider_model_id: string, display_name: string, modality: string, capabilities: list<string>}  $metadata
     */
    public function updateModel(Admin $admin, AiModel $model, array $metadata, int $expectedVersion): bool
    {
        try {
            return DB::transaction(function () use ($admin, $model, $metadata, $expectedVersion): bool {
                $lockedModel = AiModel::query()->lockForUpdate()->findOrFail($model->getKey());

                if ($lockedModel->lock_version !== $expectedVersion) {
                    throw ValidationException::withMessages([
                        'lock_version' => 'This model changed after you opened the form. Review the latest metadata and try again.',
                    ]);
                }

                $lockedModel->ai_provider_id = $metadata['ai_provider_id'];
                $lockedModel->provider_model_id = $metadata['provider_model_id'];
                $lockedModel->display_name = $metadata['display_name'];
                $lockedModel->modality = $metadata['modality'];
                $lockedModel->capabilities = $metadata['capabilities'];
                $changedFields = array_keys($lockedModel->getDirty());

                if ($changedFields === []) {
                    return false;
                }

                $lockedModel->lock_version++;
                $lockedModel->save();
                $this->recordAudit($admin, $lockedModel, 'ai_model.metadata_updated', $changedFields);

                return true;
            });
        } catch (QueryException $exception) {
            $this->throwModelUniqueValidationException($exception);
        }
    }

    /** @param list<string> $changedFields */
    private function recordAudit(Admin $admin, AiProvider|AiModel $subject, string $action, array $changedFields): void
    {
        AiConfigurationAudit::query()->create([
            'acting_admin_id' => $admin->getAuthIdentifier(),
            'subject_type' => $subject::class,
            'subject_id' => $subject->getKey(),
            'action' => $action,
            'request_id' => Str::uuid()->toString(),
            'changes' => ['changed_fields' => $changedFields],
        ]);
    }

    private function throwProviderUniqueValidationException(QueryException $exception): never
    {
        if ($this->isUniqueConstraintViolation($exception)) {
            throw ValidationException::withMessages([
                'key' => 'A provider with this normalized key already exists.',
            ]);
        }

        throw $exception;
    }

    private function throwModelUniqueValidationException(QueryException $exception): never
    {
        if ($this->isUniqueConstraintViolation($exception)) {
            throw ValidationException::withMessages([
                'provider_model_id' => 'This provider already has a model with that identifier.',
            ]);
        }

        throw $exception;
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        return preg_match('/unique constraint|duplicate entry/i', $exception->getMessage()) === 1;
    }
}
