<?php

namespace App\Http\Controllers;

use App\Enums\AiContentLength;
use App\Enums\AiContentType;
use App\Enums\ArticleScheduleFrequency;
use App\Enums\ArticleScheduleTargetStatus;
use App\Models\AiContentDraft;
use App\Models\ArticleSchedule;
use App\Models\ArticleScheduleRun;
use App\Models\Brand;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ArticleCalendarController extends Controller
{
    public function __invoke(Request $request, ?Workspace $workspace = null, ?Brand $brand = null): View
    {
        $user = $request->user();
        $workspaces = $user->accessibleWorkspaces()->with('brands')->orderBy('name')->get();

        if ($workspace === null && $request->filled('workspace')) {
            $workspace = $workspaces->firstWhere('id', $request->integer('workspace'));
            abort_if($workspace === null, 404);
        }

        $workspace = $workspace ?? ($workspaces->firstWhere('id', $user->active_workspace_id) ?? $workspaces->first());

        if ($brand === null && $request->filled('brand') && $workspace !== null) {
            $brand = $workspace->brands->firstWhere('id', $request->integer('brand'));
            abort_if($brand === null, 404);
        }

        $timezone = (string) data_get($workspace?->settings, 'timezone', 'UTC');
        if ($request->filled('timezone') && in_array($request->string('timezone')->toString(), timezone_identifiers_list(), true)) {
            $timezone = $request->string('timezone')->toString();
        }

        $dateString = $request->input('date', now($timezone)->format('Y-m-d'));
        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', $dateString, $timezone);
        } catch (\Throwable) {
            $date = CarbonImmutable::now($timezone);
        }

        $view = $request->input('view', 'month');
        if (! in_array($view, ['month', 'week', 'list'], true)) {
            $view = 'month';
        }

        [$start, $end] = match ($view) {
            'week' => [$date->startOfWeek(), $date->endOfWeek()],
            'list' => [$date->startOfDay(), $date->addDays(30)->endOfDay()],
            default => [$date->startOfMonth()->startOfWeek(), $date->endOfMonth()->endOfWeek()],
        };

        $schedulesQuery = ArticleSchedule::query()
            ->when($workspace, fn ($q) => $q->whereBelongsTo($workspace), fn ($q) => $q->whereRaw('1 = 0'))
            ->when($brand, fn ($q) => $q->whereBelongsTo($brand))
            ->with(['brand', 'brandKeyword', 'topicCluster'])
            ->orderBy('is_active', 'desc')
            ->orderBy('next_run_at', 'asc');

        $schedules = $schedulesQuery->get();

        $runs = ArticleScheduleRun::query()
            ->when($workspace, fn ($q) => $q->whereBelongsTo($workspace), fn ($q) => $q->whereRaw('1 = 0'))
            ->when($brand, fn ($q) => $q->whereBelongsTo($brand))
            ->whereBetween('created_at', [$start->utc(), $end->utc()])
            ->with(['draft', 'schedule', 'publishedPost'])
            ->latest('id')
            ->get();

        $drafts = AiContentDraft::query()
            ->when($workspace, fn ($q) => $q->whereBelongsTo($workspace), fn ($q) => $q->whereRaw('1 = 0'))
            ->when($brand, fn ($q) => $q->whereBelongsTo($brand))
            ->whereBetween('created_at', [$start->utc(), $end->utc()])
            ->latest('id')
            ->get();

        $keywords = $brand ? $brand->keywords()->orderBy('keyword')->get() : collect();
        $clusters = $brand ? $brand->topicClusters()->orderBy('name')->get() : collect();
        $hasWordPress = $brand ? (bool) $brand->wordPressConnection?->isConnected() : false;

        return view('content-studio.calendar', [
            'workspaces' => $workspaces,
            'workspace' => $workspace,
            'brand' => $brand,
            'timezone' => $timezone,
            'date' => $date,
            'view' => $view,
            'start' => $start,
            'end' => $end,
            'schedules' => $schedules,
            'runs' => $runs,
            'drafts' => $drafts,
            'keywords' => $keywords,
            'clusters' => $clusters,
            'hasWordPress' => $hasWordPress,
            'frequencies' => ArticleScheduleFrequency::cases(),
            'targetStatuses' => ArticleScheduleTargetStatus::cases(),
            'contentTypes' => AiContentType::cases(),
            'contentLengths' => AiContentLength::cases(),
        ]);
    }
}

