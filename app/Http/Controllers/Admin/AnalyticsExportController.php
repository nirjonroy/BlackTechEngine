<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Analytics\AdminDashboardAnalytics;
use App\Services\Analytics\AnalyticsCsvExporter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsExportController extends Controller
{
    public function __construct(
        private readonly AdminDashboardAnalytics $analytics,
        private readonly AnalyticsCsvExporter $csv,
    ) {}

    public function __invoke(): StreamedResponse
    {
        return $this->csv->response(
            'admin-analytics-'.now()->format('Y-m-d').'.csv',
            $this->rows($this->analytics->overview()),
        );
    }

    /**
     * @param  array<string, mixed>  $overview
     * @return iterable<int, array<int, int|string>>
     */
    private function rows(array $overview): iterable
    {
        yield ['Section', 'Metric', 'Value', 'Context'];

        foreach ($overview['metrics'] as $metric => $value) {
            yield ['Platform metrics', str($metric)->replace('_', ' ')->title()->toString(), $value, ''];
        }

        foreach ($overview['subscriptionDistribution'] as $distribution) {
            yield ['Subscription distribution', $distribution['plan'], $distribution['count'], $distribution['status']];
        }

        foreach ($overview['contentActivity'] as $metric => $value) {
            yield ['Content and AI activity', str($metric)->replace('_', ' ')->title()->toString(), $value, ''];
        }

        foreach ($overview['monthlyTrends'] as $month) {
            yield ['Six-month trend', 'Content drafts', $month['drafts'], $month['label']];
            yield ['Six-month trend', 'Social posts', $month['social_posts'], $month['label']];
            yield ['Six-month trend', 'AI attempts', $month['ai_attempts'], $month['label']];
        }
    }
}
