<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PricingPageSetting extends Model
{
    protected $guarded = [];

    protected $casts = [
        'faqs' => 'array',
    ];
}
