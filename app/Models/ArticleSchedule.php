<?php

namespace App\Models;

use App\Enums\AiContentLength;
use App\Enums\AiContentType;
use App\Enums\ArticleScheduleFrequency;
use App\Enums\ArticleScheduleTargetStatus;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\ArticleScheduleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'workspace_id',
    'brand_id',
    'created_by_user_id',
    'brand_keyword_id',
    'brand_topic_cluster_id',
    'title',
    'primary_keyword',
    'secondary_keywords',
    'content_type',
    'content_length',
    'tone',
    'target_audience',
    'language',
    'region',
    'frequency',
    'scheduled_date',
    'scheduled_time',
    'timezone',
    'days_of_week',
    'target_status',
    'publish_as_status',
    'auto_publish_to_wordpress',
    'is_active',
    'next_run_at',
    'last_run_at',
    'last_error',
    'failure_count',
    'total_runs',
])]
class ArticleSchedule extends Model
{
    /** @use HasFactory<ArticleScheduleFactory> */
    use HasFactory;

    protected $table = 'article_schedules';

    protected static function booted(): void
    {
        static::creating(function (self $schedule): void {
            if ($schedule->next_run_at === null && $schedule->is_active) {
                $schedule->next_run_at = $schedule->calculateNextRun();
            }
        });
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

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /** @return BelongsTo<BrandKeyword, $this> */
    public function brandKeyword(): BelongsTo
    {
        return $this->belongsTo(BrandKeyword::class, 'brand_keyword_id');
    }

    /** @return BelongsTo<BrandTopicCluster, $this> */
    public function topicCluster(): BelongsTo
    {
        return $this->belongsTo(BrandTopicCluster::class, 'brand_topic_cluster_id');
    }

    /** @return HasMany<ArticleScheduleRun, $this> */
    public function runs(): HasMany
    {
        return $this->hasMany(ArticleScheduleRun::class);
    }

    /** @return HasOne<ArticleScheduleRun, $this> */
    public function latestRun(): HasOne
    {
        return $this->hasOne(ArticleScheduleRun::class)->latestOfMany();
    }

    public function pause(): void
    {
        $this->update(['is_active' => false]);
    }

    public function resume(): void
    {
        $this->update([
            'is_active' => true,
            'failure_count' => 0,
            'last_error' => null,
            'next_run_at' => $this->calculateNextRun(),
        ]);
    }

    public function calculateNextRun(?CarbonInterface $reference = null): ?CarbonImmutable
    {
        $tz = $this->timezone ?: 'UTC';
        $timeStr = $this->scheduled_time ?: '09:00';
        $parts = explode(':', $timeStr);
        $hour = isset($parts[0]) ? (int) $parts[0] : 9;
        $minute = isset($parts[1]) ? (int) $parts[1] : 0;

        if ($this->frequency === ArticleScheduleFrequency::Once) {
            if (empty($this->scheduled_date)) {
                return null;
            }

            $dateString = is_string($this->scheduled_date)
                ? $this->scheduled_date
                : $this->scheduled_date->format('Y-m-d');

            $target = CarbonImmutable::parse($dateString, $tz)->setTime($hour, $minute, 0);

            if ($reference !== null && $target->isBefore($reference)) {
                return null;
            }

            return $target->utc();
        }

        $nowLocal = ($reference ? CarbonImmutable::instance($reference) : CarbonImmutable::now())->setTimezone($tz);

        if ($this->frequency === ArticleScheduleFrequency::Daily) {
            $candidate = $nowLocal->setTime($hour, $minute, 0);

            if ($candidate->isAfter($nowLocal)) {
                return $candidate->utc();
            }

            return $candidate->addDay()->utc();
        }

        if ($this->frequency === ArticleScheduleFrequency::Weekly) {
            $days = is_array($this->days_of_week) && count($this->days_of_week) > 0
                ? array_map('intval', $this->days_of_week)
                : [1];

            for ($offset = 0; $offset <= 7; $offset++) {
                $check = $nowLocal->addDays($offset);
                if (in_array($check->dayOfWeekIso, $days, true)) {
                    $candidate = $check->setTime($hour, $minute, 0);
                    if ($candidate->isAfter($nowLocal)) {
                        return $candidate->utc();
                    }
                }
            }

            return $nowLocal->addWeek()->setTime($hour, $minute, 0)->utc();
        }

        return null;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'frequency' => ArticleScheduleFrequency::class,
            'target_status' => ArticleScheduleTargetStatus::class,
            'content_type' => AiContentType::class,
            'content_length' => AiContentLength::class,
            'secondary_keywords' => 'array',
            'days_of_week' => 'array',
            'auto_publish_to_wordpress' => 'boolean',
            'is_active' => 'boolean',
            'scheduled_date' => 'date:Y-m-d',
            'next_run_at' => 'datetime',
            'last_run_at' => 'datetime',
            'failure_count' => 'integer',
            'total_runs' => 'integer',
        ];
    }
}

