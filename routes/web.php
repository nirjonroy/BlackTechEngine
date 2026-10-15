<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\Admin\AboutPageController;
use App\Http\Controllers\Admin\SeoSettingController;
use App\Http\Controllers\Admin\BlogController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin Unauthenticated Routes
Route::middleware('guest:admin')->group(function () {
    Route::get('/admin', [AuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/admin/login', [AuthController::class, 'login']);
});

// Admin Authenticated Routes
Route::middleware('auth:admin')->group(function () {
    Route::get('/admin/dashboard', function () {
        return view('admin.dashboard'); // Create this view later
    })->name('admin.dashboard');
    
    Route::post('/admin/logout', [AuthController::class, 'logout'])->name('admin.logout');

    Route::get('/admin/role-permission', [RolePermissionController::class, 'index'])->name('admin.role-permission.index');
    Route::get('/admin/role-permission/admin/create', [RolePermissionController::class, 'createAdmin'])->name('admin.role-permission.admin.create');
    Route::post('/admin/role-permission/admin/store', [RolePermissionController::class, 'storeAdmin'])->name('admin.role-permission.admin.store');
    Route::get('/admin/role-permission/role/{role}/edit', [RolePermissionController::class, 'editRole'])->name('admin.role-permission.role.edit');
    Route::post('/admin/role-permission/role/{role}/update', [RolePermissionController::class, 'updateRole'])->name('admin.role-permission.role.update');
    Route::get('/admin/role-permission/role/create', [RolePermissionController::class, 'createRole'])->name('admin.role-permission.role.create');
    Route::post('/admin/role-permission/role/store', [RolePermissionController::class, 'storeRole'])->name('admin.role-permission.role.store');

   
Route::get('/admin/settings', [SiteSettingController::class, 'index'])->name('admin.settings.index');
Route::post('/admin/settings', [SiteSettingController::class, 'update'])->name('admin.settings.update');
Route::get('/admin/about-page', [AboutPageController::class, 'index'])->name('admin.about.index');
Route::post('/admin/about-page', [AboutPageController::class, 'update'])->name('admin.about.update');
Route::get('/admin/seo-settings', [SeoSettingController::class, 'index'])->name('admin.seo.index');
Route::get('/admin/seo-settings/create', [SeoSettingController::class, 'create'])->name('admin.seo.create');
Route::post('/admin/seo-settings/store', [SeoSettingController::class, 'store'])->name('admin.seo.store');
Route::get('/admin/seo-settings/{id}/edit', [SeoSettingController::class, 'edit'])->name('admin.seo.edit');
Route::post('/admin/seo-settings/{id}/update', [SeoSettingController::class, 'update'])->name('admin.seo.update');
Route::get('/admin/seo-settings/{id}/delete', [SeoSettingController::class, 'destroy'])->name('admin.seo.destroy');

Route::get('/admin/blogs', [BlogController::class, 'index'])->name('admin.blogs.index');
Route::get('/admin/blogs/create', [BlogController::class, 'create'])->name('admin.blogs.create');
Route::post('/admin/blogs/store', [BlogController::class, 'store'])->name('admin.blogs.store');
Route::get('/admin/blogs/{id}/edit', [BlogController::class, 'edit'])->name('admin.blogs.edit');
Route::post('/admin/blogs/{id}/update', [BlogController::class, 'update'])->name('admin.blogs.update');
Route::get('/admin/blogs/{id}/delete', [BlogController::class, 'destroy'])->name('admin.blogs.destroy');

});

require __DIR__.'/auth.php';
