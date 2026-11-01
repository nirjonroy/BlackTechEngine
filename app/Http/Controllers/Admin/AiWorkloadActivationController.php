<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminAiActivationRequest;
use App\Models\AiWorkloadRoute;
use App\Services\AiConfigurationActivationManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Throwable;

class AiWorkloadActivationController extends Controller
{
    public function __construct(private readonly AiConfigurationActivationManager $activationManager) {}

    public function __invoke(AdminAiActivationRequest $request, AiWorkloadRoute $aiWorkloadRoute): RedirectResponse
    {
        try {
            $changed = $this->activationManager->setWorkloadState(
                $request->admin(),
                $aiWorkloadRoute,
                $request->enable(),
                $request->lockVersion(),
            );
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return Redirect::back()->withErrors(['state' => 'The workload state could not be changed. No partial activation was retained.']);
        }

        return Redirect::back()->with('status', $changed ? 'Workload operational state updated.' : 'Workload operational state was already current.');
    }
}
