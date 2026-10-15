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
        Schema::create('about_stats', function (Blueprint $table) {
            $table->id();
        $table->foreignId('about_page_id')->constrained()->cascadeOnDelete();
        $table->string('value'); 
        $table->string('label'); 
        $table->integer('order_by')->default(0);
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('about_stats');
    }
};
