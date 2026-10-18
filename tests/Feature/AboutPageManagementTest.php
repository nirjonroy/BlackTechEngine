<?php

use App\Models\AboutPage;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Permission::create([
        'name' => 'manage about page',
        'guard_name' => 'admin',
    ]);
});

function createAboutPageAdmin(bool $authorized = true): Admin
{
    $admin = Admin::create([
        'name' => fake()->name(),
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('Password123'),
    ]);

    if ($authorized) {
        $admin->givePermissionTo('manage about page');
    }

    return $admin;
}

test('an authorized admin can create all about page content', function () {
    $admin = createAboutPageAdmin();

    $this->actingAs($admin, 'admin')
        ->post(route('admin.about.update'), [
            'hero_badge' => 'The Future of Search Discovery',
            'hero_title' => 'Why GEO Wins',
            'hero_description' => 'Our mission is autonomous search discovery.',
            'comparison_title' => 'A Head-to-Head Comparison',
            'comparison_description' => 'Compare traditional SEO with GEO.',
            'architecture_badge' => 'Proprietary Pipeline',
            'architecture_title' => 'How the Core Operates',
            'architecture_description' => 'A multi-agent autonomous framework.',
            'core_values_title' => 'Our Core Engineering Principles',
            'core_values_description' => 'What guides every release.',
            'cta_title' => 'Join the Revolution',
            'cta_description' => 'Start building authority today.',
            'cta_btn1_text' => 'Start Free',
            'cta_btn1_link' => 'https://example.com/signup',
            'cta_btn2_text' => 'See Pricing',
            'cta_btn2_link' => 'https://example.com/pricing',
            'stats' => [['value' => '100%', 'label' => 'Zero-Touch Autopilot']],
            'comparisons' => [[
                'capability' => 'Turnaround Speed',
                'traditional_text' => 'Two weeks',
                'bte_text' => 'Continuous pipeline',
            ]],
            'architectures' => [[
                'step_number' => 1,
                'title' => 'Deep Site Ingestion',
                'description' => 'Crawls and analyzes existing content.',
            ]],
            'core_values' => [[
                'icon' => 'shield-check',
                'title' => 'Zero Hallucinations',
                'description' => 'Validate every factual claim.',
            ]],
        ])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');

    $about = AboutPage::with(['stats', 'comparisons', 'architectures', 'coreValues'])->firstOrFail();

    expect($about->hero_badge)->toBe('The Future of Search Discovery')
        ->and($about->architecture_title)->toBe('How the Core Operates')
        ->and($about->core_values_title)->toBe('Our Core Engineering Principles')
        ->and($about->cta_btn2_link)->toBe('https://example.com/pricing')
        ->and($about->stats)->toHaveCount(1)
        ->and($about->comparisons)->toHaveCount(1)
        ->and($about->architectures)->toHaveCount(1)
        ->and($about->coreValues)->toHaveCount(1);
});

test('submitted child collections update existing records add records and remove omitted records', function () {
    $about = AboutPage::create(['hero_title' => 'Original']);
    $editedStat = $about->stats()->create(['value' => '10%', 'label' => 'Edit me', 'order_by' => 0]);
    $removedStat = $about->stats()->create(['value' => '20%', 'label' => 'Remove me', 'order_by' => 1]);
    $admin = createAboutPageAdmin();

    $this->actingAs($admin, 'admin')
        ->post(route('admin.about.update'), [
            'hero_title' => 'Updated',
            'stats_submitted' => '1',
            'stats' => [
                ['id' => $editedStat->id, 'value' => '99%', 'label' => 'Edited in place'],
                ['value' => '35M+', 'label' => 'Added stat'],
            ],
        ])
        ->assertSessionHasNoErrors();

    expect($editedStat->refresh()->value)->toBe('99%')
        ->and($editedStat->label)->toBe('Edited in place')
        ->and($editedStat->order_by)->toBe(0)
        ->and($removedStat->fresh())->toBeNull();
    $this->assertDatabaseHas('about_stats', [
        'about_page_id' => $about->id,
        'value' => '35M+',
        'label' => 'Added stat',
        'order_by' => 1,
    ]);
});

