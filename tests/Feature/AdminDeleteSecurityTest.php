<?php

use App\Models\Admin;
use App\Models\Blog;
use App\Models\PricingPlan;
use App\Models\SeoSetting;
use Illuminate\Foundation\Http\Kernel;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach (['manage blogs', 'manage seo settings', 'manage pricing plans'] as $permission) {
        Permission::create([
            'name' => $permission,
            'guard_name' => 'admin',
        ]);
    }
});

function createDeleteAdmin(array $permissions = []): Admin
{
    $admin = Admin::create([
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('password'),
    ]);

    if ($permissions !== []) {
        $admin->givePermissionTo($permissions);
    }

    return $admin;
}

test('an authorized admin can delete a blog and its images', function () {
    Storage::fake('public');
    Storage::disk('public')->put('blogs/blog.jpg', 'blog');
    Storage::disk('public')->put('seo/blog-meta.jpg', 'meta');

    $blog = Blog::create([
        'title' => 'Delete me',
        'slug' => 'delete-me',
        'image' => 'blogs/blog.jpg',
        'meta_image' => 'seo/blog-meta.jpg',
    ]);
    $admin = createDeleteAdmin(['manage blogs']);

    $this->actingAs($admin, 'admin')
        ->delete(route('admin.blogs.destroy', $blog))
        ->assertRedirect(route('admin.blogs.index'));

    $this->assertDatabaseMissing('blogs', ['id' => $blog->id]);
    Storage::disk('public')->assertMissing(['blogs/blog.jpg', 'seo/blog-meta.jpg']);
});

test('an authorized admin can delete an SEO setting and its image', function () {
    Storage::fake('public');
    Storage::disk('public')->put('seo/page-meta.jpg', 'meta');

    $seo = SeoSetting::create([
        'page_name' => 'Delete me',
        'meta_image' => 'seo/page-meta.jpg',
    ]);
    $admin = createDeleteAdmin(['manage seo settings']);

    $this->actingAs($admin, 'admin')
        ->delete(route('admin.seo.destroy', $seo))
        ->assertRedirect(route('admin.seo.index'));

    $this->assertDatabaseMissing('seo_settings', ['id' => $seo->id]);
    Storage::disk('public')->assertMissing('seo/page-meta.jpg');
});

test('an authorized admin can atomically delete a pricing plan and its features', function () {
    Storage::fake('public');
    Storage::disk('public')->put('seo/plan-meta.jpg', 'meta');

    $plan = PricingPlan::create([
        'name' => 'Delete me',
        'meta_image' => 'seo/plan-meta.jpg',
    ]);
    $feature = $plan->features()->create(['name' => 'Included feature']);
    $admin = createDeleteAdmin(['manage pricing plans']);

    $this->actingAs($admin, 'admin')
        ->delete(route('admin.pricing.destroy', $plan))
        ->assertRedirect(route('admin.pricing.index'));

    $this->assertDatabaseMissing('pricing_plans', ['id' => $plan->id]);
    $this->assertDatabaseMissing('pricing_plan_features', ['id' => $feature->id]);
    Storage::disk('public')->assertMissing('seo/plan-meta.jpg');
});

test('an unauthorized admin receives 403 and cannot delete resources', function () {
    $blog = Blog::create(['title' => 'Protected blog', 'slug' => 'protected-blog']);
    $seo = SeoSetting::create(['page_name' => 'Protected SEO']);
    $plan = PricingPlan::create(['name' => 'Protected plan']);
    $admin = createDeleteAdmin();

    $this->actingAs($admin, 'admin');

    $this->delete(route('admin.blogs.destroy', $blog))->assertForbidden();
    $this->delete(route('admin.seo.destroy', $seo))->assertForbidden();
    $this->delete(route('admin.pricing.destroy', $plan))->assertForbidden();

    expect($blog->fresh())->not->toBeNull()
        ->and($seo->fresh())->not->toBeNull()
        ->and($plan->fresh())->not->toBeNull();
});

