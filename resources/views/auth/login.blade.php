<x-auth-shell :title="__('ui::auth.titles.app_name')" :subtitle="__('ui::auth.labels.sign_in_subtitle')">
    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
        @csrf

        <x-auth-input
            id="email"
            name="email"
            type="email"
            :label="__('ui::auth.fields.email')"
            value="{{ old('email') }}"
            required
            autofocus
            autocomplete="username"
            placeholder="{{ __('ui::auth.fields.email_placeholder') }}"
        />

        <div x-data="{ show: false }">
            <label for="password" class="mb-1.5 block text-[12.5px] font-medium text-ink-soft">
                {{ __('ui::auth.fields.password') }}
            </label>
            <div class="relative">
                <input
                    id="password"
                    name="password"
                    :type="show ? 'text' : 'password'"
                    type="password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                    class="h-11 w-full rounded-xl border border-hairline bg-white px-3.5 pr-11 text-[13.5px] text-ink outline-none placeholder:text-ink-faint focus:border-ink focus:ring-2 focus:ring-ink/10"
                >
                <button
                    type="button"
                    x-on:click="show = ! show"
                    class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-ink-faint transition hover:text-ink"
                    :title="show ? '{{ __('ui::auth.labels.hide_password') }}' : '{{ __('ui::auth.labels.show_password') }}'"
                >
                    <svg x-show="!show" class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                    <svg x-show="show" x-cloak class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 3l18 18M10.6 10.6a3 3 0 0 0 4.2 4.2M9.9 5.2A9.6 9.6 0 0 1 12 5c6.4 0 10 7 10 7a17.9 17.9 0 0 1-3.4 4.3M6.2 6.6C3.6 8.3 2 12 2 12s3.6 7 10 7c1.4 0 2.6-.2 3.8-.6"/>
                    </svg>
                    <span class="sr-only">{{ __('ui::auth.labels.show_password') }}</span>
                </button>
            </div>
        </div>

        <div class="flex items-center justify-between pt-1">
            <label for="remember_me" class="inline-flex items-center gap-2">
                <input id="remember_me" name="remember" type="checkbox" class="h-4 w-4 rounded border-hairline text-ink focus:ring-0">
                <span class="text-[12.5px] text-ink-muted">{{ __('ui::auth.fields.remember_me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-[12.5px] text-ink-muted underline-offset-2 transition hover:text-ink hover:underline">
                    {{ __('ui::auth.links.forgot_password') }}
                </a>
            @endif
        </div>

        <x-auth-submit>{{ __('ui::auth.actions.log_in') }}</x-auth-submit>
    </form>
</x-auth-shell>
