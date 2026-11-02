<?php

namespace App\Http\Controllers;

use App\Enums\SocialPlatform;
use App\Enums\SocialPostStatus;
use App\Http\Requests\SocialCalendarRequest;
use App\Models\SocialPost;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;

class SocialCalendarController extends Controller
{
    public function __invoke(SocialCalendarRequest $request): View
    {
        $workspaces = $request->user()->accessibleWorkspaces()->with('brands')->orderBy('name')->get();
        $workspace = $request->filled('workspace') ? $workspaces->firstWhere('id', $request->integer('workspace')) : ($workspaces->firstWhere('id', $request->user()->active_workspace_id) ?? $workspaces->first());
        abort_if($request->filled('workspace') && $workspace === null, 404);
        $brand = $request->filled('brand') ? $workspace?->brands->firstWhere('id', $request->integer('brand')) : null;
        abort_if($request->filled('brand') && $brand === null, 404);
        $timezone = (string) data_get($workspace?->settings, 'timezone', 'UTC');
        $date = CarbonImmutable::createFromFormat('!Y-m-d', $request->input('date', now($timezone)->format('Y-m-d')), $timezone);
        $view = $request->input('view', 'month');
        [$start, $end] = match ($view) {
            'week' => [$date->startOfWeek(), $date->endOfWeek()],
            'list' => [$date->startOfDay(), $date->addDays(30)->endOfDay()],
            default => [$date->startOfMonth()->startOfWeek(), $date->endOfMonth()->endOfWeek()],
        };
        $posts = SocialPost::query()->with(['brand', 'variants'])->when($workspace, fn ($query) => $query->whereBelongsTo($workspace), fn ($query) => $query->whereRaw('1 = 0'))->when($brand, fn ($query) => $query->whereBelongsTo($brand))
            ->when($request->filled('platform'), fn ($query) => $query->whereHas('variants', fn ($variants) => $variants->where('platform', $request->string('platform')->toString())))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()), fn ($query) => $query->whereIn('status', [SocialPostStatus::Planned->value, SocialPostStatus::ManuallyPublished->value]))
            ->whereBetween('planned_at', [$start->utc(), $end->utc()])->orderBy('planned_at')->get();

        return view('social-content.calendar', ['workspaces' => $workspaces, 'workspace' => $workspace, 'brand' => $brand, 'timezone' => $timezone, 'date' => $date, 'view' => $view, 'start' => $start, 'end' => $end, 'posts' => $posts, 'platforms' => SocialPlatform::cases(), 'statuses' => SocialPostStatus::cases()]);
    }
}
