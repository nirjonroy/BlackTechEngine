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
        Schema::create('ai_content_draft_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_content_draft_id')->constrained()->cascadeOnDelete();
            $table->foreignId('editor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('revision_number');
            $table->json('changed_fields');
            $table->json('snapshot');
            $table->timestamps();

            $table->unique(
                ['ai_content_draft_id', 'revision_number'],
                'ai_content_draft_revisions_draft_revision_unique',
            );
            $table->index(
                ['ai_content_draft_id', 'created_at'],
                'ai_content_draft_revisions_draft_created_index',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_content_draft_revisions');
    }
};
