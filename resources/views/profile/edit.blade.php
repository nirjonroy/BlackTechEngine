<x-app-layout>
    <x-slot name="header">
        <h1 class="font-heading text-xl font-black text-slate-900 dark:text-white sm:text-2xl">
            {{ __('Profile') }}
        </h1>
    </x-slot>

    <div class="mx-auto max-w-4xl space-y-6">
            <div class="bte-card rounded-2xl p-5 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="bte-card rounded-2xl p-5 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="bte-card rounded-2xl p-5 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
    </div>
</x-app-layout>
