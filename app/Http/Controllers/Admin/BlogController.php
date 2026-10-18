<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBlogRequest;
use App\Http\Requests\Admin\UpdateBlogRequest;
use App\Models\Blog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class BlogController extends Controller
{
    public function index()
    {
        $blogs = Blog::orderBy('id', 'desc')->get();

        return view('admin.blogs.index', compact('blogs'));
    }

    public function create()
    {
        return view('admin.blogs.create');
    }

    public function store(StoreBlogRequest $request): RedirectResponse
    {
        $blog = new Blog;
        $blog->fill($request->safe()->except(['image', 'meta_image']));

        if ($request->hasFile('image')) {
            $blog->image = $request->file('image')->store('blogs', 'public');
        }

        if ($request->hasFile('meta_image')) {
            $blog->meta_image = $request->file('meta_image')->store('seo', 'public');
        }

        $blog->save();

        return redirect()->route('admin.blogs.index')->with('success', 'Blog created successfully!');
    }

    public function edit(string $id)
    {
        $blog = Blog::findOrFail($id);

        return view('admin.blogs.edit', compact('blog'));
    }

    public function update(UpdateBlogRequest $request, string $id): RedirectResponse
    {
        $blog = Blog::findOrFail($id);
        $previousImage = $blog->image;
        $previousMetaImage = $blog->meta_image;
        $blog->fill($request->safe()->except(['image', 'meta_image']));

        if ($request->hasFile('image')) {
            $blog->image = $request->file('image')->store('blogs', 'public');
        }

        if ($request->hasFile('meta_image')) {
            $blog->meta_image = $request->file('meta_image')->store('seo', 'public');
        }

        $blog->save();

        if ($request->hasFile('image')) {
            $this->deleteStoredImage($previousImage, 'blogs');
        }

        if ($request->hasFile('meta_image')) {
            $this->deleteStoredImage($previousMetaImage, 'seo');
        }

        return redirect()->route('admin.blogs.index')->with('success', 'Blog updated successfully!');
    }

    public function destroy(Blog $blog): RedirectResponse
    {
        $image = $blog->image;
        $metaImage = $blog->meta_image;

        $blog->delete();
        $this->deleteStoredImage($image, 'blogs');
        $this->deleteStoredImage($metaImage, 'seo');

        return redirect()->route('admin.blogs.index')->with('success', 'Blog deleted successfully!');
    }

    private function deleteStoredImage(?string $path, string $directory): void
    {
        if ($path && str_starts_with($path, $directory.'/') && ! str_contains($path, '..')) {
            Storage::disk('public')->delete($path);
        }
    }
}
