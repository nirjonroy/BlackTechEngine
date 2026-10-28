<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\AiConfigurationAudit;
use App\Models\AiWorkloadRoute;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AiWorkloadDraftManager
{
    /**
     * @param  array{workload_key: string, primary_ai_model_id: int|null, fallback_ai_model_id: int|null, settings: array|null}  $metadata
     */
    public function create(Admin $admin, array $metadata): AiWorkloadRoute
    {
        try {
            return DB::transaction(function () use ($admin, $metadata): AiWorkloadRoute {
                $workloadRoute = new AiWorkloadRoute;
                $workloadRoute->workload_key = $metadata['workload_key'];
                $workloadRoute->primary_ai_model_id = $metadata['primary_ai_model_id'];
                $workloadRoute->fallback_ai_model_id = $metadata['fallback_ai_model_id'];
                $workloadRoute->settings = $metadata['settings'];
                $workloadRoute->is_enabled = false;
                $workloadRoute->lock_version = 0;
                $workloadRoute->save();

                $this->recordAudit($admin, $workloadRoute, 'ai_workload_route.draft_created', array_keys($metadata));

                return $workloadRoute;
            });
        } catch (QueryException $exception) {
            $this->throwUniqueValidationException($exception);
        }
    }

    /**
     * @param  array{workload_key: string, primary_ai_model_id: int|null, fallback_ai_model_id: int|null, settings: array|null}  $metadata
     */
    public function update(Admin $admin, AiWorkloadRoute $workloadRoute, array $metadata, int $expectedVersion): bool
    {
        try {
            return DB::transaction(function () use ($admin, $workloadRoute, $metadata, $expectedVersion): bool {
                $lockedRoute = AiWorkloadRoute::query()->lockForUpdate()->findOrFail($workloadRoute->getKey());

                if ($lockedRoute->lock_version !== $expectedVersion) {
                    throw ValidationException::withMessages([
                        'lock_version' => 'This workload draft changed after you opened the form. Review the latest configuration and try again.',
                    ]);
                }

                if ($lockedRoute->is_enabled) {
                    throw ValidationException::withMessages([
                        'draft' => 'Enabled workload routes cannot be changed through draft management.',
                    ]);
                }

                $lockedRoute->workload_key = $metadata['workload_key'];
                $lockedRoute->primary_ai_model_id = $metadata['primary_ai_model_id'];
                $lockedRoute->fallback_ai_model_id = $metadata['fallback_ai_model_id'];
                $lockedRoute->settings = $metadata['settings'];
                $changedFields = array_keys($lockedRoute->getDirty());

                if ($changedFields === []) {
                    return false;
                }

                $lockedRoute->lock_version++;
                $lockedRoute->save();
                $this->recordAudit($admin, $lockedRoute, 'ai_workload_route.draft_updated', $changedFields);

                return true;
            });
        } catch (QueryException $exception) {
            $this->throwUniqueValidationException($exception);
        }
    }

    /** @param list<string> $changedFields */
    private function recordAudit(Admin $admin, AiWorkloadRoute $workloadRoute, string $action, array $changedFields): void
    {
        AiConfigurationAudit::query()->create([
            'acting_admin_id' => $admin->getAuthIdentifier(),
            'subject_type' => $workloadRoute::class,
            'subject_id' => $workloadRoute->getKey(),
            'action' => $action,
            'request_id' => Str::uuid()->toString(),
            'changes' => ['changed_fields' => $changedFields],
        ]);
    }

    private function throwUniqueValidationException(QueryException $exception): never
    {
        if (preg_match('/unique constraint|duplicate entry/i', $exception->getMessage()) === 1) {
            throw ValidationException::withMessages([
                'workload_key' => 'A workload draft with this normalized key already exists.',
            ]);
        }

        throw $exception;
    }
}
