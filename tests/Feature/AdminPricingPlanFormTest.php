<?php

use App\Models\Admin;
use App\Models\PricingPlan;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Permission::create([
        'name' => 'manage pricing plans',
        'guard_name' => 'admin',
    ]);
});

function createPricingFormAdmin(bool $authorized = true): Admin
{
    $admin = Admin::create([
        'name' => 'Pricing Form Admin',
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('Password123'),
    ]);

    if ($authorized) {
        $admin->givePermissionTo('manage pricing plans');
    }

    return $admin;
}

test('guests are redirected to the admin login from create and edit forms', function () {
    $plan = PricingPlan::create(['name' => 'Protected plan']);

    $this->get(route('admin.pricing.create'))->assertRedirectToRoute('admin.login');
    $this->get(route('admin.pricing.edit', $plan))->assertRedirectToRoute('admin.login');

    $this->assertGuest('admin');
});

test('a regular web user cannot use their session to access pricing plan forms', function () {
    $user = User::factory()->create();
    $plan = PricingPlan::create(['name' => 'Protected plan']);

    $this->actingAs($user)
        ->get(route('admin.pricing.create'))
        ->assertRedirectToRoute('admin.login');

    $this->get(route('admin.pricing.edit', $plan))->assertRedirectToRoute('admin.login');
    $this->assertGuest('admin');
});

test('an unauthorized editor receives 403 from create and edit forms', function () {
    $admin = createPricingFormAdmin(false);
    $editorRole = Role::create(['name' => 'Editor', 'guard_name' => 'admin']);
    $admin->assignRole($editorRole);
    $plan = PricingPlan::create(['name' => 'Protected plan']);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.pricing.create'))
        ->assertForbidden();

    $this->get(route('admin.pricing.edit', $plan))->assertForbidden();
});

test('a super admin can render create and edit forms without a direct permission', function () {
    $admin = createPricingFormAdmin(false);
    $superAdminRole = Role::create(['name' => 'Super Admin', 'guard_name' => 'admin']);
    $admin->assignRole($superAdminRole);
    $plan = PricingPlan::create(['name' => 'Super plan']);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.pricing.create'))
        ->assertOk()
        ->assertSee('Create Pricing Plan');

    $this->get(route('admin.pricing.edit', $plan))
        ->assertOk()
        ->assertSee('Edit Super plan');
});

test('create form uses the shared admin layout and the existing csrf protected store route', function () {
    $admin = createPricingFormAdmin();

    $response = $this->actingAs($admin, 'admin')->get(route('admin.pricing.create'));

    $response
        ->assertOk()
        ->assertSee('BlackTech')
        ->assertSee('Admin')
        ->assertSee('action="'.route('admin.pricing.store').'"', false)
        ->assertSee('method="POST"', false)
        ->assertSee('name="_token"', false)
        ->assertSee('href="'.route('admin.pricing.index').'"', false)
        ->assertSee('name="monthly_price"', false)
        ->assertSee('name="annual_price"', false)
        ->assertSee('name="features_present" value="1"', false)
        ->assertSee('name="meta_image"', false)
        ->assertDontSee('Stripe price ID')
        ->assertDontSee('AI credits');
});

test('edit form prefills and escapes every persisted plan section in feature order', function () {
    $unsafe = '<script>alert("tier")</script>';
    $plan = PricingPlan::create([
        'name' => $unsafe,
        'badge' => 'For teams',
        'title' => 'Authority Engine',
        'description' => $unsafe,
        'monthly_price' => 79.95,
        'annual_price' => 799.50,
        'display_order' => 12,
        'is_active' => false,
        'is_popular' => true,
        'popular_label' => 'Recommended',
        'button_text' => 'Create account',
        'button_link' => 'register',
        'seo_title' => 'SEO plan title',
        'meta_description' => 'Meta plan description',
    ]);
    $plan->features()->create(['name' => 'Second feature', 'is_included' => false, 'order_by' => 20]);
    $plan->features()->create(['name' => 'First feature', 'is_included' => true, 'order_by' => 10]);
    $admin = createPricingFormAdmin();

    $response = $this->actingAs($admin, 'admin')->get(route('admin.pricing.edit', $plan));

    $response
        ->assertOk()
        ->assertSee($unsafe)
        ->assertDontSee($unsafe, false)
        ->assertSeeInOrder(['First feature', 'Second feature'])
        ->assertSee('value="79.95"', false)
        ->assertSee('value="799.50"', false)
        ->assertSee('value="12"', false)
        ->assertSee('value="Recommended"', false)
        ->assertSee('<option value="register" selected>Create account</option>', false)
        ->assertSee('SEO plan title')
        ->assertSee('Meta plan description')
        ->assertSee('action="'.route('admin.pricing.update', $plan).'"', false)
        ->assertDontSee('name="id"', false)
        ->assertDontSee('name="created_at"', false)
        ->assertDontSee('name="updated_at"', false);
});

