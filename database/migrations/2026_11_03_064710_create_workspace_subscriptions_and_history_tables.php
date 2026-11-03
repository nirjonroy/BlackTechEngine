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
        Schema::create('workspace_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('pricing_plan_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('status', 32)->default('pending_activation');
            $table->string('billing_interval', 16)->default('manual');
            $table->char('currency', 3);
            $table->unsignedBigInteger('price_minor')->default(0);
            $table->json('entitlement_snapshot');
            $table->string('activation_source', 32)->default('admin_grant');
            $table->string('external_reference', 191)->nullable()->unique();
            $table->unsignedInteger('lock_version')->default(0);
            $table->dateTime('starts_at');
            $table->dateTime('current_period_starts_at');
            $table->dateTime('current_period_ends_at')->nullable();
            $table->dateTime('renews_at')->nullable();
            $table->dateTime('trial_ends_at')->nullable();
            $table->boolean('cancel_at_period_end')->default(false);
            $table->dateTime('cancelled_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'renews_at'], 'workspace_subscriptions_status_renewal_index');
            $table->index(['pricing_plan_id', 'status'], 'workspace_subscriptions_plan_status_index');
        });

        Schema::create('subscription_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workspace_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actor_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('action', 64);
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->unsignedInteger('lock_version');
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['workspace_subscription_id', 'id'], 'subscription_histories_subscription_id_index');
            $table->index(['workspace_id', 'created_at'], 'subscription_histories_workspace_created_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_histories');
        Schema::dropIfExists('workspace_subscriptions');
    }
};
