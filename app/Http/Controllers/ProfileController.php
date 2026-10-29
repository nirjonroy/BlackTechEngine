<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($request, $validated): void {
            $user = User::query()
                ->lockForUpdate()
                ->findOrFail($request->user()->getAuthIdentifier());

            if ($user->lock_version !== (int) $validated['lock_version']) {
                throw ValidationException::withMessages([
                    'lock_version' => 'Your profile changed since you opened this form. Refresh the page and review the latest information before trying again.',
                ]);
            }

            $user->name = $validated['name'];
            $user->email = $validated['email'];

            if (! $user->isDirty(['name', 'email'])) {
                return;
            }

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            $user->lock_version++;
            $user->save();
        });

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $wasDeleted = DB::transaction(function () use ($request): bool {
            $user = User::query()
                ->lockForUpdate()
                ->findOrFail($request->user()->getAuthIdentifier());

            if ($user->ownedWorkspaces()->exists()) {
                return false;
            }

            $user->delete();

            return true;
        });

        if (! $wasDeleted) {
            return Redirect::route('profile.edit')->withErrors([
                'account' => 'Your account owns one or more workspaces. Transfer or otherwise resolve those workspaces before deleting your account.',
            ], 'userDeletion');
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
