<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminAiProviderMetadataRequest;
use App\Models\Admin;
use App\Models\AiProvider;
use App\Services\AiMetadataManager;
use App\Services\AiMetadataWriteGate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Throwable;

class AiProviderController extends Controller
{
    public function __construct(
        private AiMetadataManager $metadataManager,
        private AiMetadataWriteGate $writeGate,
    ) {}

    public function create(): View
    {
        $this->writeGate->ensureAvailable();

        return view('admin.ai-configuration.providers.create');
    }

    public function store(AdminAiProviderMetadataRequest $request): RedirectResponse
    {
        $this->writeGate->ensureAvailable();

        try {
            $provider = $this->metadataManager->createProvider($this->admin($request), $request->metadata());
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            return $this->failedResponse($exception);
        }

        return Redirect::route('admin.ai-configuration.providers.edit', $provider)
            ->with('status', 'AI provider metadata created. The provider remains disabled and unverified.');
    }

    public function edit(AiProvider $aiProvider): View
    {
        $this->writeGate->ensureAvailable();

        return view('admin.ai-configuration.providers.edit', compact('aiProvider'));
    }

    public function update(AdminAiProviderMetadataRequest $request, AiProvider $aiProvider): RedirectResponse
    {
        $this->writeGate->ensureAvailable();

        try {
            $wasChanged = $this->metadataManager->updateProvider(
                $this->admin($request),
                $aiProvider,
                $request->metadata(),
                $request->lockVersion(),
            );
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            return $this->failedResponse($exception);
        }

        return Redirect::route('admin.ai-configuration.providers.edit', $aiProvider)
            ->with('status', $wasChanged
                ? 'AI provider metadata updated. Operational state was not changed.'
                : 'No provider metadata changes were detected.');
    }

    private function admin(AdminAiProviderMetadataRequest $request): Admin
    {
        $admin = $request->user('admin');

        abort_unless($admin instanceof Admin, 403);

        return $admin;
    }

    private function failedResponse(Throwable $exception): RedirectResponse
    {
        report($exception);

        return Redirect::back()
            ->withInput()
            ->withErrors(['metadata' => 'The provider metadata could not be saved. No changes were applied.']);
    }
}
