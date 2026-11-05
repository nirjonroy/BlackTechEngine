<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\PricingPlan;
use App\Services\Analytics\AdminDashboardAnalytics;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly AdminDashboardAnalytics $analytics,
    ) {}

    public function __invoke(): View
    {
        ['metrics' => $metrics, 'subscriptionDistribution' => $subscriptionDistribution, 'contentActivity' => $contentActivity, 'monthlyTrends' => $monthlyTrends] = $this->analytics->overview();

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

        return view('admin.dashboard', compact(
            'contentActivity',
            'contentStatus',
            'metrics',
            'monthlyTrends',
            'recentPublishedBlogs',
            'subscriptionDistribution',
        ));
    }
}
