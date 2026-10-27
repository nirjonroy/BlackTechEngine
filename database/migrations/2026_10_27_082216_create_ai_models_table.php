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
        Schema::create('ai_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_provider_id')->constrained()->restrictOnDelete();
            $table->string('provider_model_id', 191);
            $table->string('display_name', 120);
            $table->string('modality', 32);
            $table->json('capabilities');
            $table->boolean('is_enabled')->default(false);
            $table->string('availability_status', 32)->default('unverified');
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamps();

            $table->unique(['ai_provider_id', 'provider_model_id']);
            $table->index(['ai_provider_id', 'is_enabled', 'availability_status'], 'ai_models_provider_state_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_models');
    }
};
