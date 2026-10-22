<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    public function index(): View
    {
        $blogs = Blog::query()
            ->published()
            ->latest('published_at')
            ->latest('id')
            ->paginate(7);

        return view('frontend.blog.index', [
            'blogs' => $blogs,
            'seoFallbackTitle' => 'Research Blog | BlackTechEngine',
            'seoFallbackDescription' => 'Practical research and insights on generative engine optimization, autonomous SEO, and AI discovery.',
            'pageSeo' => [
                'canonical' => $blogs->currentPage() > 1 ? $blogs->url($blogs->currentPage()) : route('blog.index'),
            ],
        ]);
    }

    public function show(string $slug): View
    {
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
            'relatedBlogs' => $relatedBlogs,
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
}
