<x-guest-layout>
  <x-slot:title>
    {{ t('register') }}
  </x-slot:title>
  <div class="grid grid-cols-1 md:grid-cols-12 sm:h-screen h-[calc(100vh_-_90px)]">
    <!-- Login Section -->
    <div
      class="col-span-1 md:col-span-12 lg:col-span-4 flex flex-col justify-center items-center px-6 sm:px-20 transition-all duration-300 ">
      <div class="lg:w-full md:w-1/2 ">
        <div class="w-full flex items-center my-4 justify-center p-6">
          <!-- Title -->
          <h1 class="text-center text-2xl font-bold"> {{ t('register') }}</h1>
        </div>
        <form method="POST" action="#">
          @csrf
          <!-- Company Name -->
          <div>
            <div class="flex justify-start gap-1">
              <span class="text-red-500 dark:text-red-400">*</span>
              <x-input-label for="name" :value="t('name')" />
            </div>

            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name"
              :value="old('name')" autofocus autocomplete="name" />
            <x-input-error :messages="$errors->first('name')" class="mt-2" for="name" />

          </div>
          <div class="mt-4 flex gap-4 justify-center items-center">
            <!-- Email Address -->
            <div class="w-full">
              <div class="flex justify-start gap-1">
                <span class="text-red-500 dark:text-red-400">*</span>
                <x-input-label for="email" :value="t('email')" />
              </div>
              <x-text-input id="email" class="block mt-1 w-full" type="text" name="email"
                :value="old('email')" autocomplete="email" />
              <x-input-error :messages="$errors->first('email')" class="mt-2" for="email" />
            </div>
          </div>
          <div class="mt-4 gap-4 grid grid-flow-col grid-cols-2">
            <!-- Password -->
            <div class="w-full">
              <div class="flex justify-start gap-1">
                <span class="text-red-500 dark:text-red-400">*</span>
                <x-input-label for="password" :value="t('password')" />
              </div>
              <div class="flex flex-col">
                <x-text-input id="password" class="block mt-1 w-full" type="password"
                  name="password" autocomplete="new-password" />
                <x-input-error :messages="$errors->first('password')" class="mt-2 min-h-[1.5rem]" for="password" />
              </div>
            </div>

            <!-- Confirm Password -->
            <div class="w-full">
              <div class="flex justify-start gap-1">
                <span class="text-red-500 dark:text-red-400">*</span>
                <x-input-label for="password_confirmation" :value="t('confirm_password')" />
              </div>
              <div class="flex flex-col">
                <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password"
                  name="password_confirmation" autocomplete="new-password" />
                <x-input-error :messages="$errors->first('password_confirmation')" class="mt-2 min-h-[1.5rem]"
                  for="password_confirmation" />
              </div>
            </div>
          </div>

          <div class="mt-6">
            <x-button.loading-button type="submit" class="w-full">
              {{ t('register') }}
            </x-button.loading-button>
          </div>
          <div class="flex items-center justify-center mt-4">
            <a class="text-sm text-blue-600 dark:text-gray-400 hover:text-indigo-700 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800"
              href="{{ route('login') }}">
              {{ t('auth.already_registered') }}
            </a>
          </div>
        </form>
      </div>
    </div>

    <!-- Image Section -->
    <div
      class="hidden lg:block md:col-span-8 bg-gradient-to-r from-indigo-500 to-purple-500  relative">
      <img
        src="{{ get_setting('general.cover_page_image') ? Storage::url(get_setting('general.cover_page_image')) : url('./img/coverpage.png') }}"
        alt="Cover Page Image" class="object-cover" @class([
            'w-[729px] h-[152px]' => get_setting('general.cover_page_image'),
        ])>
    </div>
  </div>
</x-guest-layout>
