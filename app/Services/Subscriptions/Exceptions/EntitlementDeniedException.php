<?php

namespace App\Services\Subscriptions\Exceptions;

use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class EntitlementDeniedException extends ValidationException implements ShouldntReport
{
    public function __construct(
        public readonly string $reason,
        string $message,
    ) {
        $validator = Validator::make([], []);
        $validator->errors()->add('subscription', $message);

        parent::__construct($validator);
    }

    /** @return array<string, string> */
    public function context(): array
    {
        return ['entitlement_reason' => $this->reason];
    }
}
