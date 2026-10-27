<?php

namespace App\Models;

use App\Enums\AiProviderHealthStatus;
use Database\Factories\AiProviderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use InvalidArgumentException;

#[Fillable(['key', 'display_name', 'adapter_type', 'description', 'is_enabled', 'health_status', 'request_timeout_seconds', 'retry_limit', 'last_verified_at'])]
class AiProvider extends Model
{
    /** @use HasFactory<AiProviderFactory> */
    use HasFactory;

    public const DEFAULT_REQUEST_TIMEOUT_SECONDS = 30;

    public const MAX_REQUEST_TIMEOUT_SECONDS = 120;

    public const MAX_RETRY_LIMIT = 5;

    /** @var list<string> */
    public const SUPPORTED_ADAPTER_TYPES = [];

    protected $attributes = [
        'is_enabled' => false,
        'health_status' => 'unverified',
        'request_timeout_seconds' => self::DEFAULT_REQUEST_TIMEOUT_SECONDS,
        'retry_limit' => 0,
        'lock_version' => 0,
    ];

    /** @return HasMany<AiModel, $this> */
    public function models(): HasMany
    {
        return $this->hasMany(AiModel::class);
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    protected function key(): Attribute
    {
        return Attribute::make(
            set: function (string $value): string {
                $normalizedKey = Str::slug(Str::lower(trim($value)));

                if ($normalizedKey === '') {
                    throw new InvalidArgumentException('AI provider key must contain letters or numbers.');
                }

                return $normalizedKey;
            },
        );
    }

    protected function adapterType(): Attribute
    {
        return Attribute::make(
            set: function (?string $value): ?string {
                if (blank($value)) {
                    return null;
                }

                $adapterType = trim($value);

                if (! in_array($adapterType, self::SUPPORTED_ADAPTER_TYPES, true)) {
                    throw new InvalidArgumentException('No AI provider adapters are implemented.');
                }

                return $adapterType;
            },
        );
    }

    protected function requestTimeoutSeconds(): Attribute
    {
        return Attribute::make(
            set: fn (int $value): int => $this->boundedInteger(
                $value,
                1,
                self::MAX_REQUEST_TIMEOUT_SECONDS,
                'AI provider request timeout',
            ),
        );
    }

    protected function retryLimit(): Attribute
    {
        return Attribute::make(
            set: fn (int $value): int => $this->boundedInteger(
                $value,
                0,
                self::MAX_RETRY_LIMIT,
                'AI provider retry limit',
            ),
        );
    }

    private function boundedInteger(int $value, int $minimum, int $maximum, string $label): int
    {
        if ($value < $minimum || $value > $maximum) {
            throw new InvalidArgumentException("{$label} must be between {$minimum} and {$maximum}.");
        }

        return $value;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'health_status' => AiProviderHealthStatus::class,
            'request_timeout_seconds' => 'integer',
            'retry_limit' => 'integer',
            'last_verified_at' => 'datetime',
            'lock_version' => 'integer',
        ];
    }
}
