<?php

namespace App\Models;

use App\Enums\SocialPostStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['social_post_id', 'workspace_id', 'brand_id', 'actor_user_id', 'action', 'from_status', 'to_status', 'lock_version', 'notes', 'context'])]
class SocialPostEvent extends Model
{
    use HasFactory;

    public function post(): BelongsTo
    {
        return $this->belongsTo(SocialPost::class, 'social_post_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    protected function casts(): array
    {
        return ['from_status' => SocialPostStatus::class, 'to_status' => SocialPostStatus::class, 'context' => 'array'];
    }
}
