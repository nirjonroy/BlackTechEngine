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
        Schema::table('ai_generation_attempts', function (Blueprint $table) {
            $table->string('single_use_key', 64)
                ->nullable()
                ->unique('ai_generation_attempts_single_use_key_unique')
                ->after('idempotency_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_generation_attempts', function (Blueprint $table) {
            $table->dropUnique('ai_generation_attempts_single_use_key_unique');
            $table->dropColumn('single_use_key');
        });
    }
};
