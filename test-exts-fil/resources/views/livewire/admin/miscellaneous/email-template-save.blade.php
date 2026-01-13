<div x-data="emailTemplateForm()" x-cloak
  class="w-full md:w-[90%] lg:w-[80%] xl:w-[60%] m-auto px-4 md:px-0">
  <x-slot:title>
    {{ t('email_template_editor') }}
  </x-slot:title>
  <x-card>
    <x-slot:header>
      <x-settings-heading>
        {{ t('email_template_editor') }}
      </x-settings-heading>
    </x-slot:header>

    <x-slot:content>
      <form wire:submit="save" class="space-y-4 md:space-y-6">
        <!-- Template Basic Info -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
          <div>
            <x-input-label for="name" value="{{ t('template_name') }}" class="mb-1" />
            <x-input type="text" id="name" wire:model="name" disabled
              class="cursor-not-allowed" />
            <x-input-error for="name" class="mt-1" />
          </div>

          <div>
            <x-input-label for="subject" value="{{ t('subject') }}" class="mb-1" />
            <x-input type="text" id="subject" wire:model="subject" />
            <x-input-error for="subject" class="mt-1" />
          </div>
        </div>

        <!-- Merge Fields Selector -->
        <x-card>
          <x-slot:content>
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-4">
              <h4
                class="text-base md:text-lg font-semibold text-slate-600 dark:text-slate-300 mb-2 sm:mb-0">
                {{ t('available_merge_fields') }}
              </h4>
            </div>

            @if (count($groupedFields) > 0)
              <div x-data="{ activeTab: '{{ array_key_first($groupedFields) }}' }" class="space-y-4">
                <!-- Tab Navigation -->
                <div
                  class="flex flex-wrap sm:flex-nowrap space-x-0 sm:space-x-2 space-y-2 sm:space-y-0 bg-slate-100 dark:bg-slate-700 rounded-md p-1">
                  @foreach ($groupedFields as $groupKey => $fields)
                    @php
                      $groupLabel = Str::title(str_replace('-', ' ', $groupKey));
                    @endphp
                    <button type="button" x-on:click="activeTab = '{{ $groupKey }}'"
                      :class="activeTab === '{{ $groupKey }}'
                          ?
                          'bg-indigo-500 text-white' :
                          'text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600'"
                      class="w-full sm:flex-1 py-2 px-4 rounded-md text-sm font-medium transition-colors">
                      {{ $groupLabel }}
                    </button>
                  @endforeach
                </div>

                <!-- Tab Content -->
                <div class="rounded-lg">
                  @foreach ($groupedFields as $groupKey => $fields)
                    <div x-show="activeTab === '{{ $groupKey }}'" x-cloak
                      class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 break-words">
                      @foreach ($fields as $field)
                        <button type="button" wire:click="insertMergeField('{{ $field['key'] }}')"
                          class="text-left px-3 md:px-4 py-2 md:py-3 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-md hover:border-indigo-500 dark:hover:border-indigo-400 transition-all duration-200 group">
                          <span
                            class="block font-medium text-xs md:text-sm text-slate-800 dark:text-slate-200 group-hover:text-indigo-600 dark:group-hover:text-indigo-400">
                            {{ $field['name'] }}
                          </span>
                          <span class="text-xs text-slate-500 dark:text-slate-400">
                            {{ $field['key'] }}
                          </span>
                        </button>
                      @endforeach
                    </div>
                  @endforeach
                </div>
              </div>
            @else
              <div class="p-4 text-center text-slate-500 dark:text-slate-400">
                {{ t('no_merge_fields_available') }}
              </div>
            @endif
          </x-slot:content>
        </x-card>

        <!-- Message Editor -->
        <div>
          <x-input-label for="message" value="{{ t('message') }}" class="mb-1" />
          <textarea id="message" wire:model="message" x-ref="messageEditor" @input="resizeTextarea"
            class="block w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-300 focus:border-indigo-500 focus:ring-indigo-500 min-h-[150px] md:min-h-[200px] resize-y"></textarea>
          <x-input-error for="message" class="mt-1" />
        </div>

        <!-- Submit Button -->
        @if (checkPermission('email_template.edit'))
          <div class="flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-4">
            <x-button.secondary wire:click="cancel" type="button" class="w-full sm:w-auto">
              {{ t('cancel') }}
            </x-button.secondary>
            <x-button.loading-button type="submit" target="save" class="w-full sm:w-auto">
              {{ t('save_template') }}
            </x-button.loading-button>
          </div>
        @endif
      </form>
    </x-slot:content>
  </x-card>

  <script>
    function emailTemplateForm() {
      return {
        resizeTextarea() {
          const textarea = this.$refs.messageEditor;
          textarea.style.height = 'auto';
          textarea.style.height = textarea.scrollHeight + 'px';
        }
      }
    }
  </script>
</div>
