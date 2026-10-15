<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = [
        'site_name', 'logo_dark', 'logo_light', 'favicon', 
        'email', 'mobile', 'address', 'facebook_url'
    ];
}
