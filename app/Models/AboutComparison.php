<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AboutComparison extends Model
{
    protected $fillable = [
        'capability',
        'traditional_text',
        'bte_text',
        'order_by',
    ];
}
