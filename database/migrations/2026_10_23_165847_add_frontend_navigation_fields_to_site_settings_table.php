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
        Schema::table('site_settings', function (Blueprint $table) {
            $table->json('header_navigation')->nullable();
            $table->text('footer_description')->nullable();
            $table->json('footer_columns')->nullable();
            $table->string('footer_copyright', 500)->nullable();
            $table->string('x_url', 2048)->nullable();
            $table->string('linkedin_url', 2048)->nullable();
            $table->string('github_url', 2048)->nullable();
            $table->string('youtube_url', 2048)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'header_navigation',
                'footer_description',
                'footer_columns',
                'footer_copyright',
                'x_url',
                'linkedin_url',
                'github_url',
                'youtube_url',
            ]);
        });
    }
};
