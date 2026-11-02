<?php

namespace App\Models;

use App\Enums\SocialPlatform;
use Database\Factories\SocialPostVariantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['social_post_id', 'platform', 'caption', 'hashtags', 'cta_text', 'target_url', 'media_reference', 'image_alt_text'])]
class SocialPostVariant extends Model
{
    /** @use HasFactory<SocialPostVariantFactory> */
    use HasFactory;

    public function post(): BelongsTo
    {
        return $this->belongsTo(SocialPost::class, 'social_post_id');
    }

    protected function casts(): array
    {
        return ['platform' => SocialPlatform::class, 'hashtags' => 'array'];
    }
}
