<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['acting_admin_id', 'target_user_id', 'action', 'request_id', 'changes'])]
class AdminUserAudit extends Model
{
    public const UPDATED_AT = null;

    /** @return BelongsTo<Admin, $this> */
    public function actingAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'acting_admin_id');
    }

    /** @return BelongsTo<User, $this> */
    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
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
