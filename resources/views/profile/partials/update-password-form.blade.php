@include('profile.partials.section-head', [
    'title' => __('ui::profile.titles.update_password'),
    'description' => __('ui::profile.descriptions.update_password'),
])

<form method="post" action="{{ route('password.update') }}" class="px-5 py-4">
    @csrf
    @method('put')

    <div class="grid max-w-2xl gap-4 sm:grid-cols-2">
        <div class="flex flex-col gap-1.5 sm:col-span-2 sm:w-1/2 sm:pr-2">
            <x-ui.field-label for="update_password_current_password">{{ __('ui::auth.fields.current_password') }}</x-ui.field-label>
            <x-ui.input id="update_password_current_password" name="current_password" type="password" autocomplete="current-password" :aria-invalid="$errors->updatePassword->has('current_password') ? 'true' : null" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" />
        </div>

        <div class="flex flex-col gap-1.5">
            <x-ui.field-label for="update_password_password">{{ __('ui::auth.fields.new_password') }}</x-ui.field-label>
            <x-ui.input id="update_password_password" name="password" type="password" autocomplete="new-password" :aria-invalid="$errors->updatePassword->has('password') ? 'true' : null" />
            <x-input-error :messages="$errors->updatePassword->get('password')" />
        </div>

        <div class="flex flex-col gap-1.5">
            <x-ui.field-label for="update_password_password_confirmation">{{ __('ui::auth.fields.confirm_password') }}</x-ui.field-label>
            <x-ui.input id="update_password_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" :aria-invalid="$errors->updatePassword->has('password_confirmation') ? 'true' : null" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" />
        </div>
    </div>

    <div class="mt-5 flex items-center gap-3 border-t border-hairline-subtle pt-4">
        <x-pill-button type="submit" :variant="$showForceResetBanner ? 'primary' : 'secondary'">{{ __('ui::profile.actions.update_password') }}</x-pill-button>
        @include('profile.partials.saved-flash', ['status' => 'password-updated'])
    </div>
</form>
