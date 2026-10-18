<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PricingPlanFeature extends Model
{
    protected $fillable = [
        'name',
        'is_included',
        'order_by',
    ];
}
