<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{
    protected $fillable = [
        'title', 'slug', 'content', 'image', 'category',
        'seo_title', 'seo_description', 'meta_title', 'meta_description', 
        'meta_image', 'author', 'publisher', 'copyright', 'site_name', 'keywords'
    ];
}
