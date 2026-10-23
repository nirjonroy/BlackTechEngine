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
        Schema::create('homepage_settings', function (Blueprint $table) {
            $table->id();
            $table->string('hero_badge')->nullable();
            $table->string('hero_title')->nullable();
            $table->string('hero_highlighted_title')->nullable();
            $table->text('hero_description')->nullable();
            $table->string('primary_cta_label')->nullable();
            $table->string('primary_cta_destination')->nullable();
            $table->string('secondary_cta_label')->nullable();
            $table->string('secondary_cta_destination')->nullable();
            $table->string('scanner_heading')->nullable();
            $table->text('scanner_description')->nullable();
            $table->string('scanner_button_label')->nullable();
            $table->string('integrations_heading')->nullable();
            $table->text('integrations_description')->nullable();
            $table->string('features_badge')->nullable();
            $table->string('features_heading')->nullable();
            $table->text('features_description')->nullable();
            $table->string('calculator_badge')->nullable();
            $table->string('calculator_heading')->nullable();
            $table->text('calculator_description')->nullable();
            $table->text('calculator_note')->nullable();
            $table->string('calculator_cta_label')->nullable();
            $table->string('calculator_cta_destination')->nullable();
            $table->string('final_cta_heading')->nullable();
            $table->text('final_cta_description')->nullable();
            $table->string('final_primary_cta_label')->nullable();
            $table->string('final_primary_cta_destination')->nullable();
            $table->string('final_secondary_cta_label')->nullable();
            $table->string('final_secondary_cta_destination')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('homepage_settings');
    }
};
