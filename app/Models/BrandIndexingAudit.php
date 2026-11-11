<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'workspace_id',
    'brand_id',
    'created_by_user_id',
    'status',
    'source',
    'pages_discovered',
    'pages_indexed',
    'error_message',
    'duration_ms',
])]
class BrandIndexingAudit extends Model
{
    use HasFactory;

    protected $table = 'brand_indexing_audits';

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

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'pages_discovered' => 'integer',
            'pages_indexed' => 'integer',
            'duration_ms' => 'integer',
        ];
    }
}

