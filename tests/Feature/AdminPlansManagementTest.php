<?php

use App\Models\Admin;
use App\Models\PricingPageSetting;
use App\Models\PricingPlan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach (['view dashboard', 'manage pricing plans', 'manage pricing page settings'] as $permission) {
        Permission::create([
            'name' => $permission,
            'guard_name' => 'admin',
        ]);
    }
});

function createPlansAdmin(array $permissions = []): Admin
{
    $admin = Admin::create([
        'name' => 'Plans Administrator',
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('Password123'),
    ]);

    if ($permissions !== []) {
        $admin->givePermissionTo($permissions);
    }

    return $admin;
}

test('a guest is redirected to the admin login from plans management', function () {
    $this->get(route('admin.pricing.index'))
        ->assertRedirectToRoute('admin.login');

    $this->assertGuest('admin');
});

test('an editor without the pricing permission receives 403', function () {
    $admin = createPlansAdmin();
    $editorRole = Role::create(['name' => 'Editor', 'guard_name' => 'admin']);
    $editorRole->givePermissionTo('view dashboard');
    $admin->assignRole($editorRole);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.pricing.index'))
        ->assertForbidden();

    expect($admin->can('manage pricing plans'))->toBeFalse();
});

test('a super admin can access plans management without a direct permission', function () {
    $admin = createPlansAdmin();
    $superAdminRole = Role::create(['name' => 'Super Admin', 'guard_name' => 'admin']);
    $admin->assignRole($superAdminRole);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.pricing.index'))
        ->assertOk()
        ->assertSee('Plans &amp; Pricing Tiers', false);
});

test('plans management renders real pricing fields ordered features and existing actions', function () {
    PricingPageSetting::create(['currency_code' => 'USD']);
    $activePlan = PricingPlan::create([
        'name' => 'Growth Autopilot',
        'title' => 'Authority growth for teams',
        'description' => 'A persisted marketing tier.',
        'monthly_price' => 99,
        'annual_price' => 990,
        'display_order' => 10,
        'is_active' => true,
        'is_popular' => true,
        'popular_label' => 'Most Popular',
    ]);
    $activePlan->features()->create(['name' => 'Second feature', 'is_included' => false, 'order_by' => 20]);
    $activePlan->features()->create(['name' => 'First feature', 'is_included' => true, 'order_by' => 10]);
    $inactivePlan = PricingPlan::create([
        'name' => 'Archived Starter',
        'monthly_price' => 49,
        'annual_price' => 490,
        'display_order' => 20,
        'is_active' => false,
    ]);
    $admin = createPlansAdmin(['manage pricing plans', 'manage pricing page settings']);

    $response = $this->actingAs($admin, 'admin')->get(route('admin.pricing.index'));

    $response
        ->assertOk()
        ->assertSeeInOrder(['Growth Autopilot', 'Archived Starter'])
        ->assertSeeInOrder(['First feature', 'Second feature'])
        ->assertSee('Plan #'.$activePlan->id)
        ->assertSee('Order 10')
        ->assertSee('A persisted marketing tier.')
        ->assertSee('$99.00')
        ->assertSee('$990.00')
        ->assertSee('Active')
        ->assertSee('Inactive')
        ->assertSee('Most Popular')
        ->assertSee('href="'.route('admin.pricing.create').'"', false)
        ->assertSee('href="'.route('admin.pricing.edit', $activePlan).'"', false)
        ->assertSee('action="'.route('admin.pricing.destroy', $inactivePlan).'"', false)
        ->assertSee('href="'.route('admin.pricing_page.index').'"', false)
        ->assertDontSee('110 Tenants')
        ->assertDontSee('924 Tenants')
        ->assertDontSee('Subscribers')
        ->assertDontSee('Revenue')
        ->assertDontSee('Stripe Products');
});

test('plans management escapes database supplied plan and feature content', function () {
    $unsafe = '<script>alert("plans")</script>';
    $plan = PricingPlan::create([
        'name' => $unsafe,
        'description' => $unsafe,
        'monthly_price' => 10,
        'annual_price' => 100,
    ]);
    $plan->features()->create(['name' => $unsafe, 'is_included' => true, 'order_by' => 0]);
    $admin = createPlansAdmin(['manage pricing plans']);

    $response = $this->actingAs($admin, 'admin')->get(route('admin.pricing.index'));

    $response
        ->assertOk()
        ->assertSee($unsafe)
        ->assertDontSee($unsafe, false);
});

test('plans management renders an honest empty state', function () {
    $admin = createPlansAdmin(['manage pricing plans']);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.pricing.index'))
        ->assertOk()
        ->assertSee('No pricing plans configured')
        ->assertSee('Create pricing plan')
        ->assertDontSee('Starter Plan')
        ->assertDontSee('Growth Autopilot')
        ->assertDontSee('Agency Matrix');
});

test('plans management paginates large catalogs on the server', function () {
    foreach (range(1, 10) as $displayOrder) {
        PricingPlan::create([
            'name' => 'Plan '.$displayOrder,
            'monthly_price' => $displayOrder,
            'annual_price' => $displayOrder * 10,
            'display_order' => $displayOrder,
        ]);
    }
    $admin = createPlansAdmin(['manage pricing plans']);

    $response = $this->actingAs($admin, 'admin')->get(route('admin.pricing.index'));

    $response
        ->assertOk()
        ->assertViewHas('plans', function ($plans): bool {
            return $plans->perPage() === 9
                && $plans->total() === 10
                && $plans->count() === 9;
        })
        ->assertSee('Plan 1')
        ->assertDontSee('Plan 10');
});

test('plans navigation is visible only with the existing pricing permission', function () {
    $authorizedAdmin = createPlansAdmin(['view dashboard', 'manage pricing plans']);

    $this->actingAs($authorizedAdmin, 'admin')
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('href="'.route('admin.pricing.index').'"', false)
        ->assertSee('Plans &amp; Pricing Tiers', false);

    $unauthorizedAdmin = createPlansAdmin(['view dashboard']);

    $this->actingAs($unauthorizedAdmin, 'admin')
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee('href="'.route('admin.pricing.index').'"', false);
});

test('existing pricing CRUD routes retain their registered methods', function () {
    $expectedMethods = [
        'admin.pricing.index' => 'GET',
        'admin.pricing.create' => 'GET',
        'admin.pricing.store' => 'POST',
        'admin.pricing.edit' => 'GET',
        'admin.pricing.update' => 'POST',
        'admin.pricing.destroy' => 'DELETE',
    ];

    foreach ($expectedMethods as $routeName => $method) {
        $route = Route::getRoutes()->getByName($routeName);

        expect($route)->not->toBeNull()
            ->and($route->methods())->toContain($method);
    }
});

test('the public pricing page still controls visibility independently of the admin catalog', function () {
    PricingPlan::create([
        'name' => 'Public Growth',
        'monthly_price' => 50,
        'annual_price' => 500,
        'is_active' => true,
    ]);
    PricingPlan::create([
        'name' => 'Internal Draft',
        'monthly_price' => 25,
        'annual_price' => 250,
        'is_active' => false,
    ]);

    $this->get(route('pricing'))
        ->assertOk()
        ->assertSee('Public Growth')
        ->assertDontSee('Internal Draft');
});
