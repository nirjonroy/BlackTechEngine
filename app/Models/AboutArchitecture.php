<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AboutArchitecture extends Model
{
    protected $fillable = [
        'step_number',
        'title',
        'description',
        'order_by',
    ];
}
