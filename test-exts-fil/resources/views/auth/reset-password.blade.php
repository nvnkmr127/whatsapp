<x-guest-layout>
  <x-slot:title>
    {{ t('reset_password_rp') }}
  </x-slot:title>
  <div class="grid grid-cols-1 md:grid-cols-12 sm:h-screen h-[calc(100vh_-_90px)]">
    <!-- Reset Password Section -->
    <div
      class="col-span-1 md:col-span-12 lg:col-span-4 flex flex-col justify-center items-center px-6 sm:px-20 transition-all duration-300 ">
      <div class="lg:w-full md:w-1/2 ">
        <!-- Title -->
        <div class="w-full flex items-center my-2 justify-center p-6">
          <h1 class="text-center text-2xl font-bold">{{ t('reset_password_rp') }}</h1>
        </div>

        <!-- Alert Message -->
        <div
          class="mb-4 bg-yellow-50 border-l-4 rounded border-yellow-300 text-yellow-800 px-2 py-3 mt-5 dark:bg-gray-800 dark:border-yellow-800 dark:text-yellow-300"
          role="alert">
          <div class="flex justify-start items-center gap-2">
            <p class="text-sm">
              {{ t('auth.forgot_password') }}
            </p>
          </div>
        </div>

        <form x-data="{ loading: false }" @submit="loading = true" method="POST"
          action="{{ route('password.store') }}">
          @csrf

          <!-- Password Reset Token -->
          <input type="hidden" name="token" value="{{ $request->route('token') }}">

          <!-- Email Address -->
          <div>
            <x-input-label for="email" :value="t('email_address')" />
            <x-text-input id="email" class="block mt-1 w-full" type="text" name="email"
              :value="old('email', $request->email)" autofocus autocomplete="username" />
            <x-input-error :messages="$errors->first('email')" class="mt-2" for="email" />
          </div>

          <!-- Password -->
          <div class="mt-4">
            <x-input-label for="password" :value="t('password')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password"
              autocomplete="new-password" />
            <x-input-error :messages="$errors->first('password')" class="mt-2" for="password" />
          </div>

          <!-- Confirm Password -->
          <div class="mt-4">
            <x-input-label for="password_confirmation" :value="t('confirm_password')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password"
              name="password_confirmation" autocomplete="new-password" />
            <x-input-error :messages="$errors->first('password_confirmation')" class="mt-2" for="password_confirmation" />
          </div>

          <div class="flex items-center justify-end mt-4">
            <button type="submit" x-bind:disabled="loading"
              class="w-full text-white bg-[#4f46e5] hover:bg-[#6366f1] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 px-4 py-2 rounded-md flex justify-center items-center text-sm min-w-[100px]">
              <span>{{ t('reset_password_rp') }}</span>
              <span x-show="loading">
                <svg class="animate-spin h-5 w-5 ml-2 text-white" xmlns="http://www.w3.org/2000/svg"
                  fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10"
                    stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                  </path>
                </svg>
              </span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Image Section -->
    <div
      class="hidden lg:block md:col-span-8 bg-gradient-to-r from-indigo-500 to-purple-500 relative">
      <img
        src="{{ get_setting('general.cover_page_image') ? Storage::url(get_setting('general.cover_page_image')) : url('./img/coverpage.png') }}"
        alt="Cover Page Image" class="object-cover" @class([
            'w-[729px] h-[152px]' => get_setting('general.cover_page_image'),
        ])>
    </div>
  </div>
</x-guest-layout>
