<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminAiProviderBindingRequest;
use App\Models\Admin;
use App\Models\AiProvider;
use App\Services\Ai\AiProviderConnectionManager;
use App\Services\AiProviderVerificationGate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Throwable;

class AiProviderBindingController extends Controller
{
    public function __construct(
        private readonly AiProviderConnectionManager $connectionManager,
        private readonly AiProviderVerificationGate $verificationGate,
    ) {}

    public function store(AdminAiProviderBindingRequest $request, AiProvider $aiProvider): RedirectResponse
    {
        $this->verificationGate->ensureAvailable();

        try {
            $wasBound = $this->connectionManager->bind(
                $this->admin($request),
                $aiProvider,
                $request->adapterIdentifier(),
                $request->lockVersion(),
            );
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return Redirect::back()->withErrors([
                'binding' => 'The trusted adapter binding could not be saved. No changes were applied.',
            ]);
        }

        return Redirect::back()->with('status', $wasBound
            ? 'The trusted Gemini adapter was bound. The provider remains disabled and unverified.'
            : 'The trusted Gemini adapter was already bound. No changes were applied.');
    }

    private function admin(AdminAiProviderBindingRequest $request): Admin
    {
        $admin = $request->user('admin');

        abort_unless($admin instanceof Admin, 403);

        return $admin;
    }
}
