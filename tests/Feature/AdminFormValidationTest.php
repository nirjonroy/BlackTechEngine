<?php

use App\Models\AboutPage;
use App\Models\Admin;
use App\Models\Blog;
use App\Models\FeaturePageSetting;
use App\Models\PricingPageSetting;
use App\Models\PricingPlan;
use App\Models\SiteSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach ([
        'manage site settings',
        'manage about page',
        'manage seo settings',
        'manage blogs',
        'manage pricing plans',
        'manage pricing page settings',
        'manage feature page settings',
        'create admin',
        'manage roles',
    ] as $permission) {
        Permission::create(['name' => $permission, 'guard_name' => 'admin']);
    }
});

function createFormAdmin(string|array $permissions): Admin
{
    $admin = Admin::create([
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('Password123'),
    ]);
    $admin->givePermissionTo($permissions);

    return $admin;
}

function createPngUpload(string $name): UploadedFile
{
    $contents = base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
        strict: true,
    );

    return UploadedFile::fake()->createWithContent($name, $contents);
}

test('site settings reject invalid contact data and preserve the existing record', function () {
    $setting = SiteSetting::create([
        'site_name' => 'Original',
        'email' => 'original@example.com',
        'facebook_url' => 'https://example.com/original',
    ]);
    $admin = createFormAdmin('manage site settings');

    $this->actingAs($admin, 'admin')
        ->post(route('admin.settings.update'), [
            'site_name' => 'Changed',
            'email' => 'not-an-email',
            'facebook_url' => 'javascript:alert(1)',
        ])
        ->assertSessionHasErrors(['email', 'facebook_url']);

    expect($setting->refresh()->site_name)->toBe('Original')
        ->and($setting->email)->toBe('original@example.com');
});

test('site settings accept valid images and safely replace an old logo', function () {
    Storage::fake('public');
    Storage::disk('public')->put('settings/old-logo.png', 'old');
    $setting = SiteSetting::create(['logo_dark' => 'settings/old-logo.png']);
    $admin = createFormAdmin('manage site settings');

    $this->actingAs($admin, 'admin')
        ->post(route('admin.settings.update'), [
            'site_name' => 'BlackTechEngine',
            'email' => 'admin@example.com',
            'facebook_url' => 'https://facebook.com/blacktech',
            'logo_dark' => createPngUpload('brand.png'),
        ])
        ->assertSessionHasNoErrors();

    $storedPath = $setting->refresh()->logo_dark;
    expect($storedPath)->toStartWith('settings/')
        ->and($storedPath)->not->toBe('settings/brand.png');
    Storage::disk('public')->assertExists($storedPath);
    Storage::disk('public')->assertMissing('settings/old-logo.png');
});

test('about page rejects invalid nested data without replacing related records', function () {
    $about = AboutPage::create(['hero_title' => 'Original']);
    $stat = $about->stats()->create(['value' => '100%', 'label' => 'Original stat', 'order_by' => 0]);
    $admin = createFormAdmin('manage about page');

    $this->actingAs($admin, 'admin')
        ->post(route('admin.about.update'), [
            'hero_title' => 'Changed',
            'stats' => [['value' => '50%']],
            'architectures' => [['step_number' => 'invalid', 'title' => 'Step', 'description' => 'Description']],
        ])
        ->assertSessionHasErrors(['stats.0.label', 'architectures.0.step_number']);

    expect($about->refresh()->hero_title)->toBe('Original')
        ->and($stat->fresh())->not->toBeNull();
});

test('about page accepts valid repeatable data', function () {
    $admin = createFormAdmin('manage about page');

    $this->actingAs($admin, 'admin')
        ->post(route('admin.about.update'), [
            'hero_title' => 'Secure About',
            'stats' => [['value' => '99%', 'label' => 'Validated']],
            'comparisons' => [[
                'capability' => 'Security',
                'traditional_text' => 'Manual',
                'bte_text' => 'Automated',
            ]],
            'architectures' => [[
                'step_number' => 1,
                'title' => 'Validate',
                'description' => 'Validate all input',
            ]],
        ])
        ->assertSessionHasNoErrors();

    $about = AboutPage::firstOrFail();
    expect($about->hero_title)->toBe('Secure About')
        ->and($about->stats)->toHaveCount(1)
        ->and($about->comparisons)->toHaveCount(1)
        ->and($about->architectures)->toHaveCount(1);
});

