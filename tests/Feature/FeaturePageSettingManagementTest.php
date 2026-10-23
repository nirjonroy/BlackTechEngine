<?php

use App\Models\Admin;
use App\Models\FeaturePageSetting;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Permission::create(['name' => 'manage feature page settings', 'guard_name' => 'admin']);
});

function featurePageAdmin(bool $authorized = true): Admin
{
    $admin = Admin::create([
        'name' => 'Feature Editor',
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('Password123'),
    ]);

    if ($authorized) {
        $admin->givePermissionTo('manage feature page settings');
    }

    return $admin;
}

test('authorized editors can persist nested feature content with stable identities', function () {
    $response = $this->actingAs(featurePageAdmin(), 'admin')->post(route('admin.feature_page.update'), [
        'features_json' => [
            [
                'id' => 'geo',
                'order' => 90,
                'icon' => 'cpu',
                'title' => 'Configured GEO',
                'description' => 'GEO description.',
                'supporting_points' => [['icon' => 'check', 'title' => 'Direct answers', 'description' => 'Clear response structure.']],
                'highlights' => [],
                'preview_heading' => 'GEO preview',
                'preview_items' => [['icon' => 'search-check', 'label' => 'Answer', 'status' => 'Ready', 'description' => 'Prepared.']],
            ],
            [
                'id' => 'brand_dna',
                'order' => 80,
                'icon' => 'dna',
                'title' => 'Configured Brand DNA',
                'description' => 'Brand description.',
                'supporting_points' => [],
                'highlights' => [['label' => 'Voice', 'value' => 'Reviewed', 'description' => 'Human approved.']],
                'preview_items' => [],
            ],
            [
                'id' => 'additional-custom-workflow',
                'order' => 110,
                'icon' => 'workflow',
                'title' => 'Custom Workflow',
                'description' => 'An extra capability.',
                'supporting_points' => [],
                'footer_label' => 'Optional capability',
            ],
        ],
        'additional_features_heading' => 'More capabilities',
        'calc_title' => 'Workflow estimator',
        'calc_disclaimer' => 'Illustrative guidance only.',
        'final_cta_heading' => 'Choose your next step',
        'final_primary_cta_label' => 'See pricing',
        'final_primary_cta_destination' => 'pricing',
        'final_secondary_cta_label' => 'Learn about us',
        'final_secondary_cta_destination' => 'about',
    ]);

    $response->assertSessionHasNoErrors();
    $setting = FeaturePageSetting::firstOrFail();

    expect(array_column($setting->features_json, 'id'))->toBe([
        'brand_dna', 'geo', 'publishing_calendar', 'social_drip', 'local_seo', 'gap_hunter', 'additional-custom-workflow',
    ])->and($setting->features_json[0]['title'])->toBe('Configured Brand DNA')
        ->and($setting->features_json[1]['title'])->toBe('Configured GEO')
        ->and($setting->features_json[1]['supporting_points'][0]['title'])->toBe('Direct answers')
        ->and($setting->final_primary_cta_destination)->toBe('pricing');
});

test('legacy id-less feature data remains compatible', function () {
    $this->actingAs(featurePageAdmin(), 'admin')->post(route('admin.feature_page.update'), [
        'features_json' => [
            ['icon' => 'shield', 'title' => 'Legacy first', 'description' => 'Legacy one.'],
            ['icon' => 'calendar', 'title' => 'Legacy second', 'description' => 'Legacy two.'],
        ],
    ])->assertSessionHasNoErrors();

    $features = FeaturePageSetting::firstOrFail()->features_json;

    expect($features[0]['id'])->toBe('brand_dna')
        ->and($features[0]['title'])->toBe('Legacy first')
        ->and($features[1]['id'])->toBe('geo')
        ->and($features[1]['title'])->toBe('Legacy second');
});

test('invalid nested content and destinations do not overwrite saved settings', function () {
    $setting = FeaturePageSetting::create(['hero_title_normal' => 'Original heading']);

    $this->actingAs(featurePageAdmin(), 'admin')->from(route('admin.feature_page.index'))->post(route('admin.feature_page.update'), [
        'hero_title_normal' => 'Changed heading',
        'features_json' => [[
            'id' => 'brand_dna',
            'icon' => 'not-a-real-icon',
            'title' => 'Invalid feature',
            'description' => 'Invalid.',
            'supporting_points' => [['title' => 'Bad point', 'unexpected' => 'value']],
        ]],
        'final_primary_cta_destination' => 'javascript:alert(1)',
    ])->assertRedirect(route('admin.feature_page.index'))
        ->assertSessionHasErrors([
            'features_json.0.icon',
            'features_json.0.supporting_points.0',
            'final_primary_cta_destination',
        ]);

    expect($setting->refresh()->hero_title_normal)->toBe('Original heading');
});

test('feature settings require the dedicated permission', function () {
    $admin = featurePageAdmin(false);

    $this->actingAs($admin, 'admin')->get(route('admin.feature_page.index'))->assertForbidden();
    $this->post(route('admin.feature_page.update'))->assertForbidden();
});

test('the admin editor redisplays submitted values after validation errors', function () {
    $admin = featurePageAdmin();

    $this->actingAs($admin, 'admin')->from(route('admin.feature_page.index'))->post(route('admin.feature_page.update'), [
        'features_json' => [['title' => 'Keep this title']],
    ])->assertSessionHasErrors('features_json.0.description');

    $this->get(route('admin.feature_page.index'))
        ->assertOk()
        ->assertSee('Keep this title');
});
