<?php

use App\Models\Admin;
use App\Models\HomepageSetting;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Permission::create(['name' => 'manage homepage settings', 'guard_name' => 'admin']);
});

function createHomepageAdmin(bool $authorized = true): Admin
{
    $admin = Admin::create([
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('Password123'),
    ]);

    if ($authorized) {
        $admin->givePermissionTo('manage homepage settings');
    }

    return $admin;
}

test('homepage settings require admin authentication and permission', function () {
    $this->get(route('admin.homepage.index'))->assertRedirect();

    $this->actingAs(createHomepageAdmin(false), 'admin')
        ->get(route('admin.homepage.index'))
        ->assertForbidden();
});

test('an authorized admin can create and update the singleton homepage settings', function () {
    $admin = createHomepageAdmin();

    $this->actingAs($admin, 'admin')
        ->post(route('admin.homepage.update'), [
            'hero_badge' => 'First Badge',
            'hero_title' => 'First Heading',
            'primary_cta_label' => 'Join now',
            'primary_cta_destination' => 'register',
            'calculator_heading' => 'First Calculator',
            'final_primary_cta_destination' => 'pricing',
        ])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');

    expect(HomepageSetting::query()->count())->toBe(1)
        ->and(HomepageSetting::query()->firstOrFail()->hero_title)->toBe('First Heading');

    $this->post(route('admin.homepage.update'), [
        'hero_badge' => 'Updated Badge',
        'hero_title' => 'Updated Heading',
        'primary_cta_destination' => 'features',
    ])->assertSessionHasNoErrors();

    expect(HomepageSetting::query()->count())->toBe(1)
        ->and(HomepageSetting::query()->firstOrFail()->hero_title)->toBe('Updated Heading')
        ->and(HomepageSetting::query()->firstOrFail()->primary_cta_destination)->toBe('features');
});

test('homepage settings reject unsafe destinations and preserve values with old input', function () {
    $admin = createHomepageAdmin();
    $setting = HomepageSetting::factory()->create(['hero_title' => 'Stored Heading']);

    $this->actingAs($admin, 'admin')
        ->from(route('admin.homepage.index'))
        ->post(route('admin.homepage.update'), [
            'hero_title' => 'Unsaved Heading',
            'primary_cta_destination' => 'javascript:alert(1)',
        ])
        ->assertRedirect(route('admin.homepage.index'))
        ->assertSessionHasErrors('primary_cta_destination');

    expect($setting->refresh()->hero_title)->toBe('Stored Heading');

    $this->get(route('admin.homepage.index'))
        ->assertOk()
        ->assertSee('value="Unsaved Heading"', false);
});

test('the homepage renders configured copy escaped and resolves only approved internal destinations', function () {
    $script = '<script>alert("cms")</script>';
    HomepageSetting::create([
        'hero_badge' => $script,
        'hero_title' => 'Configured Homepage',
        'hero_highlighted_title' => 'Safely',
        'hero_description' => 'Configured hero description.',
        'primary_cta_label' => 'Explore features',
        'primary_cta_destination' => 'features',
        'secondary_cta_destination' => 'scanner',
        'scanner_heading' => 'Configured Scanner',
        'integrations_heading' => 'Configured Integrations',
        'features_heading' => 'Configured Pillars',
        'calculator_heading' => 'Configured Calculator',
        'calculator_note' => 'Configured estimate note.',
        'final_cta_heading' => 'Configured Final CTA',
        'final_secondary_cta_destination' => 'about',
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee($script)
        ->assertDontSee($script, false)
        ->assertSee('Configured Homepage')
        ->assertSee('Configured Scanner')
        ->assertSee('Configured Integrations')
        ->assertSee('Configured Pillars')
        ->assertSee('Configured Calculator')
        ->assertSee('Configured estimate note.')
        ->assertSee('Configured Final CTA')
        ->assertSee('href="'.route('features').'"', false)
        ->assertSee('href="'.route('home').'#website-scanner"', false)
        ->assertSee('href="'.route('about').'"', false);
});

test('invalid persisted destinations fall back to safe internal routes', function () {
    HomepageSetting::create([
        'primary_cta_destination' => 'javascript:alert(1)',
        'calculator_cta_destination' => 'https://malicious.example',
    ]);

    $response = $this->get(route('home'));

    $response->assertOk()
        ->assertSee('href="'.route('register').'"', false)
        ->assertSee('href="'.route('pricing').'"', false)
        ->assertDontSee('javascript:', false)
        ->assertDontSee('malicious.example', false);
});
