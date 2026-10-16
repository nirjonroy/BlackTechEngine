<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('feature_page_settings', function (Blueprint $table) {
           $table->id();
            
            // Page Identification
            $table->string('page_name')->default('Features');

            // Hero Section
            $table->string('hero_badge')->nullable(); 
            $table->string('hero_title_normal')->nullable(); 
            $table->string('hero_title_highlighted')->nullable(); 
            $table->text('hero_description')->nullable();

            
            $table->json('features_json')->nullable(); 

            
            $table->string('calc_title')->nullable();
            $table->text('calc_description')->nullable();

            
            $table->string('testimonial_badge')->nullable(); 
            $table->string('testimonial_title')->nullable(); 
            
            $table->json('testimonials_json')->nullable();

            // SEO Data Fields
            $table->text('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_image')->nullable();
            $table->string('author')->nullable();
            $table->string('publisher')->nullable();
            $table->string('copyright')->nullable();
            $table->string('site_name')->nullable();
            $table->text('keywords')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feature_page_settings');
    }
};
