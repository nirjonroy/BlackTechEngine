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
        Schema::create('ai_providers', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64)->unique();
            $table->string('display_name', 120);
            $table->string('adapter_type', 64)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->string('health_status', 32)->default('unverified');
            $table->unsignedSmallInteger('request_timeout_seconds')->default(30);
            $table->unsignedTinyInteger('retry_limit')->default(0);
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamps();

            $table->index(['is_enabled', 'health_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_providers');
    }
};
