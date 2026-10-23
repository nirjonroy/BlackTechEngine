<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AffiliatePageController extends Controller
{
    public function __invoke(): View
    {
        return view('frontend.affiliate', [
            'seoFallbackTitle' => 'Affiliate Program | BlackTechEngine',
            'seoFallbackDescription' => 'Review the current status of the planned BlackTechEngine affiliate program and the terms that still require approval.',
        ]);
    }
}
