<?php

use App\Models\Admin;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\FrontendNavigation;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Permission::create(['name' => 'manage frontend navigation', 'guard_name' => 'admin']);
});

function createFrontendNavigationAdmin(bool $authorized = true): Admin
{
    $admin = Admin::create([
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('Password123'),
    ]);

    if ($authorized) {
        $admin->givePermissionTo('manage frontend navigation');
    }

    return $admin;
}

/**
 * @return array<string, mixed>
 */
function validFrontendNavigationPayload(): array
{
    return [
        'header_navigation' => [
            ['label' => 'Pricing last', 'route' => 'pricing', 'order' => 30, 'is_visible' => '1'],
            ['label' => 'Home first', 'route' => 'home', 'order' => 10, 'is_visible' => '1'],
            ['label' => 'Hidden features', 'route' => 'features', 'order' => 20, 'is_visible' => '0'],
        ],
        'footer_description' => 'Configured footer description.',
        'footer_columns' => [
            [
                'title' => 'Second column',
                'order' => 20,
                'is_visible' => '1',
                'links' => [
                    ['label' => 'Second link', 'route' => 'about', 'order' => 20, 'is_visible' => '1'],
                    ['label' => 'First link', 'route' => 'blog.index', 'order' => 10, 'is_visible' => '1'],
                ],
            ],
            [
                'title' => 'First column',
                'order' => 10,
                'is_visible' => '1',
                'links' => [
                    ['label' => 'Hidden link', 'route' => 'affiliate', 'order' => 10, 'is_visible' => '0'],
                ],
            ],
        ],
        'footer_copyright' => 'A configured copyright.',
        'facebook_url' => 'https://facebook.com/blacktechengine',
        'x_url' => 'https://x.com/blacktechengine',
        'linkedin_url' => null,
        'github_url' => null,
        'youtube_url' => null,
    ];
}

test('frontend navigation management requires admin authentication and permission', function () {
    $this->get(route('admin.frontend_navigation.index'))->assertRedirect();

    $this->actingAs(createFrontendNavigationAdmin(false), 'admin')
        ->get(route('admin.frontend_navigation.index'))
        ->assertForbidden();

    $this->post(route('admin.frontend_navigation.update'), validFrontendNavigationPayload())
        ->assertForbidden();
});

test('an authorized admin can create and update ordered navigation settings', function () {
    $admin = createFrontendNavigationAdmin();

    $this->actingAs($admin, 'admin')
        ->post(route('admin.frontend_navigation.update'), validFrontendNavigationPayload())
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');

    $setting = SiteSetting::query()->firstOrFail();

    expect(SiteSetting::query()->count())->toBe(1)
        ->and($setting->header_navigation)->toMatchArray([
            ['label' => 'Home first', 'route' => 'home', 'order' => 10, 'is_visible' => true],
            ['label' => 'Hidden features', 'route' => 'features', 'order' => 20, 'is_visible' => false],
            ['label' => 'Pricing last', 'route' => 'pricing', 'order' => 30, 'is_visible' => true],
        ])
        ->and($setting->footer_columns[0]['title'])->toBe('First column')
        ->and($setting->footer_columns[1]['links'][0]['label'])->toBe('First link')
        ->and($setting->facebook_url)->toBe('https://facebook.com/blacktechengine');

    $payload = validFrontendNavigationPayload();
    $payload['footer_description'] = 'Updated description.';

    $this->post(route('admin.frontend_navigation.update'), $payload)->assertSessionHasNoErrors();

    expect(SiteSetting::query()->count())->toBe(1)
        ->and($setting->refresh()->footer_description)->toBe('Updated description.');
});

test('frontend navigation rejects a Facebook URL longer than its database column', function () {
    $setting = SiteSetting::create(['facebook_url' => 'https://example.com/original']);
    $payload = validFrontendNavigationPayload();
    $payload['facebook_url'] = 'https://example.com/'.str_repeat('a', 256);

    $this->actingAs(createFrontendNavigationAdmin(), 'admin')
        ->post(route('admin.frontend_navigation.update'), $payload)
        ->assertSessionHasErrors('facebook_url');

    expect($setting->refresh()->facebook_url)->toBe('https://example.com/original');
});

test('a super admin can manage navigation without a direct permission', function () {
    $admin = createFrontendNavigationAdmin(false);
    $admin->assignRole(Role::create(['name' => 'Super Admin', 'guard_name' => 'admin']));

    $this->actingAs($admin, 'admin')
        ->get(route('admin.frontend_navigation.index'))
        ->assertOk();
});

test('the web guard cannot authorize frontend navigation administration', function () {
    $admin = createFrontendNavigationAdmin();

    $this->actingAs($admin, 'web')
        ->get(route('admin.frontend_navigation.index'))
        ->assertRedirect();

    $this->assertGuest('admin');
});

