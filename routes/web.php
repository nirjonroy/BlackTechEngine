<?php

use App\Http\Controllers\ActiveWorkspaceController;
use App\Http\Controllers\Admin\AboutPageController;
use App\Http\Controllers\Admin\AffiliateApplicationController;
use App\Http\Controllers\Admin\AffiliateController;
use App\Http\Controllers\Admin\AffiliatePageSettingController;
use App\Http\Controllers\Admin\AiConfigurationController;
use App\Http\Controllers\Admin\AiModelActivationController;
use App\Http\Controllers\Admin\AiModelController;
use App\Http\Controllers\Admin\AiProviderActivationController;
use App\Http\Controllers\Admin\AiProviderBindingController;
use App\Http\Controllers\Admin\AiProviderController;
use App\Http\Controllers\Admin\AiProviderModelDiscoveryController;
use App\Http\Controllers\Admin\AiProviderVerificationController;
use App\Http\Controllers\Admin\AiWorkloadActivationController;
use App\Http\Controllers\Admin\AiWorkloadDraftController;
use App\Http\Controllers\Admin\AnalyticsExportController as AdminAnalyticsExportController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BlogController;
use App\Http\Controllers\Admin\BlogPageSettingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FeaturePageSettingController;
use App\Http\Controllers\Admin\FrontendNavigationController;
use App\Http\Controllers\Admin\HomepageSettingController;
use App\Http\Controllers\Admin\IntegrationSettingController;
use App\Http\Controllers\Admin\PlanEntitlementController;
use App\Http\Controllers\Admin\PricingPageSettingController;
use App\Http\Controllers\Admin\PricingPlanController;
use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Controllers\Admin\SeoSettingController;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\Admin\SubscriptionAdjustmentController as AdminSubscriptionAdjustmentController;
use App\Http\Controllers\Admin\SubscriptionController as AdminSubscriptionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AiContentGenerationController;
use App\Http\Controllers\AnalyticsExportController;
use App\Http\Controllers\ArticleCalendarController;
use App\Http\Controllers\ArticleScheduleController;
use App\Http\Controllers\BillingInvoiceController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\ContentApprovalController;
use App\Http\Controllers\ContentChangeRequestController;
use App\Http\Controllers\ContentDraftController;
use App\Http\Controllers\ContentDraftExportController;
use App\Http\Controllers\ContentReviewRequestController;
use App\Http\Controllers\DashboardController as CustomerDashboardController;
use App\Http\Controllers\Frontend\AboutPageController as FrontendAboutPageController;
use App\Http\Controllers\Frontend\AffiliatePageController;
use App\Http\Controllers\Frontend\BlogController as FrontendBlogController;
use App\Http\Controllers\Frontend\FeaturesPageController as FrontendFeaturesPageController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\PricingPageController as FrontendPricingPageController;
use App\Http\Controllers\Frontend\RobotsController;
use App\Http\Controllers\Frontend\SitemapController;
use App\Http\Controllers\KeywordAiSuggestionController;
use App\Http\Controllers\KeywordClusterController;
use App\Http\Controllers\KeywordResearchController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PaymentCheckoutController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SocialCalendarController;
use App\Http\Controllers\SocialPostController;
use App\Http\Controllers\SocialPostExportController;
use App\Http\Controllers\SocialPostWorkflowController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\SubscriptionRequestController;
use App\Http\Controllers\WordPressConnectionController;
use App\Http\Controllers\WordPressPublishController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', FrontendAboutPageController::class)->name('about');
Route::get('/features', FrontendFeaturesPageController::class)->name('features');
Route::get('/pricing', FrontendPricingPageController::class)->name('pricing');
Route::get('/affiliate', AffiliatePageController::class)->name('affiliate');
Route::get('/blog', [FrontendBlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [FrontendBlogController::class, 'show'])->name('blog.show');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', RobotsController::class)->name('robots');
Route::post('/billing/webhooks/payment', PaymentWebhookController::class)
    ->middleware('throttle:60,1')
    ->name('billing.webhooks.payment');

if (config('customer.workspaces_enabled')) {
    Route::middleware(['auth', 'verified'])->group(function () {
        Route::get('/dashboard', CustomerDashboardController::class)->name('dashboard');
        Route::get('/dashboard/analytics.csv', AnalyticsExportController::class)->name('dashboard.analytics.export');

        if (config('subscriptions.management_enabled')) {
            Route::get('/subscription', SubscriptionController::class)->name('subscriptions.index');
            Route::post('/workspaces/{workspace}/subscription/change-requests', [SubscriptionRequestController::class, 'change'])
                ->name('subscriptions.change-requests.store');
            Route::post('/workspaces/{workspace}/subscription/checkout', PaymentCheckoutController::class)
                ->name('subscriptions.checkout.store');
            Route::post('/workspaces/{workspace}/subscription/cancellation-requests', [SubscriptionRequestController::class, 'cancel'])
                ->name('subscriptions.cancellation-requests.store');
            Route::get('/workspaces/{workspace}/billing/invoices/{billingInvoice}', BillingInvoiceController::class)
                ->scopeBindings()->name('subscriptions.invoices.show');
        }

        Route::get('/onboarding', [OnboardingController::class, 'start'])->name('onboarding.start');
        Route::get('/onboarding/workspace', [WorkspaceController::class, 'create'])->name('onboarding.workspace.create');
        Route::post('/onboarding/workspace', [WorkspaceController::class, 'store'])->name('onboarding.workspace.store');
        Route::get('/onboarding/workspaces/{workspace}/brand', [BrandController::class, 'create'])->name('onboarding.brand.create');
        Route::post('/onboarding/workspaces/{workspace}/brand', [BrandController::class, 'store'])->name('onboarding.brand.store');
        Route::get('/onboarding/workspaces/{workspace}/brands/{brand}/confirm', [OnboardingController::class, 'confirm'])
            ->scopeBindings()
            ->name('onboarding.confirm');
        Route::post('/onboarding/workspaces/{workspace}/brands/{brand}/complete', [OnboardingController::class, 'complete'])
            ->scopeBindings()
            ->name('onboarding.complete');

        Route::put('/workspaces/{workspace}/active', ActiveWorkspaceController::class)->name('workspaces.active.update');
        Route::resource('workspaces', WorkspaceController::class)->except('destroy');
        Route::resource('workspaces.brands', BrandController::class)
            ->except('destroy')
            ->scoped();

        Route::post('/workspaces/{workspace}/brands/{brand}/wordpress/connect', [WordPressConnectionController::class, 'connect'])
            ->scopeBindings()->name('workspaces.brands.wordpress.connect');
        Route::post('/workspaces/{workspace}/brands/{brand}/wordpress/verify', [WordPressConnectionController::class, 'verify'])
            ->scopeBindings()->name('workspaces.brands.wordpress.verify');
        Route::delete('/workspaces/{workspace}/brands/{brand}/wordpress/disconnect', [WordPressConnectionController::class, 'disconnect'])
            ->scopeBindings()->name('workspaces.brands.wordpress.disconnect');

        Route::get('/workspaces/{workspace}/brands/{brand}/keywords', [KeywordResearchController::class, 'index'])
            ->scopeBindings()->name('workspaces.brands.keywords.index');
        Route::post('/workspaces/{workspace}/brands/{brand}/keywords', [KeywordResearchController::class, 'store'])
            ->scopeBindings()->name('workspaces.brands.keywords.store');
        Route::delete('/workspaces/{workspace}/brands/{brand}/keywords/{brandKeyword}', [KeywordResearchController::class, 'destroy'])
            ->scopeBindings()->name('workspaces.brands.keywords.destroy');
        Route::patch('/workspaces/{workspace}/brands/{brand}/keywords/{brandKeyword}/star', [KeywordResearchController::class, 'toggleStar'])
            ->scopeBindings()->name('workspaces.brands.keywords.star');

        Route::post('/workspaces/{workspace}/brands/{brand}/keyword-clusters', [KeywordClusterController::class, 'store'])
            ->scopeBindings()->name('workspaces.brands.keyword-clusters.store');
        Route::delete('/workspaces/{workspace}/brands/{brand}/keyword-clusters/{brandTopicCluster}', [KeywordClusterController::class, 'destroy'])
            ->scopeBindings()->name('workspaces.brands.keyword-clusters.destroy');

        Route::post('/workspaces/{workspace}/brands/{brand}/keywords/ai-suggestions', [KeywordAiSuggestionController::class, 'suggest'])
            ->scopeBindings()->middleware('throttle:ai-content-generation')->name('workspaces.brands.keywords.ai-suggestions');
        Route::post('/workspaces/{workspace}/brands/{brand}/keywords/import-suggestions', [KeywordAiSuggestionController::class, 'import'])
            ->scopeBindings()->name('workspaces.brands.keywords.import-suggestions');

        if (config('ai.content_studio_enabled')) {
            Route::get('/content-studio', [ContentDraftController::class, 'index'])->name('content-studio.index');
            Route::get('/content-studio/calendar', ArticleCalendarController::class)->name('content-studio.calendar');
            Route::get('/workspaces/{workspace}/brands/{brand}/calendar', ArticleCalendarController::class)
                ->scopeBindings()->name('workspaces.brands.calendar');
            Route::post('/workspaces/{workspace}/brands/{brand}/schedules', [ArticleScheduleController::class, 'store'])
                ->scopeBindings()->name('workspaces.brands.schedules.store');
            Route::patch('/workspaces/{workspace}/brands/{brand}/schedules/{articleSchedule}/toggle', [ArticleScheduleController::class, 'toggleActive'])
                ->scopeBindings()->name('workspaces.brands.schedules.toggle');
            Route::delete('/workspaces/{workspace}/brands/{brand}/schedules/{articleSchedule}', [ArticleScheduleController::class, 'destroy'])
                ->scopeBindings()->name('workspaces.brands.schedules.destroy');
            Route::post('/workspaces/{workspace}/brands/{brand}/schedules/{articleSchedule}/run-now', [ArticleScheduleController::class, 'runNow'])
                ->scopeBindings()->name('workspaces.brands.schedules.run-now');
            Route::get('/workspaces/{workspace}/brands/{brand}/content-drafts/create', [ContentDraftController::class, 'create'])
                ->scopeBindings()->name('content-studio.drafts.create');
            Route::post('/workspaces/{workspace}/brands/{brand}/content-drafts', [ContentDraftController::class, 'store'])
                ->scopeBindings()->name('content-studio.drafts.store');
            Route::get('/workspaces/{workspace}/brands/{brand}/content-drafts/{aiContentDraft}', [ContentDraftController::class, 'show'])
                ->scopeBindings()->name('content-studio.drafts.show');
            Route::get('/workspaces/{workspace}/brands/{brand}/content-drafts/{aiContentDraft}/edit', [ContentDraftController::class, 'edit'])
                ->scopeBindings()->name('content-studio.drafts.edit');
            Route::patch('/workspaces/{workspace}/brands/{brand}/content-drafts/{aiContentDraft}', [ContentDraftController::class, 'update'])
                ->scopeBindings()->name('content-studio.drafts.update');
            if (config('ai.content_review_enabled')) {
                Route::post('/workspaces/{workspace}/brands/{brand}/content-drafts/{aiContentDraft}/review-requests', ContentReviewRequestController::class)
                    ->scopeBindings()->name('content-studio.drafts.review-requests.store');
                Route::post('/workspaces/{workspace}/brands/{brand}/content-drafts/{aiContentDraft}/approval', ContentApprovalController::class)
                    ->scopeBindings()->name('content-studio.drafts.approval.store');
                Route::post('/workspaces/{workspace}/brands/{brand}/content-drafts/{aiContentDraft}/change-requests', ContentChangeRequestController::class)
                    ->scopeBindings()->name('content-studio.drafts.change-requests.store');
                Route::post('/workspaces/{workspace}/brands/{brand}/content-drafts/{aiContentDraft}/exports', ContentDraftExportController::class)
                    ->scopeBindings()->name('content-studio.drafts.exports.store');
                Route::post('/workspaces/{workspace}/brands/{brand}/content-drafts/{aiContentDraft}/wordpress/dispatch', [WordPressPublishController::class, 'store'])
                    ->scopeBindings()->name('content-studio.drafts.wordpress.publish');
            }
            Route::post('/workspaces/{workspace}/brands/{brand}/content-generations', AiContentGenerationController::class)
                ->scopeBindings()->middleware('throttle:ai-content-generation')->name('content-studio.generations.store');
        }

        if (config('social.enabled')) {
            Route::get('/social-content', [SocialPostController::class, 'index'])->name('social-content.index');
            Route::get('/social-content/calendar', SocialCalendarController::class)->name('social-content.calendar');
            Route::get('/workspaces/{workspace}/brands/{brand}/social-posts/create', [SocialPostController::class, 'create'])
                ->scopeBindings()->name('social-content.posts.create');
            Route::post('/workspaces/{workspace}/brands/{brand}/social-posts', [SocialPostController::class, 'store'])
                ->scopeBindings()->name('social-content.posts.store');
            Route::get('/workspaces/{workspace}/brands/{brand}/social-posts/{socialPost}', [SocialPostController::class, 'show'])
                ->scopeBindings()->name('social-content.posts.show');
            Route::get('/workspaces/{workspace}/brands/{brand}/social-posts/{socialPost}/edit', [SocialPostController::class, 'edit'])
                ->scopeBindings()->name('social-content.posts.edit');
            Route::patch('/workspaces/{workspace}/brands/{brand}/social-posts/{socialPost}', [SocialPostController::class, 'update'])
                ->scopeBindings()->name('social-content.posts.update');
            Route::post('/workspaces/{workspace}/brands/{brand}/social-posts/{socialPost}/duplicate', [SocialPostController::class, 'duplicate'])
                ->scopeBindings()->name('social-content.posts.duplicate');
            Route::post('/workspaces/{workspace}/brands/{brand}/social-posts/{socialPost}/workflow/{action}', SocialPostWorkflowController::class)
                ->whereIn('action', ['submit', 'approve', 'changes', 'plan', 'publish', 'cancel'])
                ->scopeBindings()->name('social-content.posts.workflow');
            Route::post('/workspaces/{workspace}/brands/{brand}/social-posts/{socialPost}/exports', SocialPostExportController::class)
                ->scopeBindings()->name('social-content.posts.exports');
        }
    });
} else {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->middleware(['auth', 'verified'])->name('dashboard');
}

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
    Route::get('/admin/dashboard', DashboardController::class)
        ->middleware(PermissionMiddleware::using('view dashboard', 'admin'))
        ->name('admin.dashboard');
    Route::get('/admin/dashboard/analytics.csv', AdminAnalyticsExportController::class)
        ->middleware(PermissionMiddleware::using('view dashboard', 'admin'))
        ->name('admin.dashboard.analytics.export');

    Route::middleware(RoleMiddleware::using('Super Admin', 'admin'))->group(function () {
        Route::get('/admin/integrations', [IntegrationSettingController::class, 'index'])
            ->name('admin.integrations.index');
        Route::patch('/admin/integrations/gemini', [IntegrationSettingController::class, 'updateGemini'])
            ->name('admin.integrations.gemini.update');
        Route::patch('/admin/integrations/stripe', [IntegrationSettingController::class, 'updateStripe'])
            ->name('admin.integrations.stripe.update');
        Route::patch('/admin/integrations/mail', [IntegrationSettingController::class, 'updateMail'])
            ->name('admin.integrations.mail.update');
        Route::post('/admin/integrations/mail/test', [IntegrationSettingController::class, 'sendTestMail'])
            ->middleware('throttle:3,1')
            ->name('admin.integrations.mail.test');
    });

    if (config('subscriptions.management_enabled')) {
        Route::middleware(PermissionMiddleware::using('manage subscriptions', 'admin'))->group(function () {
            Route::get('/admin/subscriptions', [AdminSubscriptionController::class, 'index'])->name('admin.subscriptions.index');
            Route::get('/admin/subscriptions/{workspaceSubscription}', [AdminSubscriptionController::class, 'show'])->name('admin.subscriptions.show');
            Route::get('/admin/pricing-plans/{pricingPlan}/entitlements', [PlanEntitlementController::class, 'edit'])->name('admin.pricing.entitlements.edit');
            Route::patch('/admin/pricing-plans/{pricingPlan}/entitlements', [PlanEntitlementController::class, 'update'])->name('admin.pricing.entitlements.update');
            Route::post('/admin/workspaces/{workspace}/subscription-adjustments', AdminSubscriptionAdjustmentController::class)
                ->name('admin.subscriptions.adjustments.store');
        });
    }

    Route::middleware(PermissionMiddleware::using('manage users', 'admin'))->group(function () {
        Route::get('/admin/users', [UserController::class, 'index'])->name('admin.users.index');
        Route::get('/admin/users/{user}', [UserController::class, 'show'])->name('admin.users.show');
    });

    Route::middleware(PermissionMiddleware::using('manage ai models', 'admin'))->group(function () {
        Route::get('/admin/ai-configuration', [AiConfigurationController::class, 'index'])->name('admin.ai-configuration.index');
        Route::get('/admin/ai-configuration/providers', [AiConfigurationController::class, 'providers'])->name('admin.ai-configuration.providers');
        Route::get('/admin/ai-configuration/providers/create', [AiProviderController::class, 'create'])->name('admin.ai-configuration.providers.create');
        Route::post('/admin/ai-configuration/providers', [AiProviderController::class, 'store'])->name('admin.ai-configuration.providers.store');
        Route::get('/admin/ai-configuration/providers/{aiProvider}/edit', [AiProviderController::class, 'edit'])->name('admin.ai-configuration.providers.edit');
        Route::patch('/admin/ai-configuration/providers/{aiProvider}', [AiProviderController::class, 'update'])->name('admin.ai-configuration.providers.update');
        Route::patch('/admin/ai-configuration/providers/{aiProvider}/activation', AiProviderActivationController::class)
            ->name('admin.ai-configuration.providers.activation.update');
        Route::get('/admin/ai-configuration/models', [AiConfigurationController::class, 'models'])->name('admin.ai-configuration.models');
        Route::get('/admin/ai-configuration/models/create', [AiModelController::class, 'create'])->name('admin.ai-configuration.models.create');
        Route::post('/admin/ai-configuration/models', [AiModelController::class, 'store'])->name('admin.ai-configuration.models.store');
        Route::get('/admin/ai-configuration/models/{aiModel}/edit', [AiModelController::class, 'edit'])->name('admin.ai-configuration.models.edit');
        Route::patch('/admin/ai-configuration/models/{aiModel}', [AiModelController::class, 'update'])->name('admin.ai-configuration.models.update');
        Route::patch('/admin/ai-configuration/models/{aiModel}/activation', AiModelActivationController::class)
            ->name('admin.ai-configuration.models.activation.update');
        Route::get('/admin/ai-configuration/workload-routes', [AiConfigurationController::class, 'workloadRoutes'])->name('admin.ai-configuration.workload-routes');
        Route::get('/admin/ai-configuration/workload-routes/create', [AiWorkloadDraftController::class, 'create'])->name('admin.ai-configuration.workload-routes.create');
        Route::post('/admin/ai-configuration/workload-routes', [AiWorkloadDraftController::class, 'store'])->name('admin.ai-configuration.workload-routes.store');
        Route::get('/admin/ai-configuration/workload-routes/{aiWorkloadRoute}/edit', [AiWorkloadDraftController::class, 'edit'])->name('admin.ai-configuration.workload-routes.edit');
        Route::patch('/admin/ai-configuration/workload-routes/{aiWorkloadRoute}', [AiWorkloadDraftController::class, 'update'])->name('admin.ai-configuration.workload-routes.update');
        Route::patch('/admin/ai-configuration/workload-routes/{aiWorkloadRoute}/activation', AiWorkloadActivationController::class)
            ->name('admin.ai-configuration.workload-routes.activation.update');
    });

    Route::middleware(PermissionMiddleware::using('verify ai providers', 'admin'))->group(function () {
        Route::post('/admin/ai-configuration/providers/{aiProvider}/binding', [AiProviderBindingController::class, 'store'])
            ->name('admin.ai-configuration.providers.binding.store');
        Route::post('/admin/ai-configuration/providers/{aiProvider}/verification', [AiProviderVerificationController::class, 'store'])
            ->middleware('throttle:ai-provider-verification')
            ->name('admin.ai-configuration.providers.verification.store');
        Route::post('/admin/ai-configuration/providers/{aiProvider}/discovery', [AiProviderModelDiscoveryController::class, 'store'])
            ->middleware('throttle:ai-provider-verification')
            ->name('admin.ai-configuration.providers.discovery.store');
    });

    Route::middleware(PermissionMiddleware::using('edit users', 'admin'))->group(function () {
        Route::get('/admin/users/{user}/edit', [UserController::class, 'edit'])->name('admin.users.edit');
        Route::patch('/admin/users/{user}', [UserController::class, 'update'])->name('admin.users.update');
    });

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

    Route::middleware(PermissionMiddleware::using('manage affiliates', 'admin'))->group(function () {
        Route::get('/admin/affiliates', [AffiliateController::class, 'index'])->name('admin.affiliates.index');
        Route::get('/admin/affiliates/{affiliate}', [AffiliateController::class, 'show'])->name('admin.affiliates.show');
        Route::get('/admin/affiliate-applications', [AffiliateApplicationController::class, 'index'])->name('admin.affiliate-applications.index');
        Route::get('/admin/affiliate-applications/{affiliateApplication}', [AffiliateApplicationController::class, 'show'])->name('admin.affiliate-applications.show');
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
