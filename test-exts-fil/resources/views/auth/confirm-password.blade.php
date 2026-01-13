<x-guest-layout>
  <x-slot:title>
    {{ t('confirm_password') }}
  </x-slot:title>
  <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
    {{ t('This is a secure area of the application. Please confirm your password before continuing.') }}
  </div>

  <form method="POST" action="{{ route('password.confirm') }}">
    @csrf

    <div>
      <x-input-label for="password" :value="t('password')" />

      <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required
        autocomplete="current-password" />

      <x-input-error :messages="$errors->get('password')" class="mt-2" for="password" />
    </div>

    <div class="flex justify-end mt-4">
      <x-button.primary>
        {{ t('confirm') }}
      </x-button.primary>
    </div>
  </form>
</x-guest-layout>
