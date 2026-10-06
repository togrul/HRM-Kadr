<x-auth-shell
    :title="__('ui::auth.actions.register')"
    :subtitle="__('ui::auth.titles.app_name')"
    :back-to-login="true"
>
    <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
        @csrf

        <x-auth-input
            id="name"
            name="name"
            :label="__('ui::auth.fields.name')"
            value="{{ old('name') }}"
            required
            autofocus
            autocomplete="name"
        />

        <x-auth-input
            id="email"
            name="email"
            type="email"
            :label="__('ui::auth.fields.email')"
            value="{{ old('email') }}"
            required
            autocomplete="username"
            placeholder="{{ __('ui::auth.fields.email_placeholder') }}"
        />

        <x-auth-input
            id="password"
            name="password"
            type="password"
            :label="__('ui::auth.fields.password')"
            required
            autocomplete="new-password"
        />

        <x-auth-input
            id="password_confirmation"
            name="password_confirmation"
            type="password"
            :label="__('ui::auth.fields.password_confirmation')"
            required
            autocomplete="new-password"
        />

        <x-auth-submit>{{ __('ui::auth.actions.register') }}</x-auth-submit>
    </form>
</x-auth-shell>
