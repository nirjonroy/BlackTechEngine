<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PricingPlan extends Model
{
    protected $guarded = []; 

    public function features()
    {
        return $this->hasMany(PricingPlanFeature::class)->orderBy('order_by');
    }
}
