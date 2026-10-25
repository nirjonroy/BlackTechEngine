<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $sitemapUrl = rtrim((string) config('app.url'), '/')
            .'/'.ltrim(route('sitemap', [], false), '/');

        return response(implode(PHP_EOL, [
            'User-agent: *',
            'Disallow:',
            '',
            'Sitemap: '.$sitemapUrl,
            '',
        ]))->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
