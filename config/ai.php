<?php

return [
    'metadata_writes_enabled' => env('AI_METADATA_WRITES_ENABLED', false),
    'provider_verification_enabled' => env('AI_PROVIDER_VERIFICATION_ENABLED', false),
    'workload_draft_writes_enabled' => env('AI_WORKLOAD_DRAFT_WRITES_ENABLED', false),
    'content_studio_enabled' => env('AI_CONTENT_STUDIO_ENABLED', false),
    'content_review_enabled' => env('AI_CONTENT_REVIEW_ENABLED', false),
    'content_review_history_limit' => (int) env('AI_CONTENT_REVIEW_HISTORY_LIMIT', 100),
    'content_revision_limit' => (int) env('AI_CONTENT_REVISION_LIMIT', 25),
    'content_generation_enabled' => env('AI_CONTENT_GENERATION_ENABLED', false),
    'content_generation_daily_limit' => (int) env('AI_CONTENT_GENERATION_DAILY_LIMIT', 20),
    'autopilot_enabled' => env('AI_AUTOPILOT_ENABLED', false),
    'autopilot_batch_limit' => (int) env('AI_AUTOPILOT_BATCH_LIMIT', 10),
    'autopilot_max_retries' => (int) env('AI_AUTOPILOT_MAX_RETRIES', 3),
    'first_generation_test' => [
        'enabled' => env('AI_FIRST_GENERATION_TEST_ENABLED', false),
        'user_id' => env('AI_FIRST_GENERATION_USER_ID'),
        'workspace_id' => env('AI_FIRST_GENERATION_WORKSPACE_ID'),
        'brand_id' => env('AI_FIRST_GENERATION_BRAND_ID'),
    ],
];
