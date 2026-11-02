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
        Schema::create('social_post_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_post_id')->constrained()->cascadeOnDelete();
            $table->string('platform', 48);
            $table->text('caption');
            $table->json('hashtags')->nullable();
            $table->string('cta_text', 160)->nullable();
            $table->string('target_url', 2048)->nullable();
            $table->string('media_reference', 500)->nullable();
            $table->string('image_alt_text', 500)->nullable();
            $table->timestamps();

            $table->unique(['social_post_id', 'platform'], 'social_post_variants_post_platform_unique');
            $table->index(['platform', 'social_post_id'], 'social_post_variants_platform_post_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_post_variants');
    }
};
