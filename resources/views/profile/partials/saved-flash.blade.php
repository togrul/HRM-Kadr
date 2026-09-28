{{-- Brief "saved" note beside a form's submit button after the redirect back. --}}
@if (session('status') === $status)
    <p
        x-data="{ show: true }"
        x-show="show"
        x-transition
        x-init="setTimeout(() => show = false, 2000)"
        class="inline-flex items-center gap-1.5 text-[12.5px] font-medium text-[#047857]"
        role="status"
    >
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
        {{ __('ui::profile.actions.saved') }}
    </p>
@endif
