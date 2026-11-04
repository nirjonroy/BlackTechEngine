<?php

return [
    'management_enabled' => env('SUBSCRIPTION_MANAGEMENT_ENABLED', false),
    'enforcement_enabled' => env('SUBSCRIPTION_ENFORCEMENT_ENABLED', false),
    'free_plan_code' => env('SUBSCRIPTION_FREE_PLAN_CODE', 'free'),
    'reservation_minutes' => (int) env('SUBSCRIPTION_RESERVATION_MINUTES', 15),
    'invoice_due_days' => (int) env('SUBSCRIPTION_INVOICE_DUE_DAYS', 7),
    'past_due_grace_days' => (int) env('SUBSCRIPTION_PAST_DUE_GRACE_DAYS', 7),
];
