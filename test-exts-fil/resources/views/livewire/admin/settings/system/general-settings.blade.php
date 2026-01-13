<div class="mx-auto px-4 md:px-0" x-data="{
    fileTypes: {{ json_encode(get_whatsmark_allowed_extension()) }},
    imageExtensions: '',
    file: null,
    preview: null,
    errorMessage: '',

    init() {
        if (this.fileTypes?.file_types?.extension) {
            let allExtensions = this.fileTypes.file_types.extension.split(',').map(ext => ext.trim());
            this.imageExtensions = allExtensions.slice(0, 4).join(', '); // First 4 extensions
        }
    },

    handleFileChange(event) {
        this.errorMessage = ''; // Clear previous errors
        this.file = event.target.files[0];

        if (!this.file) return;

        let fileExt = '.' + this.file.name.split('.').pop().toLowerCase();
        let allowedExtensions = this.imageExtensions.split(', ');

        if (!allowedExtensions.includes(fileExt)) {
            this.errorMessage = `Invalid file type. Allowed: ${this.imageExtensions}`;
            this.file = null;
            this.preview = null;
            event.target.value = ''; // Reset input
            return;
        }

        let reader = new FileReader();
        reader.onload = (e) => {
            this.preview = e.target.result;
        };
        reader.readAsDataURL(this.file);
    }

}">

  <x-slot:title>
    {{ t('system_core_settings') }}
  </x-slot:title>
  <!-- Page Heading -->
  <div class="pb-6">
    <x-settings-heading>{{ t('system_setting') }}</x-settings-heading>
  </div>

  <div class="flex flex-wrap lg:flex-nowrap gap-4">
    <!-- Sidebar Menu -->
    <div class="w-full lg:w-1/5">
      <x-admin-system-settings-navigation wire:ignore />
    </div>
    <div class="flex-1 space-y-5">
      <form wire:submit="save" class="space-y-6" x-data x-init="window.initTomSelect('.tom-select')">
        <x-card class="rounded-lg">
          <x-slot:header>
            <x-settings-heading>{{ t('system_core_settings') }}</x-settings-heading>
            <x-settings-description>{{ t('system_core_settings_description') }}</x-settings-description>
          </x-slot:header>
          <x-slot:content>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
              <div class="sm:col-span-3 lg:col-span-1">
                <x-label for="site_name" :value="t('site_name')" />
                <x-input wire:model.defer="site_name" type="text" id="site_name"
                  class="mt-1 block w-full" />
                <x-input-error for="site_name" class="mt-2" />
              </div>
              <div class="sm:col-span-3 lg:col-span-2">
                <x-label for="site_description" :value="t('site_description')" />
                <x-input wire:model.defer="site_description" id="site_description"
                  class="mt-1 block w-full" />
                <x-input-error for="site_description" class="mt-2" />
              </div>
            </div>

            <h3 class="text-lg font-medium text-gray-900 mb-2 mt-4 dark:text-slate-300">
              {{ t('localization') }} </h3>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-4 z-0">
              <div wire:ignore>
                <x-label for="timezone" :value="t('timezone')" />
                <x-select id="timezone" class="mt-1 block w-full tom-select"
                  wire:model.defer="timezone" wire:change="$set('timezone', $event.target.value)">
                  @foreach ($timezone_list as $tz)
                    <option value="{{ $tz }}" {{ $tz == $timezone ? 'selected' : '' }}>
                      {{ $tz }}
                    </option>
                  @endforeach
                </x-select>

                <x-input-error for="timezone" class="mt-2" />
              </div>
              <div wire:ignore>
                <x-label for="date_format" :value="t('date_format')" />
                <x-select id="date_format" class="mt-1 block w-full tom-select"
                  wire:model.defer="date_format"
                  wire:change="$set('date_format', $event.target.value)">
                  @foreach ($date_formats as $format => $example)
                    <option value="{{ $format }}"
                      {{ $format == $date_format ? 'selected' : '' }}>
                      {{ $example }}
                    </option>
                  @endforeach
                </x-select>

                <x-input-error for="date_format" class="mt-2" />
              </div>
              <div wire:ignore>
                <x-label for="time_format" :value="t('time_format')" />
                <x-select id="time_format" class="mt-1 block w-full tom-select"
                  wire:model.defer="time_format"
                  wire:change="$set('time_format', $event.target.value)">
                  <option value="24" {{ $time_format == '24' ? 'selected' : '' }}>
                    {{ t('24_hours') }}
                  </option>
                  <option value="12" {{ $time_format == '12' ? 'selected' : '' }}>
                    {{ t('12_hours') }}
                  </option>
                </x-select>

                <x-input-error for="time_format" class="mt-2" />
              </div>
              <div wire:ignore>
                <x-label for="active_language" :value="t('default_language')" />
                <x-select id="active_language" class="mt-1 block w-full tom-select"
                  wire:model.defer="active_language"
                  wire:change="$set('active_language', $event.target.value)">
                  @foreach (getLanguage(null, ['code', 'name']) as $language)
                    <option value="{{ $language->code }}"
                      {{ $language->code == $active_language ? 'selected' : '' }}>
                      {{ $language->name }}
                    </option>
                  @endforeach
                </x-select>

                <x-input-error for="active_language" class="mt-2" />
              </div>
            </div>

            <h3 class="text-lg font-medium text-gray-900 mb-2 mt-4 dark:text-slate-300">
              {{ t('logo_favicon') }} </h3>

            <div class="w-full">
              <x-label for="site_logo" :value="new \Illuminate\Support\HtmlString(
                  t('site_logo') .
                      '<em class=\'text-indigo-600\'> (' .
                      t('recommended') .
                      ' 22px x 40px)</em>',
              )" class="mb-2" />
              <div
                class="relative p-6 border-2 border-dashed rounded-lg cursor-pointer hover:border-blue-500 transition duration-300"
                x-on:click="$refs.imageInput.click()">
                <template x-if="preview">
                  <img :src="preview" alt="Image Preview"
                    class="h-24 w-48 object-contain rounded-lg shadow-md">
                </template>
                <template x-if="!preview && '{{ get_setting('general.site_logo') }}'">
                  <img src="{{ asset('storage/' . get_setting('general.site_logo')) }}"
                    alt="Site Logo" class="h-24 w-48 object-contain rounded-lg shadow-md">
                </template>
                <template x-if="!preview && !'{{ get_setting('general.site_logo') }}'">
                  <div class="text-center">
                    <x-heroicon-s-photo class="h-12 w-12 text-gray-400 mx-auto" />
                    <p class="mt-2 text-sm text-gray-600"> {{ t('select_or_browse_to') }} <span
                        class="text-blue-600 underline"> {{ t('site_logo') }} </span></p>
                  </div>
                </template>
                <input x-ref="imageInput" type="file" class="hidden" :accept="imageExtensions"
                  x-on:change="handleFileChange" wire:model="site_logo" wire:ignore>
              </div>
              <p x-show="errorMessage" class="text-red-500 text-sm mt-2" x-text="errorMessage"></p>
              @if (get_setting('general.site_logo'))
                <x-button.text class="mt-2 text-red-500 hover:text-red-600"
                  wire:click="removeSetting('site_logo')" x-on:click="preview = null; file = null;">
                  {{ t('remove_img') }} </x-button.text>
              @endif
              <x-input-error for="site_logo" class="mt-2" />
            </div>

            <div class="w-full mt-2" x-data="{ preview: null, file: null, errorMessage: '' }">
              <x-label for="site_dark_logo" :value="new \Illuminate\Support\HtmlString(
                  t('dark_logo') .
                      ' <em class=\'text-indigo-600\'>(' .
                      t('recommended') .
                      ' 22px x 40px)</em>',
              )" class="mb-2" />

              <div
                class="relative p-6 border-2 border-dashed rounded-lg cursor-pointer hover:border-blue-500 transition duration-300"
                x-on:click="$refs.darkImageInput.click()">
                <template x-if="preview">
                  <img :src="preview" alt="Dark Logo"
                    class="h-24 w-48 object-contain rounded-lg shadow-md">
                </template>
                <template x-if="!preview && '{{ get_setting('general.site_dark_logo') }}'">
                  <img src="{{ asset('storage/' . get_setting('general.site_dark_logo')) }}"
                    alt="Dark Logo" class="h-24 w-48 object-contain rounded-lg shadow-md">
                </template>
                <template x-if="!preview && !'{{ get_setting('general.site_dark_logo') }}'">
                  <div class="text-center">
                    <x-heroicon-s-photo class="h-12 w-12 text-gray-400 mx-auto" />
                    <p class="mt-2 text-sm text-gray-600"> {{ t('select_or_browse_to') }} <span
                        class="text-blue-600 underline"> {{ t('dark_logo') }} </span></p>
                  </div>
                </template>
                <input x-ref="darkImageInput" type="file" class="hidden"
                  :accept="imageExtensions" x-on:change="handleFileChange"
                  wire:model="site_dark_logo" wire:ignore>
              </div>
              <p x-show="errorMessage" class="text-red-500 text-sm mt-2" x-text="errorMessage">
              </p>
              @if (get_setting('general.site_dark_logo'))
                <x-button.text class="mt-2 text-red-500 hover:text-red-600"
                  wire:click="removeSetting('site_dark_logo')"
                  x-on:click="preview = null; file = null;"> {{ t('remove_img') }}
                </x-button.text>
              @endif
              <x-input-error for="site_dark_logo" class="mt-2" />
            </div>

            <div class="w-full mt-2" x-data="{ preview: null, file: null, errorMessage: '' }">
              <x-label for="favicon" :value="t('favicon_icon')" class="mb-2" />
              <div
                class="relative p-6 border-2 border-dashed rounded-lg cursor-pointer hover:border-blue-500 transition duration-300"
                x-on:click="$refs.faviconInput.click()">
                <template x-if="preview">
                  <img :src="preview" alt="Favicon"
                    class="h-16 w-16 object-contain rounded-lg shadow-md">
                </template>
                <template x-if="!preview && '{{ get_setting('general.favicon') }}'">
                  <img src="{{ asset('storage/' . get_setting('general.favicon')) }}"
                    alt="Favicon" class="h-16 w-16 object-contain rounded-lg shadow-md">
                </template>
                <template x-if="!preview && !'{{ get_setting('general.favicon') }}'">
                  <div class="text-center">
                    <x-heroicon-s-photo class="h-12 w-12 text-gray-400 mx-auto" />
                    <p class="mt-2 text-sm text-gray-600"> {{ t('select_or_browse_to') }} <span
                        class="text-blue-600 underline"> {{ t('favicon') }} </span></p>
                  </div>
                </template>
                <input x-ref="faviconInput" type="file"
                  class="hidden":accept="imageExtensions" x-on:change="handleFileChange"
                  wire:model="favicon" wire:ignore>
              </div>
              <p x-show="errorMessage" class="text-red-500 text-sm mt-2" x-text="errorMessage">
              </p>
              @if (get_setting('general.favicon'))
                <x-button.text class="mt-2 text-red-500 hover:text-red-600"
                  wire:click="removeSetting('favicon')" x-on:click="preview = null; file = null;">
                  {{ t('remove_img') }} </x-button.text>
              @endif
              <x-input-error for="favicon" class="mt-2" />
            </div>

            <div class="w-full mt-2" x-data="{ preview: null, file: null, errorMessage: '' }">
              <x-label for="cover_page_image" :value="new \Illuminate\Support\HtmlString(
                  t('cover_page_image') . ' <em class=\'text-indigo-600\'>(729px x 152px)</em>',
              )" class="mb-2" />
              <div
                class="relative p-6 border-2 border-dashed rounded-lg cursor-pointer hover:border-blue-500 transition duration-300"
                x-on:click="$refs.coverPageInput.click()">
                <template x-if="preview">
                  <img :src="preview" alt="cover_page_image"
                    class="h-24 w-48 object-contain rounded-lg shadow-md">
                </template>
                <template x-if="!preview && '{{ get_setting('general.cover_page_image') }}'">
                  <img src="{{ asset('storage/' . get_setting('general.cover_page_image')) }}"
                    alt="Cover Page Image" class="h-24 w-48 object-contain rounded-lg shadow-md">
                </template>
                <template x-if="!preview && !'{{ get_setting('general.cover_page_image') }}'">
                  <div class="text-center">
                    <x-heroicon-s-photo class="h-12 w-12 text-gray-400 mx-auto" />
                    <p class="mt-2 text-sm text-gray-600"> {{ t('select_or_browse_to') }} <span
                        class="text-blue-600 underline"> {{ t('cover_page_image') }} </span>
                    </p>
                  </div>
                </template>
                <input x-ref="coverPageInput" type="file"
                  class="hidden":accept="imageExtensions" x-on:change="handleFileChange"
                  wire:model="cover_page_image" wire:ignore>
              </div>
              <p x-show="errorMessage" class="text-red-500 text-sm mt-2" x-text="errorMessage">
              </p>
              @if (get_setting('general.cover_page_image') || $cover_page_image)
                <x-button.text class="mt-2 text-red-500 hover:text-red-600"
                  wire:click="removeSetting('cover_page_image')"
                  x-on:click="preview = null; file = null;">
                  {{ t('remove_img') }} </x-button.text>
              @endif
              <x-input-error for="cover_page_image" class="mt-2" />
            </div>

          </x-slot:content>
          @if (checkPermission('system_settings.edit'))
            <x-slot:footer class="bg-slate-50 dark:bg-transparent rounded-b-lg">
              <div class="flex justify-end">
                <x-button.loading-button type="submit" target="save">
                  {{ t('save_changes_button') }}
                </x-button.loading-button>
              </div>
            </x-slot:footer>
          @endif
        </x-card>
      </form>
    </div>
  </div>
</div>
<script>
  document.addEventListener('alpine:init', () => {
    Alpine.data('fileUploadComponent', (extensions) => ({
      preview: null,
      file: null,
      errorMessage: '',
      allowedExtensions: extensions.extension.split(','), // Convert string to array

      validateFile(event) {
        this.file = event.target.files[0];

        if (!this.file) {
          this.errorMessage = 'No file selected.';
          return;
        }

        const fileExtension = '.' + this.file.name.split('.').pop().toLowerCase();

        if (!this.allowedExtensions.includes(fileExtension)) {
          this.errorMessage =
            `Invalid file type. Allowed types: ${this.allowedExtensions.join(', ')}`;
          this.preview = null;
          this.file = null;
          return;
        }

        this.errorMessage = ''; // Clear error if valid
        let reader = new FileReader();
        reader.onload = (e) => this.preview = e.target.result;
        reader.readAsDataURL(this.file);
      }
    }));
  });
</script>
