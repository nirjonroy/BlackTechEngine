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
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->string('provider_payment_reference', 191)->nullable()->after('provider_reference');
            $table->index(
                ['provider', 'provider_payment_reference'],
                'payment_transactions_provider_payment_reference_index',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropIndex('payment_transactions_provider_payment_reference_index');
            $table->dropColumn('provider_payment_reference');
        });
    }
};
