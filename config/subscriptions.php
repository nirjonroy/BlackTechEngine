<?php

return [
    'management_enabled' => env('SUBSCRIPTION_MANAGEMENT_ENABLED', false),
    'enforcement_enabled' => env('SUBSCRIPTION_ENFORCEMENT_ENABLED', false),
    'reservation_minutes' => (int) env('SUBSCRIPTION_RESERVATION_MINUTES', 15),
    'invoice_due_days' => (int) env('SUBSCRIPTION_INVOICE_DUE_DAYS', 7),
];
