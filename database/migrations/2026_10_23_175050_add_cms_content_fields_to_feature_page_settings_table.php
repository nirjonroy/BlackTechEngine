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
        Schema::table('feature_page_settings', function (Blueprint $table) {
            $table->string('additional_features_heading')->nullable();
            $table->text('additional_features_description')->nullable();
            $table->text('calc_disclaimer')->nullable();
            $table->string('final_cta_heading')->nullable();
            $table->text('final_cta_description')->nullable();
            $table->string('final_primary_cta_label')->nullable();
            $table->string('final_primary_cta_destination')->nullable();
            $table->string('final_secondary_cta_label')->nullable();
            $table->string('final_secondary_cta_destination')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feature_page_settings', function (Blueprint $table) {
            $table->dropColumn([
                'additional_features_heading',
                'additional_features_description',
                'calc_disclaimer',
                'final_cta_heading',
                'final_cta_description',
                'final_primary_cta_label',
                'final_primary_cta_destination',
                'final_secondary_cta_label',
                'final_secondary_cta_destination',
            ]);
        });
    }
};
