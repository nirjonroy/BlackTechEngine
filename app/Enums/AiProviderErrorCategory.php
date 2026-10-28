<?php

namespace App\Enums;

enum AiProviderErrorCategory: string
{
    case MissingCredentials = 'missing_credentials';
    case AuthenticationFailed = 'authentication_failed';
    case PermissionDenied = 'permission_denied';
    case BillingRequired = 'billing_required';
    case InvalidRequest = 'invalid_request';
    case ModelNotFound = 'model_not_found';
    case RateLimited = 'rate_limited';
    case QuotaExceeded = 'quota_exceeded';
    case ProviderUnavailable = 'provider_unavailable';
    case Timeout = 'timeout';
    case NetworkFailure = 'network_failure';
    case MalformedResponse = 'malformed_response';
}
