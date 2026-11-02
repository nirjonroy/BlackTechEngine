<?php

namespace App\Services;

use App\Enums\AiContentDraftStatus;
use App\Models\AiContentDraft;
use App\Models\AiContentDraftEvent;
use App\Models\AiContentDraftRevision;
use App\Models\User;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ContentEditorialWorkflow
{
    /** @var list<string> */
    private const REVISION_FIELDS = [
        'content_type',
        'topic',
        'primary_keyword',
        'secondary_keywords',
        'target_audience',
        'language',
        'region',
        'content_length',
        'tone',
        'seo_title',
        'meta_description',
        'suggested_slug',
        'outline',
        'content',
        'faq_entries',
        'internal_link_opportunities',
        'external_research_topics',
        'verification_notes',
    ];

    public function __construct(private readonly ContentReviewGate $gate) {}

    public function recordCreation(AiContentDraft $draft, User $actor, string $action = 'created'): void
    {
        if (! $this->gate->enabled()) {
            return;
        }

        $draft->current_revision = 1;
        $draft->save();

        $this->recordRevision($draft, $actor, self::REVISION_FIELDS);
        $this->recordEvent($draft, $actor, $action, null, $draft->status);
    }

    /** @param array<string, mixed> $attributes */
    public function update(AiContentDraft $draft, User $actor, array $attributes, int $expectedVersion): AiContentDraft
    {
        $this->ensureEnabled();

        return DB::transaction(function () use ($actor, $attributes, $draft, $expectedVersion): AiContentDraft {
            $lockedDraft = $this->lockedDraft($draft);
            $this->ensureVersion($lockedDraft, $expectedVersion);

            $fromStatus = $lockedDraft->status;
            $lockedDraft->fill($attributes);
            $changedFields = array_values(array_intersect(array_keys($lockedDraft->getDirty()), self::REVISION_FIELDS));

            if ($changedFields === []) {
                return $lockedDraft;
            }

            if ($fromStatus === AiContentDraftStatus::NeedsReview) {
                $lockedDraft->status = AiContentDraftStatus::Draft;
                $lockedDraft->review_submitted_at = null;
            } elseif (in_array($fromStatus, [AiContentDraftStatus::Approved, AiContentDraftStatus::ExportReady], true)) {
                $lockedDraft->status = AiContentDraftStatus::NeedsReview;
                $lockedDraft->review_submitted_at = now();
            }

            $lockedDraft->approved_at = null;
            $lockedDraft->approved_by_user_id = null;
            $lockedDraft->review_feedback = null;
            $lockedDraft->changes_requested_at = null;
            $lockedDraft->current_revision++;
            $lockedDraft->lock_version++;
            $lockedDraft->save();

            $this->recordRevision($lockedDraft, $actor, $changedFields);
            $this->recordEvent(
                $lockedDraft,
                $actor,
                'content_edited',
                $fromStatus,
                $lockedDraft->status,
                context: ['changed_fields' => $changedFields],
            );

            return $lockedDraft;
        });
    }

    public function submitForReview(AiContentDraft $draft, User $actor, int $expectedVersion, ?string $notes): AiContentDraft
    {
        $this->ensureEnabled();

        return DB::transaction(function () use ($actor, $draft, $expectedVersion, $notes): AiContentDraft {
            $lockedDraft = $this->lockedDraft($draft);
            $this->ensureVersion($lockedDraft, $expectedVersion);

            if ($lockedDraft->status !== AiContentDraftStatus::Draft) {
                throw ValidationException::withMessages(['status' => 'Only a draft can be submitted for review.']);
            }

            if (blank($lockedDraft->content) && empty($lockedDraft->faq_entries)) {
                throw ValidationException::withMessages(['content' => 'Add content or FAQ entries before requesting review.']);
            }

            $lockedDraft->status = AiContentDraftStatus::NeedsReview;
            $lockedDraft->review_submitted_at = now();
            $lockedDraft->review_feedback = null;
            $lockedDraft->changes_requested_at = null;
            $lockedDraft->lock_version++;
            $lockedDraft->save();

            $this->recordEvent(
                $lockedDraft,
                $actor,
                'review_submitted',
                AiContentDraftStatus::Draft,
                AiContentDraftStatus::NeedsReview,
                $notes,
            );

            return $lockedDraft;
        });
    }

    public function approve(AiContentDraft $draft, User $actor, int $expectedVersion, ?string $notes): AiContentDraft
    {
        $this->ensureEnabled();

        return DB::transaction(function () use ($actor, $draft, $expectedVersion, $notes): AiContentDraft {
            $lockedDraft = $this->lockedDraft($draft);
            $this->ensureVersion($lockedDraft, $expectedVersion);

            if ($lockedDraft->status !== AiContentDraftStatus::NeedsReview) {
                throw ValidationException::withMessages(['status' => 'Only content awaiting review can be approved.']);
            }

            $lockedDraft->status = AiContentDraftStatus::Approved;
            $lockedDraft->approved_at = now();
            $lockedDraft->approved_by_user_id = $actor->getKey();
            $lockedDraft->review_feedback = $this->sanitizeNotes($notes);
            $lockedDraft->lock_version++;
            $lockedDraft->save();

            $this->recordEvent(
                $lockedDraft,
                $actor,
                'approved',
                AiContentDraftStatus::NeedsReview,
                AiContentDraftStatus::Approved,
                $notes,
            );

            return $lockedDraft;
        });
    }

    public function requestChanges(AiContentDraft $draft, User $actor, int $expectedVersion, string $notes): AiContentDraft
    {
        $this->ensureEnabled();

        return DB::transaction(function () use ($actor, $draft, $expectedVersion, $notes): AiContentDraft {
            $lockedDraft = $this->lockedDraft($draft);
            $this->ensureVersion($lockedDraft, $expectedVersion);

            if ($lockedDraft->status !== AiContentDraftStatus::NeedsReview) {
                throw ValidationException::withMessages(['status' => 'Changes can only be requested for content awaiting review.']);
            }

            $lockedDraft->status = AiContentDraftStatus::Draft;
            $lockedDraft->review_feedback = $this->sanitizeNotes($notes);
            $lockedDraft->changes_requested_at = now();
            $lockedDraft->review_submitted_at = null;
            $lockedDraft->approved_at = null;
            $lockedDraft->approved_by_user_id = null;
            $lockedDraft->lock_version++;
            $lockedDraft->save();

            $this->recordEvent(
                $lockedDraft,
                $actor,
                'changes_requested',
                AiContentDraftStatus::NeedsReview,
                AiContentDraftStatus::Draft,
                $notes,
            );

            return $lockedDraft;
        });
    }

    public function recordExport(AiContentDraft $draft, User $actor, int $expectedVersion, string $format): AiContentDraft
    {
        $this->ensureEnabled();

        return DB::transaction(function () use ($actor, $draft, $expectedVersion, $format): AiContentDraft {
            $lockedDraft = $this->lockedDraft($draft);
            $this->ensureVersion($lockedDraft, $expectedVersion);

            if (! in_array($lockedDraft->status, [AiContentDraftStatus::Approved, AiContentDraftStatus::ExportReady], true)) {
                throw ValidationException::withMessages(['status' => 'Content must be approved before it can be exported.']);
            }

            $fromStatus = $lockedDraft->status;

            if ($lockedDraft->status === AiContentDraftStatus::Approved) {
                $lockedDraft->status = AiContentDraftStatus::ExportReady;
                $lockedDraft->save();
            }

            $this->recordEvent(
                $lockedDraft,
                $actor,
                'exported',
                $fromStatus,
                $lockedDraft->status,
                context: ['format' => $format],
            );

            return $lockedDraft;
        });
    }

    private function lockedDraft(AiContentDraft $draft): AiContentDraft
    {
        return AiContentDraft::query()->lockForUpdate()->findOrFail($draft->getKey());
    }

    private function ensureVersion(AiContentDraft $draft, int $expectedVersion): void
    {
        if ($draft->lock_version !== $expectedVersion) {
            throw ValidationException::withMessages([
                'lock_version' => 'This content changed after you opened the page. Review the latest version and try again.',
            ]);
        }
    }

    /** @param list<string> $changedFields */
    private function recordRevision(AiContentDraft $draft, User $actor, array $changedFields): void
    {
        AiContentDraftRevision::query()->create([
            'ai_content_draft_id' => $draft->getKey(),
            'editor_user_id' => $actor->getKey(),
            'revision_number' => $draft->current_revision,
            'changed_fields' => $changedFields,
            'snapshot' => $this->snapshot($draft),
        ]);

        $this->pruneHistory(
            $draft->revisions()->getRelated()->newQuery()->whereBelongsTo($draft, 'draft'),
            max(1, (int) config('ai.content_revision_limit', 25)),
        );
    }

    /**
     * @param  array<string, mixed>|null  $context
     */
    private function recordEvent(
        AiContentDraft $draft,
        User $actor,
        string $action,
        ?AiContentDraftStatus $fromStatus,
        AiContentDraftStatus $toStatus,
        ?string $notes = null,
        ?array $context = null,
    ): void {
        AiContentDraftEvent::query()->create([
            'ai_content_draft_id' => $draft->getKey(),
            'workspace_id' => $draft->workspace_id,
            'brand_id' => $draft->brand_id,
            'actor_user_id' => $actor->getKey(),
            'action' => $action,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'lock_version' => $draft->lock_version,
            'notes' => $this->sanitizeNotes($notes),
            'context' => $context,
        ]);

        $this->pruneHistory(
            $draft->editorialEvents()->getRelated()->newQuery()->whereBelongsTo($draft, 'draft'),
            max(1, (int) config('ai.content_review_history_limit', 100)),
        );
    }

    /** @return array<string, mixed> */
    private function snapshot(AiContentDraft $draft): array
    {
        return collect(self::REVISION_FIELDS)
            ->mapWithKeys(function (string $field) use ($draft): array {
                $value = $draft->{$field};

                return [$field => $value instanceof BackedEnum ? $value->value : $value];
            })
            ->all();
    }

    private function sanitizeNotes(?string $notes): ?string
    {
        if ($notes === null || trim($notes) === '') {
            return null;
        }

        $withoutControls = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $notes) ?? '';

        return Str::limit(Str::squish(strip_tags($withoutControls)), 2000, '');
    }

    private function pruneHistory(Builder $query, int $limit): void
    {
        $expiredIds = $query->latest('id')->skip($limit)->take(100)->pluck('id');

        if ($expiredIds->isNotEmpty()) {
            $query->getModel()->newQuery()->whereKey($expiredIds)->delete();
        }
    }

    private function ensureEnabled(): void
    {
        abort_unless($this->gate->enabled(), 404);
    }
}
