<?php

use App\Enums\AiContentDraftStatus;
use App\Enums\AiContentType;
use App\Models\AiContentDraft;
use App\Models\AiContentDraftEvent;
use App\Models\AiContentDraftRevision;
use App\Models\User;

test('each content type follows the owner-approved review lifecycle', function (AiContentType $contentType) {
    $this->freezeTime();
    [$owner, $workspace, $brand] = contentStudioTenant();
    $draft = AiContentDraft::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'created_by_user_id' => $owner->id,
        'content_type' => $contentType,
        'content' => '# Review-ready content',
    ]);

    $this->actingAs($owner)->post(
        route('content-studio.drafts.review-requests.store', [$workspace, $brand, $draft]),
        ['lock_version' => 0, 'notes' => 'Please review current claims.'],
    )->assertRedirectToRoute('content-studio.drafts.show', [$workspace, $brand, $draft]);

    $this->actingAs($owner)->post(
        route('content-studio.drafts.approval.store', [$workspace, $brand, $draft]),
        ['lock_version' => 1, 'notes' => 'Claims checked.'],
    )->assertRedirectToRoute('content-studio.drafts.show', [$workspace, $brand, $draft]);

    $draft->refresh();
    $events = $draft->editorialEvents()->get();

    expect($draft->status)->toBe(AiContentDraftStatus::Approved)
        ->and($draft->lock_version)->toBe(2)
        ->and($draft->approved_by_user_id)->toBe($owner->id)
        ->and($draft->approved_at)->not->toBeNull()
        ->and($events->pluck('created_at')->unique()->count())->toBe(1)
        ->and($events->pluck('action')->all())->toBe(['approved', 'review_submitted']);
})->with([
    'SEO article' => AiContentType::SeoBlogArticle,
    'GEO explanatory article' => AiContentType::GeoExplanatoryArticle,
    'FAQ content' => AiContentType::FrequentlyAskedQuestions,
    'landing page' => AiContentType::LandingPage,
]);

test('a workspace member can request review but cannot approve it', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    [, $workspace, $brand] = contentStudioTenant($owner);
    $workspace->members()->attach($member, ['role' => 'member']);
    $draft = AiContentDraft::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'created_by_user_id' => $member->id,
        'content' => 'Member-authored content.',
    ]);

    $this->actingAs($member)->post(
        route('content-studio.drafts.review-requests.store', [$workspace, $brand, $draft]),
        ['lock_version' => 0],
    )->assertSessionHasNoErrors();

    $this->actingAs($member)->post(
        route('content-studio.drafts.approval.store', [$workspace, $brand, $draft]),
        ['lock_version' => 1],
    )->assertNotFound();

    expect($draft->fresh()->status)->toBe(AiContentDraftStatus::NeedsReview)
        ->and($workspace->fresh()->owner_id)->toBe($owner->id);
});

test('only the workspace owner has the approval abilities', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $outsider = User::factory()->create();
    [, $workspace, $brand] = contentStudioTenant($owner);
    $workspace->members()->attach($member, ['role' => 'member']);
    $draft = AiContentDraft::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'created_by_user_id' => $member->id,
    ]);

    expect($owner->can('approve', $draft))->toBeTrue()
        ->and($owner->can('requestChanges', $draft))->toBeTrue()
        ->and($member->can('approve', $draft))->toBeFalse()
        ->and($member->can('requestChanges', $draft))->toBeFalse()
        ->and($outsider->can('approve', $draft))->toBeFalse();
});

test('review decisions are hidden across workspace boundaries', function () {
    [$owner, $workspace, $brand] = contentStudioTenant();
    [$attacker] = contentStudioTenant();
    $draft = AiContentDraft::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'created_by_user_id' => $owner->id,
        'content' => 'Private content.',
        'status' => AiContentDraftStatus::NeedsReview,
    ]);

    $this->actingAs($attacker)->post(
        route('content-studio.drafts.approval.store', [$workspace, $brand, $draft]),
        ['lock_version' => 0],
    )->assertNotFound();

    expect($draft->fresh()->status)->toBe(AiContentDraftStatus::NeedsReview)
        ->and(AiContentDraftEvent::query()->count())->toBe(0);
});

