<?php

use App\Models\Admin;
use App\Models\AffiliatePageSetting;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Permission::create(['name' => 'manage affiliate page settings', 'guard_name' => 'admin']);
});

function createAffiliateSettingsAdmin(bool $authorized = true): Admin
{
    $admin = Admin::create([
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('Password123'),
    ]);

    if ($authorized) {
        $admin->givePermissionTo('manage affiliate page settings');
    }

    return $admin;
}

/**
 * @return array<string, mixed>
 */
function validAffiliateSettingsPayload(): array
{
    return [
        'hero_badge' => 'Partner preview',
        'hero_title' => 'A Configured Partner Page',
        'hero_highlighted_title' => 'Coming Later',
        'hero_description' => 'Configured hero explanation.',
        'primary_cta_label' => 'Read the status',
        'primary_cta_destination' => 'program-status',
        'secondary_cta_label' => 'See features',
        'secondary_cta_destination' => 'features',
        'hero_disclosure' => 'Additional configured disclosure.',
        'status_heading' => 'Configured Status Heading',
        'status_description' => 'Configured status explanation.',
        'terms_heading' => 'Configured Pending Terms',
        'availability_status' => 'pending_approval',
        'availability_description' => 'Applications remain unavailable.',
        'workflow_heading' => 'Configured Proposed Workflow',
        'workflow_description' => 'The sequence remains informational.',
        'workflow_steps' => [
            ['icon' => 'megaphone', 'title' => 'First Configured Step', 'description' => 'First configured description.'],
            ['icon' => 'file-check-2', 'title' => 'Second Configured Step', 'description' => 'Second configured description.'],
            ['icon' => 'shield-check', 'title' => 'Third Configured Step', 'description' => 'Third configured description.'],
        ],
        'resources_badge' => 'Planned resources',
        'resources_heading' => 'Configured Resource Heading',
        'resources_description' => 'Configured resource description.',
        'resources_availability_status' => 'planned',
        'final_cta_label' => 'Read the blog',
        'final_cta_destination' => 'blog.index',
    ];
}

test('affiliate CMS routes require the admin guard and dedicated permission', function () {
    $this->get(route('admin.affiliate_page.index'))->assertRedirect();

    $admin = createAffiliateSettingsAdmin(false);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.affiliate_page.index'))
        ->assertForbidden();

    $this->post(route('admin.affiliate_page.update'), validAffiliateSettingsPayload())
        ->assertForbidden();

    $this->assertDatabaseCount('affiliate_page_settings', 0);
});

test('a super admin can access the affiliate CMS without a direct permission', function () {
    $admin = createAffiliateSettingsAdmin(false);
    $superAdmin = Role::create(['name' => 'Super Admin', 'guard_name' => 'admin']);
    $admin->assignRole($superAdmin);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.affiliate_page.index'))
        ->assertOk()
        ->assertSee('Affiliate Page Settings');
});

test('an authorized admin can save settings that appear on the public affiliate page', function () {
    $admin = createAffiliateSettingsAdmin();

    $this->actingAs($admin, 'admin')
        ->post(route('admin.affiliate_page.update'), validAffiliateSettingsPayload())
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');

    $setting = AffiliatePageSetting::query()->firstOrFail();
    expect(AffiliatePageSetting::query()->count())->toBe(1)
        ->and($setting->availability_status)->toBe('pending_approval')
        ->and($setting->workflow_steps)->toHaveCount(3)
        ->and($setting->workflow_steps[0]['title'])->toBe('First Configured Step');

    $updatedPayload = validAffiliateSettingsPayload();
    $updatedPayload['status_heading'] = 'Updated Configured Status';

    $this->post(route('admin.affiliate_page.update'), $updatedPayload)
        ->assertSessionHasNoErrors();

    expect(AffiliatePageSetting::query()->count())->toBe(1);

    $this->get(route('affiliate'))
        ->assertOk()
        ->assertSee('A Configured Partner Page')
        ->assertSee('Updated Configured Status')
        ->assertSee('Pending Approval')
        ->assertSeeInOrder(['First Configured Step', 'Second Configured Step', 'Third Configured Step'])
        ->assertSee('Configured Resource Heading')
        ->assertSee('href="'.route('features').'"', false)
        ->assertSee('href="'.route('blog.index').'"', false);
});

