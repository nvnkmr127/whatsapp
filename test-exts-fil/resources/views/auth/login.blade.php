<x-guest-layout>
  <x-slot:title>
    {{ t('login') }}
  </x-slot:title>
  <div class="h-screen">
    @php
      $defaultBg = 'bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500';
      $bgStyle = $announcement->background_color
          ? "background-color: {$announcement->background_color};"
          : '';
      $defaultTextColor = 'text-white';
      $textColor = $announcement->message_color ? "color: {$announcement->message_color};" : '';
      $defaultlinkColor = 'text-purple-500';
      $linktextColor = $announcement->link_text_color
          ? "color: {$announcement->link_text_color};"
          : '';
    @endphp

    @if ($announcement->isEnable)
      <div class="py-3 {{ !$announcement->background_color ? $defaultBg : '' }}"
        style="{{ $bgStyle }}">
        <div
          class="max-w-6xl mx-auto px-4 flex flex-col sm:flex-row justify-center items-center gap-2 sm:gap-4">
          <p class="font-medium text-center {{ !$announcement->message_color ? $defaultTextColor : '' }}"
            style="{{ $textColor }}">

            {{ $announcement->message }}
          </p>
          @if ($announcement->link)
            <a href="{{ $announcement->link }}"
              class="px-4 py-1.5 text-sm font-semibold rounded-full {{ !$announcement->link_text_color ? $defaultlinkColor : '' }} bg-white shadow-md hover:shadow-lg transition-all transform hover:scale-105"
              style="{{ $linktextColor }}">
              {{ $announcement->link_text }}
            </a>
          @endif
        </div>
      </div>
    @endif

    <div x-data="{
        showAnnouncement: {{ $announcement->isEnable ? 'true' : 'false' }},
        showInfo: {{ empty(session('status')) && empty(session('error')) ? 'true' : 'false' }}
    }" :class="showAnnouncement ? 'overflow-hidden' : ''"
      class="grid grid-cols-1 md:grid-cols-12 sm:h-screen h-[calc(100vh_-_90px)]">
      <!-- Login Section -->
      <div
        class="col-span-1 md:col-span-12 lg:col-span-4 flex flex-col justify-center items-center px-6 sm:px-20 transition-all duration-300 my-0">

        <div class="lg:w-full md:w-1/2 ">
          <!-- Title -->
          <div class="w-full flex items-center my-4 justify-center p-6">
            <h1 class="text-center text-2xl font-bold dark:text-slate-200"> {{ t('login') }}
            </h1>
          </div>

          <!-- Session Status -->
          <x-auth-session-status class="mb-4" x-show="!showInfo" x-init="showInfo = false" />
          <form method="POST" action="{{ route('login') }}" x-data="{ loading: false }"
            x-on:submit="loading = true; $el.submit();">
            @csrf

            <!-- Email Address -->
            <div>
              <div class="flex item-centar justify-start gap-1">
                <span class="text-red-500">*</span>
                <x-input-label for="email" :value="t('email')" />
              </div>
              <x-text-input id="email" class="block mt-1 w-full" type="text" name="email"
                :value="old('email')" autofocus autocomplete="username" />
              <x-input-error :messages="$errors->first('email')" class="mt-2" for="email" />
            </div>

            <!-- Password -->
            <div class="mt-4" x-data="{ showPassword: false }">
              <div class="flex item-centar justify-start gap-1">
                <span class="text-red-500">*</span>
                <x-input-label for="password" :value="t('password')" />
              </div>
              <div class="relative">
                <x-text-input id="password" class="block mt-1 w-full pr-10"
                  x-bind:type="showPassword ? 'text' : 'password'" name="password"
                  autocomplete="current-password" />

                <!-- Eye Icon Button -->
                <button type="button"
                  class="absolute inset-y-0 right-3 flex items-center text-gray-500"
                  x-on:click="showPassword = !showPassword">
                  <x-heroicon-m-eye x-show="showPassword" class="h-5 w-5 text-gray-400" />
                  <x-heroicon-m-eye-slash x-show="!showPassword" class="h-5 w-5 text-gray-400" />
                </button>
              </div>
              <x-input-error :messages="$errors->first('password')" class="mt-2" for="password" />
            </div>

            <div class="flex items-center justify-between mt-4">
              <!-- Remember Me -->
              <div class="block">
                <label for="remember_me" class="inline-flex items-center">
                  <input id="remember_me" type="checkbox"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                    name="remember">
                  <span class="ms-2 text-sm text-gray-600">{{ t('remember_me') }}</span>
                </label>
              </div>
            </div>

            @if (get_setting('re-captcha.isReCaptchaEnable'))
              <div>
                <div class="bg-slate-100 p-4 rounded-md text-sm text-slate-600">
                  This site is protected by reCAPTCHA and the Google
                  <a href="https://policies.google.com/privacy" class="hover:text-slate-500"
                    tabindex="-1">Privacy Policy</a> and
                  <a href="https://policies.google.com/terms" class="hover:text-slate-500"
                    tabindex="-1">Terms of Service</a> apply.
                </div>
                <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
              </div>
            @endif

            <div class="mt-6 flex flex-col justify-center items-center gap-4">

              <button type="submit"
                class="relative w-full px-3 py-3 font-medium text-white bg-[#4f46e5] hover:bg-[#6366f1] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 rounded-lg min-h-12"
                :disabled="loading">
                <span x-show="loading" class="absolute inset-0 flex items-center justify-center">
                  <x-heroicon-o-arrow-path class="animate-spin w-6 h-6" />
                </span>
                <span x-show="!loading"> {{ t('login_lb') }}</span>
              </button>

              @if (Route::has('password.request') && can_send_email('password-reset'))
                <a class=" text-sm text-blue-600 hover:text-indigo-700"
                  href="{{ route('password.request') }}">
                  {{ t('forgot_password_fp') }}
                </a>
              @endif
            </div>
          </form>
        </div>
      </div>

      <!-- Image Section -->

      <div
        class="hidden lg:block md:col-span-8 bg-gradient-to-r from-indigo-500 to-purple-500 relative">
        <div class="absolute inset-0 flex justify-center items-center">
          <img
            src="{{ get_setting('general.cover_page_image') ? Storage::url(get_setting('general.cover_page_image')) : url('./img/coverpage.png') }}"
            alt="Cover Page Image" class="object-cover" @class([
                'w-[729px] h-[152px]' => get_setting('general.cover_page_image'),
            ])>
        </div>
      </div>
    </div>
  </div>
</x-guest-layout>
@if (get_setting('re-captcha.isReCaptchaEnable'))
  <script
    src="https://www.google.com/recaptcha/api.js?render={{ get_setting('re-captcha.site_key') }}">
  </script>
  <script>
    grecaptcha.ready(function() {
      grecaptcha.execute('{{ get_setting('re-captcha.site_key') }}', {
        action: 'login'
      }).then(function(token) {
        document.getElementById('g-recaptcha-response').value = token;
      });
    });
  </script>
@endif
