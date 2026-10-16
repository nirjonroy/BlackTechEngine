<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeaturePageSetting extends Model
{
    protected $guarded = [];

    protected $casts = [
        'features_json' => 'array',
        'testimonials_json' => 'array',
    ];
}