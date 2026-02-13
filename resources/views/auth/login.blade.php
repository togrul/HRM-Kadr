<x-guest-layout>
    <div class="grid grid-cols-1 md:grid-cols-2 w-full">
        <div class="w-full bg-white/40 dark:bg-gray-800 h-screen shadow-sm overflow-hidden sm:rounded-lg">

            <div class="flex flex-col w-full space-y-4 py-10 px-8 justify-center max-w-lg mx-auto h-full">
                <a href="/" class="py-2 mx-auto flex">
                    <x-application-logo class="w-16 h-16 fill-current text-gray-500" />
                </a>
                <h1 class="text-black font-title font-bold text-2xl text-center">
                    {{ __('Human Resources Management system') }}</h1>
                <div class="">
                    <!-- Session Status -->
                    <x-auth-session-status class="mb-4" :status="session('status')" />

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <!-- Email Address -->
                        <div>
                            <x-amazing-input id="email" :label="__('Email')" type="email" :value="old('email')" />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <!-- Password -->
                        <div class="mt-4">
                            <x-amazing-input id="password" :label="__('Password')" type="password"
                                autocomplete="current-password" />

                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>

                        <!-- Remember Me -->

                        <div class="flex items-center justify-between mt-4">
                            <label for="remember_me" class="inline-flex items-center">
                                <input id="remember_me" type="checkbox"
                                    class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"
                                    name="remember">
                                <span
                                    class="ml-2 text-sm text-gray-600 dark:text-gray-400">{{ __('Remember me') }}</span>
                            </label>
                            @if (Route::has('password.request'))
                                <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800"
                                    href="{{ route('password.request') }}">
                                    {{ __('Forgot your password?') }}
                                </a>
                            @endif
                        </div>

                        <div class="block mt-4">
                            <x-button
                                type="submit"
                                class="flex transition w-full py-4 duration-300 justify-center items-center font-medium text-base"
                                mode="gray">
                                {{ __('Log in') }}
                            </x-button>
                        </div>
                    </form>
                </div>
            </div>

        </div>

        {{-- right panel --}}
        <div class="w-full bg-white/40 py-3 px-3 h-full">
            <div
                class="flex justify-center bg-zinc-900 h-full rounded-tl-xl rounded-tr-xl rounded-br-xl rounded-bl-[60px]">
                <img src="{{ asset('assets/images/login2.png') }}" alt="" class="object-contain max-w-lg">
            </div>
        </div>
    </div>



</x-guest-layout>