test('invalid destinations and unsafe social URLs are rejected without changing settings', function () {
    $setting = SiteSetting::create(['footer_description' => 'Stored description.']);
    $payload = validFrontendNavigationPayload();
    $payload['header_navigation'][0]['label'] = 'Unsaved navigation label';
    $payload['header_navigation'][0]['route'] = 'admin.dashboard';
    $payload['header_navigation'][1]['route'] = 'javascript:alert(1)';
    $payload['github_url'] = 'javascript:alert(1)';

    $this->actingAs(createFrontendNavigationAdmin(), 'admin')
        ->from(route('admin.frontend_navigation.index'))
        ->post(route('admin.frontend_navigation.update'), $payload)
        ->assertRedirect(route('admin.frontend_navigation.index'))
        ->assertSessionHasErrors([
            'header_navigation.0.route',
            'header_navigation.1.route',
            'github_url',
        ]);

    $this->get(route('admin.frontend_navigation.index'))
        ->assertOk()
        ->assertSee('value="Unsaved navigation label"', false);

    expect($setting->refresh()->footer_description)->toBe('Stored description.')
        ->and($setting->header_navigation)->toBeNull();
});

test('missing settings render safe desktop and mobile navigation defaults', function () {
    $response = $this->get(route('home'))->assertOk();
    $content = $response->getContent();

    expect(substr_count($content, 'data-navigation-location='))->toBe(2)
        ->and(substr_count($content, '>Home</span>'))->toBeGreaterThanOrEqual(1);

    foreach (FrontendNavigation::DEFAULT_HEADER_LINKS as $link) {
        expect(substr_count($content, 'href="'.route($link['route']).'"'))->toBeGreaterThanOrEqual(2);
    }

    $response->assertSee(FrontendNavigation::DEFAULT_DESCRIPTION)
        ->assertSee('Sign In')
        ->assertSee('Start Autopilot');
});

test('configured ordering visibility escaping and active routes are applied consistently', function () {
    $script = '<script>alert("navigation")</script>';
    $payload = validFrontendNavigationPayload();
    $payload['header_navigation'] = [
        ['label' => 'Pricing second', 'route' => 'pricing', 'order' => 20, 'is_visible' => true],
        ['label' => $script, 'route' => 'blog.index', 'order' => 10, 'is_visible' => true],
        ['label' => 'Never render', 'route' => 'features', 'order' => 30, 'is_visible' => false],
    ];
    $payload['footer_columns'][0]['title'] = $script;
    SiteSetting::create($payload);

    $response = $this->get(route('blog.index'))->assertOk();
    $content = $response->getContent();

    $response->assertSee($script)
        ->assertDontSee($script, false)
        ->assertDontSee('Never render')
        ->assertSee('aria-current="page"', false);

    expect(substr_count($content, e($script)))->toBe(3)
        ->and(strpos($content, '<span>'.e($script).'</span>'))->toBeLessThan(strpos($content, '<span>Pricing second</span>'))
        ->and(strpos($content, '>First column</h4>'))->toBeLessThan(strpos($content, '>'.e($script).'</h4>'))
        ->and(strpos($content, '>First link</a>'))->toBeLessThan(strpos($content, '>Second link</a>'));
});

test('persisted invalid routes and unsafe external URLs never render', function () {
    SiteSetting::create([
        'header_navigation' => [
            ['label' => 'Admin leak', 'route' => 'admin.dashboard', 'order' => 10, 'is_visible' => true],
            ['label' => 'Safe home', 'route' => 'home', 'order' => 20, 'is_visible' => true],
        ],
        'footer_columns' => [[
            'title' => 'Unsafe',
            'order' => 10,
            'is_visible' => true,
            'links' => [['label' => 'Script', 'route' => 'javascript:alert(1)', 'order' => 10, 'is_visible' => true]],
        ]],
        'facebook_url' => 'javascript:alert(1)',
        'linkedin_url' => 'https://linkedin.com/company/blacktechengine',
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('Admin leak')
        ->assertDontSee('Script')
        ->assertDontSee('javascript:alert(1)', false)
        ->assertSee('Safe home')
        ->assertSee('https://linkedin.com/company/blacktechengine', false)
        ->assertSee('rel="noopener noreferrer"', false);
});

test('public pages still load and auth actions remain independent from configured menus', function () {
    SiteSetting::create([
        'header_navigation' => [],
        'footer_columns' => [],
    ]);

    foreach (['home', 'about', 'features', 'pricing', 'blog.index', 'affiliate'] as $routeName) {
        $this->get(route($routeName))->assertOk();
    }

    $this->get(route('home'))
        ->assertSee(route('login'), false)
        ->assertSee(route('register'), false);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertSee(route('dashboard'), false)
        ->assertSee('Log Out')
        ->assertDontSee('Start Autopilot');
});
