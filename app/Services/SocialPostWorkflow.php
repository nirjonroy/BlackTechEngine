<?php

namespace App\Services;

use App\Enums\EntitlementKey;
use App\Enums\SocialPostStatus;
use App\Models\AiContentDraft;
use App\Models\Brand;
use App\Models\SocialPost;
use App\Models\SocialPostEvent;
use App\Models\SocialPostRevision;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Subscriptions\EntitlementService;
use App\Services\Subscriptions\UsageMeter;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class SocialPostWorkflow
{
    public function __construct(
        private readonly EntitlementService $entitlements,
        private readonly UsageMeter $usage,
    ) {}

    /** @param array<string, mixed> $attributes @param list<array<string, mixed>> $variants */
    public function create(Workspace $workspace, Brand $brand, User $actor, string $idempotencyKey, array $attributes, array $variants, ?AiContentDraft $source): SocialPost
    {
        $this->entitlements->assertFeature($workspace, EntitlementKey::SocialContent);
        $fingerprint = $this->requestFingerprint($workspace, $brand, $actor, $attributes, $variants, $source);

        try {
            return DB::transaction(function () use ($actor, $attributes, $brand, $fingerprint, $idempotencyKey, $source, $variants, $workspace): SocialPost {
                $existing = SocialPost::query()
                    ->whereBelongsTo($workspace)
                    ->where('idempotency_key', $idempotencyKey)
                    ->first();

                if ($existing !== null) {
                    return $this->validatedReplay($existing, $brand, $actor, $fingerprint);
                }

                $reservation = $this->usage->reserve(
                    $workspace,
                    EntitlementKey::SocialPostsPerPeriod,
                    1,
                    $idempotencyKey,
                    $actor,
                );

                $post = SocialPost::query()->create([
                    ...$attributes, 'workspace_id' => $workspace->getKey(), 'brand_id' => $brand->getKey(),
                    'owner_user_id' => $workspace->owner_id, 'created_by_user_id' => $actor->getKey(),
                    'source_ai_content_draft_id' => $source?->getKey(), 'source_revision' => $source?->current_revision,
                    'source_snapshot' => $source === null ? null : $this->sourceSnapshot($source),
                    'idempotency_key' => $idempotencyKey, 'request_fingerprint' => $fingerprint,
                ]);
                $post->variants()->createMany($variants);
                $this->recordRevision($post, $actor, ['created']);
                $this->recordEvent($post, $actor, 'created', null, SocialPostStatus::Draft);
                $this->usage->commit($reservation, SocialPost::class, $post->getKey());

                return $post;
            });
        } catch (QueryException $exception) {
            if (! $this->isUniqueConstraintViolation($exception)) {
                throw $exception;
            }

            $existing = SocialPost::query()
                ->whereBelongsTo($workspace)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing === null) {
                throw $exception;
            }

            return $this->validatedReplay($existing, $brand, $actor, $fingerprint);
        }
    }

    /** @param array<string, mixed> $attributes @param list<array<string, mixed>> $variants */
    public function update(SocialPost $post, User $actor, array $attributes, array $variants, int $expectedVersion): SocialPost
    {
        return DB::transaction(function () use ($actor, $attributes, $expectedVersion, $post, $variants): SocialPost {
            $locked = $this->locked($post);
            $this->ensureVersion($locked, $expectedVersion);
            $this->ensureEditable($locked);
            $before = $this->snapshot($locked);
            $from = $locked->status;
            $locked->fill($attributes);
            $incoming = collect($variants)->keyBy('platform');
            $existing = $locked->variants()->get()->keyBy(fn ($variant) => $variant->platform->value);
            $changed = $locked->isDirty();

            foreach ($incoming as $platform => $data) {
                $variant = $existing->get($platform);
                if ($variant === null) {
                    $locked->variants()->create($data);
                    $changed = true;

                    continue;
                }
                $variant->fill($data);
                if ($variant->isDirty()) {
                    $variant->save();
                    $changed = true;
                }
            }
            $removed = $existing->keys()->diff($incoming->keys());
            if ($removed->isNotEmpty()) {
                $locked->variants()->whereIn('platform', $removed)->delete();
                $changed = true;
            }
            if (! $changed) {
                return $locked->load('variants');
            }

            if (in_array($from, [SocialPostStatus::NeedsReview, SocialPostStatus::Approved, SocialPostStatus::Planned], true)) {
                $locked->status = SocialPostStatus::NeedsReview;
                $locked->review_submitted_at = now();
                $locked->approved_at = null;
                $locked->approved_by_user_id = null;
                $locked->planned_at = null;
            }
            $locked->current_revision++;
            $locked->lock_version++;
            $locked->save();
            $locked->load('variants');
            $this->recordRevision($locked, $actor, $this->changedKeys($before, $this->snapshot($locked)));
            $this->recordEvent($locked, $actor, 'edited', $from, $locked->status);

            return $locked;
        });
    }

    public function duplicate(SocialPost $post, User $actor): SocialPost
    {
        $post->load('variants');

        return $this->create($post->workspace, $post->brand, $actor, Str::uuid()->toString(), [
            'title' => Str::limit($post->title.' (copy)', 180, ''), 'campaign' => $post->campaign,
        ], $post->variants->map(fn ($variant) => collect($variant->only(['platform', 'caption', 'hashtags', 'cta_text', 'target_url', 'media_reference', 'image_alt_text']))->map(fn ($value) => $value instanceof \BackedEnum ? $value->value : $value)->all())->all(), $post->sourceDraft);
    }

    public function transition(SocialPost $post, User $actor, string $action, int $expectedVersion, ?string $notes = null, ?CarbonImmutable $at = null, ?string $url = null): SocialPost
    {
        return DB::transaction(function () use ($action, $actor, $at, $expectedVersion, $notes, $post, $url): SocialPost {
            $locked = $this->locked($post);
            $this->ensureVersion($locked, $expectedVersion);
            $from = $locked->status;
            $to = match ($action) {
                'submit' => $this->requireStatus($locked, [SocialPostStatus::Draft, SocialPostStatus::ChangesRequested], SocialPostStatus::NeedsReview),
                'approve' => $this->requireStatus($locked, [SocialPostStatus::NeedsReview], SocialPostStatus::Approved),
                'changes' => $this->requireStatus($locked, [SocialPostStatus::NeedsReview], SocialPostStatus::ChangesRequested),
                'plan' => $this->requireStatus($locked, [SocialPostStatus::Approved, SocialPostStatus::Planned], SocialPostStatus::Planned),
                'publish' => $this->requireStatus($locked, [SocialPostStatus::Approved, SocialPostStatus::Planned], SocialPostStatus::ManuallyPublished),
                'cancel' => $this->requireStatus($locked, [SocialPostStatus::Draft, SocialPostStatus::ChangesRequested, SocialPostStatus::NeedsReview, SocialPostStatus::Approved, SocialPostStatus::Planned], SocialPostStatus::Cancelled),
                default => throw ValidationException::withMessages(['action' => 'Unsupported social workflow action.']),
            };
            if ($action === 'plan' && $at === null) {
                throw ValidationException::withMessages(['planned_at' => 'Choose a planned publishing time.']);
            }
            if ($action === 'changes' && blank($notes)) {
                throw ValidationException::withMessages(['notes' => 'Explain the requested changes.']);
            }
            $locked->status = $to;
            $locked->review_feedback = $this->sanitize($notes);
            $locked->lock_version++;
            if ($action === 'submit') {
                $locked->review_submitted_at = now();
            }
            if ($action === 'approve') {
                $locked->approved_at = now();
                $locked->approved_by_user_id = $actor->getKey();
            }
            if ($action === 'changes') {
                $locked->approved_at = null;
                $locked->approved_by_user_id = null;
            }
            if ($action === 'plan') {
                $locked->planned_at = $at;
            }
            if ($action === 'publish') {
                $locked->published_at = $at ?? CarbonImmutable::now();
                $locked->published_url = $url;
            }
            if ($action === 'cancel') {
                $locked->cancelled_at = now();
            }
            $locked->save();
            $this->recordEvent($locked, $actor, $action, $from, $to, $notes, $action === 'publish' ? ['published_url' => $url] : null);

            return $locked;
        });
    }

    public function recordExport(SocialPost $post, User $actor, string $format): void
    {
        DB::transaction(function () use ($actor, $format, $post): void {
            $locked = $this->locked($post);
            $this->recordEvent($locked, $actor, 'exported', $locked->status, $locked->status, context: ['format' => $format]);
        });
    }

    private function locked(SocialPost $post): SocialPost
    {
        return SocialPost::query()->with('variants')->lockForUpdate()->findOrFail($post->getKey());
    }

    private function ensureVersion(SocialPost $post, int $expected): void
    {
        if ($post->lock_version !== $expected) {
            throw ValidationException::withMessages(['lock_version' => 'This post changed after you opened it. Review the latest version and try again.']);
        }
    }

    private function ensureEditable(SocialPost $post): void
    {
        if (in_array($post->status, [SocialPostStatus::ManuallyPublished, SocialPostStatus::Cancelled], true)) {
            throw ValidationException::withMessages(['status' => 'Published or cancelled posts cannot be edited.']);
        }
    }

    private function requireStatus(SocialPost $post, array $allowed, SocialPostStatus $to): SocialPostStatus
    {
        if (! in_array($post->status, $allowed, true)) {
            throw ValidationException::withMessages(['status' => 'That workflow action is not available for this post.']);
        }

        return $to;
    }

    private function recordRevision(SocialPost $post, User $actor, array $fields): void
    {
        SocialPostRevision::query()->create(['social_post_id' => $post->getKey(), 'editor_user_id' => $actor->getKey(), 'revision_number' => $post->current_revision, 'changed_fields' => $fields, 'snapshot' => $this->snapshot($post)]);
        $ids = $post->revisions()->pluck('id')->slice(max(1, (int) config('social.revision_limit', 25)));
        if ($ids->isNotEmpty()) {
            SocialPostRevision::query()->whereKey($ids)->delete();
        }
    }

    private function recordEvent(SocialPost $post, User $actor, string $action, ?SocialPostStatus $from, SocialPostStatus $to, ?string $notes = null, ?array $context = null): void
    {
        SocialPostEvent::query()->create(['social_post_id' => $post->getKey(), 'workspace_id' => $post->workspace_id, 'brand_id' => $post->brand_id, 'actor_user_id' => $actor->getKey(), 'action' => $action, 'from_status' => $from, 'to_status' => $to, 'lock_version' => $post->lock_version, 'notes' => $this->sanitize($notes), 'context' => $context]);
    }

    private function snapshot(SocialPost $post): array
    {
        $post->loadMissing('variants');

        return ['title' => $post->title, 'campaign' => $post->campaign, 'status' => $post->status->value, 'variants' => $post->variants->map(fn ($variant) => ['platform' => $variant->platform->value, 'caption' => $variant->caption, 'hashtags' => $variant->hashtags, 'cta_text' => $variant->cta_text, 'target_url' => $variant->target_url, 'media_reference' => $variant->media_reference, 'image_alt_text' => $variant->image_alt_text])->all()];
    }

    private function sourceSnapshot(AiContentDraft $draft): array
    {
        return ['revision' => $draft->current_revision, 'title' => $draft->seo_title, 'content_type' => $draft->content_type->value, 'content' => $draft->content, 'suggested_slug' => $draft->suggested_slug];
    }

    private function changedKeys(array $before, array $after): array
    {
        return collect(array_keys($after))->filter(fn (string $key) => ($before[$key] ?? null) !== ($after[$key] ?? null))->values()->all();
    }

    private function sanitize(?string $notes): ?string
    {
        return blank($notes) ? null : Str::limit(Str::squish(strip_tags(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $notes) ?? '')), 2000, '');
    }

    /** @param array<string, mixed> $attributes @param list<array<string, mixed>> $variants */
    private function requestFingerprint(Workspace $workspace, Brand $brand, User $actor, array $attributes, array $variants, ?AiContentDraft $source): string
    {
        $canonicalVariants = collect($variants)
            ->sortBy('platform')
            ->map(fn (array $variant): array => collect($variant)->sortKeys()->all())
            ->values()
            ->all();

        return hash('sha256', json_encode([
            'workspace_id' => $workspace->getKey(),
            'brand_id' => $brand->getKey(),
            'actor_user_id' => $actor->getKey(),
            'source_ai_content_draft_id' => $source?->getKey(),
            'attributes' => collect($attributes)->sortKeys()->all(),
            'variants' => $canonicalVariants,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private function validatedReplay(SocialPost $post, Brand $brand, User $actor, string $fingerprint): SocialPost
    {
        if ($post->brand_id !== $brand->getKey()
            || $post->created_by_user_id !== $actor->getKey()
            || ! hash_equals($post->request_fingerprint, $fingerprint)) {
            throw new ConflictHttpException('This submission key has already been used for different social content.');
        }

        return $post->load('variants');
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        return in_array((string) $exception->getCode(), ['23000', '23505'], true)
            && in_array((int) ($exception->errorInfo[1] ?? 0), [19, 1062, 1555, 2067], true);
    }
}
