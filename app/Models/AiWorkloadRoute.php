<?php

namespace App\Models;

use App\Support\AiConfigurationMetadata;
use Database\Factories\AiWorkloadRouteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use InvalidArgumentException;

#[Fillable(['workload_key', 'primary_ai_model_id', 'fallback_ai_model_id', 'is_enabled', 'settings'])]
class AiWorkloadRoute extends Model
{
    /** @use HasFactory<AiWorkloadRouteFactory> */
    use HasFactory;

    protected $attributes = [
        'is_enabled' => false,
    ];

    protected static function booted(): void
    {
        static::saving(function (self $route): void {
            if ($route->primary_ai_model_id !== null && $route->primary_ai_model_id === $route->fallback_ai_model_id) {
                throw new InvalidArgumentException('Primary and fallback AI models must be different.');
            }
        });
    }

    /** @return BelongsTo<AiModel, $this> */
    public function primaryModel(): BelongsTo
    {
        return $this->belongsTo(AiModel::class, 'primary_ai_model_id');
    }

    /** @return BelongsTo<AiModel, $this> */
    public function fallbackModel(): BelongsTo
    {
        return $this->belongsTo(AiModel::class, 'fallback_ai_model_id');
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    protected function workloadKey(): Attribute
    {
        return Attribute::make(
            set: function (string $value): string {
                $normalizedKey = Str::slug(Str::lower(trim($value)));

                if ($normalizedKey === '') {
                    throw new InvalidArgumentException('AI workload key must contain letters or numbers.');
                }

                return $normalizedKey;
            },
        );
    }

    protected function settings(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?array => $value === null
                ? null
                : json_decode($value, true, flags: JSON_THROW_ON_ERROR),
            set: fn (?array $value): ?string => AiConfigurationMetadata::encodeJson(
                AiConfigurationMetadata::routeSettings($value),
            ),
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
        ];
    }
}
