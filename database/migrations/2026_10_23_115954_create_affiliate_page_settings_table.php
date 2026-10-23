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
        Schema::create('affiliate_page_settings', function (Blueprint $table) {
            $table->id();
            $table->string('hero_badge')->nullable();
            $table->string('hero_title')->nullable();
            $table->string('hero_highlighted_title')->nullable();
            $table->text('hero_description')->nullable();
            $table->string('primary_cta_label')->nullable();
            $table->string('primary_cta_destination')->nullable();
            $table->string('secondary_cta_label')->nullable();
            $table->string('secondary_cta_destination')->nullable();
            $table->text('hero_disclosure')->nullable();
            $table->string('status_heading')->nullable();
            $table->text('status_description')->nullable();
            $table->string('terms_heading')->nullable();
            $table->string('availability_status')->nullable();
            $table->text('availability_description')->nullable();
            $table->string('workflow_heading')->nullable();
            $table->text('workflow_description')->nullable();
            $table->json('workflow_steps')->nullable();
            $table->string('resources_badge')->nullable();
            $table->string('resources_heading')->nullable();
            $table->text('resources_description')->nullable();
            $table->string('resources_availability_status')->nullable();
            $table->string('final_cta_label')->nullable();
            $table->string('final_cta_destination')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affiliate_page_settings');
    }
};
