<x-app-layout>
    {{-- ===================== contextual panel ===================== --}}
    <x-slot name="sidebar">
        <x-context-panel>
            <x-context-panel.section :title="__('ui::profile.titles.profile')">
                <x-context-panel.item href="#profile-information" :note="__('ui::profile.descriptions.profile_information')">
                    {{ __('ui::profile.titles.profile_information') }}
                </x-context-panel.item>
                <x-context-panel.item href="#update-password" :note="__('ui::profile.descriptions.update_password')">
                    {{ __('ui::profile.titles.update_password') }}
                </x-context-panel.item>
                <x-context-panel.item href="#delete-account" dot="bg-[#f43f5e]">
                    {{ __('ui::profile.titles.delete_account') }}
                </x-context-panel.item>
            </x-context-panel.section>
        </x-context-panel>
    </x-slot>

    <div class="space-y-4 px-4 py-5 sm:px-6">
        @php
            $mustResetPassword = (bool) ($user->getAttributes()['must_reset_password'] ?? false);
            $showForceResetBanner = $mustResetPassword && $user->hasRole(\App\Modules\Personnel\Application\Services\MyHr\MyHrAccountProvisioningService::EMPLOYEE_ROLE);
        @endphp

        @if (request()->boolean('force_password_reset') || $showForceResetBanner)
            <div class="rounded-2xl border border-amber-200 bg-[#fef3c7] px-5 py-4 text-[13px] leading-6 text-[#b45309]">
                <p class="font-semibold">{{ __('ui::profile.titles.force_password_reset') }}</p>
                <p class="mt-1">{{ __('ui::profile.descriptions.force_password_reset') }}</p>
            </div>
        @endif

        <section id="profile-information" class="rounded-2xl border border-hairline bg-white p-5 shadow-card sm:p-6">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </section>

        <section id="update-password" class="rounded-2xl border border-hairline bg-white p-5 shadow-card sm:p-6">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </section>

        <section id="delete-account" class="rounded-2xl border border-hairline bg-white p-5 shadow-card sm:p-6">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </section>
    </div>
</x-app-layout>
