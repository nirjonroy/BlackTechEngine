<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateBlogPageSettingRequest;
use App\Models\BlogPageSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class BlogPageSettingController extends Controller
{
    public function index(): View
    {
        return view('admin.blog_page_settings.index', [
            'setting' => BlogPageSetting::query()->first() ?? new BlogPageSetting,
            'destinationOptions' => BlogPageSetting::DESTINATION_OPTIONS,
        ]);
    }

    public function update(UpdateBlogPageSettingRequest $request): RedirectResponse
    {
        $setting = BlogPageSetting::query()->first() ?? new BlogPageSetting;
        $setting->fill($request->validated())->save();

        return back()->with('success', 'Blog Page Settings updated successfully!');
    }
}
