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
        Schema::create('ai_configuration_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acting_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->nullableMorphs('subject');
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
        Schema::dropIfExists('ai_configuration_audits');
    }
};
