<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AboutStat extends Model
{
    protected $fillable = [
        'value',
        'label',
        'order_by',
    ];
}
