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
        Schema::create('brand_indexed_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('wordpress_post_id')->nullable();
            $table->string('url', 1024);
            $table->char('url_hash', 64);
            $table->string('title', 500);
            $table->string('slug', 255)->nullable();
            $table->string('page_type', 32)->default('post');
            $table->text('snippet')->nullable();
            $table->json('keywords')->nullable();
            $table->string('source', 32)->default('wordpress_api');
            $table->timestamp('last_indexed_at')->nullable();
            $table->timestamps();

            $table->unique(['brand_id', 'url_hash']);
            $table->index(['brand_id', 'last_indexed_at']);
            $table->index(['workspace_id', 'brand_id']);
        });

        Schema::create('brand_indexing_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 32)->default('completed');
            $table->string('source', 32)->default('wordpress_api');
            $table->unsignedInteger('pages_discovered')->default(0);
            $table->unsignedInteger('pages_indexed')->default(0);
            $table->text('error_message')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamps();

            $table->index(['brand_id', 'created_at']);
            $table->index(['workspace_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brand_indexing_audits');
        Schema::dropIfExists('brand_indexed_pages');
    }
};
