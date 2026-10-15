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
        Schema::create('blogs', function (Blueprint $table) {
           $table->id();
        // Basic Blog Fields
        $table->string('title');
        $table->string('slug')->unique();
        $table->longText('content')->nullable();
        $table->string('image')->nullable(); // Main blog image
        $table->string('category')->nullable();
        
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
        Schema::dropIfExists('blogs');
    }
};
