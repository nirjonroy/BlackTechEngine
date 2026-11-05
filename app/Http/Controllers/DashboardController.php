<?php

namespace App\Http\Controllers;

use App\Services\Analytics\CustomerDashboardAnalytics;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly CustomerDashboardAnalytics $analytics,
    ) {}

    public function __invoke(Request $request): View
    {
        return view('dashboard', $this->analytics->forUser($request->user()));
    }
}
