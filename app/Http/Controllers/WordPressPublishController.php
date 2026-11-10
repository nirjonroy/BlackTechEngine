<?php

namespace App\Http\Controllers;

use App\Http\Requests\PublishWordPressRequest;
use App\Models\AiContentDraft;
use App\Models\Brand;
use App\Models\Workspace;
use App\Services\WordPress\WordPressPublishingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;

class WordPressPublishController extends Controller
{
    public function __construct(
        private readonly WordPressPublishingService $publishingService,
    ) {}

    public function store(PublishWordPressRequest $request, Workspace $workspace, Brand $brand, AiContentDraft $aiContentDraft): RedirectResponse
    {
        Gate::authorize('update', $aiContentDraft);

        abort_unless(
            $brand->workspace_id === $workspace->getKey()
            && $aiContentDraft->workspace_id === $workspace->getKey()
            && $aiContentDraft->brand_id === $brand->getKey(),
            404
        );

        $publication = $this->publishingService->publish(
            $aiContentDraft,
            $request->user(),
            $request->string('target_status')->toString(),
            $request->boolean('reconciled'),
        );

        $statusLabel = $publication->status->label();

        return Redirect::route('content-studio.drafts.show', [$workspace, $brand, $aiContentDraft])
            ->with('status', "Article published to WordPress as {$statusLabel}. (Post #{$publication->wordpress_post_id})");
    }
}