test('affiliate settings reject unsafe destinations states icons and incomplete workflows', function () {
    $admin = createAffiliateSettingsAdmin();
    $payload = validAffiliateSettingsPayload();
    $payload['primary_cta_destination'] = 'javascript:alert(1)';
    $payload['availability_status'] = 'open';
    $payload['resources_availability_status'] = 'available';
    $payload['final_cta_destination'] = 'register';
    $payload['workflow_steps'][0]['icon'] = 'badge-dollar-sign';
    array_pop($payload['workflow_steps']);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.affiliate_page.update'), $payload)
        ->assertSessionHasErrors([
            'primary_cta_destination',
            'availability_status',
            'resources_availability_status',
            'final_cta_destination',
            'workflow_steps',
            'workflow_steps.0.icon',
        ]);

    $this->assertDatabaseCount('affiliate_page_settings', 0);
});

test('the affiliate admin form recovers old workflow input after validation fails', function () {
    $admin = createAffiliateSettingsAdmin();
    $payload = validAffiliateSettingsPayload();
    $payload['workflow_steps'][1]['title'] = 'Unsaved Step Title';
    $payload['availability_status'] = 'operational';

    $this->actingAs($admin, 'admin')
        ->from(route('admin.affiliate_page.index'))
        ->post(route('admin.affiliate_page.update'), $payload)
        ->assertRedirect(route('admin.affiliate_page.index'))
        ->assertSessionHasErrors('availability_status');

    $this->get(route('admin.affiliate_page.index'))
        ->assertOk()
        ->assertSee('value="Unsaved Step Title"', false);
});

test('persisted unsafe affiliate settings are escaped and fall back to safe presentation values', function () {
    $script = '<script>alert("affiliate-cms")</script>';
    AffiliatePageSetting::create([
        'hero_badge' => $script,
        'primary_cta_destination' => 'javascript:alert(1)',
        'secondary_cta_destination' => 'https://unsafe.example',
        'availability_status' => 'open',
        'resources_availability_status' => 'available',
        'final_cta_label' => 'Unsafe Final Destination',
        'final_cta_destination' => 'register',
        'workflow_steps' => [
            ['icon' => 'badge-dollar-sign', 'title' => $script, 'description' => $script],
            ['icon' => 'clipboard-check', 'title' => 'Second', 'description' => 'Second description'],
            ['icon' => 'link-2', 'title' => 'Third', 'description' => 'Third description'],
        ],
    ]);

    $response = $this->get(route('affiliate'));

    $response->assertOk()
        ->assertSee($script)
        ->assertDontSee($script, false)
        ->assertSee('data-lucide="bell-ring"', false)
        ->assertDontSee('data-lucide="badge-dollar-sign"', false)
        ->assertSee('Not Open Yet')
        ->assertSee('Not Available Yet')
        ->assertSee('href="#program-status"', false)
        ->assertSee('href="'.route('register').'"', false)
        ->assertSee('href="'.route('features').'" aria-label="Affiliate informational link"', false)
        ->assertDontSee('javascript:', false)
        ->assertDontSee('unsafe.example', false);
});

test('affiliate CMS remains informational and introduces no enrollment or earnings features', function () {
    AffiliatePageSetting::factory()->create();

    $response = $this->get(route('affiliate'));

    $response->assertOk()
        ->assertSee('Standard account registration is not affiliate enrollment.')
        ->assertSee('Affiliate applications are not currently available.')
        ->assertDontSee('affiliateRefSlider', false)
        ->assertDontSee('affiliateMonthlyEarnings', false)
        ->assertDontSee('personalized tracking link')
        ->assertDontSee('30% For Life')
        ->assertDontSee('$742.50');
});
