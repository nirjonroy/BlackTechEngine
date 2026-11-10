<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable(['acting_admin_id', 'integration_setting_id', 'integration', 'action', 'request_id', 'changed_fields'])]
class IntegrationSettingAudit extends Model
{
    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Integration setting audits are append-only.');
        });

        static::deleting(function (): never {
            throw new LogicException('Integration setting audits are append-only.');
        });
    }

    public function setting(): BelongsTo
    {
        return $this->belongsTo(IntegrationSetting::class, 'integration_setting_id');
    }

    public function actingAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'acting_admin_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['changed_fields' => 'array'];
    }
}
