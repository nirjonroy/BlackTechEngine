<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = collect([
            'home',
            'about',
            'features',
            'pricing',
            'blog.index',
            'affiliate',
        ])->map(fn (string $routeName): array => [
            'loc' => $this->absoluteRouteUrl($routeName),
            'lastmod' => null,
        ]);

        $blogUrls = Blog::query()
            ->published()
            ->whereNotNull('slug')
            ->where('slug', '<>', '')
            ->oldest('id')
            ->get(['id', 'slug', 'updated_at'])
            ->map(fn (Blog $blog): array => [
                'loc' => $this->absoluteRouteUrl('blog.show', ['slug' => $blog->slug]),
                'lastmod' => $blog->updated_at?->toAtomString(),
            ]);

        return response()
            ->view('frontend.sitemap', ['urls' => $urls->concat($blogUrls)])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private function absoluteRouteUrl(string $routeName, array $parameters = []): string
    {
        return rtrim((string) config('app.url'), '/')
            .'/'.ltrim(route($routeName, $parameters, false), '/');
    }
}
