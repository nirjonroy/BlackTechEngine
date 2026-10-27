<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AiModelAvailabilityStatus;
use App\Enums\AiModelModality;
use App\Enums\AiProviderHealthStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminAiModelIndexRequest;
use App\Http\Requests\Admin\AdminAiProviderIndexRequest;
use App\Http\Requests\Admin\AdminAiWorkloadRouteIndexRequest;
use App\Models\AiConfigurationAudit;
use App\Models\AiModel;
use App\Models\AiProvider;
use App\Models\AiWorkloadRoute;
use App\Services\AiMetadataWriteGate;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AiConfigurationController extends Controller
{
    public function __construct(private AiMetadataWriteGate $writeGate) {}

    public function index(): View
    {
        return view('admin.ai-configuration.index', [
            'auditActivity' => $this->recentAuditActivity(),
            'metrics' => [
                'providers' => AiProvider::query()->count(),
                'enabled_providers' => AiProvider::query()->enabled()->count(),
                'models' => AiModel::query()->count(),
                'enabled_models' => AiModel::query()->enabled()->count(),
                'workload_routes' => AiWorkloadRoute::query()->count(),
                'enabled_workload_routes' => AiWorkloadRoute::query()->enabled()->count(),
            ],
        ]);
    }

    public function providers(AdminAiProviderIndexRequest $request): View
    {
        $filters = $request->filters();
        $providersQuery = AiProvider::query()
            ->select([
                'id', 'key', 'display_name', 'adapter_type', 'description', 'is_enabled',
                'health_status', 'request_timeout_seconds', 'retry_limit', 'last_verified_at', 'created_at',
            ])
            ->withCount('models');

        if ($filters['search'] !== '') {
            $search = $filters['search'];

            $providersQuery->where(function (Builder $query) use ($search): void {
                $query
                    ->where('display_name', 'like', "%{$search}%")
                    ->orWhere('key', 'like', "%{$search}%")
                    ->orWhere('adapter_type', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($filters['health'] !== 'all') {
            $providersQuery->where('health_status', $filters['health']);
        }

        if ($filters['state'] !== 'all') {
            $providersQuery->where('is_enabled', $filters['state'] === 'enabled');
        }

        match ($filters['sort']) {
            'newest' => $providersQuery->orderByDesc('created_at')->orderByDesc('id'),
            'oldest' => $providersQuery->orderBy('created_at')->orderBy('id'),
            'key' => $providersQuery->orderBy('key')->orderBy('id'),
            'health' => $providersQuery->orderBy('health_status')->orderBy('display_name')->orderBy('id'),
            default => $providersQuery->orderBy('display_name')->orderBy('id'),
        };

        return view('admin.ai-configuration.providers', [
            'filters' => $filters,
            'healthStatuses' => AiProviderHealthStatus::cases(),
            'providers' => $providersQuery->paginate($filters['per_page'])->withQueryString(),
            'metadataWritesAvailable' => $this->writeGate->isAvailable(),
        ]);
    }

    public function models(AdminAiModelIndexRequest $request): View
    {
        $filters = $request->filters();
        $modelsQuery = AiModel::query()
            ->select([
                'id', 'ai_provider_id', 'provider_model_id', 'display_name', 'modality', 'capabilities',
                'is_enabled', 'availability_status', 'last_verified_at', 'created_at',
            ])
            ->with('provider:id,key,display_name');

        if ($filters['search'] !== '') {
            $search = $filters['search'];

            $modelsQuery->where(function (Builder $query) use ($search): void {
                $query
                    ->where('display_name', 'like', "%{$search}%")
                    ->orWhere('provider_model_id', 'like', "%{$search}%")
                    ->orWhereHas('provider', function (Builder $providerQuery) use ($search): void {
                        $providerQuery
                            ->where('display_name', 'like', "%{$search}%")
                            ->orWhere('key', 'like', "%{$search}%");
                    });
            });
        }

        if ($filters['provider'] !== null) {
            $modelsQuery->where('ai_provider_id', $filters['provider']);
        }

        if ($filters['status'] !== 'all') {
            $modelsQuery->where('availability_status', $filters['status']);
        }

        if ($filters['modality'] !== 'all') {
            $modelsQuery->where('modality', $filters['modality']);
        }

        match ($filters['sort']) {
            'newest' => $modelsQuery->orderByDesc('created_at')->orderByDesc('id'),
            'oldest' => $modelsQuery->orderBy('created_at')->orderBy('id'),
            'identifier' => $modelsQuery->orderBy('provider_model_id')->orderBy('id'),
            'status' => $modelsQuery->orderBy('availability_status')->orderBy('display_name')->orderBy('id'),
            default => $modelsQuery->orderBy('display_name')->orderBy('id'),
        };

        return view('admin.ai-configuration.models', [
            'availabilityStatuses' => AiModelAvailabilityStatus::cases(),
            'filters' => $filters,
            'modalities' => AiModelModality::cases(),
            'models' => $modelsQuery->paginate($filters['per_page'])->withQueryString(),
            'metadataWritesAvailable' => $this->writeGate->isAvailable(),
            'providers' => AiProvider::query()->orderBy('display_name')->orderBy('id')->get(['id', 'display_name']),
        ]);
    }

    public function workloadRoutes(AdminAiWorkloadRouteIndexRequest $request): View
    {
        $filters = $request->filters();
        $routesQuery = AiWorkloadRoute::query()
            ->select(['id', 'workload_key', 'primary_ai_model_id', 'fallback_ai_model_id', 'is_enabled', 'created_at'])
            ->with([
                'primaryModel:id,ai_provider_id,provider_model_id,display_name,availability_status,is_enabled',
                'primaryModel.provider:id,key,display_name',
                'fallbackModel:id,ai_provider_id,provider_model_id,display_name,availability_status,is_enabled',
                'fallbackModel.provider:id,key,display_name',
            ]);

        if ($filters['search'] !== '') {
            $search = $filters['search'];

            $routesQuery->where(function (Builder $query) use ($search): void {
                $query->where('workload_key', 'like', "%{$search}%")
                    ->orWhereHas('primaryModel', function (Builder $modelQuery) use ($search): void {
                        $modelQuery
                            ->where('display_name', 'like', "%{$search}%")
                            ->orWhere('provider_model_id', 'like', "%{$search}%");
                    })
                    ->orWhereHas('fallbackModel', function (Builder $modelQuery) use ($search): void {
                        $modelQuery
                            ->where('display_name', 'like', "%{$search}%")
                            ->orWhere('provider_model_id', 'like', "%{$search}%");
                    });
            });
        }

        match ($filters['state']) {
            'enabled' => $routesQuery->where('is_enabled', true),
            'disabled' => $routesQuery->where('is_enabled', false),
            'unassigned' => $routesQuery->whereNull('primary_ai_model_id'),
            default => null,
        };

        match ($filters['sort']) {
            'newest' => $routesQuery->orderByDesc('created_at')->orderByDesc('id'),
            'oldest' => $routesQuery->orderBy('created_at')->orderBy('id'),
            'state' => $routesQuery->orderByDesc('is_enabled')->orderBy('workload_key')->orderBy('id'),
            default => $routesQuery->orderBy('workload_key')->orderBy('id'),
        };

        return view('admin.ai-configuration.workload-routes', [
            'filters' => $filters,
            'workloadRoutes' => $routesQuery->paginate($filters['per_page'])->withQueryString(),
        ]);
    }

    /**
     * @return Collection<int, array{action: string, actor: string, changed_fields: list<string>, created_at: mixed, subject: string}>
     */
    private function recentAuditActivity(): Collection
    {
        return AiConfigurationAudit::query()
            ->select(['id', 'acting_admin_id', 'subject_type', 'subject_id', 'action', 'changes', 'created_at'])
            ->with('actingAdmin:id,name')
            ->latest('id')
            ->limit(8)
            ->get()
            ->map(function (AiConfigurationAudit $audit): array {
                $changedFields = $audit->changes['changed_fields'] ?? [];
                $safeChangedFields = is_array($changedFields)
                    ? collect($changedFields)
                        ->filter(fn (mixed $field): bool => is_string($field)
                            && preg_match('/authorization|api[_-]?key|token|secret|credential|prompt|payload|headers?/i', $field) !== 1)
                        ->take(6)
                        ->map(fn (string $field): string => Str::headline($field))
                        ->values()
                        ->all()
                    : [];

                return [
                    'action' => Str::headline($audit->action),
                    'actor' => $audit->actingAdmin?->name ?? 'System or deleted admin',
                    'changed_fields' => $safeChangedFields,
                    'created_at' => $audit->created_at,
                    'subject' => $audit->subject_type !== null && $audit->subject_id !== null
                        ? class_basename($audit->subject_type).' #'.$audit->subject_id
                        : 'General configuration',
                ];
            });
    }
}
