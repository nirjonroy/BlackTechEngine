<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Blog;
use App\Models\PricingPlan;
use App\Models\User;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $metrics = [
            'registered_users' => User::query()->count(),
            'active_pricing_plans' => PricingPlan::query()->publiclyVisible()->count(),
            'published_blogs' => Blog::query()->published()->count(),
            'admin_accounts' => Admin::query()->count(),
        ];

        $contentStatus = [
            'draft_blogs' => Blog::query()
                ->where(function ($query): void {
                    $query
                        ->whereNull('published_at')
                        ->orWhere('published_at', '>', now());
                })
                ->count(),
            'inactive_pricing_plans' => PricingPlan::query()->where('is_active', false)->count(),
        ];

        $recentPublishedBlogs = Blog::query()
            ->published()
            ->select(['id', 'title', 'slug', 'category', 'published_at'])
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(4)
            ->get();

        return view('admin.dashboard', compact('contentStatus', 'metrics', 'recentPublishedBlogs'));
    }
}
