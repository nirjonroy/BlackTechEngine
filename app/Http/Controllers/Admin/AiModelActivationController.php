<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminAiActivationRequest;
use App\Models\AiModel;
use App\Services\AiConfigurationActivationManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Throwable;

class AiModelActivationController extends Controller
{
    public function __construct(private readonly AiConfigurationActivationManager $activationManager) {}

    public function __invoke(AdminAiActivationRequest $request, AiModel $aiModel): RedirectResponse
    {
        try {
            $changed = $this->activationManager->setModelState(
                $request->admin(),
                $aiModel,
                $request->enable(),
                $request->lockVersion(),
            );
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return Redirect::back()->withErrors(['state' => 'The model state could not be changed. No partial activation was retained.']);
        }

        return Redirect::back()->with('status', $changed ? 'Model operational state updated.' : 'Model operational state was already current.');
    }
}
