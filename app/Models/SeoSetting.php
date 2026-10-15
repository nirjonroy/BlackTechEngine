<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoSetting extends Model
{
    protected $fillable = [
        'page_name', 'seo_title', 'seo_description', 'meta_title', 
        'meta_description', 'meta_image', 'author', 'publisher', 
        'copyright', 'site_name', 'keywords', 'value'
    ];
}
