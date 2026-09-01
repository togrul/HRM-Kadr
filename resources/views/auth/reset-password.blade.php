<x-auth-shell
    :title="__('ui::auth.titles.reset_password')"
    :subtitle="__('ui::auth.messages.reset_password_help')"
    :back-to-login="true"
>
    <form method="POST" action="{{ route('password.store') }}" class="mt-6 space-y-4">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <x-auth-input
            id="email"
            name="email"
            type="email"
            :label="__('ui::auth.fields.email')"
            value="{{ old('email', $request->email) }}"
            required
            autofocus
            autocomplete="username"
        />

        <x-auth-input
            id="password"
            name="password"
            type="password"
            :label="__('ui::auth.fields.new_password')"
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

        <x-auth-submit>{{ __('ui::auth.actions.reset_password') }}</x-auth-submit>
    </form>
</x-auth-shell>
