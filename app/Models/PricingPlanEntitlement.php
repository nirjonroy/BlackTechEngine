<?php

namespace App\Models;

use Database\Factories\PricingPlanEntitlementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['pricing_plan_id', 'key', 'is_enabled', 'limit', 'is_unlimited', 'unit', 'metadata'])]
class PricingPlanEntitlement extends Model
{
    /** @use HasFactory<PricingPlanEntitlementFactory> */
    use HasFactory;

    /** @return BelongsTo<PricingPlan, $this> */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(PricingPlan::class, 'pricing_plan_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'limit' => 'integer',
            'is_unlimited' => 'boolean',
            'metadata' => 'array',
        ];
    }
}
