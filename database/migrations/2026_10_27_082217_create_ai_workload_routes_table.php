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
        Schema::create('ai_workload_routes', function (Blueprint $table) {
            $table->id();
            $table->string('workload_key', 64)->unique();
            $table->foreignId('primary_ai_model_id')->nullable()->constrained('ai_models')->restrictOnDelete();
            $table->foreignId('fallback_ai_model_id')->nullable()->constrained('ai_models')->restrictOnDelete();
            $table->boolean('is_enabled')->default(false);
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index('is_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_workload_routes');
    }
};
