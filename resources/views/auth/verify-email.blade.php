<x-auth-shell
    :title="__('ui::auth.titles.verify_email')"
    :subtitle="__('ui::auth.messages.verification_pending')"
>
    @if (session('status') == 'verification-link-sent')
        <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-3 text-[12.5px] text-emerald-700">
            {{ __('ui::auth.messages.verification_link_sent') }}
        </div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}" class="mt-6">
        @csrf
        <x-auth-submit>{{ __('ui::auth.actions.resend_verification_email') }}</x-auth-submit>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-3">
        @csrf
        <button type="submit" class="h-11 w-full rounded-xl border border-hairline bg-[#f4f4f5] text-[13.5px] font-semibold text-ink-soft transition hover:bg-[#e4e4e7] hover:text-ink">
            {{ __('ui::auth.actions.log_out') }}
        </button>
    </form>
</x-auth-shell>
