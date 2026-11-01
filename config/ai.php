<?php

return [
    'metadata_writes_enabled' => env('AI_METADATA_WRITES_ENABLED', false),
    'provider_verification_enabled' => env('AI_PROVIDER_VERIFICATION_ENABLED', false),
    'workload_draft_writes_enabled' => env('AI_WORKLOAD_DRAFT_WRITES_ENABLED', false),
    'content_studio_enabled' => env('AI_CONTENT_STUDIO_ENABLED', false),
    'content_generation_enabled' => env('AI_CONTENT_GENERATION_ENABLED', false),
    'content_generation_daily_limit' => (int) env('AI_CONTENT_GENERATION_DAILY_LIMIT', 20),
    'first_generation_test' => [
        'enabled' => env('AI_FIRST_GENERATION_TEST_ENABLED', false),
        'user_id' => env('AI_FIRST_GENERATION_USER_ID'),
        'workspace_id' => env('AI_FIRST_GENERATION_WORKSPACE_ID'),
        'brand_id' => env('AI_FIRST_GENERATION_BRAND_ID'),
    ],
];
