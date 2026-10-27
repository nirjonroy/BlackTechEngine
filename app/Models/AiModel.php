<?php

namespace App\Models;

use App\Enums\AiModelAvailabilityStatus;
use App\Enums\AiModelModality;
use App\Support\AiConfigurationMetadata;
use Database\Factories\AiModelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['ai_provider_id', 'provider_model_id', 'display_name', 'modality', 'capabilities', 'is_enabled', 'availability_status', 'last_verified_at'])]
class AiModel extends Model
{
    /** @use HasFactory<AiModelFactory> */
    use HasFactory;

    protected $attributes = [
        'capabilities' => '[]',
        'is_enabled' => false,
        'availability_status' => 'unverified',
    ];

    /** @return BelongsTo<AiProvider, $this> */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class, 'ai_provider_id');
    }

    /** @return HasMany<AiWorkloadRoute, $this> */
    public function primaryWorkloadRoutes(): HasMany
    {
        return $this->hasMany(AiWorkloadRoute::class, 'primary_ai_model_id');
    }

    /** @return HasMany<AiWorkloadRoute, $this> */
    public function fallbackWorkloadRoutes(): HasMany
    {
        return $this->hasMany(AiWorkloadRoute::class, 'fallback_ai_model_id');
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    protected function providerModelId(): Attribute
    {
        return Attribute::make(
            set: fn (string $value): string => trim($value),
        );
    }

    protected function capabilities(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): array => json_decode($value ?? '[]', true, flags: JSON_THROW_ON_ERROR),
            set: fn (array $value): string => AiConfigurationMetadata::encodeJson(
                AiConfigurationMetadata::capabilities($value),
            ),
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'modality' => AiModelModality::class,
            'is_enabled' => 'boolean',
            'availability_status' => AiModelAvailabilityStatus::class,
            'last_verified_at' => 'datetime',
        ];
    }
}
