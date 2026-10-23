<?php

use App\Models\Admin;
use App\Models\PricingPageSetting;
use App\Models\PricingPlan;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach (['manage pricing plans', 'manage pricing page settings'] as $permission) {
        Permission::create(['name' => $permission, 'guard_name' => 'admin']);
    }
});

function pricingCmsAdmin(string|array $permissions): Admin
{
    $admin = Admin::create([
        'name' => 'Pricing Editor',
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('Password123'),
    ]);
    $admin->givePermissionTo($permissions);

    return $admin;
}

test('authorized administrators create ordered visible plans and ordered features', function () {
    $response = $this->actingAs(pricingCmsAdmin('manage pricing plans'), 'admin')
        ->post(route('admin.pricing.store'), [
            'name' => 'Growth',
            'title' => 'Growth Engine',
            'monthly_price' => '79.95',
            'annual_price' => '799.50',
            'display_order' => 20,
            'is_active' => '1',
            'is_popular' => '1',
            'popular_label' => 'Recommended',
            'button_text' => 'Create account',
            'button_link' => 'register',
            'features_present' => '1',
            'features' => [
                ['name' => 'Second feature', 'is_included' => '0', 'order_by' => 20],
                ['name' => 'First feature', 'is_included' => '1', 'order_by' => 10],
            ],
        ]);

    $response->assertRedirect(route('admin.pricing.index'))->assertSessionHasNoErrors();
    $plan = PricingPlan::with('features')->firstOrFail();

    expect($plan->monthly_price)->toBe('79.95')
        ->and($plan->annual_price)->toBe('799.50')
        ->and($plan->display_order)->toBe(20)
        ->and($plan->is_active)->toBeTrue()
        ->and($plan->popular_label)->toBe('Recommended')
        ->and($plan->button_link)->toBe('register')
        ->and($plan->features->pluck('name')->all())->toBe(['First feature', 'Second feature'])
        ->and($plan->features[1]->is_included)->toBeFalse();
});

test('updating plan metadata without feature input preserves existing child features', function () {
    $plan = PricingPlan::create([
        'name' => 'Original',
        'monthly_price' => 20,
        'annual_price' => 200,
    ]);
    $feature = $plan->features()->create(['name' => 'Preserved feature', 'order_by' => 10]);

    $this->actingAs(pricingCmsAdmin('manage pricing plans'), 'admin')
        ->post(route('admin.pricing.update', $plan), [
            'name' => 'Updated',
            'monthly_price' => '25.00',
            'annual_price' => '250.00',
            'display_order' => 5,
            'is_active' => '1',
        ])
        ->assertRedirect(route('admin.pricing.index'))
        ->assertSessionHasNoErrors();

    expect($plan->refresh()->name)->toBe('Updated')
        ->and($feature->fresh()->name)->toBe('Preserved feature');
});

test('pricing page settings persist currency calculator FAQ and CTA content', function () {
    $response = $this->actingAs(pricingCmsAdmin('manage pricing page settings'), 'admin')
        ->post(route('admin.pricing_page.update'), [
            'hero_badge_icon' => 'wallet-cards',
            'currency_code' => 'eur',
            'billing_monthly_label' => 'Pay monthly',
            'billing_annual_label' => 'Pay annually',
            'annual_billing_description' => 'Annual totals are billed once per year.',
            'calc_title' => 'External Content Estimate',
            'calc_articles_label' => 'Monthly article assumption',
            'calc_articles_unit' => 'Articles',
            'calc_default_articles' => 20,
            'calc_cost_label' => 'Assumed external unit cost',
            'calc_default_cost_per_article' => '175.50',
            'calc_monthly_result_label' => 'Estimated monthly external cost',
            'calc_annual_result_label' => 'Estimated annual external cost',
            'calc_disclaimer' => 'This is based only on visitor inputs.',
            'faqs' => [['question' => 'Is this a checkout?', 'answer' => 'No. No payment is collected here.']],
            'final_cta_heading' => 'Explore the platform',
            'final_primary_cta_label' => 'Create account',
            'final_primary_cta_destination' => 'register',
            'final_secondary_cta_label' => 'Review features',
            'final_secondary_cta_destination' => 'features',
        ]);

    $response->assertSessionHasNoErrors();
    $setting = PricingPageSetting::firstOrFail();

    expect($setting->currency_code)->toBe('EUR')
        ->and($setting->calc_default_articles)->toBe(20)
        ->and($setting->calc_default_cost_per_article)->toBe('175.50')
        ->and($setting->faqs)->toHaveCount(1)
        ->and($setting->final_primary_cta_destination)->toBe('register');
});

