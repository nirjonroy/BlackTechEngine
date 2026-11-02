<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitContentReviewRequest;
use App\Models\AiContentDraft;
use App\Models\Brand;
use App\Models\Workspace;
use App\Services\ContentEditorialWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class ContentReviewRequestController extends Controller
{
    public function __construct(private readonly ContentEditorialWorkflow $workflow) {}

    public function __invoke(
        SubmitContentReviewRequest $request,
        Workspace $workspace,
        Brand $brand,
        AiContentDraft $aiContentDraft,
    ): RedirectResponse {
        $draft = $this->workflow->submitForReview(
            $aiContentDraft,
            $request->user(),
            $request->expectedVersion(),
            $request->validated('notes'),
        );

        return Redirect::route('content-studio.drafts.show', [$workspace, $brand, $draft])
            ->with('status', 'Content submitted for owner review. Nothing has been published.');
    }
}
