<?php

namespace App\Models;

use App\Enums\WordPressPostStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'workspace_id',
    'brand_id',
    'wordpress_site_connection_id',
    'ai_content_draft_id',
    'created_by_user_id',
    'wordpress_post_id',
    'post_url',
    'status',
    'published_at',
    'last_error',
    'transferred_payload',
])]
class WordPressPublishedPost extends Model
{
    use HasFactory;

    protected $table = 'wordpress_published_posts';

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

    /** @return BelongsTo<WordPressSiteConnection, $this> */
    public function connection(): BelongsTo
    {
        return $this->belongsTo(WordPressSiteConnection::class, 'wordpress_site_connection_id');
    }

    /** @return BelongsTo<AiContentDraft, $this> */
    public function contentDraft(): BelongsTo
    {
        return $this->belongsTo(AiContentDraft::class, 'ai_content_draft_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => WordPressPostStatus::class,
            'published_at' => 'datetime',
            'transferred_payload' => 'array',
        ];
    }
}
