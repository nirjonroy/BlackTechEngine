<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\AboutPage;
use App\Models\FeaturePageSetting;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $featureSetting = FeaturePageSetting::first();
        $aboutStats = AboutPage::query()->with('stats')->first()?->stats->take(4) ?? collect();

        return view('frontend.home', [
            'featureSetting' => $featureSetting,
            'aboutStats' => $aboutStats,
            'seoFallbackDescription' => 'Autonomous SEO and generative engine optimization for modern growth teams.',
        ]);
    }
}
