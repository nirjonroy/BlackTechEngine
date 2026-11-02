<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestContentChangesRequest;
use App\Models\AiContentDraft;
use App\Models\Brand;
use App\Models\Workspace;
use App\Services\ContentEditorialWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class ContentChangeRequestController extends Controller
{
    public function __construct(private readonly ContentEditorialWorkflow $workflow) {}

    public function __invoke(
        RequestContentChangesRequest $request,
        Workspace $workspace,
        Brand $brand,
        AiContentDraft $aiContentDraft,
    ): RedirectResponse {
        $draft = $this->workflow->requestChanges(
            $aiContentDraft,
            $request->user(),
            $request->expectedVersion(),
            (string) $request->validated('notes'),
        );

        return Redirect::route('content-studio.drafts.show', [$workspace, $brand, $draft])
            ->with('status', 'Changes requested. The content returned to draft status.');
    }
}
