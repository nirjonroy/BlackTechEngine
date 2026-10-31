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
        Schema::create('ai_content_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->restrictOnDelete();
            $table->foreignId('brand_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('ai_provider_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ai_model_id')->nullable()->constrained()->nullOnDelete();
            $table->string('content_type', 64);
            $table->string('topic', 240);
            $table->string('primary_keyword', 160);
            $table->json('secondary_keywords')->nullable();
            $table->text('target_audience');
            $table->string('language', 64);
            $table->string('region', 120);
            $table->string('content_length', 32);
            $table->string('tone', 500);
            $table->string('seo_title', 120)->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->string('suggested_slug', 191)->nullable();
            $table->json('outline')->nullable();
            $table->longText('content')->nullable();
            $table->json('faq_entries')->nullable();
            $table->json('internal_link_opportunities')->nullable();
            $table->json('external_research_topics')->nullable();
            $table->json('verification_notes')->nullable();
            $table->string('status', 32)->default('draft');
            $table->timestamps();

            $table->index(['workspace_id', 'status', 'updated_at'], 'ai_content_drafts_workspace_status_index');
            $table->index(['brand_id', 'updated_at'], 'ai_content_drafts_brand_updated_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_content_drafts');
    }
};
