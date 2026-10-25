<?php

use App\Http\Controllers\Admin\AboutPageController;
use App\Http\Controllers\Admin\AffiliatePageSettingController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BlogController;
use App\Http\Controllers\Admin\BlogPageSettingController;
use App\Http\Controllers\Admin\FeaturePageSettingController;
use App\Http\Controllers\Admin\FrontendNavigationController;
use App\Http\Controllers\Admin\HomepageSettingController;
use App\Http\Controllers\Admin\PricingPageSettingController;
use App\Http\Controllers\Admin\PricingPlanController;
use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Controllers\Admin\SeoSettingController;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\Frontend\AboutPageController as FrontendAboutPageController;
use App\Http\Controllers\Frontend\AffiliatePageController;
use App\Http\Controllers\Frontend\BlogController as FrontendBlogController;
use App\Http\Controllers\Frontend\FeaturesPageController as FrontendFeaturesPageController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\PricingPageController as FrontendPricingPageController;
use App\Http\Controllers\Frontend\RobotsController;
use App\Http\Controllers\Frontend\SitemapController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', FrontendAboutPageController::class)->name('about');
Route::get('/features', FrontendFeaturesPageController::class)->name('features');
Route::get('/pricing', FrontendPricingPageController::class)->name('pricing');
Route::get('/affiliate', AffiliatePageController::class)->name('affiliate');
Route::get('/blog', [FrontendBlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [FrontendBlogController::class, 'show'])->name('blog.show');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', RobotsController::class)->name('robots');

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
    })->middleware(PermissionMiddleware::using('view dashboard', 'admin'))->name('admin.dashboard');

    Route::post('/admin/logout', [AuthController::class, 'logout'])->name('admin.logout');

    Route::get('/admin/role-permission', [RolePermissionController::class, 'index'])
        ->middleware(PermissionMiddleware::using('manage roles|create admin|edit admin|delete admin', 'admin'))
        ->name('admin.role-permission.index');

    Route::middleware(PermissionMiddleware::using('create admin', 'admin'))->group(function () {
        Route::get('/admin/role-permission/admin/create', [RolePermissionController::class, 'createAdmin'])->name('admin.role-permission.admin.create');
        Route::post('/admin/role-permission/admin/store', [RolePermissionController::class, 'storeAdmin'])->name('admin.role-permission.admin.store');
    });

    Route::middleware(PermissionMiddleware::using('manage roles', 'admin'))->group(function () {
        Route::get('/admin/role-permission/role/{role}/edit', [RolePermissionController::class, 'editRole'])->name('admin.role-permission.role.edit');
        Route::post('/admin/role-permission/role/{role}/update', [RolePermissionController::class, 'updateRole'])->name('admin.role-permission.role.update');
        Route::get('/admin/role-permission/role/create', [RolePermissionController::class, 'createRole'])->name('admin.role-permission.role.create');
        Route::post('/admin/role-permission/role/store', [RolePermissionController::class, 'storeRole'])->name('admin.role-permission.role.store');
    });

    Route::middleware(PermissionMiddleware::using('manage site settings', 'admin'))->group(function () {
        Route::get('/admin/settings', [SiteSettingController::class, 'index'])->name('admin.settings.index');
        Route::post('/admin/settings', [SiteSettingController::class, 'update'])->name('admin.settings.update');
    });

    Route::middleware(PermissionMiddleware::using('manage homepage settings', 'admin'))->group(function () {
        Route::get('/admin/homepage-settings', [HomepageSettingController::class, 'index'])->name('admin.homepage.index');
        Route::post('/admin/homepage-settings', [HomepageSettingController::class, 'update'])->name('admin.homepage.update');
    });

    Route::middleware(PermissionMiddleware::using('manage affiliate page settings', 'admin'))->group(function () {
        Route::get('/admin/affiliate-page-settings', [AffiliatePageSettingController::class, 'index'])->name('admin.affiliate_page.index');
        Route::post('/admin/affiliate-page-settings', [AffiliatePageSettingController::class, 'update'])->name('admin.affiliate_page.update');
    });

    Route::middleware(PermissionMiddleware::using('manage frontend navigation', 'admin'))->group(function () {
        Route::get('/admin/frontend-navigation', [FrontendNavigationController::class, 'index'])->name('admin.frontend_navigation.index');
        Route::post('/admin/frontend-navigation', [FrontendNavigationController::class, 'update'])->name('admin.frontend_navigation.update');
    });

    Route::middleware(PermissionMiddleware::using('manage about page', 'admin'))->group(function () {
        Route::get('/admin/about-page', [AboutPageController::class, 'index'])->name('admin.about.index');
        Route::post('/admin/about-page', [AboutPageController::class, 'update'])->name('admin.about.update');
    });

    Route::middleware(PermissionMiddleware::using('manage seo settings', 'admin'))->group(function () {
        Route::get('/admin/seo-settings', [SeoSettingController::class, 'index'])->name('admin.seo.index');
        Route::get('/admin/seo-settings/create', [SeoSettingController::class, 'create'])->name('admin.seo.create');
        Route::post('/admin/seo-settings/store', [SeoSettingController::class, 'store'])->name('admin.seo.store');
        Route::get('/admin/seo-settings/{id}/edit', [SeoSettingController::class, 'edit'])->name('admin.seo.edit');
        Route::post('/admin/seo-settings/{id}/update', [SeoSettingController::class, 'update'])->name('admin.seo.update');
        Route::delete('/admin/seo-settings/{seo}/delete', [SeoSettingController::class, 'destroy'])->name('admin.seo.destroy');
    });

    Route::middleware(PermissionMiddleware::using('manage blogs', 'admin'))->group(function () {
        Route::get('/admin/blogs', [BlogController::class, 'index'])->name('admin.blogs.index');
        Route::get('/admin/blogs/create', [BlogController::class, 'create'])->name('admin.blogs.create');
        Route::post('/admin/blogs/store', [BlogController::class, 'store'])->name('admin.blogs.store');
        Route::get('/admin/blogs/{id}/edit', [BlogController::class, 'edit'])->name('admin.blogs.edit');
        Route::post('/admin/blogs/{id}/update', [BlogController::class, 'update'])->name('admin.blogs.update');
        Route::delete('/admin/blogs/{blog}/delete', [BlogController::class, 'destroy'])->name('admin.blogs.destroy');
    });

    Route::middleware(PermissionMiddleware::using('manage blog page settings', 'admin'))->group(function () {
        Route::get('/admin/blog-page-settings', [BlogPageSettingController::class, 'index'])->name('admin.blog_page.index');
        Route::post('/admin/blog-page-settings', [BlogPageSettingController::class, 'update'])->name('admin.blog_page.update');
    });

    Route::middleware(PermissionMiddleware::using('manage pricing plans', 'admin'))->group(function () {
        Route::get('/admin/pricing-plans', [PricingPlanController::class, 'index'])->name('admin.pricing.index');
        Route::get('/admin/pricing-plans/create', [PricingPlanController::class, 'create'])->name('admin.pricing.create');
        Route::post('/admin/pricing-plans/store', [PricingPlanController::class, 'store'])->name('admin.pricing.store');
        Route::get('/admin/pricing-plans/{id}/edit', [PricingPlanController::class, 'edit'])->name('admin.pricing.edit');
        Route::post('/admin/pricing-plans/{id}/update', [PricingPlanController::class, 'update'])->name('admin.pricing.update');
        Route::delete('/admin/pricing-plans/{plan}/delete', [PricingPlanController::class, 'destroy'])->name('admin.pricing.destroy');
    });

    Route::middleware(PermissionMiddleware::using('manage pricing page settings', 'admin'))->group(function () {
        Route::get('/admin/pricing-page-settings', [PricingPageSettingController::class, 'index'])->name('admin.pricing_page.index');
        Route::post('/admin/pricing-page-settings', [PricingPageSettingController::class, 'update'])->name('admin.pricing_page.update');
    });

    Route::middleware(PermissionMiddleware::using('manage feature page settings', 'admin'))->group(function () {
        Route::get('/admin/feature-page-settings', [FeaturePageSettingController::class, 'index'])->name('admin.feature_page.index');
        Route::post('/admin/feature-page-settings', [FeaturePageSettingController::class, 'update'])->name('admin.feature_page.update');
    });
});

require __DIR__.'/auth.php';
