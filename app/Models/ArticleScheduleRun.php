<?php

namespace App\Models;

use App\Enums\ArticleScheduleRunStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'workspace_id',
    'brand_id',
    'article_schedule_id',
    'ai_content_draft_id',
    'wordpress_published_post_id',
    'status',
    'scheduled_for',
    'started_at',
    'completed_at',
    'error_message',
])]
class ArticleScheduleRun extends Model
{
    use HasFactory;

    protected $table = 'article_schedule_runs';

    /** @return BelongsTo<Workspace, $this> */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /** @return BelongsTo<Brand, $this> */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /** @return BelongsTo<ArticleSchedule, $this> */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ArticleSchedule::class, 'article_schedule_id');
    }

    /** @return BelongsTo<AiContentDraft, $this> */
    public function draft(): BelongsTo
    {
        return $this->belongsTo(AiContentDraft::class, 'ai_content_draft_id');
    }

    /** @return BelongsTo<WordPressPublishedPost, $this> */
    public function publishedPost(): BelongsTo
    {
        return $this->belongsTo(WordPressPublishedPost::class, 'wordpress_published_post_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => ArticleScheduleRunStatus::class,
            'scheduled_for' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}

