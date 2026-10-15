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
        Schema::create('about_core_values', function (Blueprint $table) {
            $table->id();
        $table->foreignId('about_page_id')->constrained()->cascadeOnDelete();
        $table->string('icon')->nullable(); // jemon: shield-check
        $table->string('title');
        $table->text('description');
        $table->integer('order_by')->default(0);
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('about_core_values');
    }
};
