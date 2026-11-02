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
        Schema::table('ai_content_drafts', function (Blueprint $table) {
            $table->unsignedInteger('lock_version')->default(0)->after('status');
            $table->unsignedInteger('current_revision')->default(0)->after('lock_version');
            $table->text('review_feedback')->nullable()->after('current_revision');
            $table->timestamp('review_submitted_at')->nullable()->after('review_feedback');
            $table->timestamp('changes_requested_at')->nullable()->after('review_submitted_at');
            $table->timestamp('approved_at')->nullable()->after('changes_requested_at');
            $table->foreignId('approved_by_user_id')
                ->nullable()
                ->after('approved_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->index(
                ['workspace_id', 'status', 'review_submitted_at'],
                'ai_content_drafts_workspace_review_index',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_content_drafts', function (Blueprint $table) {
            $table->dropIndex('ai_content_drafts_workspace_review_index');
            $table->dropConstrainedForeignId('approved_by_user_id');
            $table->dropColumn([
                'lock_version',
                'current_revision',
                'review_feedback',
                'review_submitted_at',
                'changes_requested_at',
                'approved_at',
            ]);
        });
    }
};
