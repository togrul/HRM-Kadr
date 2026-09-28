@include('profile.partials.section-head', [
    'title' => __('ui::profile.titles.delete_account'),
    'description' => __('ui::profile.descriptions.delete_account'),
])

<div class="flex items-center gap-3 px-5 py-4">
    <x-pill-button
        variant="danger"
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >{{ __('ui::profile.titles.delete_account') }}</x-pill-button>
</div>

{{-- The password re-entry IS the confirmation here, so this keeps Breeze's modal form
     rather than the global confirm-action dialog. --}}
<x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" maxWidth="md" focusable>
    <form method="post" action="{{ route('profile.destroy') }}">
        @csrf
        @method('delete')

        <div class="px-5 pb-4 pt-5">
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-rose-50 text-rose-600">
                <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
            </div>

            <h2 class="mt-3 text-[15px] font-semibold tracking-[-0.02em] text-ink">
                {{ __('ui::profile.descriptions.delete_account_confirmation') }}
            </h2>
            <p class="mt-1 text-[12.5px] leading-5 text-ink-muted">
                {{ __('ui::profile.descriptions.delete_account_password_confirmation') }}
            </p>

            <div class="mt-4 flex flex-col gap-1.5">
                <x-ui.field-label for="delete_account_password">{{ __('ui::auth.fields.password') }}</x-ui.field-label>
                <x-ui.input id="delete_account_password" name="password" type="password" autocomplete="current-password" :aria-invalid="$errors->userDeletion->has('password') ? 'true' : null" />
                <x-input-error :messages="$errors->userDeletion->get('password')" />
            </div>
        </div>

        <div class="flex justify-end gap-2 border-t border-hairline-subtle bg-[#fafafa] px-5 py-3">
            <x-pill-button x-on:click="$dispatch('close')">{{ __('ui::profile.actions.cancel') }}</x-pill-button>
            <x-pill-button type="submit" variant="danger">{{ __('ui::profile.titles.delete_account') }}</x-pill-button>
        </div>
    </form>
</x-modal>
