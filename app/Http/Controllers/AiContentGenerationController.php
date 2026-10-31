<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateAiContentRequest;
use App\Models\Brand;
use App\Models\Workspace;
use App\Services\Ai\AiContentGenerationManager;
use App\Services\Ai\Exceptions\AiContentGenerationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class AiContentGenerationController extends Controller
{
    public function __construct(private readonly AiContentGenerationManager $generationManager) {}

    public function __invoke(GenerateAiContentRequest $request, Workspace $workspace, Brand $brand): RedirectResponse
    {
        try {
            $draft = $this->generationManager->generate(
                $request->user(),
                $workspace,
                $brand,
                $request->generationData(),
            );
        } catch (AiContentGenerationException $exception) {
            return Redirect::back()
                ->withInput($request->safe()->except('idempotency_key'))
                ->withErrors(['generation' => $exception->getMessage()]);
        }

        return Redirect::route('content-studio.drafts.show', [$workspace, $brand, $draft])
            ->with('status', 'AI draft generated for human review. Nothing has been published.');
    }
}