test('an unauthenticated request cannot delete resources', function () {
    $blog = Blog::create(['title' => 'Protected blog', 'slug' => 'protected-blog']);

    $this->delete(route('admin.blogs.destroy', $blog))->assertRedirect();

    expect($blog->fresh())->not->toBeNull();
});

test('GET requests cannot execute destructive operations', function () {
    $blog = Blog::create(['title' => 'Protected blog', 'slug' => 'protected-blog']);
    $seo = SeoSetting::create(['page_name' => 'Protected SEO']);
    $plan = PricingPlan::create(['name' => 'Protected plan']);
    $admin = createDeleteAdmin(['manage blogs', 'manage seo settings', 'manage pricing plans']);

    $this->actingAs($admin, 'admin');

    $this->get(route('admin.blogs.destroy', $blog))->assertStatus(405);
    $this->get(route('admin.seo.destroy', $seo))->assertStatus(405);
    $this->get(route('admin.pricing.destroy', $plan))->assertStatus(405);

    expect($blog->fresh())->not->toBeNull()
        ->and($seo->fresh())->not->toBeNull()
        ->and($plan->fresh())->not->toBeNull();
});

test('DELETE requests for nonexistent resources return 404', function () {
    $admin = createDeleteAdmin(['manage blogs', 'manage seo settings', 'manage pricing plans']);

    $this->actingAs($admin, 'admin');

    $this->delete(route('admin.blogs.destroy', 999))->assertNotFound();
    $this->delete(route('admin.seo.destroy', 999))->assertNotFound();
    $this->delete(route('admin.pricing.destroy', 999))->assertNotFound();
});

test('delete forms contain CSRF tokens and DELETE method spoofing', function () {
    $blog = Blog::create(['title' => 'Blog', 'slug' => 'blog']);
    $seo = SeoSetting::create(['page_name' => 'SEO']);
    $plan = PricingPlan::create(['name' => 'Plan']);
    $admin = createDeleteAdmin(['manage blogs', 'manage seo settings', 'manage pricing plans']);

    $this->actingAs($admin, 'admin');

    foreach ([
        ['admin.blogs.index', 'admin.blogs.destroy', $blog],
        ['admin.seo.index', 'admin.seo.destroy', $seo],
        ['admin.pricing.index', 'admin.pricing.destroy', $plan],
    ] as [$indexRoute, $destroyRoute, $resource]) {
        $this->get(route($indexRoute))
            ->assertOk()
            ->assertSee('action="'.route($destroyRoute, $resource).'"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('name="_method" value="DELETE"', false)
            ->assertSee('onsubmit="return confirm(', false);
    }
});

test('delete routes retain web CSRF middleware and module permissions', function () {
    $router = app(Router::class);
    $webMiddleware = app(Kernel::class)->getMiddlewareGroups()['web'];

    expect($webMiddleware)->toContain(PreventRequestForgery::class);

    foreach ([
        'admin.blogs.destroy' => 'manage blogs',
        'admin.seo.destroy' => 'manage seo settings',
        'admin.pricing.destroy' => 'manage pricing plans',
    ] as $routeName => $permission) {
        $route = $router->getRoutes()->getByName($routeName);
        $middleware = $route->gatherMiddleware();

        expect($route->methods())->toBe(['DELETE'])
            ->and($middleware)->toContain('web')
            ->and(implode('|', $middleware))->toContain($permission.',admin');
    }
});

test('a DELETE request without a CSRF token is rejected outside the test environment', function () {
    $blog = Blog::create(['title' => 'Protected blog', 'slug' => 'protected-blog']);
    $admin = createDeleteAdmin(['manage blogs']);

    $this->actingAs($admin, 'admin');
    $this->app['env'] = 'production';

    $this->delete(route('admin.blogs.destroy', $blog))->assertStatus(419);

    expect($blog->fresh())->not->toBeNull();
});
