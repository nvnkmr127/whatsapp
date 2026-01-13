<div class="px-4 md:px-0">
  <x-slot:title>
    {{ t('import_contact') }}
  </x-slot:title>

  <div class="py-3 font-display">
    <x-settings-heading>{{ t('import_contact_from_csv_file') }}</x-settings-heading>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 items-start">
    <x-card class="rounded-lg">
      <x-slot:header>
        <h1 class="text-xl font-semibold text-slate-700 dark:text-slate-300">
          {{ t('import_contact_camel') }}
        </h1>
      </x-slot:header>

      <x-slot:content>
        <!-- File Upload Section -->
        <div class="col-span-3">
          <div class="flex flex-col 2xl:flex-row 2xl:items-center 2xl:justify-between">
            <x-label class="mt-[2px]" :value="t('choose_csv_file')" />
            <p class="text-sm cursor-pointer text-blue-500 hover:underline"
              x-on:click="$dispatch('open-modal', 'example-modal')">
              {{ t('csv_sample_file_download') }}
            </p>
          </div>

          <div x-data="{
              fileState: @entangle('csvFile'),
              isDragging: false
          }" class="mt-1 w-full relative">
            <div x-ref="dropZone"
              class="relative text-gray-400 border-2 border-dashed rounded-lg cursor-pointer transition-all duration-200"
              :class="{
                  'border-gray-300 dark:border-gray-600': !isDragging,
                  'border-blue-500 bg-blue-50 dark:border-blue-400 dark:bg-blue-900/20': isDragging
              }"
              @dragover.prevent="isDragging = true" @dragleave.prevent="isDragging = false"
              @drop.prevent="isDragging = false">
              <input type="file" wire:model="csvFile" accept=".csv"
                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" />

              <div class="flex flex-col items-center justify-center py-10 text-center">
                <template x-if="!fileState">
                  <div>
                    <x-heroicon-o-computer-desktop class="mx-auto h-10 w-10 text-gray-400" />
                    <p class="mt-2 text-sm text-gray-500">{{ t('drag_and_drop_description') }}</p>
                    <p class="mt-1 text-xs text-gray-500">{{ t('csv_file_only') }}</p>
                  </div>
                </template>
                <template x-if="fileState">
                  <div class="text-center">
                    <x-heroicon-o-document-text class="mx-auto h-10 w-10 text-blue-500" />
                    <p class="mt-2 text-sm text-gray-900 dark:text-gray-100">
                      {{ t('file_selected') }}
                    </p>
                  </div>
                </template>
              </div>
            </div>
          </div>
          <x-input-error for="csvFile" class="mt-2" />
        </div>

        @if ($csvFile)
          <!-- Progress Bar -->
          <div x-data="{ processed: @entangle('processedRecords'), total: @entangle('totalRecords') }" x-show="total > 0" class="mt-4">
            <div class="w-full bg-gray-200 rounded-full h-4 dark:bg-gray-700">
              <div :style="`width: ${total > 0 ? (processed/total)*100 : 0}%`"
                class="bg-indigo-600 h-4 rounded-full"></div>
            </div>
            <div class="text-sm text-gray-600 mt-2">
              <span x-text="processed"></span> / <span x-text="total"></span>
              {{ t('records_processed') }}
              (<span x-text="total > 0 ? Math.round((processed/total)*100) : 0"></span>%)
            </div>
          </div>

          <div class="mt-4">
            <p class="text-sm text-gray-600">
              {{ t('record_successfully_inserted') }}
              <span class="font-semibold">{{ $validRecords }}</span>
            </p>
            <p class="text-sm text-gray-600">
              {{ t('records_with_error') }}
              <span class="font-semibold">{{ $invalidRecords }}</span>
            </p>
          </div>
        @endif

        @if (!empty($errorMessages))
          <div class="mb-8">
            <h3 class="text-lg font-medium mb-4"> {{ t('import_error') }} </h3>
            <div class="space-y-4 max-h-96 overflow-y-auto px-1">
              @foreach ($errorMessages as $error)
                <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
                  <div class="flex items-center">
                    <div class="flex-shrink-0">
                      <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                          d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                          clip-rule="evenodd" />
                      </svg>
                    </div>
                    <div class="ml-3">
                      <h3 class="text-sm font-medium text-red-800 dark:text-red-200">
                        Row {{ $error['row'] }}
                      </h3>
                      <div class="mt-2 text-sm text-red-700 dark:text-red-300">
                        <ul class="list-disc pl-5 space-y-1">
                          @foreach ($error['errors'] as $field => $messages)
                            @foreach ($messages as $message)
                              <li>{{ ucfirst($field) }}: {{ $message }}</li>
                            @endforeach
                          @endforeach
                        </ul>
                      </div>
                    </div>
                  </div>
                </div>
              @endforeach
            </div>
          </div>
        @endif

      </x-slot:content>

      <x-slot:footer>
        <div class="flex justify-end space-x-3">
          <x-button.secondary wire:click="$set('csvFile', null)">
            {{ t('cancel') }}
          </x-button.secondary>

          @if ($processedRecords)
            <x-button.primary x-on:click="window.location.reload()">
              <span wire:loading.remove> {{ t('reset') }} </span>
            </x-button.primary>
          @else
            <x-button.primary wire:click="processImport" wire:loading.attr="disabled"
              @class(['opacity-50 cursor-not-allowed' => $importInProgress]) :disabled="$importInProgress">
              <span wire:loading.remove wire:target="processImport">
                {{ t('upload') }}
              </span>
              <span wire:loading wire:target="processImport"
                class="flex items-center justify-center min-w-12 min-h-2">
                <x-heroicon-o-arrow-path class="animate-spin w-4 h-4 my-1 ms-3.5" />
              </span>
              </x-button-primary>
          @endif

        </div>
      </x-slot:footer>
    </x-card>
  </div>

  <!-- Sample File Modal -->
  <x-modal name="example-modal" :show="false" maxWidth="5xl">
    <x-card>
      <x-slot:header>
        <div>
          <h1 class="text-xl font-medium text-slate-800 dark:text-slate-300">
            {{ t('download_sample') }}
          </h1>
        </div>
      </x-slot:header>

      <x-slot:content>

        <div class="mt-3">
          <x-dynamic-alert type="primary">
            <span class="font-base font-semibold">{{ t('phone_requirement_column') }}</span>
            {{ t('phone_req_description') }}
          </x-dynamic-alert>

          <x-dynamic-alert type="primary">
            <span class="font-base font-semibold">{{ t('csv_encoding_format') }}</span>
            {{ t('csv_encoding_description') }}
          </x-dynamic-alert>
        </div>

        <div class="flex justify-between my-7 items-center">
          <p class="text-xl text-slate-700 dark:text-slate-200">{{ t('contact') }}</p>
          <button wire:click="downloadSample"
            class="px-4 py-2 bg-gradient-to-r from-green-500 to-green-500 text-white rounded-md  cursor-pointer transition duration-150 ease-in-out dark:bg-gradient-to-r dark:from-green-800 dark:to-green-800">
            {{ t('download_sample') }}
          </button>
        </div>

        <!-- Sample Table structure -->
        <div class="relative overflow-x-auto border border-3 rounded-sm my-4">
          <table class="w-full text-sm text-left text-slate-700 dark:text-slate-200">
            <thead class="text-xs text-gray-700  bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
              <tr>
                <th class="border-r px-4 py-2"><span
                    class="text-red-500 me-1">*</span>{{ t('status_id') }}</th>
                <th class="border-r px-4 py-2"><span
                    class="text-red-500 me-1">*</span>{{ t('source_id') }}</th>
                <th class="border-r px-4 py-2">{{ t('assigned_id') }}</th>
                <th class="border-r px-4 py-2"><span
                    class="text-red-500 me-1">*</span>{{ t('firstname') }}</th>
                <th class="border-r px-4 py-2"><span
                    class="text-red-500 me-1">*</span>{{ t('lastname') }}</th>
                <th class="border-r px-4 py-2">{{ t('company') }}</th>
                <th class="border-r px-4 py-2"><span
                    class="text-red-500 me-1">*</span>{{ t('type') }}</th>
                <th class="border-r px-4 py-2">{{ t('email') }}</th>
                <th class="border-r px-4 py-2"><span
                    class="text-red-500 me-1">*</span>{{ t('phone') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                <td class="border-r border-t px-4 py-2">{{ t('2') }}</td>
                <td class="border-r border-t px-4 py-2">{{ t('4') }}</td>
                <td class="border-r border-t px-4 py-2">{{ t('1') }}</td>
                <td class="border-r border-t px-4 py-2">{{ t('sample_data') }}</td>
                <td class="border-r border-t px-4 py-2">{{ t('sample_data') }}</td>
                <td class="border-r border-t px-4 py-2">{{ t('sample_data') }}</td>
                <td class="border-r border-t px-4 py-2">{{ t('lead/customer') }}</td>
                <td class="border-r border-t px-4 py-2">{{ t('abc@gmail.com') }}</td>
                <td class="px-4 border-t py-2">{{ t('phone_sample') }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </x-slot:content>

      <x-slot:footer>
        <div class="flex justify-end">
          <x-button.secondary x-on:click="$dispatch('close-modal', 'example-modal')">
            {{ t('cancel') }}
          </x-button.secondary>
        </div>
      </x-slot:footer>
    </x-card>
  </x-modal>
</div>
