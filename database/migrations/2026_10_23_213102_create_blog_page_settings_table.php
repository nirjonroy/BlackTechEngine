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
        Schema::create('blog_page_settings', function (Blueprint $table) {
            $table->id();
            $table->string('hero_badge')->nullable();
            $table->string('hero_title')->nullable();
            $table->string('hero_highlighted_title')->nullable();
            $table->text('hero_description')->nullable();
            $table->string('search_label')->nullable();
            $table->string('search_placeholder')->nullable();
            $table->string('filter_heading')->nullable();
            $table->string('all_posts_label')->nullable();
            $table->string('featured_label')->nullable();
            $table->string('read_full_article_label')->nullable();
            $table->string('read_article_label')->nullable();
            $table->string('minutes_read_label')->nullable();
            $table->string('empty_state_heading')->nullable();
            $table->text('empty_state_description')->nullable();
            $table->string('no_results_heading')->nullable();
            $table->text('no_results_description')->nullable();
            $table->string('back_to_blog_label')->nullable();
            $table->string('published_label')->nullable();
            $table->text('article_empty_state')->nullable();
            $table->string('related_articles_heading')->nullable();
            $table->string('sidebar_heading')->nullable();
            $table->text('sidebar_description')->nullable();
            $table->string('sidebar_cta_label')->nullable();
            $table->string('sidebar_cta_destination')->nullable();
            $table->string('sidebar_note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blog_page_settings');
    }
};
