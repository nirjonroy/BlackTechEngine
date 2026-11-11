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
        Schema::create('ai_content_draft_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ai_content_draft_id')->constrained('ai_content_drafts')->cascadeOnDelete();
            $table->foreignId('ai_model_id')->nullable()->constrained('ai_models')->nullOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('prompt');
            $table->text('user_instructions')->nullable();
            $table->string('image_path', 1024);
            $table->string('file_name', 255);
            $table->string('mime_type', 64);
            $table->unsignedInteger('file_size_bytes');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('alt_text', 500);
            $table->string('status', 32)->default('completed');
            $table->text('error_message')->nullable();
            $table->unsignedBigInteger('wordpress_media_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['ai_content_draft_id', 'is_active', 'created_at']);
            $table->index(['workspace_id', 'brand_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_content_draft_images');
    }
};
