<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['social_post_id', 'editor_user_id', 'revision_number', 'changed_fields', 'snapshot'])]
class SocialPostRevision extends Model
{
    use HasFactory;

    public function post(): BelongsTo
    {
        return $this->belongsTo(SocialPost::class, 'social_post_id');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editor_user_id');
    }

    protected function casts(): array
    {
        return ['changed_fields' => 'array', 'snapshot' => 'array'];
    }
}
