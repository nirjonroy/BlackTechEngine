<?php

namespace App\Services\Autopilot;

use App\Enums\AiContentDraftStatus;
use App\Enums\ArticleScheduleFrequency;
use App\Enums\ArticleScheduleRunStatus;
use App\Enums\ArticleScheduleTargetStatus;
use App\Enums\EntitlementKey;
use App\Models\ArticleSchedule;
use App\Models\ArticleScheduleRun;
use App\Services\Ai\AiContentGenerationManager;
use App\Services\ContentEditorialWorkflow;
use App\Services\Subscriptions\EntitlementService;
use App\Services\WordPress\WordPressPublishingService;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

class AutopilotProcessor
{
    public function __construct(
        private readonly AiContentGenerationManager $generationManager,
        private readonly ContentEditorialWorkflow $editorialWorkflow,
        private readonly WordPressPublishingService $wordPressPublishingService,
        private readonly EntitlementService $entitlements,
    ) {}

    /**
     * Process an eligible article schedule.
     *
     * @return array{success: bool, message: string, run_id: ?int, draft_id: ?int}
     */
    public function process(ArticleSchedule $schedule): array
    {
        if (! config('ai.autopilot_enabled')) {
            return [
                'success' => false,
                'message' => 'Autopilot processing is currently disabled by configuration.',
                'run_id' => null,
                'draft_id' => null,
            ];
        }

        if (! config('ai.content_generation_enabled')) {
            return [
                'success' => false,
                'message' => 'AI content generation is currently disabled by configuration.',
                'run_id' => null,
                'draft_id' => null,
            ];
        }

        $this->entitlements->assertFeature($schedule->workspace, EntitlementKey::ContentStudio);

        $lockKey = "autopilot:schedule:{$schedule->getKey()}";
        $lock = Cache::lock($lockKey, 300);

        try {
            return $lock->block(1, function () use ($schedule): array {
                return $this->executeSchedule($schedule);
            });
        } catch (LockTimeoutException) {
            return [
                'success' => false,
                'message' => "Schedule #{$schedule->id} is already being processed by another worker.",
                'run_id' => null,
                'draft_id' => null,
            ];
        }
    }

    /**
     * Internal execution under lock.
     *
     * @return array{success: bool, message: string, run_id: int, draft_id: ?int}
     */
    private function executeSchedule(ArticleSchedule $schedule): array
    {
        /** @var ArticleScheduleRun $run */
        $run = ArticleScheduleRun::query()->create([
            'workspace_id' => $schedule->workspace_id,
            'brand_id' => $schedule->brand_id,
            'article_schedule_id' => $schedule->getKey(),
            'status' => ArticleScheduleRunStatus::Generating,
            'scheduled_for' => $schedule->next_run_at ?? now(),
            'started_at' => now(),
        ]);

        $brand = $schedule->brand;
        $workspace = $schedule->workspace;
        $creator = $schedule->creator;

        $generationInput = [
            'idempotency_key' => (string) Str::uuid(),
            'content_type' => $schedule->content_type->value,
            'topic' => $schedule->title,
            'primary_keyword' => $schedule->primary_keyword,
            'secondary_keywords' => $schedule->secondary_keywords ?? [],
            'target_audience' => $schedule->target_audience ?: ($brand->target_audience ?: 'General audience'),
            'language' => $schedule->language ?: ($brand->preferred_language ?: 'en'),
            'region' => $schedule->region ?: ($brand->primary_market ?: 'US'),
            'content_length' => $schedule->content_length->value,
            'tone' => $schedule->tone ?: ($brand->voice_tone ?: 'Professional, informative'),
        ];

        try {
            $draft = $this->generationManager->generate($creator, $workspace, $brand, $generationInput);
            $wpPublishedPostId = null;

            // WordPress auto-publishing if opted in and requested
            if ($schedule->target_status === ArticleScheduleTargetStatus::WordPressPublish
                && $schedule->auto_publish_to_wordpress) {
                $connection = $brand->wordPressConnection;

                if ($connection && $connection->isConnected()) {
                    // Follow editorial approval requirement if enabled
                    if (config('ai.content_review_enabled') && $draft->status === AiContentDraftStatus::NeedsReview) {
                        $draft = $this->editorialWorkflow->approve(
                            $draft,
                            $creator,
                            $draft->lock_version,
                            'Auto-approved via scheduled autopilot',
                        );
                    }

                    $publishedPost = $this->wordPressPublishingService->publish(
                        $draft,
                        $creator,
                        $schedule->publish_as_status ?: 'draft',
                    );

                    $wpPublishedPostId = $publishedPost->getKey();
                }
            }

            // Record completion on run
            $run->update([
                'status' => ArticleScheduleRunStatus::Completed,
                'ai_content_draft_id' => $draft->getKey(),
                'wordpress_published_post_id' => $wpPublishedPostId,
                'completed_at' => now(),
            ]);

            // Update schedule next run
            $this->advanceSchedule($schedule);

            return [
                'success' => true,
                'message' => "Successfully generated draft #{$draft->id}".($wpPublishedPostId ? " and published to WordPress #{$wpPublishedPostId}." : '.'),
                'run_id' => $run->getKey(),
                'draft_id' => $draft->getKey(),
            ];
        } catch (Throwable $e) {
            $run->update([
                'status' => ArticleScheduleRunStatus::Failed,
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            $schedule->increment('failure_count');
            $schedule->update([
                'last_error' => $e->getMessage(),
            ]);

            $maxRetries = (int) config('ai.autopilot_max_retries', 3);
            if ($schedule->failure_count >= $maxRetries) {
                $schedule->pause();
            }

            return [
                'success' => false,
                'message' => "Autopilot execution failed: {$e->getMessage()}",
                'run_id' => $run->getKey(),
                'draft_id' => null,
            ];
        }
    }

    private function advanceSchedule(ArticleSchedule $schedule): void
    {
        $schedule->update([
            'last_run_at' => now(),
            'failure_count' => 0,
            'last_error' => null,
            'total_runs' => $schedule->total_runs + 1,
        ]);

        if ($schedule->frequency === ArticleScheduleFrequency::Once) {
            $schedule->update([
                'is_active' => false,
                'next_run_at' => null,
            ]);
        } else {
            $schedule->update([
                'next_run_at' => $schedule->calculateNextRun(now()),
            ]);
        }
    }
}
