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
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('website_url', 2048);
            $table->text('business_description');
            $table->string('industry', 120);
            $table->text('target_audience');
            $table->string('primary_market', 120);
            $table->string('preferred_language', 64);
            $table->text('voice_tone');
            $table->json('content_objectives');
            $table->json('seo_topic_categories');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['workspace_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};
