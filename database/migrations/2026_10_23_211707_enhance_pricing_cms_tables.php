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
            $table->unsignedInteger('display_order')->default(0)->after('annual_price');
            $table->boolean('is_active')->default(true)->after('display_order');
            $table->string('popular_label')->nullable()->after('is_popular');
        });

        Schema::table('pricing_page_settings', function (Blueprint $table) {
            $table->char('currency_code', 3)->default('USD')->after('hero_description');
            $table->string('plans_heading')->nullable()->after('currency_code');
            $table->string('billing_monthly_label')->nullable()->after('plans_heading');
            $table->string('billing_annual_label')->nullable()->after('billing_monthly_label');
            $table->text('annual_billing_description')->nullable()->after('billing_annual_label');
            $table->string('monthly_period_label')->nullable()->after('annual_billing_description');
            $table->string('annual_period_label')->nullable()->after('monthly_period_label');
            $table->string('empty_state_title')->nullable()->after('annual_period_label');
            $table->text('empty_state_description')->nullable()->after('empty_state_title');
            $table->string('calc_articles_label')->nullable()->after('calc_description');
            $table->string('calc_articles_unit')->nullable()->after('calc_articles_label');
            $table->unsignedSmallInteger('calc_default_articles')->default(16)->after('calc_articles_unit');
            $table->string('calc_cost_label')->nullable()->after('calc_default_articles');
            $table->decimal('calc_default_cost_per_article', 8, 2)->default(150)->after('calc_cost_label');
            $table->string('calc_monthly_result_label')->nullable()->after('calc_default_cost_per_article');
            $table->string('calc_annual_result_label')->nullable()->after('calc_monthly_result_label');
            $table->text('calc_disclaimer')->nullable()->after('calc_annual_result_label');
            $table->string('faq_empty_state')->nullable()->after('faqs');
            $table->string('final_cta_heading')->nullable()->after('faq_empty_state');
            $table->text('final_cta_description')->nullable()->after('final_cta_heading');
            $table->string('final_primary_cta_label')->nullable()->after('final_cta_description');
            $table->string('final_primary_cta_destination')->nullable()->after('final_primary_cta_label');
            $table->string('final_secondary_cta_label')->nullable()->after('final_primary_cta_destination');
            $table->string('final_secondary_cta_destination')->nullable()->after('final_secondary_cta_label');
            $table->string('unavailable_plan_label')->nullable()->after('final_secondary_cta_destination');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pricing_page_settings', function (Blueprint $table) {
            $table->dropColumn([
                'currency_code',
                'plans_heading',
                'billing_monthly_label',
                'billing_annual_label',
                'annual_billing_description',
                'monthly_period_label',
                'annual_period_label',
                'empty_state_title',
                'empty_state_description',
                'calc_articles_label',
                'calc_articles_unit',
                'calc_default_articles',
                'calc_cost_label',
                'calc_default_cost_per_article',
                'calc_monthly_result_label',
                'calc_annual_result_label',
                'calc_disclaimer',
                'faq_empty_state',
                'final_cta_heading',
                'final_cta_description',
                'final_primary_cta_label',
                'final_primary_cta_destination',
                'final_secondary_cta_label',
                'final_secondary_cta_destination',
                'unavailable_plan_label',
            ]);
        });

        Schema::table('pricing_plans', function (Blueprint $table) {
            $table->dropColumn(['display_order', 'is_active', 'popular_label']);
        });
    }
};
