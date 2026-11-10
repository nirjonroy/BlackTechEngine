<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_transaction_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('provider', 64);
            $table->string('provider_event_id', 191);
            $table->string('provider_reference', 191);
            $table->string('event_type', 64);
            $table->string('payment_status', 24);
            $table->string('processing_status', 24)->default('received');
            $table->char('payload_hash', 64);
            $table->string('failure_code', 96)->nullable();
            $table->dateTime('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_event_id'], 'payment_webhooks_provider_event_unique');
            $table->index(['provider', 'provider_reference'], 'payment_webhooks_provider_reference_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_events');
    }
};