test('SEO settings reject missing fields and unsafe uploads', function () {
    Storage::fake('public');
    $admin = createFormAdmin('manage seo settings');

    $this->actingAs($admin, 'admin')
        ->post(route('admin.seo.store'), [
            'meta_image' => UploadedFile::fake()->create('payload.svg', 10, 'image/svg+xml'),
        ])
        ->assertSessionHasErrors(['page_name', 'meta_image']);

    $this->assertDatabaseCount('seo_settings', 0);
    expect(Storage::disk('public')->allFiles())->toBeEmpty();
});

test('blog validation enforces required fields and unique normalized slugs', function () {
    Blog::create(['title' => 'Existing', 'slug' => 'existing-post']);
    $admin = createFormAdmin('manage blogs');

    $this->actingAs($admin, 'admin')
        ->post(route('admin.blogs.store'), ['title' => '', 'slug' => ''])
        ->assertSessionHasErrors(['title', 'slug']);

    $this->post(route('admin.blogs.store'), [
        'title' => 'Duplicate',
        'slug' => 'Existing Post',
    ])->assertSessionHasErrors(['slug']);

    $this->assertDatabaseCount('blogs', 1);
});

test('blog uploads reject oversized images and executable extensions', function () {
    Storage::fake('public');
    $admin = createFormAdmin('manage blogs');

    $this->actingAs($admin, 'admin')
        ->post(route('admin.blogs.store'), [
            'title' => 'Unsafe upload',
            'slug' => 'unsafe-upload',
            'image' => UploadedFile::fake()->create('oversized.jpg', 6000, 'image/jpeg'),
            'meta_image' => UploadedFile::fake()->create('payload.php', 10, 'application/x-httpd-php'),
        ])
        ->assertSessionHasErrors(['image', 'meta_image']);

    $this->assertDatabaseMissing('blogs', ['slug' => 'unsafe-upload']);
    expect(Storage::disk('public')->allFiles())->toBeEmpty();
});

test('a valid blog update keeps its slug and safely replaces images', function () {
    Storage::fake('public');
    Storage::disk('public')->put('blogs/old.jpg', 'old');
    Storage::disk('public')->put('seo/old-meta.jpg', 'old');
    $blog = Blog::create([
        'title' => 'Original',
        'slug' => 'original',
        'image' => 'blogs/old.jpg',
        'meta_image' => 'seo/old-meta.jpg',
    ]);
    $admin = createFormAdmin('manage blogs');

    $this->actingAs($admin, 'admin')
        ->post(route('admin.blogs.update', $blog), [
            'title' => 'Updated',
            'slug' => 'Original',
            'image' => createPngUpload('new.png'),
            'meta_image' => createPngUpload('new-meta.png'),
        ])
        ->assertRedirect(route('admin.blogs.index'));

    $blog->refresh();
    expect($blog->title)->toBe('Updated')
        ->and($blog->slug)->toBe('original');
    Storage::disk('public')->assertExists([$blog->image, $blog->meta_image]);
    Storage::disk('public')->assertMissing(['blogs/old.jpg', 'seo/old-meta.jpg']);
});

test('a failed blog update leaves the existing record and image unchanged', function () {
    Storage::fake('public');
    Storage::disk('public')->put('blogs/original.jpg', 'old');
    Blog::create(['title' => 'Taken', 'slug' => 'taken']);
    $blog = Blog::create(['title' => 'Original', 'slug' => 'original', 'image' => 'blogs/original.jpg']);
    $admin = createFormAdmin('manage blogs');

    $this->actingAs($admin, 'admin')
        ->post(route('admin.blogs.update', $blog), [
            'title' => 'Changed',
            'slug' => 'taken',
            'image' => createPngUpload('replacement.png'),
        ])
        ->assertSessionHasErrors(['slug']);

    expect($blog->refresh()->title)->toBe('Original')
        ->and($blog->image)->toBe('blogs/original.jpg');
    Storage::disk('public')->assertExists('blogs/original.jpg');
    expect(Storage::disk('public')->allFiles('blogs'))->toBe(['blogs/original.jpg']);
});

test('pricing plans reject invalid URLs and nested feature data', function () {
    $admin = createFormAdmin('manage pricing plans');

    $this->actingAs($admin, 'admin')
        ->post(route('admin.pricing.store'), [
            'name' => 'Invalid plan',
            'button_link' => 'javascript:alert(1)',
            'monthly_price' => -1,
            'features' => [['is_included' => 'on']],
        ])
        ->assertSessionHasErrors(['button_link', 'monthly_price', 'features.0.name']);

    $this->assertDatabaseCount('pricing_plans', 0);
    $this->assertDatabaseCount('pricing_plan_features', 0);
});

