<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AiModelCapability;
use App\Enums\AiModelModality;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminAiModelMetadataRequest;
use App\Models\Admin;
use App\Models\AiModel;
use App\Models\AiProvider;
use App\Services\AiMetadataManager;
use App\Services\AiMetadataWriteGate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Throwable;

class AiModelController extends Controller
{
    public function __construct(
        private AiMetadataManager $metadataManager,
        private AiMetadataWriteGate $writeGate,
    ) {}

    public function create(): View
    {
        $this->writeGate->ensureAvailable();

        return view('admin.ai-configuration.models.create', $this->formData());
    }

    public function store(AdminAiModelMetadataRequest $request): RedirectResponse
    {
        $this->writeGate->ensureAvailable();

        try {
            $model = $this->metadataManager->createModel($this->admin($request), $request->metadata());
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            return $this->failedResponse($exception);
        }

        return Redirect::route('admin.ai-configuration.models.edit', $model)
            ->with('status', 'AI model metadata created. The model remains disabled and unverified.');
    }

    public function edit(AiModel $aiModel): View
    {
        $this->writeGate->ensureAvailable();

        return view('admin.ai-configuration.models.edit', [
            'aiModel' => $aiModel,
            ...$this->formData(),
        ]);
    }

    public function update(AdminAiModelMetadataRequest $request, AiModel $aiModel): RedirectResponse
    {
        $this->writeGate->ensureAvailable();

        try {
            $wasChanged = $this->metadataManager->updateModel(
                $this->admin($request),
                $aiModel,
                $request->metadata(),
                $request->lockVersion(),
            );
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            return $this->failedResponse($exception);
        }

        return Redirect::route('admin.ai-configuration.models.edit', $aiModel)
            ->with('status', $wasChanged
                ? 'AI model metadata updated. Operational state was not changed.'
                : 'No model metadata changes were detected.');
    }

    /** @return array{capabilities: array, modalities: array, providers: mixed} */
    private function formData(): array
    {
        return [
            'capabilities' => AiModelCapability::cases(),
            'modalities' => AiModelModality::cases(),
            'providers' => AiProvider::query()->orderBy('display_name')->orderBy('id')->get(['id', 'display_name', 'key']),
        ];
    }

    private function admin(AdminAiModelMetadataRequest $request): Admin
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
            ->withErrors(['metadata' => 'The model metadata could not be saved. No changes were applied.']);
    }
}