test('empty submitted collections intentionally remove their related records', function () {
    $about = AboutPage::create(['hero_title' => 'Original']);
    $about->stats()->create(['value' => '100%', 'label' => 'Stat', 'order_by' => 0]);
    $about->comparisons()->create([
        'capability' => 'Speed',
        'traditional_text' => 'Slow',
        'bte_text' => 'Fast',
        'order_by' => 0,
    ]);
    $about->architectures()->create([
        'step_number' => 1,
        'title' => 'Step',
        'description' => 'Description',
        'order_by' => 0,
    ]);
    $about->coreValues()->create([
        'icon' => 'shield',
        'title' => 'Value',
        'description' => 'Description',
        'order_by' => 0,
    ]);
    $admin = createAboutPageAdmin();

    $this->actingAs($admin, 'admin')
        ->post(route('admin.about.update'), [
            'stats_submitted' => '1',
            'comparisons_submitted' => '1',
            'architectures_submitted' => '1',
            'core_values_submitted' => '1',
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseCount('about_stats', 0);
    $this->assertDatabaseCount('about_comparisons', 0);
    $this->assertDatabaseCount('about_architectures', 0);
    $this->assertDatabaseCount('about_core_values', 0);
});

test('omitted child collections survive unrelated about page updates', function () {
    $about = AboutPage::create(['hero_title' => 'Original']);
    $stat = $about->stats()->create(['value' => '100%', 'label' => 'Preserved stat', 'order_by' => 0]);
    $comparison = $about->comparisons()->create([
        'capability' => 'Security',
        'traditional_text' => 'Manual',
        'bte_text' => 'Automated',
        'order_by' => 0,
    ]);
    $architecture = $about->architectures()->create([
        'step_number' => 1,
        'title' => 'Preserved step',
        'description' => 'Preserved description',
        'order_by' => 0,
    ]);
    $coreValue = $about->coreValues()->create([
        'icon' => 'lock',
        'title' => 'Preserved value',
        'description' => 'Preserved description',
        'order_by' => 0,
    ]);
    $admin = createAboutPageAdmin();

    $this->actingAs($admin, 'admin')
        ->post(route('admin.about.update'), ['hero_title' => 'Unrelated edit'])
        ->assertSessionHasNoErrors();

    expect($about->refresh()->hero_title)->toBe('Unrelated edit')
        ->and($stat->fresh())->not->toBeNull()
        ->and($comparison->fresh())->not->toBeNull()
        ->and($architecture->fresh())->not->toBeNull()
        ->and($coreValue->fresh())->not->toBeNull();
});

test('invalid about input preserves all saved parent and child data', function () {
    $about = AboutPage::create(['hero_title' => 'Original title', 'cta_btn1_link' => 'https://example.com/original']);
    $coreValue = $about->coreValues()->create([
        'icon' => 'lock',
        'title' => 'Original value',
        'description' => 'Original description',
        'order_by' => 0,
    ]);
    $admin = createAboutPageAdmin();

    $this->actingAs($admin, 'admin')
        ->post(route('admin.about.update'), [
            'hero_title' => 'Rejected title',
            'cta_btn1_link' => 'javascript:alert(1)',
            'core_values_submitted' => '1',
        ])
        ->assertSessionHasErrors(['cta_btn1_link']);

    expect($about->refresh()->hero_title)->toBe('Original title')
        ->and($about->cta_btn1_link)->toBe('https://example.com/original')
        ->and($coreValue->fresh())->not->toBeNull();
});

test('the about form exposes all fields and repopulates submitted values after validation failure', function () {
    $admin = createAboutPageAdmin();

    $this->actingAs($admin, 'admin')
        ->from(route('admin.about.index'))
        ->post(route('admin.about.update'), [
            'architecture_badge' => 'Retained architecture badge',
            'core_values_title' => 'Retained core title',
            'cta_title' => 'Retained CTA title',
            'cta_btn1_link' => 'invalid-url',
            'core_values_submitted' => '1',
            'core_values' => [[
                'icon' => 'shield-check',
                'title' => 'Retained core value',
                'description' => 'Retained core description',
            ]],
        ])
        ->assertRedirect(route('admin.about.index'))
        ->assertSessionHasErrors(['cta_btn1_link']);

    $this->get(route('admin.about.index'))
        ->assertOk()
        ->assertSee('name="architecture_badge"', false)
        ->assertSee('value="Retained architecture badge"', false)
        ->assertSee('name="core_values_title"', false)
        ->assertSee('value="Retained core title"', false)
        ->assertSee('name="core_values[0][title]"', false)
        ->assertSee('value="Retained core value"', false)
        ->assertSee('name="cta_title"', false)
        ->assertSee('value="Retained CTA title"', false);
});

test('an unauthorized admin receives 403 and cannot update about content', function () {
    $about = AboutPage::create(['hero_title' => 'Protected title']);
    $admin = createAboutPageAdmin(authorized: false);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.about.update'), ['hero_title' => 'Unauthorized change'])
        ->assertForbidden();

    expect($about->refresh()->hero_title)->toBe('Protected title');
});
