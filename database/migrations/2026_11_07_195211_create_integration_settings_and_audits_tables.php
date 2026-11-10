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
        Schema::create('integration_settings', function (Blueprint $table) {
            $table->id();
            $table->text('gemini_api_key')->nullable();
            $table->boolean('gemini_managed')->default(false);
            $table->boolean('gemini_enabled')->default(false);
            $table->string('stripe_mode', 8)->default('test');
            $table->string('stripe_publishable_key', 255)->nullable();
            $table->text('stripe_secret_key')->nullable();
            $table->text('stripe_webhook_secret')->nullable();
            $table->boolean('stripe_enabled')->default(false);
            $table->string('mail_host', 255)->nullable();
            $table->unsignedSmallInteger('mail_port')->nullable();
            $table->string('mail_encryption', 8)->nullable();
            $table->string('mail_username', 255)->nullable();
            $table->text('mail_password')->nullable();
            $table->string('mail_from_name', 255)->nullable();
            $table->string('mail_from_address', 255)->nullable();
            $table->string('mail_reply_to', 255)->nullable();
            $table->boolean('mail_enabled')->default(false);
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();
        });

        Schema::create('integration_setting_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acting_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('integration_setting_id')->constrained()->restrictOnDelete();
            $table->string('integration', 32);
            $table->string('action', 64)->index();
            $table->uuid('request_id')->index();
            $table->json('changed_fields');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['integration', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integration_setting_audits');
        Schema::dropIfExists('integration_settings');
    }
};
