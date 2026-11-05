<?php

namespace App\Http\Controllers;

use App\Services\Analytics\AnalyticsCsvExporter;
use App\Services\Analytics\CustomerDashboardAnalytics;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsExportController extends Controller
{
    public function __construct(
        private readonly CustomerDashboardAnalytics $analytics,
        private readonly AnalyticsCsvExporter $csv,
    ) {}

    public function __invoke(Request $request): StreamedResponse
    {
        $overview = $this->analytics->forUser($request->user());

        return $this->csv->response(
            'customer-analytics-'.now()->format('Y-m-d').'.csv',
            $this->rows($overview),
        );
    }

    /**
     * @param  array<string, mixed>  $overview
     * @return iterable<int, array<int, bool|int|string|null>>
     */
    private function rows(array $overview): iterable
    {
        yield ['Section', 'Metric', 'Value', 'Context'];

        $workspace = $overview['activeWorkspace'];
        if ($workspace === null) {
            yield ['Subscription', 'Status', 'No active workspace', ''];

            return;
        }

        $subscription = $overview['subscription'];
        if ($subscription === null) {
            yield ['Subscription', 'Plan', 'No active subscription', ''];
        } else {
            yield ['Subscription', 'Plan', $subscription->plan?->name ?? 'Unavailable plan', ''];
            yield ['Subscription', 'Status', $subscription->status->label(), ''];
            yield ['Subscription', 'Billing interval', str($subscription->billing_interval->value)->title()->toString(), ''];
            yield ['Subscription', 'Period start', $subscription->current_period_starts_at?->toDateString(), ''];
            yield ['Subscription', 'Period end', $subscription->current_period_ends_at?->toDateString(), ''];
        }

        foreach ($overview['quotaUsage'] as $quota) {
            $limit = $quota['limit'] === null ? 'Unlimited' : (string) $quota['limit'];
            $remaining = $quota['remaining'] === null ? 'Unlimited' : (string) $quota['remaining'];
            yield ['Quota usage', $quota['label'], $quota['used'], 'Limit: '.$limit.'; Remaining: '.$remaining.'; Included: '.($quota['enabled'] ? 'Yes' : 'No')];
        }

        foreach (['Content drafts' => $overview['contentStats']['drafts'], 'Social posts' => $overview['contentStats']['social']] as $section => $statuses) {
            foreach ($statuses as $status) {
                yield [$section, $status['label'], $status['count'], ''];
            }
        }

        foreach ($overview['monthlyTrends'] as $month) {
            yield ['Six-month trend', 'Content drafts', $month['drafts'], $month['label']];
            yield ['Six-month trend', 'Social posts', $month['social_posts'], $month['label']];
        }
    }
}
