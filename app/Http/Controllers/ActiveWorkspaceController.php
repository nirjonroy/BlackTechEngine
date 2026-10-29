<?php

namespace App\Http\Controllers;

use App\Http\Requests\SelectActiveWorkspaceRequest;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class ActiveWorkspaceController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(SelectActiveWorkspaceRequest $request, Workspace $workspace): RedirectResponse
    {
        $user = $request->user();
        $user->active_workspace_id = $workspace->getKey();
        $user->save();

        return Redirect::back()->with('status', "{$workspace->name} is now your active workspace.");
    }
}
