<?php

namespace App\View\Composers;

use App\Models\Workspace;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CustomerLayoutComposer
{
    public function compose(View $view): void
    {
        $user = auth('web')->user();

        if (! config('customer.workspaces_enabled') || $user === null) {
            $view->with([
                'customerWorkspaces' => new Collection,
                'customerActiveWorkspace' => null,
            ]);

            return;
        }

        $workspaces = $user->accessibleWorkspaces()->orderBy('name')->get();
        $activeWorkspace = $workspaces->firstWhere('id', $user->active_workspace_id) ?? $workspaces->first();

        $view->with([
            'customerWorkspaces' => $workspaces,
            'customerActiveWorkspace' => $activeWorkspace instanceof Workspace ? $activeWorkspace : null,
        ]);
    }
}
