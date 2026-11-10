<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConnectWordPressRequest;
use App\Models\Brand;
use App\Models\Workspace;
use App\Services\WordPress\WordPressPublishingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redirect;

class WordPressConnectionController extends Controller
{
    public function __construct(
        private readonly WordPressPublishingService $publishingService,
    ) {}

    public function connect(ConnectWordPressRequest $request, Workspace $workspace, Brand $brand): RedirectResponse
    {
        Gate::authorize('update', $brand);
        abort_unless($brand->workspace_id === $workspace->getKey(), 404);

        $this->publishingService->connect(
            $brand,
            $request->user(),
            $request->string('site_url')->toString(),
            $request->string('username')->toString(),
            $request->string('application_password')->toString(),
        );

        return Redirect::route('workspaces.brands.show', [$workspace, $brand])
            ->with('status', 'WordPress site connected and verified successfully.');
    }

    public function verify(Workspace $workspace, Brand $brand): RedirectResponse
    {
        Gate::authorize('update', $brand);
        abort_unless($brand->workspace_id === $workspace->getKey(), 404);

        $connection = $brand->wordPressConnection;
        abort_if($connection === null, 404);

        $this->publishingService->verify($connection);

        return Redirect::route('workspaces.brands.show', [$workspace, $brand])
            ->with('status', 'WordPress connection re-verified successfully.');
    }

    public function disconnect(Workspace $workspace, Brand $brand): RedirectResponse
    {
        Gate::authorize('update', $brand);
        abort_unless($brand->workspace_id === $workspace->getKey(), 404);

        $connection = $brand->wordPressConnection;
        abort_if($connection === null, 404);

        $this->publishingService->disconnect($connection);

        return Redirect::route('workspaces.brands.show', [$workspace, $brand])
            ->with('status', 'WordPress site disconnected.');
    }
}
