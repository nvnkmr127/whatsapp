<div class="px-8 md:px-0">
  <x-slot:title>
    {{ t('email_template_list_title') }}
  </x-slot:title>

  <div class="mt-4 max-w-2xl">
    <!-- Templates List -->
    <x-card class="rounded-lg">
      <x-slot:header>
        <x-settings-heading>
          {{ t('email_template_list_title') }}
        </x-settings-heading>
      </x-slot:header>
      <x-slot:content>
        <div>
          <ul role="list" class="space-y-4">
            @foreach ($templates as $item)
              <li
                class="flex items-center justify-between bg-white dark:bg-slate-800 px-4 py-6 rounded-lg ring-1 ring-slate-300 sm:rounded-lg dark:bg-transparent dark:ring-slate-600 hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                <!-- Template Info -->
                <div class="flex flex-col sm:flex-row sm:justify-between w-full">
                  <!-- Content Section -->
                  <div class="flex items-start sm:items-center space-x-4 w-full">
                    <div class="flex flex-col sm:flex-row sm:justify-between w-full">
                      <div class="max-w-md text-sm">
                        <h4 class="font-medium text-slate-900 dark:text-slate-200 truncate">
                          <a href="{{ route('admin.emails.save', $item->id) }}"
                            class="hover:text-blue-500 dark:hover:text-blue-400">
                            {{ $item->name }}
                          </a>
                        </h4>
                        <div class="mt-1 text-slate-500 dark:text-slate-400">
                          {!! $item->subject !!}
                        </div>
                      </div>
                    </div>
                  </div>
                  <!-- Action Section (Edit Button and Switch) -->
                  <div class="flex gap-3 items-center">
                    <!-- Toggle Button for Active Status -->
                    <button
                      wire:click="toggleActive({{ $item->id }}, {{ $item->is_active ? 'false' : 'true' }})"
                      type="button"
                      class="group relative inline-flex h-5 w-10 flex-shrink-0 cursor-pointer items-center justify-center rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-slate-800"
                      role="switch" aria-checked="{{ $item->is_active ? 'true' : 'false' }}">
                      <span class="sr-only">{{ t('Enable announcement') }}</span>
                      <span aria-hidden="true"
                        class="pointer-events-none absolute h-full w-full rounded-md"></span>
                      <span aria-hidden="true" @class([
                          'pointer-events-none absolute mx-auto h-4 w-9 rounded-full transition-colors duration-200 ease-in-out',
                          'bg-blue-600' => $item->is_active,
                          'bg-slate-200' => !$item->is_active,
                      ])></span>
                      <span aria-hidden="true" @class([
                          'pointer-events-none absolute left-0 inline-block h-5 w-5 transform rounded-full border border-slate-200 bg-white shadow ring-0 transition-transform duration-200 ease-in-out',
                          'translate-x-5' => $item->is_active,
                          'translate-x-0' => !$item->is_active,
                      ])></span>
                    </button>

                    <!-- Edit Button -->
                    <a href="{{ route('admin.emails.save', $item->id) }}"
                      class="text-slate-500 hover:text-blue-500 dark:text-slate-400 dark:hover:text-blue-400">
                    </a>
                  </div>
                </div>
              </li>
            @endforeach
          </ul>
        </div>
      </x-slot:content>
    </x-card>
  </div>
</div>
