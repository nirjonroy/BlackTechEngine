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
        Schema::create('article_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('brand_keyword_id')->nullable()->constrained('brand_keywords')->nullOnDelete();
            $table->foreignId('brand_topic_cluster_id')->nullable()->constrained('brand_topic_clusters')->nullOnDelete();
            $table->string('title', 240);
            $table->string('primary_keyword', 160);
            $table->json('secondary_keywords')->nullable();
            $table->string('content_type', 32)->default('seo_blog_article');
            $table->string('content_length', 32)->default('standard');
            $table->string('tone', 240)->nullable();
            $table->text('target_audience')->nullable();
            $table->string('language', 64)->default('en');
            $table->string('region', 120)->nullable();
            $table->string('frequency', 32)->default('once');
            $table->date('scheduled_date')->nullable();
            $table->string('scheduled_time', 8)->default('09:00');
            $table->string('timezone', 64)->default('UTC');
            $table->json('days_of_week')->nullable();
            $table->string('target_status', 32)->default('draft');
            $table->string('publish_as_status', 32)->default('draft');
            $table->boolean('auto_publish_to_wordpress')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('next_run_at')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->text('last_error')->nullable();
            $table->unsignedInteger('failure_count')->default(0);
            $table->unsignedInteger('total_runs')->default(0);
            $table->timestamps();

            $table->index(['brand_id', 'is_active', 'next_run_at']);
            $table->index(['workspace_id', 'next_run_at']);
        });

        Schema::create('article_schedule_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('article_schedule_id')->constrained('article_schedules')->cascadeOnDelete();
            $table->foreignId('ai_content_draft_id')->nullable()->constrained('ai_content_drafts')->nullOnDelete();
            $table->foreignId('wordpress_published_post_id')->nullable()->constrained('wordpress_published_posts')->nullOnDelete();
            $table->string('status', 32)->default('pending');
            $table->timestamp('scheduled_for');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['article_schedule_id', 'created_at']);
            $table->index(['workspace_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_schedule_runs');
        Schema::dropIfExists('article_schedules');
    }
};
