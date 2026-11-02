<?php

namespace App\Services;

use Illuminate\Support\Facades\Schema;

class ContentReviewGate
{
    public function enabled(): bool
    {
        return (bool) config('ai.content_review_enabled')
            && Schema::hasColumns('ai_content_drafts', [
                'lock_version',
                'current_revision',
                'review_submitted_at',
                'approved_at',
            ])
            && Schema::hasTable('ai_content_draft_revisions')
            && Schema::hasTable('ai_content_draft_events');
    }
}
