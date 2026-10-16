<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SiteSetting;
use App\Models\FeaturePageSetting;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index()
    {
        // Fetch existing data from your current tables
        $siteSetting = SiteSetting::first();
        $featureSetting = FeaturePageSetting::first();
        
        // Fetch stats from about_stats table (assuming you have this from About Page setup)
        $aboutStats = DB::table('about_stats')->take(4)->get();

        return view('frontend.home', compact('siteSetting', 'featureSetting', 'aboutStats'));
    }
}