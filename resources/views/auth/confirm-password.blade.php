<x-auth-shell
    :title="__('ui::auth.titles.confirm_password')"
    :subtitle="__('ui::auth.messages.secure_area_confirmation')"
>
    <form method="POST" action="{{ route('password.confirm') }}" class="mt-6 space-y-4">
        @csrf

        <x-auth-input
            id="password"
            name="password"
            type="password"
            :label="__('ui::auth.fields.password')"
            required
            autofocus
            autocomplete="current-password"
        />

        <x-auth-submit>{{ __('ui::auth.actions.confirm') }}</x-auth-submit>
    </form>
</x-auth-shell>
