<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminUserIndexRequest;
use App\Http\Requests\Admin\AdminUserUpdateRequest;
use App\Models\AdminUserAudit;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(AdminUserIndexRequest $request): View
    {
        $filters = $request->filters();

        $usersQuery = User::query()
            ->select(['id', 'name', 'email', 'email_verified_at', 'created_at', 'updated_at']);

        if ($filters['search'] !== '') {
            $search = $filters['search'];

            $usersQuery->where(function (Builder $query) use ($search): void {
                $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");

                if (ctype_digit($search)) {
                    $query->orWhereKey((int) $search);
                }
            });
        }

        if ($filters['verification'] === 'verified') {
            $usersQuery->whereNotNull('email_verified_at');
        } elseif ($filters['verification'] === 'unverified') {
            $usersQuery->whereNull('email_verified_at');
        }

        match ($filters['sort']) {
            'oldest' => $usersQuery->orderBy('created_at')->orderBy('id'),
            'name' => $usersQuery->orderBy('name')->orderBy('id'),
            'email' => $usersQuery->orderBy('email')->orderBy('id'),
            'id' => $usersQuery->orderByDesc('id'),
            default => $usersQuery->orderByDesc('created_at')->orderByDesc('id'),
        };

        $users = $usersQuery
            ->paginate($filters['per_page'])
            ->withQueryString();

        $totalUsers = User::query()->count();
        $verifiedUsers = User::query()->whereNotNull('email_verified_at')->count();

        return view('admin.users.index', [
            'filters' => $filters,
            'totalUsers' => $totalUsers,
            'unverifiedUsers' => $totalUsers - $verifiedUsers,
            'users' => $users,
            'verifiedUsers' => $verifiedUsers,
        ]);
    }

    public function show(User $user): View
    {
        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(AdminUserUpdateRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        $wasChanged = DB::transaction(function () use ($request, $user, $validated): bool {
            $lockedUser = User::query()
                ->lockForUpdate()
                ->findOrFail($user->getKey());

            if ($lockedUser->lock_version !== (int) $validated['lock_version']) {
                throw ValidationException::withMessages([
                    'lock_version' => 'This user was updated after you opened the form. Review the latest details and try again.',
                ]);
            }

            if ($lockedUser->name === $validated['name']) {
                return false;
            }

            $lockedUser->name = $validated['name'];
            $lockedUser->lock_version++;
            $lockedUser->save();

            AdminUserAudit::query()->create([
                'acting_admin_id' => $request->user('admin')->getAuthIdentifier(),
                'target_user_id' => $lockedUser->getKey(),
                'action' => 'user.name_updated',
                'request_id' => Str::uuid()->toString(),
                'changes' => ['fields' => ['name']],
            ]);

            return true;
        });

        return Redirect::route('admin.users.show', $user)
            ->with('status', $wasChanged ? 'user-name-updated' : 'user-name-unchanged');
    }
}