test('an owner can request changes with sanitized feedback and the member can resubmit', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    [, $workspace, $brand] = contentStudioTenant($owner);
    $workspace->members()->attach($member, ['role' => 'member']);
    $draft = AiContentDraft::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'created_by_user_id' => $member->id,
        'content' => 'Review this.',
        'status' => AiContentDraftStatus::NeedsReview,
    ]);

    $this->actingAs($owner)->post(
        route('content-studio.drafts.change-requests.store', [$workspace, $brand, $draft]),
        ['lock_version' => 0, 'notes' => '<script>bad()</script> Add a cited source.'],
    )->assertSessionHasNoErrors();

    $draft->refresh();
    expect($draft->status)->toBe(AiContentDraftStatus::Draft)
        ->and($draft->review_feedback)->toBe('bad() Add a cited source.')
        ->and($draft->approved_by_user_id)->toBeNull();

    $this->actingAs($member)->post(
        route('content-studio.drafts.review-requests.store', [$workspace, $brand, $draft]),
        ['lock_version' => 1],
    )->assertSessionHasNoErrors();

    expect($draft->fresh()->status)->toBe(AiContentDraftStatus::NeedsReview);
});

test('equal timestamps retain deterministic change request and revision history order', function () {
    $this->freezeTime();
    [$owner, $workspace, $brand] = contentStudioTenant();
    $draft = AiContentDraft::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'created_by_user_id' => $owner->id,
        'content' => 'Initial content.',
    ]);

    $this->actingAs($owner)->post(
        route('content-studio.drafts.review-requests.store', [$workspace, $brand, $draft]),
        ['lock_version' => 0],
    )->assertSessionHasNoErrors();

    $this->actingAs($owner)->post(
        route('content-studio.drafts.change-requests.store', [$workspace, $brand, $draft]),
        ['lock_version' => 1, 'notes' => 'Add a source.'],
    )->assertSessionHasNoErrors();

    $this->actingAs($owner)->patch(
        route('content-studio.drafts.update', [$workspace, $brand, $draft]),
        contentDraftPayload(['content' => 'Revised with a source.', 'lock_version' => 2]),
    )->assertSessionHasNoErrors();

    $draft->refresh();
    $events = $draft->editorialEvents()->get();
    $revisions = $draft->revisions()->get();

    expect($events->pluck('created_at')->unique()->count())->toBe(1)
        ->and($events->pluck('action')->all())->toBe([
            'content_edited',
            'changes_requested',
            'review_submitted',
        ])
        ->and($events->pluck('lock_version')->all())->toBe([3, 2, 1])
        ->and($revisions->pluck('revision_number')->all())->toBe([1])
        ->and($draft->current_revision)->toBe(1)
        ->and($draft->lock_version)->toBe(3);
});

test('material edits create revisions and invalidate prior approval', function () {
    [$owner, $workspace, $brand] = contentStudioTenant();
    $draft = AiContentDraft::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'created_by_user_id' => $owner->id,
        'content' => 'Approved original.',
        'status' => AiContentDraftStatus::Approved,
        'lock_version' => 4,
        'current_revision' => 1,
        'approved_at' => now(),
        'approved_by_user_id' => $owner->id,
    ]);

    $this->actingAs($owner)->patch(
        route('content-studio.drafts.update', [$workspace, $brand, $draft]),
        contentDraftPayload(['content' => 'Materially edited content.', 'lock_version' => 4]),
    )->assertSessionHasNoErrors();

    $draft->refresh();
    $revision = AiContentDraftRevision::query()->sole();

    expect($draft->status)->toBe(AiContentDraftStatus::NeedsReview)
        ->and($draft->lock_version)->toBe(5)
        ->and($draft->current_revision)->toBe(2)
        ->and($draft->approved_at)->toBeNull()
        ->and($draft->approved_by_user_id)->toBeNull()
        ->and($revision->snapshot['content'])->toBe('Materially edited content.')
        ->and($revision->changed_fields)->toContain('content');
});

test('editing content under review returns it to draft for explicit resubmission', function () {
    [$owner, $workspace, $brand] = contentStudioTenant();
    $draft = AiContentDraft::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'created_by_user_id' => $owner->id,
        'status' => AiContentDraftStatus::NeedsReview,
        'review_submitted_at' => now(),
    ]);

    $this->actingAs($owner)->patch(
        route('content-studio.drafts.update', [$workspace, $brand, $draft]),
        contentDraftPayload(['content' => 'Changed during review.', 'lock_version' => 0]),
    )->assertSessionHasNoErrors();

    expect($draft->fresh()->status)->toBe(AiContentDraftStatus::Draft)
        ->and($draft->fresh()->review_submitted_at)->toBeNull();
});

test('optimistic locking rejects stale edits without overwriting the latest content', function () {
    [$owner, $workspace, $brand] = contentStudioTenant();
    $draft = AiContentDraft::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'created_by_user_id' => $owner->id,
        'content' => 'Original.',
    ]);

    $this->actingAs($owner)->patch(
        route('content-studio.drafts.update', [$workspace, $brand, $draft]),
        contentDraftPayload(['content' => 'First editor wins.', 'lock_version' => 0]),
    )->assertSessionHasNoErrors();

    $this->actingAs($owner)->from(route('content-studio.drafts.edit', [$workspace, $brand, $draft]))->patch(
        route('content-studio.drafts.update', [$workspace, $brand, $draft]),
        contentDraftPayload(['content' => 'Stale overwrite.', 'lock_version' => 0]),
    )->assertSessionHasErrors('lock_version');

    expect($draft->fresh()->content)->toBe('First editor wins.')
        ->and($draft->fresh()->lock_version)->toBe(1)
        ->and(AiContentDraftRevision::query()->count())->toBe(1);
});

