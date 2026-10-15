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
        Schema::create('about_pages', function (Blueprint $table) {
            $table->id();
        
        // Hero Section
        $table->string('hero_badge')->nullable();
        $table->string('hero_title')->nullable();
        $table->text('hero_description')->nullable();

        // Section Titles
        $table->string('comparison_title')->nullable();
        $table->text('comparison_description')->nullable();
        $table->string('architecture_badge')->nullable();
        $table->string('architecture_title')->nullable();
        $table->text('architecture_description')->nullable();
        $table->string('core_values_title')->nullable();
        $table->text('core_values_description')->nullable();

        // CTA Section
        $table->string('cta_title')->nullable();
        $table->text('cta_description')->nullable();
        $table->string('cta_btn1_text')->nullable();
        $table->string('cta_btn1_link')->nullable();
        $table->string('cta_btn2_text')->nullable();
        $table->string('cta_btn2_link')->nullable();

        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('about_pages');
    }
};
