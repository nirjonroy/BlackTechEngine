<?php

namespace App\Http\Controllers;

use App\Http\Requests\SocialPostWorkflowRequest;
use App\Models\Brand;
use App\Models\SocialPost;
use App\Models\Workspace;
use App\Services\SocialPostWorkflow;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;

class SocialPostWorkflowController extends Controller
{
    public function __construct(private readonly SocialPostWorkflow $workflow) {}

    public function __invoke(SocialPostWorkflowRequest $request, Workspace $workspace, Brand $brand, SocialPost $socialPost, string $action): RedirectResponse
    {
        Gate::authorize(match ($action) {
            'submit' => 'requestReview', 'changes' => 'requestChanges', 'publish' => 'publish', default => $action
        }, $socialPost);
        $timezone = (string) data_get($workspace->settings, 'timezone', 'UTC');
        $atInput = $action === 'plan' ? $request->input('planned_at') : $request->input('published_at');
        $at = $atInput ? CarbonImmutable::parse($atInput, $timezone)->utc() : null;
        $this->workflow->transition($socialPost, $request->user(), $action, $request->integer('lock_version'), $request->input('notes'), $at, $request->input('published_url'));

        return Redirect::route('social-content.posts.show', [$workspace, $brand, $socialPost])->with('status', match ($action) {
            'submit' => 'Post submitted for review.', 'approve' => 'Post approved.', 'changes' => 'Changes requested.',
            'plan' => 'Post added to the publishing plan. No automatic publishing will occur.',
            'publish' => 'Post marked as manually published.', 'cancel' => 'Post cancelled.',
        });
    }
}
