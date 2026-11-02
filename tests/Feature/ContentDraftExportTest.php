<?php

use App\Enums\AiContentDraftStatus;
use App\Models\AiContentDraft;
use App\Models\AiContentDraftEvent;
use App\Models\AiModel;

test('approved content exports as safe structured HTML and records the action', function () {
    [$owner, $workspace, $brand] = contentStudioTenant();
    $draft = AiContentDraft::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'created_by_user_id' => $owner->id,
        'content' => "# Safe heading\n\n<script>alert('xss')</script>\n\n[unsafe](javascript:alert(1))",
        'faq_entries' => [[
            'question' => '<img src=x onerror=alert(1)>What is safe?',
            'answer' => '<script>bad()</script>Human-reviewed output.',
        ]],
        'status' => AiContentDraftStatus::Approved,
        'approved_at' => now(),
        'approved_by_user_id' => $owner->id,
    ]);

    $response = $this->actingAs($owner)->post(
        route('content-studio.drafts.exports.store', [$workspace, $brand, $draft]),
        ['lock_version' => 0, 'format' => 'html'],
    );

    $response->assertOk()
        ->assertHeader('content-type', 'text/html; charset=UTF-8')
        ->assertHeader('x-content-type-options', 'nosniff')
        ->assertSee('itemscope', false)
        ->assertSee('https://schema.org/FAQPage', false)
        ->assertDontSee('<script', false)
        ->assertDontSee('javascript:', false)
        ->assertDontSee('onerror=', false);

    expect($draft->fresh()->status)->toBe(AiContentDraftStatus::ExportReady)
        ->and($draft->fresh()->lock_version)->toBe(0)
        ->and(AiContentDraftEvent::query()->sole()->context)->toBe(['format' => 'html']);
});

test('approved content exports as copy-ready text and markdown', function (string $format, string $mime, string $expectedText) {
    [$owner, $workspace, $brand] = contentStudioTenant();
    $draft = AiContentDraft::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'created_by_user_id' => $owner->id,
        'content' => "## Reviewed section\n\nUseful copy.",
        'status' => AiContentDraftStatus::Approved,
        'approved_at' => now(),
        'approved_by_user_id' => $owner->id,
    ]);

    $this->actingAs($owner)->post(
        route('content-studio.drafts.exports.store', [$workspace, $brand, $draft]),
        ['lock_version' => 0, 'format' => $format],
    )->assertOk()
        ->assertHeader('content-type', $mime)
        ->assertSee($expectedText);
})->with([
    'plain text' => ['text', 'text/plain; charset=UTF-8', 'Reviewed section'],
    'Markdown' => ['markdown', 'text/markdown; charset=UTF-8', '## Reviewed section'],
]);

test('unapproved and cross-workspace content cannot be exported', function () {
    [$owner, $workspace, $brand] = contentStudioTenant();
    [$attacker] = contentStudioTenant();
    $draft = AiContentDraft::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'created_by_user_id' => $owner->id,
        'status' => AiContentDraftStatus::Draft,
    ]);
    $route = route('content-studio.drafts.exports.store', [$workspace, $brand, $draft]);

    $this->actingAs($owner)->from(route('content-studio.drafts.show', [$workspace, $brand, $draft]))
        ->post($route, ['lock_version' => 0, 'format' => 'html'])
        ->assertSessionHasErrors('status');
    $this->actingAs($attacker)->post($route, ['lock_version' => 0, 'format' => 'html'])
        ->assertNotFound();

    expect($draft->fresh()->status)->toBe(AiContentDraftStatus::Draft)
        ->and(AiContentDraftEvent::query()->count())->toBe(0);
});

test('AI-assisted exports carry an explicit human verification notice', function () {
    [$owner, $workspace, $brand] = contentStudioTenant();
    $model = AiModel::factory()->create();
    $draft = AiContentDraft::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'created_by_user_id' => $owner->id,
        'ai_provider_id' => $model->ai_provider_id,
        'ai_model_id' => $model->id,
        'status' => AiContentDraftStatus::Approved,
        'approved_at' => now(),
        'approved_by_user_id' => $owner->id,
    ]);

    $this->actingAs($owner)->post(
        route('content-studio.drafts.exports.store', [$workspace, $brand, $draft]),
        ['lock_version' => 0, 'format' => 'html'],
    )->assertOk()
        ->assertSee('Human verification required');
});

test('exports never create a public publishing route or mutate publication data', function () {
    expect(collect(app('router')->getRoutes()->getRoutes())
        ->contains(fn ($route): bool => str_contains($route->uri(), 'content-drafts') && str_contains($route->uri(), 'publish')))
        ->toBeFalse();
});
