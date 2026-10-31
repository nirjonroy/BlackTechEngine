<?php

use App\Enums\AiContentDraftStatus;
use App\Enums\AiContentLength;
use App\Enums\AiContentType;
use App\Models\Admin;
use App\Models\AiContentDraft;
use App\Models\Brand;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

function contentStudioTenant(?User $user = null): array
{
    $user ??= User::factory()->create();
    $workspace = Workspace::factory()->for($user, 'owner')->create();
    $workspace->members()->attach($user, ['role' => 'owner']);
    $brand = Brand::factory()->for($workspace)->create();

    return [$user, $workspace, $brand];
}

function contentDraftPayload(array $overrides = []): array
{
    return array_merge([
        'content_type' => AiContentType::SeoBlogArticle->value,
        'topic' => 'A safe guide to technical SEO',
        'primary_keyword' => 'technical seo guide',
        'secondary_keywords' => "site audits\nsearch visibility",
        'target_audience' => 'Marketing leaders at growing SaaS companies.',
        'language' => 'English',
        'region' => 'United States',
        'content_length' => AiContentLength::Standard->value,
        'tone' => 'Clear and practical.',
        'seo_title' => 'A Safe Technical SEO Guide',
        'meta_description' => 'Learn a practical and careful approach to technical SEO.',
        'suggested_slug' => 'safe-technical-seo-guide',
        'outline' => "Introduction\nAudit foundations\nConclusion",
        'content' => "# A Safe Guide\n\nReview every claim before publishing.",
        'faq_entries' => [['question' => 'What is technical SEO?', 'answer' => 'It improves how search systems access a site.']],
        'internal_link_opportunities' => 'SEO audit page',
        'external_research_topics' => 'Current search engine documentation',
        'verification_notes' => 'Verify current platform limits',
        'status' => AiContentDraftStatus::Draft->value,
    ], $overrides);
}

test('guests and unverified customers cannot enter the content studio', function () {
    [$user, $workspace, $brand] = contentStudioTenant(User::factory()->unverified()->create());

    $this->get(route('content-studio.index'))->assertRedirectToRoute('login');
    $this->actingAs($user)->get(route('content-studio.drafts.create', [$workspace, $brand]))
        ->assertRedirectToRoute('verification.notice');
});

test('an authorized customer saves views and edits an unpublished draft', function () {
    [$user, $workspace, $brand] = contentStudioTenant();

    $this->actingAs($user)->get(route('content-studio.index'))
        ->assertOk()
        ->assertSee('AI Content Studio');
    $this->actingAs($user)->get(route('content-studio.drafts.create', [$workspace, $brand]))
        ->assertOk()
        ->assertSee('Generate with AI');

    $storeResponse = $this->actingAs($user)->post(
        route('content-studio.drafts.store', [$workspace, $brand]),
        contentDraftPayload([
            'workspace_id' => Workspace::factory()->create()->id,
            'brand_id' => Brand::factory()->create()->id,
            'created_by_user_id' => User::factory()->create()->id,
            'ai_model_id' => 999,
        ]),
    );
    $draft = AiContentDraft::query()->sole();

    $storeResponse->assertRedirectToRoute('content-studio.drafts.show', [$workspace, $brand, $draft]);
    expect($draft->workspace_id)->toBe($workspace->id)
        ->and($draft->brand_id)->toBe($brand->id)
        ->and($draft->created_by_user_id)->toBe($user->id)
        ->and($draft->ai_model_id)->toBeNull()
        ->and($draft->secondary_keywords)->toBe(['site audits', 'search visibility']);

    $this->actingAs($user)->get(route('content-studio.drafts.show', [$workspace, $brand, $draft]))
        ->assertOk()
        ->assertSee('Unpublished draft');
    $this->actingAs($user)->get(route('content-studio.drafts.edit', [$workspace, $brand, $draft]))
        ->assertOk()
        ->assertSee('Draft editor');

    $this->actingAs($user)->patch(
        route('content-studio.drafts.update', [$workspace, $brand, $draft]),
        contentDraftPayload(['seo_title' => 'Updated Human-Reviewed Title']),
    )->assertRedirectToRoute('content-studio.drafts.show', [$workspace, $brand, $draft]);

    expect($draft->fresh()->seo_title)->toBe('Updated Human-Reviewed Title');
});

test('nested bindings hide drafts and brands belonging to another tenant', function () {
    [$owner, $workspace, $brand] = contentStudioTenant();
    [$attacker, $attackerWorkspace, $attackerBrand] = contentStudioTenant();
    $draft = AiContentDraft::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'created_by_user_id' => $owner->id,
    ]);

    $this->actingAs($attacker)->get(route('content-studio.drafts.show', [$workspace, $brand, $draft]))->assertNotFound();
    $this->actingAs($attacker)->get(route('content-studio.drafts.show', [$attackerWorkspace, $attackerBrand, $draft]))->assertNotFound();
    $this->actingAs($attacker)->post(route('content-studio.drafts.store', [$workspace, $brand]), contentDraftPayload())->assertNotFound();
});

test('authorized membership permits drafts without transferring workspace ownership', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    [$unused, $workspace, $brand] = contentStudioTenant($owner);
    $workspace->members()->attach($member, ['role' => 'member']);

    $this->actingAs($member)->post(route('content-studio.drafts.store', [$workspace, $brand]), contentDraftPayload())
        ->assertSessionHasNoErrors();

    expect($workspace->fresh()->owner_id)->toBe($owner->id)
        ->and(AiContentDraft::query()->sole()->created_by_user_id)->toBe($member->id);
});

test('draft output is escaped and protected ownership fields cannot be edited', function () {
    [$user, $workspace, $brand] = contentStudioTenant();
    $dangerous = '<script>alert("draft")</script>';
    $draft = AiContentDraft::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'created_by_user_id' => $user->id,
        'content' => $dangerous,
    ]);

    $this->actingAs($user)->get(route('content-studio.drafts.show', [$workspace, $brand, $draft]))
        ->assertOk()
        ->assertSee($dangerous)
        ->assertDontSee($dangerous, false);

    $this->actingAs($user)->patch(
        route('content-studio.drafts.update', [$workspace, $brand, $draft]),
        contentDraftPayload(['workspace_id' => Workspace::factory()->create()->id, 'created_by_user_id' => 999]),
    )->assertSessionHasNoErrors();

    expect($draft->fresh()->workspace_id)->toBe($workspace->id)
        ->and($draft->fresh()->created_by_user_id)->toBe($user->id);
});

test('an admin guard session does not grant customer content access', function () {
    [$user, $workspace, $brand] = contentStudioTenant();
    $admin = Admin::create(['name' => 'Admin', 'email' => 'studio-admin@example.test', 'password' => Hash::make('password')]);
    Auth::guard('admin')->login($admin);

    $this->get(route('content-studio.drafts.create', [$workspace, $brand]))->assertRedirectToRoute('login');
    $this->assertAuthenticatedAs($admin, 'admin');
    $this->assertGuest('web');
});

test('the content studio has no delete or public publishing routes', function () {
    expect(route('content-studio.index'))->toContain('/content-studio')
        ->and(collect(app('router')->getRoutes()->getRoutes())->contains(fn ($route): bool => in_array('DELETE', $route->methods(), true) && str_contains($route->uri(), 'content-drafts')))->toBeFalse()
        ->and(collect(app('router')->getRoutes()->getRoutes())->contains(fn ($route): bool => str_contains($route->uri(), 'publish')))->toBeFalse();
});
