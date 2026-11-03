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
        Schema::table('pricing_plans', function (Blueprint $table) {
            $table->string('code', 64)->nullable()->unique()->after('name');
            $table->char('billing_currency', 3)->default('USD')->after('annual_price');
            $table->unsignedBigInteger('monthly_price_minor')->nullable()->after('billing_currency');
            $table->unsignedBigInteger('annual_price_minor')->nullable()->after('monthly_price_minor');
            $table->unsignedInteger('billing_version')->default(1)->after('annual_price_minor');
            $table->unsignedSmallInteger('trial_days')->default(0)->after('billing_version');
            $table->boolean('is_billable')->default(false)->after('is_active');
        });

        Schema::create('pricing_plan_entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pricing_plan_id')->constrained()->cascadeOnDelete();
            $table->string('key', 96);
            $table->boolean('is_enabled')->default(false);
            $table->unsignedBigInteger('limit')->nullable();
            $table->boolean('is_unlimited')->default(false);
            $table->string('unit', 32)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['pricing_plan_id', 'key'], 'plan_entitlements_plan_key_unique');
            $table->index(['key', 'is_enabled'], 'plan_entitlements_key_enabled_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_plan_entitlements');

        Schema::table('pricing_plans', function (Blueprint $table) {
            $table->dropUnique('pricing_plans_code_unique');
            $table->dropColumn([
                'code',
                'billing_currency',
                'monthly_price_minor',
                'annual_price_minor',
                'billing_version',
                'trial_days',
                'is_billable',
            ]);
        });
    }
};
