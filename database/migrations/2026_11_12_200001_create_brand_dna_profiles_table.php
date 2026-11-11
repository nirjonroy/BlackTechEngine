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
        Schema::create('brand_dna_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 32)->default('pending');
            $table->json('source_urls')->nullable();
            $table->text('extracted_raw_summary')->nullable();
            $table->text('brand_identity')->nullable();
            $table->text('business_description')->nullable();
            $table->string('industry', 255)->nullable();
            $table->text('target_audience')->nullable();
            $table->string('primary_market', 100)->nullable();
            $table->string('voice_tone', 255)->nullable();
            $table->json('key_services')->nullable();
            $table->json('suggested_keywords')->nullable();
            $table->json('content_themes')->nullable();
            $table->json('brand_values')->nullable();
            $table->text('competitor_differentiation')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('last_error')->nullable();
            $table->timestamp('last_analyzed_at')->nullable();
            $table->timestamps();

            $table->index(['brand_id', 'is_approved'], 'brand_dna_brand_approved_idx');
            $table->index(['workspace_id', 'brand_id'], 'brand_dna_workspace_brand_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brand_dna_profiles');
    }
};