test('strict pricing validation rejects unsafe destinations icons currency and nested keys', function () {
    $plan = PricingPlan::create(['name' => 'Safe plan', 'monthly_price' => 10, 'annual_price' => 100]);
    $planAdmin = pricingCmsAdmin('manage pricing plans');

    $this->actingAs($planAdmin, 'admin')->post(route('admin.pricing.update', $plan), [
        'name' => 'Unsafe plan',
        'monthly_price' => '10.00',
        'annual_price' => '100.00',
        'display_order' => 0,
        'is_active' => '1',
        'button_link' => 'javascript:alert(1)',
        'features' => [['name' => 'Feature', 'is_included' => '1', 'unexpected' => 'value']],
    ])->assertSessionHasErrors(['button_link', 'features.0']);

    expect($plan->refresh()->name)->toBe('Safe plan');

    $this->actingAs(pricingCmsAdmin('manage pricing page settings'), 'admin')
        ->post(route('admin.pricing_page.update'), [
            'hero_badge_icon' => 'script-alert',
            'currency_code' => 'BTC',
            'final_primary_cta_destination' => 'https://malicious.example',
        ])
        ->assertSessionHasErrors(['hero_badge_icon', 'currency_code', 'final_primary_cta_destination']);

    $this->assertDatabaseCount('pricing_page_settings', 0);
});

test('public pricing uses configured order visibility currency copy and internal CTAs', function () {
    PricingPageSetting::create([
        'currency_code' => 'EUR',
        'billing_monthly_label' => 'Monthly option',
        'billing_annual_label' => 'Annual option',
        'calc_articles_label' => 'Article assumption',
        'calc_cost_label' => 'External cost assumption',
        'calc_disclaimer' => 'Input-based estimate only.',
        'faq_empty_state' => 'Questions are being prepared.',
        'final_cta_heading' => 'Safe next steps',
        'final_primary_cta_label' => 'Join now',
        'final_primary_cta_destination' => 'register',
    ]);
    PricingPlan::create([
        'name' => 'Hidden plan',
        'monthly_price' => 1,
        'annual_price' => 10,
        'display_order' => 1,
        'is_active' => false,
    ]);
    PricingPlan::create([
        'name' => 'Displayed second',
        'monthly_price' => 20,
        'annual_price' => 200,
        'display_order' => 20,
        'button_text' => 'Sign in',
        'button_link' => 'login',
    ]);
    PricingPlan::create([
        'name' => 'Displayed first',
        'monthly_price' => 30,
        'annual_price' => 300,
        'display_order' => 10,
        'button_text' => 'Create account',
        'button_link' => 'register',
    ]);

    $this->get(route('pricing'))
        ->assertOk()
        ->assertSeeInOrder(['Displayed first', 'Displayed second'])
        ->assertDontSee('Hidden plan')
        ->assertSee('€30.00')
        ->assertSee('€300.00')
        ->assertSee('Monthly option')
        ->assertSee('Annual option')
        ->assertSee('Article assumption')
        ->assertSee('External cost assumption')
        ->assertSee('Input-based estimate only.')
        ->assertSee('Questions are being prepared.')
        ->assertSee('Safe next steps')
        ->assertSee('href="'.route('register').'"', false)
        ->assertSee('href="'.route('login').'"', false);
});

test('homepage starting price excludes hidden plans and uses the pricing currency', function () {
    PricingPageSetting::create(['currency_code' => 'BDT']);
    PricingPlan::create(['name' => 'Hidden cheap plan', 'monthly_price' => 5, 'annual_price' => 50, 'is_active' => false]);
    PricingPlan::create(['name' => 'Published plan', 'monthly_price' => 25.75, 'annual_price' => 257.50, 'is_active' => true]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('From ৳25.75/mo')
        ->assertDontSee('From ৳5.00/mo');
});

test('legacy plans remain public and retain price-based ordering before order values change', function () {
    PricingPlan::create(['name' => 'Higher legacy plan', 'monthly_price' => 80, 'annual_price' => 800]);
    PricingPlan::create(['name' => 'Lower legacy plan', 'monthly_price' => 40, 'annual_price' => 400]);

    $this->get(route('pricing'))
        ->assertOk()
        ->assertSeeInOrder(['Lower legacy plan', 'Higher legacy plan']);
});
