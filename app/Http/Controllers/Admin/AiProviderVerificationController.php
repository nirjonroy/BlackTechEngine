<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AiProviderErrorCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminAiProviderVerificationRequest;
use App\Models\Admin;
use App\Models\AiProvider;
use App\Services\Ai\AiProviderConnectionManager;
use App\Services\AiProviderVerificationGate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Throwable;

class AiProviderVerificationController extends Controller
{
    public function __construct(
        private readonly AiProviderConnectionManager $connectionManager,
        private readonly AiProviderVerificationGate $verificationGate,
    ) {}

    public function store(AdminAiProviderVerificationRequest $request, AiProvider $aiProvider): RedirectResponse
    {
        $this->verificationGate->ensureAvailable();

        try {
            $result = $this->connectionManager->verify(
                $this->admin($request),
                $aiProvider,
                $request->lockVersion(),
            );
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return Redirect::back()->withErrors([
                'verification' => 'Gemini verification could not be completed. No provider state was changed.',
            ]);
        }

        if (! $result->successful) {
            return Redirect::back()->withErrors([
                'verification' => $this->failureMessage(
                    $result->errorCategory ?? AiProviderErrorCategory::ProviderUnavailable,
                ),
            ]);
        }

        return Redirect::back()->with('status', 'Gemini credentials were accepted and the Models endpoint returned a parseable response. The provider remains disabled and operational health remains unverified.');
    }

    private function admin(AdminAiProviderVerificationRequest $request): Admin
    {
        $admin = $request->user('admin');

        abort_unless($admin instanceof Admin, 403);

        return $admin;
    }

    private function failureMessage(AiProviderErrorCategory $category): string
    {
        return match ($category) {
            AiProviderErrorCategory::MissingCredentials => 'Gemini credentials are not configured on the server.',
            AiProviderErrorCategory::AuthenticationFailed => 'Gemini rejected the configured server credential.',
            AiProviderErrorCategory::PermissionDenied => 'The configured Gemini credential cannot access the Models endpoint.',
            AiProviderErrorCategory::BillingRequired => 'Gemini reported that billing configuration is required.',
            AiProviderErrorCategory::InvalidRequest => 'Gemini rejected the non-generating Models request.',
            AiProviderErrorCategory::ModelNotFound => 'Gemini reported that the requested model resource was not found.',
            AiProviderErrorCategory::RateLimited => 'Gemini rate-limited the verification request. Try again later.',
            AiProviderErrorCategory::QuotaExceeded => 'Gemini reported that the configured project quota is exhausted.',
            AiProviderErrorCategory::ProviderUnavailable => 'Gemini is temporarily unavailable. Try again later.',
            AiProviderErrorCategory::Timeout => 'The Gemini verification request timed out.',
            AiProviderErrorCategory::NetworkFailure => 'The server could not reach the Gemini Models endpoint.',
            AiProviderErrorCategory::MalformedResponse => 'Gemini returned an unexpected Models response.',
        };
    }
}
