<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminAiWorkloadDraftRequest;
use App\Models\Admin;
use App\Models\AiModel;
use App\Models\AiWorkloadRoute;
use App\Services\AiWorkloadDraftManager;
use App\Services\AiWorkloadDraftWriteGate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Throwable;

class AiWorkloadDraftController extends Controller
{
    public function __construct(
        private AiWorkloadDraftManager $draftManager,
        private AiWorkloadDraftWriteGate $writeGate,
    ) {}

    public function create(): View
    {
        $this->writeGate->ensureAvailable();

        return view('admin.ai-configuration.workload-routes.create', $this->formData());
    }

    public function store(AdminAiWorkloadDraftRequest $request): RedirectResponse
    {
        $this->writeGate->ensureAvailable();

        try {
            $workloadRoute = $this->draftManager->create($this->admin($request), $request->metadata());
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            return $this->failedResponse($exception);
        }

        return Redirect::route('admin.ai-configuration.workload-routes.edit', $workloadRoute)
            ->with('status', 'AI workload draft created. It remains disabled and cannot execute.');
    }

    public function edit(AiWorkloadRoute $aiWorkloadRoute): View
    {
        $this->writeGate->ensureAvailable();
        abort_if($aiWorkloadRoute->is_enabled, 409, 'Enabled workload routes cannot be edited through draft management.');

        $aiWorkloadRoute->load([
            'primaryModel.provider:id,key,display_name',
            'fallbackModel.provider:id,key,display_name',
        ]);

        return view('admin.ai-configuration.workload-routes.edit', [
            'aiWorkloadRoute' => $aiWorkloadRoute,
            ...$this->formData(),
        ]);
    }

    public function update(AdminAiWorkloadDraftRequest $request, AiWorkloadRoute $aiWorkloadRoute): RedirectResponse
    {
        $this->writeGate->ensureAvailable();

        try {
            $wasChanged = $this->draftManager->update(
                $this->admin($request),
                $aiWorkloadRoute,
                $request->metadata(),
                $request->lockVersion(),
            );
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            return $this->failedResponse($exception);
        }

        return Redirect::route('admin.ai-configuration.workload-routes.edit', $aiWorkloadRoute)
            ->with('status', $wasChanged
                ? 'AI workload draft updated. It remains disabled and cannot execute.'
                : 'No workload draft changes were detected.');
    }

    /** @return array{models: mixed} */
    private function formData(): array
    {
        return [
            'models' => AiModel::query()
                ->with('provider:id,key,display_name')
                ->orderBy('display_name')
                ->orderBy('id')
                ->get([
                    'id', 'ai_provider_id', 'provider_model_id', 'display_name',
                    'is_enabled', 'availability_status',
                ]),
        ];
    }

    private function admin(AdminAiWorkloadDraftRequest $request): Admin
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
            ->withErrors(['draft' => 'The workload draft could not be saved. No changes were applied.']);
    }
}
