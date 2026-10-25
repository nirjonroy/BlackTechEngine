<x-admin.layout title="Edit User" :breadcrumbs="[['label' => 'Mission Control', 'route' => 'admin.dashboard'], ['label' => 'Registered Users', 'route' => 'admin.users.index'], ['label' => 'User #'.$user->id], ['label' => 'Edit Profile']]">
    <div class="flex flex-col gap-4 border-b border-slate-200 pb-6 dark:border-slate-800 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-500">Secure profile update</p>
            <h1 class="mt-1 font-heading text-2xl font-extrabold tracking-tight sm:text-3xl">Edit user name</h1>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Only the registered user's display name can be changed in this phase.</p>
        </div>
        <a href="{{ route('admin.users.show', $user) }}" class="inline-flex w-fit items-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-600 transition hover:border-cyan-500 hover:text-cyan-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 dark:border-slate-700 dark:text-slate-300">
            <i data-lucide="arrow-left" class="h-4 w-4" aria-hidden="true"></i>
            Cancel
        </a>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-12 xl:gap-8">
        <section class="bte-card rounded-3xl border border-slate-200 p-5 shadow-xl dark:border-slate-800 sm:p-7 xl:col-span-7" aria-labelledby="edit-user-heading">
            <div class="flex items-center gap-3 border-b border-slate-200 pb-5 dark:border-slate-800">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-indigo-500/10 font-heading text-sm font-black text-indigo-600 dark:text-indigo-400">{{ Str::upper(Str::substr($user->name, 0, 2)) }}</span>
                <div class="min-w-0">
                    <h2 id="edit-user-heading" class="truncate font-heading text-lg font-bold">User #{{ $user->id }}</h2>
                    <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ $user->email }}</p>
                </div>
            </div>

            @if($errors->any())
                <div class="mt-6 rounded-2xl border border-rose-500/20 bg-rose-500/10 px-4 py-3 text-sm text-rose-700 dark:text-rose-300" role="alert">
                    <p class="font-bold">The profile could not be updated.</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="mt-6 flex flex-col gap-6">
                @csrf
                @method('PATCH')
                <input type="hidden" name="lock_version" value="{{ old('lock_version', $user->lock_version) }}">

                <div>
                    <label for="name" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Name</label>
                    <input id="name" name="name" type="text" maxlength="255" required autocomplete="off" value="{{ old('name', $user->name) }}" aria-describedby="name-help @error('name') name-error @enderror" @error('name') aria-invalid="true" @enderror class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    <p id="name-help" class="mt-2 text-xs text-slate-500 dark:text-slate-400">Maximum 255 characters. Leading and trailing spaces are removed.</p>
                    @error('name')
                        <p id="name-error" class="mt-2 text-xs font-semibold text-rose-600 dark:text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-6 dark:border-slate-800 sm:flex-row sm:justify-end">
                    <a href="{{ route('admin.users.show', $user) }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-5 py-3 text-xs font-bold text-slate-600 transition hover:border-cyan-500 hover:text-cyan-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 dark:border-slate-700 dark:text-slate-300">Cancel</a>
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 py-3 text-xs font-bold text-white shadow-lg shadow-indigo-500/20 transition hover:bg-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
                        <i data-lucide="save" class="h-4 w-4" aria-hidden="true"></i>
                        Save name
                    </button>
                </div>
            </form>
        </section>

        <aside class="bte-card rounded-3xl border border-slate-200 p-5 shadow-xl dark:border-slate-800 sm:p-7 xl:col-span-5" aria-labelledby="protected-fields-heading">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                <i data-lucide="shield-check" class="h-5 w-5" aria-hidden="true"></i>
            </span>
            <h2 id="protected-fields-heading" class="mt-4 font-heading text-lg font-bold">Protected account fields</h2>
            <p class="mt-2 text-xs leading-5 text-slate-500 dark:text-slate-400">Email, verification status, password, sessions, permissions, and account state are not changed by this form.</p>
            <div class="mt-5 rounded-2xl border border-cyan-500/20 bg-cyan-500/5 p-4 text-xs leading-5 text-cyan-700 dark:text-cyan-300">
                Successful changes are recorded in the Admin user audit log without storing the old or new name.
            </div>
        </aside>
    </div>
</x-admin.layout>
