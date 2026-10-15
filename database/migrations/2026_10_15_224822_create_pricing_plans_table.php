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
        Schema::create('pricing_plans', function (Blueprint $table) {
            $table->id();
        // Plan Details
        $table->string('name'); 
        $table->string('badge')->nullable(); 
        $table->string('title')->nullable(); 
        $table->text('description')->nullable();
        $table->decimal('monthly_price', 8, 2)->default(0); 
        $table->decimal('annual_price', 8, 2)->default(0); 
        $table->boolean('is_popular')->default(false); 
        $table->string('button_text')->nullable();
        $table->string('button_link')->nullable();

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
        Schema::dropIfExists('pricing_plans');
    }
};
