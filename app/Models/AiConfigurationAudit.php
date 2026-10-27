<?php

namespace App\Models;

use App\Support\AiConfigurationMetadata;
use Database\Factories\AiConfigurationAuditFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

#[Fillable(['acting_admin_id', 'subject_type', 'subject_id', 'action', 'request_id', 'changes'])]
class AiConfigurationAudit extends Model
{
    /** @use HasFactory<AiConfigurationAuditFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('AI configuration audit records are append-only.');
        });

        static::deleting(function (): never {
            throw new LogicException('AI configuration audit records are append-only.');
        });
    }

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

    protected function changes(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): array => json_decode($value ?? '[]', true, flags: JSON_THROW_ON_ERROR),
            set: fn (array $value): string => AiConfigurationMetadata::encodeJson(
                AiConfigurationMetadata::auditChanges($value),
            ),
        );
    }
}
