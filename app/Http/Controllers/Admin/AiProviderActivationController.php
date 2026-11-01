<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminAiActivationRequest;
use App\Models\AiProvider;
use App\Services\AiConfigurationActivationManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Throwable;

class AiProviderActivationController extends Controller
{
    public function __construct(private readonly AiConfigurationActivationManager $activationManager) {}

    public function __invoke(AdminAiActivationRequest $request, AiProvider $aiProvider): RedirectResponse
    {
        try {
            $changed = $this->activationManager->setProviderState(
                $request->admin(),
                $aiProvider,
                $request->enable(),
                $request->lockVersion(),
            );
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return Redirect::back()->withErrors(['state' => 'The provider state could not be changed. No partial activation was retained.']);
        }

        return Redirect::back()->with('status', $changed ? 'Provider operational state updated.' : 'Provider operational state was already current.');
    }
}
