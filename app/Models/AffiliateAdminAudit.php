<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['acting_admin_id', 'subject_type', 'subject_id', 'action', 'request_id', 'changes'])]
class AffiliateAdminAudit extends Model
{
    public const UPDATED_AT = null;

    /** @return BelongsTo<Admin, $this> */
    public function actingAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'acting_admin_id');
    }

    /** @return MorphTo<Model, $this> */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'changes' => 'array',
        ];
    }
}
