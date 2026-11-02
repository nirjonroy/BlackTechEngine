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
        Schema::create('social_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->restrictOnDelete();
            $table->foreignId('brand_id')->constrained()->restrictOnDelete();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('source_ai_content_draft_id')->nullable()->constrained('ai_content_drafts')->nullOnDelete();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 180);
            $table->string('campaign', 120)->nullable();
            $table->uuid('idempotency_key');
            $table->char('request_fingerprint', 64);
            $table->string('status', 32)->default('draft');
            $table->unsignedInteger('lock_version')->default(0);
            $table->unsignedInteger('current_revision')->default(1);
            $table->unsignedInteger('source_revision')->nullable();
            $table->json('source_snapshot')->nullable();
            $table->text('review_feedback')->nullable();
            $table->timestamp('review_submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->dateTime('planned_at')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->string('published_url', 2048)->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'status', 'planned_at'], 'social_posts_workspace_status_planned_index');
            $table->index(['brand_id', 'updated_at'], 'social_posts_brand_updated_index');
            $table->unique(['workspace_id', 'idempotency_key'], 'social_posts_workspace_idempotency_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_posts');
    }
};