test('validation restores submitted plan and feature input on the create form', function () {
    $admin = createPricingFormAdmin();

    $response = $this->actingAs($admin, 'admin')
        ->followingRedirects()
        ->from(route('admin.pricing.create'))
        ->post(route('admin.pricing.store'), [
            'name' => '',
            'badge' => 'Restored badge',
            'monthly_price' => '12.34',
            'annual_price' => '123.45',
            'display_order' => 4,
            'is_active' => '0',
            'is_popular' => '0',
            'features_present' => '1',
            'features' => [
                3 => ['name' => 'Restored feature', 'is_included' => '1', 'order_by' => 7],
            ],
        ]);

    $response
        ->assertOk()
        ->assertSeeText('Please correct the highlighted fields.')
        ->assertSee('value="Restored badge"', false)
        ->assertSee('value="12.34"', false)
        ->assertSee('value="123.45"', false)
        ->assertSee('value="Restored feature"', false)
        ->assertSee('data-next-index="4"', false);

    $this->assertDatabaseCount('pricing_plans', 0);
});

test('prices with more than two decimal places are rejected without changing stored data', function () {
    $admin = createPricingFormAdmin();

    $this->actingAs($admin, 'admin')
        ->post(route('admin.pricing.store'), [
            'name' => 'Invalid precision',
            'monthly_price' => '10.999',
            'annual_price' => '100.001',
            'display_order' => 0,
            'is_active' => '1',
            'is_popular' => '0',
        ])
        ->assertSessionHasErrors(['monthly_price', 'annual_price']);

    $this->assertDatabaseCount('pricing_plans', 0);

    $plan = PricingPlan::create([
        'name' => 'Preserved plan',
        'monthly_price' => 10,
        'annual_price' => 100,
    ]);

    $this->post(route('admin.pricing.update', $plan), [
        'name' => 'Changed plan',
        'monthly_price' => '11.999',
        'annual_price' => '111.999',
        'display_order' => 0,
        'is_active' => '1',
        'is_popular' => '0',
    ])->assertSessionHasErrors(['monthly_price', 'annual_price']);

    expect($plan->refresh()->name)->toBe('Preserved plan')
        ->and($plan->monthly_price)->toBe('10.00')
        ->and($plan->annual_price)->toBe('100.00');
});

test('update persists unchecked states and replaces features in their configured order', function () {
    $plan = PricingPlan::create([
        'name' => 'Existing plan',
        'monthly_price' => 50,
        'annual_price' => 500,
        'is_active' => true,
        'is_popular' => true,
    ]);
    $oldFeature = $plan->features()->create(['name' => 'Old feature', 'is_included' => true, 'order_by' => 0]);
    $admin = createPricingFormAdmin();

    $this->actingAs($admin, 'admin')
        ->post(route('admin.pricing.update', $plan), [
            'name' => 'Updated plan',
            'monthly_price' => '55.00',
            'annual_price' => '550.00',
            'display_order' => 8,
            'is_active' => '0',
            'is_popular' => '0',
            'features_present' => '1',
            'features' => [
                ['name' => 'Displayed second', 'is_included' => '0', 'order_by' => 20],
                ['name' => 'Displayed first', 'is_included' => '1', 'order_by' => 10],
            ],
        ])
        ->assertRedirect(route('admin.pricing.index'))
        ->assertSessionHasNoErrors();

    $plan->refresh()->load('features');

    expect($plan->is_active)->toBeFalse()
        ->and($plan->is_popular)->toBeFalse()
        ->and($plan->features->pluck('name')->all())->toBe(['Displayed first', 'Displayed second'])
        ->and($plan->features[1]->is_included)->toBeFalse()
        ->and($oldFeature->fresh())->toBeNull();
});
