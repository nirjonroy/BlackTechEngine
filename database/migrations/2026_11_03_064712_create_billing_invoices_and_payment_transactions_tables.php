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
        Schema::create('billing_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_subscription_id')->constrained()->restrictOnDelete();
            $table->foreignId('workspace_id')->constrained()->restrictOnDelete();
            $table->string('number', 64)->unique();
            $table->string('status', 32)->default('draft');
            $table->char('currency', 3);
            $table->unsignedBigInteger('subtotal_minor')->default(0);
            $table->unsignedBigInteger('discount_minor')->default(0);
            $table->unsignedBigInteger('tax_minor')->default(0);
            $table->unsignedBigInteger('total_minor')->default(0);
            $table->unsignedBigInteger('refunded_minor')->default(0);
            $table->dateTime('period_starts_at');
            $table->dateTime('period_ends_at')->nullable();
            $table->dateTime('due_at')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->dateTime('voided_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'created_at'], 'billing_invoices_workspace_created_index');
            $table->index(['status', 'due_at'], 'billing_invoices_status_due_index');
        });

        Schema::create('billing_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_invoice_id')->constrained()->cascadeOnDelete();
            $table->string('description', 255);
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedBigInteger('unit_amount_minor');
            $table->unsignedBigInteger('total_amount_minor');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_invoice_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('workspace_subscription_id')->constrained()->restrictOnDelete();
            $table->foreignId('workspace_id')->constrained()->restrictOnDelete();
            $table->string('provider', 64);
            $table->string('provider_reference', 191)->nullable();
            $table->string('provider_event_id', 191)->nullable();
            $table->uuid('idempotency_key')->unique();
            $table->string('type', 24)->default('charge');
            $table->string('status', 24)->default('pending');
            $table->string('reconciliation_status', 24)->default('unreconciled');
            $table->char('currency', 3);
            $table->unsignedBigInteger('amount_minor');
            $table->string('failure_code', 96)->nullable();
            $table->json('metadata')->nullable();
            $table->dateTime('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_reference'], 'payments_provider_reference_unique');
            $table->unique(['provider', 'provider_event_id'], 'payments_provider_event_unique');
            $table->index(['workspace_id', 'created_at'], 'payments_workspace_created_index');
            $table->index(['status', 'reconciliation_status'], 'payments_status_reconciliation_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('billing_invoice_items');
        Schema::dropIfExists('billing_invoices');
    }
};
