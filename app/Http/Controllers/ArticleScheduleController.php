<?php

namespace App\Http\Controllers;

use App\Enums\EntitlementKey;
use App\Http\Requests\StoreArticleScheduleRequest;
use App\Models\ArticleSchedule;
use App\Models\Brand;
use App\Models\Workspace;
use App\Services\Autopilot\AutopilotProcessor;
use App\Services\Subscriptions\EntitlementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class ArticleScheduleController extends Controller
{
    public function __construct(
        private readonly EntitlementService $entitlements,
    ) {}

    public function store(StoreArticleScheduleRequest $request, Workspace $workspace, Brand $brand): RedirectResponse
    {
        $this->entitlements->assertFeature($workspace, EntitlementKey::ContentStudio);

        $data = $request->validated();

        $schedule = new ArticleSchedule;
        $schedule->workspace_id = $workspace->getKey();
        $schedule->brand_id = $brand->getKey();
        $schedule->created_by_user_id = $request->user()->getKey();
        $schedule->fill($data);
        $schedule->next_run_at = $schedule->calculateNextRun();
        $schedule->save();

        return Redirect::back()->with('status', "Autopilot schedule '{$schedule->title}' created successfully.");
    }

    public function toggleActive(Request $request, Workspace $workspace, Brand $brand, ArticleSchedule $articleSchedule): RedirectResponse
    {
        $this->ensureTenantOwnership($workspace, $brand, $articleSchedule, $request);

        if ($articleSchedule->is_active) {
            $articleSchedule->pause();
            $message = "Autopilot schedule '{$articleSchedule->title}' paused.";
        } else {
            $articleSchedule->resume();
            $message = "Autopilot schedule '{$articleSchedule->title}' resumed.";
        }

        return Redirect::back()->with('status', $message);
    }

    public function destroy(Request $request, Workspace $workspace, Brand $brand, ArticleSchedule $articleSchedule): RedirectResponse
    {
        $this->ensureTenantOwnership($workspace, $brand, $articleSchedule, $request);

        $title = $articleSchedule->title;
        $articleSchedule->delete();

        return Redirect::back()->with('status', "Schedule '{$title}' deleted.");
    }

    public function runNow(
        Request $request,
        Workspace $workspace,
        Brand $brand,
        ArticleSchedule $articleSchedule,
        AutopilotProcessor $processor,
    ): RedirectResponse {
        $this->ensureTenantOwnership($workspace, $brand, $articleSchedule, $request);

        $result = $processor->process($articleSchedule);

        if ($result['success']) {
            return Redirect::back()->with('status', $result['message']);
        }

        return Redirect::back()->withErrors(['autopilot' => $result['message']]);
    }

    private function ensureTenantOwnership(
        Workspace $workspace,
        Brand $brand,
        ArticleSchedule $articleSchedule,
        Request $request,
    ): void {
        abort_unless(
            $brand->workspace_id === $workspace->getKey()
            && $articleSchedule->brand_id === $brand->getKey()
            && $articleSchedule->workspace_id === $workspace->getKey()
            && ($request->user()?->can('view', $brand) ?? false),
            404,
        );
    }
}
