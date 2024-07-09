<x-guest-layout>
    <div class="w-full sm:max-w-3xl bg-white dark:bg-gray-800 shadow-sm overflow-hidden sm:rounded-lg grid grid-cols-1 sm:grid-cols-2">
        <div class="w-full relative">
            <img src="{{ asset('/assets/images/hr.jpg') }}" alt="" class="w-full h-full">
            <div class="absolute bottom-0 px-4 py-6 bg-gray-200/50 z-20 w-full flex items-end justify-center">
                <a href="/">
                    <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
                </a>
            </div>
        </div>
        <div class="flex flex-col space-y-4 py-10 px-8 justify-center">
           <h1 class="text-black font-title font-bold text-2xl text-center">{{ __('Human Resources Management system') }}</h1>
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
                  <x-amazing-input id="password" :label="__('Password')" type="password" autocomplete="current-password" />

                  <x-input-error :messages="$errors->get('password')" class="mt-2" />
              </div>

              <!-- Remember Me -->

              <div class="flex items-center justify-between mt-4">
                  <label for="remember_me" class="inline-flex items-center">
                      <input id="remember_me" type="checkbox" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800" name="remember">
                      <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">{{ __('Remember me') }}</span>
                  </label>
                  @if (Route::has('password.request'))
                      <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800" href="{{ route('password.request') }}">
                          {{ __('Forgot your password?') }}
                      </a>
                  @endif
              </div>

              <div class="block mt-4">
                  <x-button
                      class="flex transition w-full py-3 duration-300 justify-center items-center font-medium text-base"
                      mode="success"
                  >
                      {{ __('Log in') }}
                  </x-button>
              </div>
          </form>
          </div>
        </div>

    </div>

</x-guest-layout>
