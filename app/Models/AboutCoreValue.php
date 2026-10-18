<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AboutCoreValue extends Model
{
    protected $fillable = [
        'icon',
        'title',
        'description',
        'order_by',
    ];
}
