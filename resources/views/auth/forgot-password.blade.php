<x-auth-shell
    :title="__('ui::auth.titles.forgot_password')"
    :subtitle="__('ui::auth.messages.forgot_password_help')"
    :back-to-login="true"
>
    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
        @csrf

        <x-auth-input
            id="email"
            name="email"
            type="email"
            :label="__('ui::auth.fields.email')"
            value="{{ old('email') }}"
            required
            autofocus
            placeholder="{{ __('ui::auth.fields.email_placeholder') }}"
        />

        <x-auth-submit>{{ __('ui::auth.actions.email_password_reset_link') }}</x-auth-submit>
    </form>
</x-auth-shell>
