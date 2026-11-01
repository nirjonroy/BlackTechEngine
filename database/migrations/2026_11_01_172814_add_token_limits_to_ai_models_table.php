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
        Schema::table('ai_models', function (Blueprint $table) {
            $table->unsignedInteger('input_token_limit')->nullable()->after('capabilities');
            $table->unsignedInteger('output_token_limit')->nullable()->after('input_token_limit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_models', function (Blueprint $table) {
            $table->dropColumn(['input_token_limit', 'output_token_limit']);
        });
    }
};
