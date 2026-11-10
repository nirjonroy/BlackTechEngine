<?php

namespace App\Services\WordPress;

use App\Enums\AiContentDraftStatus;
use App\Enums\WordPressConnectionStatus;
use App\Enums\WordPressPostStatus;
use App\Models\AiContentDraft;
use App\Models\Brand;
use App\Models\User;
use App\Models\WordPressPublishedPost;
use App\Models\WordPressSiteConnection;
use App\Services\ContentDraftExporter;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class WordPressPublishingService
{
    public const AMBIGUOUS_TIMEOUT_MESSAGE = 'Ambiguous remote timeout: WordPress server did not respond before timeout. Please check your WordPress site before retrying.';

    public function __construct(
        private readonly WordPressUrlValidator $urlValidator,
        private readonly WordPressClient $client,
        private readonly ContentDraftExporter $draftExporter,
    ) {}

    /**
     * Connect and verify a WordPress site for a brand.
     *
     * @throws ValidationException
     */
    public function connect(Brand $brand, User $user, string $siteUrl, string $username, string $applicationPassword): WordPressSiteConnection
    {
        $normalizedUrl = $this->urlValidator->validateAndNormalize($siteUrl);
        $cleanPassword = str_replace(' ', '', trim($applicationPassword));
        $trimmedUsername = trim($username);

        $verification = $this->client->verifyConnection($normalizedUrl, $trimmedUsername, $cleanPassword);

        return DB::transaction(function () use ($brand, $user, $normalizedUrl, $trimmedUsername, $cleanPassword, $verification) {
            $connection = WordPressSiteConnection::query()->updateOrCreate(
                ['brand_id' => $brand->getKey()],
                [
                    'workspace_id' => $brand->workspace_id,
                    'created_by_user_id' => $user->getKey(),
                    'site_url' => $normalizedUrl,
                    'rest_api_endpoint' => $normalizedUrl.'/wp-json/wp/v2',
                    'username' => $trimmedUsername,
                    'application_password' => $cleanPassword,
                    'status' => WordPressConnectionStatus::Connected,
                    'site_name' => $verification['site_name'],
                    'wordpress_version' => $verification['wordpress_version'],
                    'last_verified_at' => now(),
                    'last_error' => null,
                ]
            );

            return $connection;
        });
    }

    /**
     * Re-verify an existing WordPress site connection.
     *
     * @throws ValidationException
     */
    public function verify(WordPressSiteConnection $connection): bool
    {
        try {
            $normalizedUrl = $this->urlValidator->validateAndNormalize($connection->site_url);
            $verification = $this->client->verifyConnection(
                $normalizedUrl,
                $connection->username,
                $connection->application_password,
            );

            $connection->update([
                'site_name' => $verification['site_name'],
                'status' => WordPressConnectionStatus::Connected,
                'last_verified_at' => now(),
                'last_error' => null,
            ]);

            return true;
        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first() ?: 'Verification failed';
            $connection->update([
                'status' => WordPressConnectionStatus::Error,
                'last_error' => $firstError,
            ]);

            throw $e;
        }
    }

    /**
     * Disconnect a WordPress site connection safely.
     */
    public function disconnect(WordPressSiteConnection $connection): void
    {
        $connection->update([
            'status' => WordPressConnectionStatus::Disconnected,
            'last_error' => 'Disconnected by user.',
        ]);
    }

    /**
     * Publish an approved content draft to the connected WordPress site.
     * Protected by per-draft atomic lock and duplicate publishing prevention.
     *
     * @throws ValidationException
     */
    public function publish(AiContentDraft $draft, User $user, string $targetStatus = 'draft', bool $reconciled = false): WordPressPublishedPost
    {
        // 1. Verify tenant boundary
        if ($draft->workspace_id !== $draft->brand->workspace_id) {
            throw ValidationException::withMessages([
                'draft' => 'Tenant boundary mismatch detected between draft and brand.',
            ]);
        }

        // 2. Draft must be approved before publication
        if (! in_array($draft->status, [AiContentDraftStatus::Approved, AiContentDraftStatus::ExportReady], true)) {
            throw ValidationException::withMessages([
                'status' => 'Only approved drafts can be published to WordPress. Please complete editorial review first.',
            ]);
        }

        // 3. Brand must have active WordPress connection
        $connection = $draft->brand->wordPressConnection;

        if ($connection === null || ! $connection->isConnected()) {
            throw ValidationException::withMessages([
                'connection' => 'WordPress site is not connected or requires re-verification before publishing.',
            ]);
        }

        // 4. Atomic per-draft lock to prevent race conditions and concurrent posts
        $lockKey = "wp-publish:draft:{$draft->getKey()}";
        $lock = Cache::lock($lockKey, 30);

        try {
            $lock->block(0);
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages([
                'publishing' => 'A WordPress publication request for this article is currently being processed. Please wait.',
            ]);
        }

        try {
            return $this->executePublish($draft, $connection, $user, $targetStatus, $reconciled);
        } finally {
            $lock->release();
        }
    }

    /**
     * Execute publication within the held atomic lock.
     *
     * @throws ValidationException
     */
    private function executePublish(
        AiContentDraft $draft,
        WordPressSiteConnection $connection,
        User $user,
        string $targetStatus,
        bool $reconciled
    ): WordPressPublishedPost {
        // Check previous publications
        $latestPublication = WordPressPublishedPost::query()
            ->where('ai_content_draft_id', $draft->getKey())
            ->where('wordpress_site_connection_id', $connection->getKey())
            ->latest('id')
            ->first();

        // If previous publication ended with an ambiguous remote timeout and no post ID was confirmed:
        if ($latestPublication !== null
            && $latestPublication->status === WordPressPostStatus::Failed
            && $latestPublication->wordpress_post_id === null
            && str_contains((string) $latestPublication->last_error, 'Ambiguous remote timeout')
            && ! $reconciled) {
            throw ValidationException::withMessages([
                'reconciliation' => 'Previous publishing attempt timed out ambiguously without remote receipt confirmation. Please verify your WordPress site before retrying.',
            ]);
        }

        $validStatus = in_array($targetStatus, ['draft', 'publish'], true) ? $targetStatus : 'draft';

        // Reuse existing remote post ID if already created on WordPress
        $existingWpPostId = $latestPublication?->wordpress_post_id;

        $renderedHtml = $this->renderArticleHtml($draft);
        $title = $draft->seo_title ?: $draft->topic;
        $slug = $draft->suggested_slug ?: Str::slug($title);

        $payload = [
            'title' => $title,
            'slug' => $slug,
            'content' => $renderedHtml,
            'excerpt' => $draft->meta_description ?? '',
            'status' => $validStatus,
            'meta' => [
                '_yoast_wpseo_title' => $draft->seo_title ?: $title,
                '_yoast_wpseo_metadesc' => $draft->meta_description ?? '',
                'rank_math_title' => $draft->seo_title ?: $title,
                'rank_math_description' => $draft->meta_description ?? '',
            ],
        ];

        try {
            $result = $this->client->publishPost(
                $connection->site_url,
                $connection->username,
                $connection->application_password,
                $payload,
                $existingWpPostId,
            );
        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first() ?: 'Publication failed';
            $isTimeout = str_contains($firstError, 'timed out') || str_contains($firstError, 'Connection timed out');
            $recordedError = $isTimeout ? self::AMBIGUOUS_TIMEOUT_MESSAGE : $firstError;

            WordPressPublishedPost::query()->create([
                'workspace_id' => $draft->workspace_id,
                'brand_id' => $draft->brand_id,
                'wordpress_site_connection_id' => $connection->getKey(),
                'ai_content_draft_id' => $draft->getKey(),
                'created_by_user_id' => $user->getKey(),
                'wordpress_post_id' => $existingWpPostId,
                'status' => WordPressPostStatus::Failed,
                'last_error' => $recordedError,
                'transferred_payload' => $payload,
            ]);

            throw $e;
        } catch (Throwable $e) {
            WordPressPublishedPost::query()->create([
                'workspace_id' => $draft->workspace_id,
                'brand_id' => $draft->brand_id,
                'wordpress_site_connection_id' => $connection->getKey(),
                'ai_content_draft_id' => $draft->getKey(),
                'created_by_user_id' => $user->getKey(),
                'wordpress_post_id' => $existingWpPostId,
                'status' => WordPressPostStatus::Failed,
                'last_error' => $e->getMessage(),
                'transferred_payload' => $payload,
            ]);

            throw $e;
        }

        return WordPressPublishedPost::query()->create([
            'workspace_id' => $draft->workspace_id,
            'brand_id' => $draft->brand_id,
            'wordpress_site_connection_id' => $connection->getKey(),
            'ai_content_draft_id' => $draft->getKey(),
            'created_by_user_id' => $user->getKey(),
            'wordpress_post_id' => $result['id'],
            'post_url' => $result['link'],
            'status' => $result['status'] === 'publish' ? WordPressPostStatus::Publish : WordPressPostStatus::Draft,
            'published_at' => now(),
            'last_error' => null,
            'transferred_payload' => $payload,
        ]);
    }

    /**
     * Render the draft into safe, publication-ready HTML including FAQ schema markup if present.
     */
    public function renderArticleHtml(AiContentDraft $draft): string
    {
        $body = $this->draftExporter->previewHtml($draft);

        if (empty($draft->faq_entries)) {
            return $body;
        }

        $faqHtml = "\n\n<div class=\"bte-faq-section\">\n<h2>Frequently Asked Questions</h2>\n";

        foreach ($draft->faq_entries as $faq) {
            $question = e(strip_tags($faq['question'] ?? ''));
            $answer = e(strip_tags($faq['answer'] ?? ''));

            $faqHtml .= "<div class=\"bte-faq-item\">\n";
            $faqHtml .= "<h3>{$question}</h3>\n";
            $faqHtml .= "<p>{$answer}</p>\n";
            $faqHtml .= "</div>\n";
        }

        $faqHtml .= "</div>\n";

        return $body.$faqHtml;
    }
}
