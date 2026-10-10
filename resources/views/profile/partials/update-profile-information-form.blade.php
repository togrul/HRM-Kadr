@include('profile.partials.section-head', [
    'title' => __('ui::profile.titles.profile_information'),
    'description' => __('ui::profile.descriptions.profile_information'),
])

<form id="send-verification" method="post" action="{{ route('verification.send') }}">
    @csrf
</form>

<form method="post" action="{{ route('profile.update') }}" class="px-5 py-4">
    @csrf
    @method('patch')

    <div class="grid max-w-2xl gap-4 sm:grid-cols-2">
        <div class="flex flex-col gap-1.5">
            <x-ui.field-label for="name">{{ __('ui::auth.fields.name') }}</x-ui.field-label>
            <x-ui.input id="name" name="name" type="text" :value="old('name', $user->name)" required autofocus autocomplete="name" :aria-invalid="$errors->has('name') ? 'true' : null" />
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <div class="flex flex-col gap-1.5">
            <x-ui.field-label for="email">{{ __('ui::auth.fields.email') }}</x-ui.field-label>
            <x-ui.input id="email" type="email" :value="$user->email" readonly disabled autocomplete="username" aria-describedby="email-admin-only" />
            <p id="email-admin-only" class="text-[11.5px] leading-4 text-ink-faint">{{ __('ui::auth.messages.email_admin_only') }}</p>
        </div>
    </div>

    @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
        <div class="mt-4 max-w-2xl rounded-xl border border-hairline bg-[#fafafa] px-4 py-3 text-[12.5px] leading-5 text-ink-muted">
            {{ __('ui::auth.messages.email_unverified') }}
            <button form="send-verification" class="font-semibold text-ink underline underline-offset-2 hover:text-ink-soft focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400">
                {{ __('ui::auth.messages.resend_verification_prompt') }}
            </button>

            @if (session('status') === 'verification-link-sent')
                <p class="mt-1.5 font-medium text-[#047857]">{{ __('ui::auth.messages.new_verification_link_sent') }}</p>
            @endif
        </div>
    @endif

    <div class="mt-5 flex items-center gap-3 border-t border-hairline-subtle pt-4">
        <x-pill-button type="submit" :variant="$showForceResetBanner ? 'secondary' : 'primary'">{{ __('ui::profile.actions.save') }}</x-pill-button>
        @include('profile.partials.saved-flash', ['status' => 'profile-updated'])
    </div>
</form>
