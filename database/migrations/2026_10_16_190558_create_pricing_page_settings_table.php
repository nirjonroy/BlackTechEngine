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
        Schema::create('pricing_page_settings', function (Blueprint $table) {
            $table->id();
        
        // Page Info
        $table->string('page_name')->default('Pricing');

        // Hero Section
        $table->string('hero_badge_icon')->nullable(); 
        $table->string('hero_badge_text')->nullable(); 
        $table->string('hero_title_normal')->nullable();
        $table->string('hero_title_highlighted')->nullable(); 
        $table->text('hero_description')->nullable();

        // Calculator Section
        $table->string('calc_title')->nullable(); 
        $table->text('calc_description')->nullable();
        
        // FAQ Section
        $table->string('faq_title')->nullable(); 
        $table->text('faq_description')->nullable();
        $table->json('faqs')->nullable(); 

        // SEO Fields
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
        Schema::dropIfExists('pricing_page_settings');
    }
};
