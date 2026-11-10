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
        Schema::create('wordpress_site_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete()->unique();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('site_url');
            $table->string('rest_api_endpoint');
            $table->string('username');
            $table->text('application_password');
            $table->string('status')->default('disconnected');
            $table->string('site_name')->nullable();
            $table->string('wordpress_version')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });

        Schema::create('wordpress_published_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wordpress_site_connection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ai_content_draft_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('wordpress_post_id')->nullable();
            $table->string('post_url')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->text('last_error')->nullable();
            $table->json('transferred_payload')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'brand_id']);
            $table->index(['ai_content_draft_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wordpress_published_posts');
        Schema::dropIfExists('wordpress_site_connections');
    }
};
