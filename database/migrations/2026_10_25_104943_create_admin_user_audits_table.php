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
        Schema::create('admin_user_audits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('acting_admin_id')->index();
            $table->unsignedBigInteger('target_user_id')->index();
            $table->string('action', 64)->index();
            $table->uuid('request_id')->nullable()->index();
            $table->json('changes');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_user_audits');
    }
};
