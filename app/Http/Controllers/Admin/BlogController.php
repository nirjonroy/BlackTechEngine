<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $blog = new Blog;
        $blog->fill($request->except(['_token', 'image', 'meta_image']));

        // Slug generation
        $blog->slug = $request->slug ? Str::slug($request->slug) : Str::slug($request->title).'-'.time();

        if ($request->hasFile('image')) {
            $blog->image = $request->file('image')->store('blogs', 'public');
        }
        if ($request->hasFile('meta_image')) {
            $blog->meta_image = $request->file('meta_image')->store('seo', 'public');
        }

        $blog->save();

        return redirect()->route('admin.blogs.index')->with('success', 'Blog created successfully!');
    }

    public function edit($id)
    {
        $blog = Blog::findOrFail($id);

        return view('admin.blogs.edit', compact('blog'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $blog = Blog::findOrFail($id);
        $blog->fill($request->except(['_token', 'image', 'meta_image']));

        if ($request->hasFile('image')) {
            if ($blog->image) {
                Storage::disk('public')->delete($blog->image);
            }
            $blog->image = $request->file('image')->store('blogs', 'public');
        }
        if ($request->hasFile('meta_image')) {
            if ($blog->meta_image) {
                Storage::disk('public')->delete($blog->meta_image);
            }
            $blog->meta_image = $request->file('meta_image')->store('seo', 'public');
        }
        $blog->slug = $request->slug ? Str::slug($request->slug) : Str::slug($request->title);
        $blog->save();

        return redirect()->route('admin.blogs.index')->with('success', 'Blog updated successfully!');
    }

    public function destroy(Blog $blog): RedirectResponse
    {
        $imagePaths = array_filter([$blog->image, $blog->meta_image]);

        $blog->delete();

        if ($imagePaths !== []) {
            Storage::disk('public')->delete($imagePaths);
        }

        return redirect()->route('admin.blogs.index')->with('success', 'Blog deleted successfully!');
    }
}
