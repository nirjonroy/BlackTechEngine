<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AboutPage extends Model
{
    protected $guarded = []; // Shob fillable

    // Relationships
    public function stats()
    {
        return $this->hasMany(AboutStat::class)->orderBy('order_by');
    }

    public function comparisons()
    {
        return $this->hasMany(AboutComparison::class)->orderBy('order_by');
    }

    public function architectures()
    {
        return $this->hasMany(AboutArchitecture::class)->orderBy('order_by');
    }

    public function coreValues()
    {
        return $this->hasMany(AboutCoreValue::class)->orderBy('order_by');
    }
}
