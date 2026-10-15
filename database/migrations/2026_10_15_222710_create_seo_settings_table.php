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
        Schema::create('seo_settings', function (Blueprint $table) {
           $table->id();
        $table->text('page_name')->nullable();
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
        $table->text('value')->nullable();
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_settings');
    }
};
