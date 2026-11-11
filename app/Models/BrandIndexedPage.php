<?php

namespace App\Models;

use Database\Factories\BrandIndexedPageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'workspace_id',
    'brand_id',
    'wordpress_post_id',
    'url',
    'url_hash',
    'title',
    'slug',
    'page_type',
    'snippet',
    'keywords',
    'source',
    'last_indexed_at',
])]
class BrandIndexedPage extends Model
{
    /** @use HasFactory<BrandIndexedPageFactory> */
    use HasFactory;

    protected $table = 'brand_indexed_pages';

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            if ($model->url !== null && empty($model->url_hash)) {
                $model->url_hash = hash('sha256', $model->url);
            }
        });
    }

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

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'keywords' => 'array',
            'last_indexed_at' => 'datetime',
        ];
    }
}

