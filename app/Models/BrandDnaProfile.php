<?php

namespace App\Models;

use Database\Factories\BrandDnaProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'workspace_id',
    'brand_id',
    'created_by_user_id',
    'status',
    'source_urls',
    'extracted_raw_summary',
    'brand_identity',
    'business_description',
    'industry',
    'target_audience',
    'primary_market',
    'voice_tone',
    'key_services',
    'suggested_keywords',
    'content_themes',
    'brand_values',
    'competitor_differentiation',
    'is_approved',
    'approved_at',
    'approved_by_user_id',
    'last_error',
    'last_analyzed_at',
])]
class BrandDnaProfile extends Model
{
    /** @use HasFactory<BrandDnaProfileFactory> */
    use HasFactory;

    protected $table = 'brand_dna_profiles';

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

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'source_urls' => 'array',
            'key_services' => 'array',
            'suggested_keywords' => 'array',
            'content_themes' => 'array',
            'brand_values' => 'array',
            'is_approved' => 'boolean',
            'approved_at' => 'datetime',
            'last_analyzed_at' => 'datetime',
        ];
    }
}

