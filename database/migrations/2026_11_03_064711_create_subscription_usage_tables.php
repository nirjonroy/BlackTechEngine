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
        Schema::create('usage_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workspace_id')->constrained()->restrictOnDelete();
            $table->string('entitlement_key', 96);
            $table->dateTime('period_starts_at');
            $table->dateTime('period_ends_at')->nullable();
            $table->unsignedBigInteger('used')->default(0);
            $table->unsignedBigInteger('reserved')->default(0);
            $table->bigInteger('manual_adjustment')->default(0);
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();

            $table->unique(['workspace_subscription_id', 'entitlement_key', 'period_starts_at'], 'usage_counters_subscription_key_period_unique');
            $table->index(['workspace_id', 'entitlement_key', 'period_ends_at'], 'usage_counters_workspace_key_period_index');
        });

        Schema::create('usage_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usage_counter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workspace_subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workspace_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actor_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('entitlement_key', 96);
            $table->uuid('idempotency_key');
            $table->unsignedBigInteger('quantity');
            $table->string('status', 24)->default('reserved');
            $table->string('reference_type', 120)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->json('metadata')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('committed_at')->nullable();
            $table->dateTime('released_at')->nullable();
            $table->timestamps();

            $table->unique(['workspace_id', 'entitlement_key', 'idempotency_key'], 'usage_events_workspace_key_idempotency_unique');
            $table->index(['status', 'expires_at'], 'usage_events_status_expiry_index');
            $table->index(['reference_type', 'reference_id'], 'usage_events_reference_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usage_events');
        Schema::dropIfExists('usage_counters');
    }
};
