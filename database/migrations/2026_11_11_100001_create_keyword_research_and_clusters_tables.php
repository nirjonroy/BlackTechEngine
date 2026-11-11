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
        Schema::create('brand_topic_clusters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('pillar_keyword')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'brand_id']);
        });

        Schema::create('brand_keywords', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_topic_cluster_id')->nullable()->constrained('brand_topic_clusters')->nullOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('keyword');
            $table->string('normalized_keyword');
            $table->string('search_intent')->default('informational');
            $table->unsignedInteger('search_volume')->nullable();
            $table->unsignedTinyInteger('difficulty_score')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_starred')->default(false);
            $table->timestamps();

            $table->unique(['brand_id', 'normalized_keyword']);
            $table->index(['workspace_id', 'brand_id', 'search_intent']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brand_keywords');
        Schema::dropIfExists('brand_topic_clusters');
    }
};
