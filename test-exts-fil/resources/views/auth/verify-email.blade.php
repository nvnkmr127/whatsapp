<x-guest-layout>
  <x-slot:title>
    {{ t('verify_email') }}
  </x-slot:title>
  <div class="grid grid-cols-1 md:grid-cols-12 sm:h-screen h-[calc(100vh_-_90px)]">
    <div
      class="col-span-1 md:col-span-12 lg:col-span-4 flex flex-col justify-center items-center px-6 sm:px-20 transition-all duration-300">
      <div class="lg:w-full md:w-1/2">
        <!-- Title -->
        <div class="w-full flex items-center my-2 justify-center p-6">
          <h1 class="text-center text-2xl font-bold">{{ t('email_veri') }}</h1>
        </div>

        <!-- Status Messages -->
        <x-auth-session-status class="mb-4" x-show="!showInfo" x-init="showInfo = false" />

        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">{{ t('email_verification') }}</p>

        <div class="mt-4 flex justify-between items-center">
          <!-- Verification Form -->
          <form method="POST" action="{{ route('email.varified') }}" x-data="{ loading: false }"
            @submit="loading = true">
            @csrf
            <button type="submit" x-bind:disabled="loading"
              class="w-full text-white bg-[#4f46e5] hover:bg-[#6366f1] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 px-4 py-2 rounded-md flex justify-center items-center text-sm min-w-[100px]">
              <span>{{ t('verify_email') }}</span>
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
          </form>

          <!-- Logout Form -->
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
              class="text-red-700 dark:text-red-400 bg-red-200 hover:bg-red-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 px-4 py-2 rounded-md flex justify-center items-center text-sm min-w-[100px] dark:bg-slate-700 dark:hover:bg-slate-600 dark:hover:text-red-500 dark:focus:ring-offset-slate-800">
              {{ t('logout_ve') }}
            </button>
          </form>
        </div>
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