test('pricing plans persist valid features atomically', function () {
    $admin = createFormAdmin('manage pricing plans');

    $this->actingAs($admin, 'admin')
        ->post(route('admin.pricing.store'), [
            'name' => 'Secure plan',
            'monthly_price' => '19.99',
            'annual_price' => '199.99',
            'button_link' => 'https://example.com/subscribe',
            'features' => [
                ['name' => 'Included', 'is_included' => 'on'],
                ['name' => 'Excluded'],
            ],
        ])
        ->assertRedirect(route('admin.pricing.index'));

    $plan = PricingPlan::with('features')->firstOrFail();
    expect($plan->features)->toHaveCount(2)
        ->and((bool) $plan->features[0]->is_included)->toBeTrue()
        ->and((bool) $plan->features[1]->is_included)->toBeFalse();
});

test('pricing and feature page settings validate nested JSON data', function () {
    $admin = createFormAdmin(['manage pricing page settings', 'manage feature page settings']);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.pricing_page.update'), [
            'faqs' => [['question' => 'Missing answer']],
        ])
        ->assertSessionHasErrors(['faqs.0.answer']);

    $this->post(route('admin.feature_page.update'), [
        'features_json' => [['title' => 'Missing description']],
        'testimonials_json' => [['quote' => 'Missing author']],
    ])->assertSessionHasErrors(['features_json.0.description', 'testimonials_json.0.author_name']);

    $this->assertDatabaseCount('pricing_page_settings', 0);
    $this->assertDatabaseCount('feature_page_settings', 0);
});

test('pricing and feature page settings accept valid nested data', function () {
    $admin = createFormAdmin(['manage pricing page settings', 'manage feature page settings']);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.pricing_page.update'), [
            'hero_title_normal' => 'Pricing',
            'faqs' => [['question' => 'Is it secure?', 'answer' => 'Yes']],
        ])
        ->assertSessionHasNoErrors();

    $this->post(route('admin.feature_page.update'), [
        'hero_title_normal' => 'Features',
        'features_json' => [['icon' => 'shield', 'title' => 'Secure', 'description' => 'Validated']],
        'testimonials_json' => [[
            'quote' => 'Excellent',
            'author_initials' => 'AB',
            'author_name' => 'Admin Builder',
            'author_role' => 'Engineer',
        ]],
    ])->assertSessionHasNoErrors();

    expect(PricingPageSetting::firstOrFail()->faqs)->toHaveCount(1)
        ->and(FeaturePageSetting::firstOrFail()->features_json)->toHaveCount(1);
});

test('admin creation validates email password and admin guard role', function () {
    $admin = createFormAdmin('create admin');
    Role::create(['name' => 'Editor', 'guard_name' => 'admin']);
    Role::create(['name' => 'Web Role', 'guard_name' => 'web']);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.role-permission.admin.store'), [
            'name' => 'Invalid Admin',
            'email' => 'invalid',
            'password' => 'short',
            'role' => 'Web Role',
        ])
        ->assertSessionHasErrors(['email', 'password', 'role']);

    $this->post(route('admin.role-permission.admin.store'), [
        'name' => 'Valid Admin',
        'email' => 'valid-admin@example.com',
        'password' => 'Password123',
        'role' => 'Editor',
    ])->assertRedirect(route('admin.role-permission.index'));

    expect(Admin::where('email', 'valid-admin@example.com')->firstOrFail()->hasRole('Editor', 'admin'))->toBeTrue();
});

test('role management validates unique names and admin guard permissions', function () {
    $admin = createFormAdmin('manage roles');
    $role = Role::create(['name' => 'Editor', 'guard_name' => 'admin']);
    Role::create(['name' => 'Existing', 'guard_name' => 'admin']);
    Permission::create(['name' => 'web permission', 'guard_name' => 'web']);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.role-permission.role.update', $role), [
            'name' => 'Existing',
            'permissions' => ['web permission'],
        ])
        ->assertSessionHasErrors(['name', 'permissions.0']);

    expect($role->fresh()->name)->toBe('Editor')
        ->and($role->permissions)->toBeEmpty();
});

test('unauthorized admins cannot submit validated forms', function () {
    $admin = Admin::create([
        'name' => 'Unauthorized',
        'email' => 'unauthorized@example.com',
        'password' => Hash::make('Password123'),
    ]);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.blogs.store'), [
            'title' => 'Blocked',
            'slug' => 'blocked',
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('blogs', ['slug' => 'blocked']);
});
