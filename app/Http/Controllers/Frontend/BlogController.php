<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\BlogIndexRequest;
use App\Models\Blog;
use App\Models\BlogPageSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    public function index(BlogIndexRequest $request): View
    {
        $filters = $request->validated();
        $search = Str::squish((string) ($filters['q'] ?? ''));
        $selectedCategory = Str::squish((string) ($filters['category'] ?? ''));
        $publishedBlogs = Blog::query()->published();
        $categories = (clone $publishedBlogs)
            ->whereNotNull('category')
            ->where('category', '<>', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $blogs = (clone $publishedBlogs)
            ->when($selectedCategory !== '', fn ($query) => $query->where('category', $selectedCategory))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($searchQuery) use ($search) {
                    $pattern = '%'.$search.'%';

                    $searchQuery
                        ->whereLike('title', $pattern)
                        ->orWhereLike('content', $pattern)
                        ->orWhereLike('category', $pattern)
                        ->orWhereLike('author', $pattern);
                });
            })
            ->latest('published_at')
            ->latest('id')
            ->paginate(7)
            ->appends(array_filter([
                'q' => $search,
                'category' => $selectedCategory,
            ], fn (string $value): bool => $value !== ''));

        $canonicalParameters = array_filter([
            'q' => $search,
            'category' => $selectedCategory,
        ], fn (string $value): bool => $value !== '');

        if ($blogs->currentPage() > 1) {
            $canonicalParameters['page'] = $blogs->currentPage();
        }

        return view('frontend.blog.index', [
            'blogs' => $blogs,
            'setting' => BlogPageSetting::query()->first(),
            'categories' => $categories,
            'search' => $search,
            'selectedCategory' => $selectedCategory,
            'seoFallbackTitle' => 'Research Blog | BlackTechEngine',
            'seoFallbackDescription' => 'Practical research and insights on generative engine optimization, autonomous SEO, and AI discovery.',
            'pageSeo' => [
                'canonical' => route('blog.index', $canonicalParameters),
            ],
        ]);
    }

    public function show(string $slug): View
    {
        $setting = BlogPageSetting::query()->first();
        $blog = Blog::query()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        $relatedBlogs = filled($blog->category)
            ? Blog::query()
                ->published()
                ->where('category', $blog->category)
                ->whereKeyNot($blog->getKey())
                ->latest('published_at')
                ->limit(3)
                ->get()
            : collect();

        $description = $blog->meta_description
            ?: $blog->seo_description
            ?: Str::limit(trim(strip_tags((string) $blog->content)), 160);

        return view('frontend.blog.show', [
            'blog' => $blog,
            'setting' => $setting,
            'relatedBlogs' => $relatedBlogs,
            'sidebarCtaUrl' => $this->destinationUrl($setting?->sidebar_cta_destination, 'register'),
            'seoFallbackTitle' => $blog->title,
            'seoFallbackDescription' => $description,
            'pageSeo' => [
                'meta_title' => $blog->meta_title,
                'seo_title' => $blog->seo_title,
                'meta_description' => $blog->meta_description,
                'seo_description' => $blog->seo_description,
                'keywords' => $blog->keywords,
                'author' => $blog->author,
                'publisher' => $blog->publisher,
                'copyright' => $blog->copyright,
                'site_name' => $blog->site_name,
                'meta_image' => $blog->meta_image ?: $blog->image,
                'canonical' => route('blog.show', $blog->slug),
                'type' => 'article',
                'published_time' => $blog->published_at?->toIso8601String(),
            ],
        ]);
    }

    private function destinationUrl(?string $destination, string $fallback): ?string
    {
        $routeName = array_key_exists((string) $destination, BlogPageSetting::DESTINATION_OPTIONS)
            ? $destination
            : $fallback;

        return array_key_exists($routeName, BlogPageSetting::DESTINATION_OPTIONS) && Route::has($routeName)
            ? route($routeName)
            : null;
    }
}
