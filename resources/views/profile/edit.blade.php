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

    @php
        $mustResetPassword = (bool) ($user->getAttributes()['must_reset_password'] ?? false);
        $showForceResetBanner = request()->boolean('force_password_reset')
            || ($mustResetPassword && $user->hasRole(\App\Modules\Personnel\Application\Services\MyHr\MyHrAccountProvisioningService::EMPLOYEE_ROLE));
        $cardClass = 'scroll-mt-4 overflow-hidden rounded-2xl border border-hairline bg-white shadow-card';
    @endphp

    {{-- ===================== header ===================== --}}
    <x-page-header
        :title="__('ui::profile.titles.account_settings')"
        :breadcrumb="__('ui::profile.titles.profile')"
    >
        <x-slot:icon>
            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
        </x-slot:icon>

        <x-slot:actions>
            <div class="min-w-0 leading-tight sm:text-right">
                <p class="truncate text-[13px] font-semibold text-ink">{{ $user->name }}</p>
                <p class="truncate text-[11.5px] text-ink-faint">{{ $user->email }}</p>
            </div>
        </x-slot:actions>
    </x-page-header>

    {{-- ===================== body ===================== --}}
    <div class="flex flex-col gap-4 px-4 py-4 sm:px-5">
        @if ($showForceResetBanner)
            <div class="rounded-2xl border border-amber-200 bg-[#fef3c7] px-5 py-4 text-[13px] leading-6 text-[#b45309]" role="alert">
                <p class="font-semibold">{{ __('ui::profile.titles.force_password_reset') }}</p>
                <p class="mt-1">{{ __('ui::profile.descriptions.force_password_reset') }}</p>
            </div>
        @endif

        <section id="profile-information" class="{{ $cardClass }}">
            @include('profile.partials.update-profile-information-form')
        </section>

        <section id="update-password" class="{{ $cardClass }}">
            @include('profile.partials.update-password-form')
        </section>

        <section id="delete-account" class="{{ $cardClass }}">
            @include('profile.partials.delete-user-form')
        </section>
    </div>
</x-app-layout>
