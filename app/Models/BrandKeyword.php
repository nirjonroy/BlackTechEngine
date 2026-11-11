<?php

namespace App\Models;

use App\Enums\SearchIntent;
use Database\Factories\BrandKeywordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'workspace_id',
    'brand_id',
    'brand_topic_cluster_id',
    'created_by_user_id',
    'keyword',
    'normalized_keyword',
    'search_intent',
    'search_volume',
    'difficulty_score',
    'notes',
    'is_starred',
])]
class BrandKeyword extends Model
{
    /** @use HasFactory<BrandKeywordFactory> */
    use HasFactory;

    protected $table = 'brand_keywords';

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            if ($model->keyword !== null && empty($model->normalized_keyword)) {
                $model->normalized_keyword = self::normalize($model->keyword);
            }
        });
    }

    public static function normalize(string $keyword): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', $keyword)));
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

    /** @return BelongsTo<BrandTopicCluster, $this> */
    public function cluster(): BelongsTo
    {
        return $this->belongsTo(BrandTopicCluster::class, 'brand_topic_cluster_id');
    }

    /** @return BelongsTo<BrandTopicCluster, $this> */
    public function topicCluster(): BelongsTo
    {
        return $this->cluster();
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
            'search_intent' => SearchIntent::class,
            'is_starred' => 'boolean',
            'search_volume' => 'integer',
            'difficulty_score' => 'integer',
        ];
    }
}
