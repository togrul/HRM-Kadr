<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('ui::profile.titles.profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @php
                $mustResetPassword = (bool) ($user->getAttributes()['must_reset_password'] ?? false);
            @endphp
            @php
                $showForceResetBanner = $mustResetPassword && $user->hasRole(\App\Modules\Personnel\Application\Services\MyHr\MyHrAccountProvisioningService::EMPLOYEE_ROLE);
            @endphp

            @if (request()->boolean('force_password_reset') || $showForceResetBanner)
                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm leading-6 text-amber-800 shadow-sm">
                    <p class="font-semibold">{{ __('ui::profile.titles.force_password_reset') }}</p>
                    <p class="mt-1">{{ __('ui::profile.descriptions.force_password_reset') }}</p>
                </div>
            @endif

            <div class="p-4 sm:p-8 bg-gray-50 dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-gray-50 dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-gray-50 dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
