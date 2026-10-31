<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AiProviderErrorCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminAiProviderDiscoveryRequest;
use App\Models\AiProvider;
use App\Services\Ai\AiProviderConnectionManager;
use App\Services\Ai\Exceptions\AiProviderException;
use App\Services\AiProviderVerificationGate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Throwable;

class AiProviderModelDiscoveryController extends Controller
{
    public function __construct(
        private readonly AiProviderConnectionManager $connectionManager,
        private readonly AiProviderVerificationGate $verificationGate,
    ) {}

    public function store(AdminAiProviderDiscoveryRequest $request, AiProvider $aiProvider): View|RedirectResponse
    {
        $this->verificationGate->ensureAvailable();

        try {
            $result = $this->connectionManager->discoverModels($aiProvider);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (AiProviderException $exception) {
            return Redirect::back()->withErrors([
                'discovery' => $this->failureMessage($exception->category),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return Redirect::back()->withErrors([
                'discovery' => 'Gemini model discovery could not be completed. No provider or model state was changed.',
            ]);
        }

        return view('admin.ai-configuration.providers.discovery', [
            'aiProvider' => $aiProvider->fresh(),
            'discovery' => $result,
        ]);
    }

    private function failureMessage(AiProviderErrorCategory $category): string
    {
        return match ($category) {
            AiProviderErrorCategory::MissingCredentials => 'Gemini credentials are not configured on the server.',
            AiProviderErrorCategory::AuthenticationFailed => 'Gemini rejected the configured server credential.',
            AiProviderErrorCategory::PermissionDenied => 'The configured Gemini credential cannot access the Models endpoint.',
            AiProviderErrorCategory::BillingRequired => 'Gemini reported that billing configuration is required.',
            AiProviderErrorCategory::InvalidRequest => 'Gemini rejected the model discovery request.',
            AiProviderErrorCategory::ModelNotFound => 'Gemini reported that a requested model resource was not found.',
            AiProviderErrorCategory::RateLimited => 'Gemini rate-limited model discovery. Try again later.',
            AiProviderErrorCategory::QuotaExceeded => 'Gemini reported that the configured project quota is exhausted.',
            AiProviderErrorCategory::ProviderUnavailable => 'Gemini model discovery is temporarily unavailable. Try again later.',
            AiProviderErrorCategory::Timeout => 'Gemini model discovery timed out.',
            AiProviderErrorCategory::NetworkFailure => 'The server could not reach the Gemini Models endpoint.',
            AiProviderErrorCategory::MalformedResponse => 'Gemini returned an unexpected model catalog response.',
        };
    }
}