test('duplicate review submission is rejected without a duplicate event', function () {
    [$owner, $workspace, $brand] = contentStudioTenant();
    $draft = AiContentDraft::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'created_by_user_id' => $owner->id,
        'content' => 'Ready.',
    ]);
    $route = route('content-studio.drafts.review-requests.store', [$workspace, $brand, $draft]);

    $this->actingAs($owner)->post($route, ['lock_version' => 0])->assertSessionHasNoErrors();
    $this->actingAs($owner)->from(route('content-studio.drafts.show', [$workspace, $brand, $draft]))
        ->post($route, ['lock_version' => 0])
        ->assertSessionHasErrors('lock_version');

    expect(AiContentDraftEvent::query()->where('action', 'review_submitted')->count())->toBe(1);
});

test('a no-op edit does not create a revision or increment the version', function () {
    [$owner, $workspace, $brand] = contentStudioTenant();

    $this->actingAs($owner)->post(
        route('content-studio.drafts.store', [$workspace, $brand]),
        contentDraftPayload(),
    )->assertSessionHasNoErrors();
    $draft = AiContentDraft::query()->sole();

    $this->actingAs($owner)->patch(
        route('content-studio.drafts.update', [$workspace, $brand, $draft]),
        contentDraftPayload(['lock_version' => 0]),
    )->assertSessionHasNoErrors();

    expect($draft->fresh()->lock_version)->toBe(0)
        ->and($draft->fresh()->current_revision)->toBe(1)
        ->and(AiContentDraftRevision::query()->count())->toBe(1);
});

test('empty briefs cannot be submitted for review', function () {
    [$owner, $workspace, $brand] = contentStudioTenant();
    $draft = AiContentDraft::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'created_by_user_id' => $owner->id,
        'content' => null,
        'faq_entries' => [],
    ]);

    $this->actingAs($owner)->from(route('content-studio.drafts.show', [$workspace, $brand, $draft]))->post(
        route('content-studio.drafts.review-requests.store', [$workspace, $brand, $draft]),
        ['lock_version' => 0],
    )->assertSessionHasErrors('content');

    expect($draft->fresh()->status)->toBe(AiContentDraftStatus::Draft)
        ->and(AiContentDraftEvent::query()->count())->toBe(0);
});

test('forged editorial ownership and approval fields are rejected', function () {
    [$owner, $workspace, $brand] = contentStudioTenant();
    $forgedApprover = User::factory()->create();
    $draft = AiContentDraft::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'created_by_user_id' => $owner->id,
    ]);

    $this->actingAs($owner)->from(route('content-studio.drafts.edit', [$workspace, $brand, $draft]))->patch(
        route('content-studio.drafts.update', [$workspace, $brand, $draft]),
        contentDraftPayload([
            'status' => AiContentDraftStatus::Approved->value,
            'approved_by_user_id' => $forgedApprover->id,
            'approved_at' => now()->toDateTimeString(),
            'workspace_id' => 999,
        ]),
    )->assertSessionHasErrors(['status', 'approved_by_user_id', 'approved_at', 'workspace_id']);

    $draft->refresh();
    expect($draft->status)->toBe(AiContentDraftStatus::Draft)
        ->and($draft->approved_by_user_id)->toBeNull()
        ->and($draft->workspace_id)->toBe($workspace->id);
});

test('revision retention remains bounded per draft', function () {
    config()->set('ai.content_revision_limit', 2);
    [$owner, $workspace, $brand] = contentStudioTenant();
    $draft = AiContentDraft::factory()->create([
        'workspace_id' => $workspace->id,
        'brand_id' => $brand->id,
        'created_by_user_id' => $owner->id,
    ]);

    foreach (['First revision.', 'Second revision.', 'Third revision.'] as $version => $content) {
        $this->actingAs($owner)->patch(
            route('content-studio.drafts.update', [$workspace, $brand, $draft]),
            contentDraftPayload(['content' => $content, 'lock_version' => $version]),
        )->assertSessionHasNoErrors();
    }

    expect($draft->fresh()->current_revision)->toBe(3)
        ->and($draft->revisions()->count())->toBe(2)
        ->and($draft->revisions()->pluck('revision_number')->all())->toBe([3, 2]);
});
