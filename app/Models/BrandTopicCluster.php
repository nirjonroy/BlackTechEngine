<?php

namespace App\Models;

use Database\Factories\BrandTopicClusterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'workspace_id',
    'brand_id',
    'created_by_user_id',
    'name',
    'description',
    'pillar_keyword',
])]
class BrandTopicCluster extends Model
{
    /** @use HasFactory<BrandTopicClusterFactory> */
    use HasFactory;

    protected $table = 'brand_topic_clusters';

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

    /** @return HasMany<BrandKeyword, $this> */
    public function keywords(): HasMany
    {
        return $this->hasMany(BrandKeyword::class, 'brand_topic_cluster_id');
    }
}
