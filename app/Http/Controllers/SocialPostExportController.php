<?php

namespace App\Http\Controllers;

use App\Enums\SocialPostStatus;
use App\Models\Brand;
use App\Models\SocialPost;
use App\Models\Workspace;
use App\Services\SocialPostExporter;
use App\Services\SocialPostWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SocialPostExportController extends Controller
{
    public function __construct(private readonly SocialPostExporter $exporter, private readonly SocialPostWorkflow $workflow) {}

    public function __invoke(Request $request, Workspace $workspace, Brand $brand, SocialPost $socialPost): StreamedResponse
    {
        Gate::authorize('export', $socialPost);
        abort_unless(in_array($socialPost->status, [SocialPostStatus::Approved, SocialPostStatus::Planned, SocialPostStatus::ManuallyPublished], true), 422);
        $validated = $request->validate(['format' => ['required', Rule::in(['text', 'markdown', 'csv'])]]);

        $this->workflow->recordExport($socialPost, $request->user(), $validated['format']);

        return $this->exporter->response($socialPost, $validated['format']);
    }
}
