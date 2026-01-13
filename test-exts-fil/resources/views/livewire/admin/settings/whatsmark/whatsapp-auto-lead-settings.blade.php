<div class="mx-auto px-4 md:px-0">
  <x-slot:title>
    {{ t('whatsapp_auto_lead') }}
  </x-slot:title>
  <!-- Page Heading -->
  <div class="pb-6">
    <x-settings-heading>{{ t('whatsmark_settings') }}</x-settings-heading>
  </div>

  <div class="flex flex-wrap lg:flex-nowrap gap-4">
    <!-- Sidebar Menu -->
    <div class="w-full lg:w-1/5">
      <x-admin-whatsmark-settings-navigation wire:ignore />
    </div>
    <!-- Main Content -->
    <div class="flex-1 space-y-5">
      <form wire:submit="save" class="space-y-6" x-data x-init="window.initTomSelect('.tom-select')">
        <x-card class="rounded-lg">
          <x-slot:header>
            <x-settings-heading>
              {{ t('whatsapp_auto_lead') }}
            </x-settings-heading>
            <x-settings-description>
              {{ t('automate_lead_generation') }}
            </x-settings-description>
          </x-slot:header>
          <x-slot:content>
            <div x-data="{ 'auto_lead_enabled': @entangle('auto_lead_enabled') }">
              <x-label for="" :value="t('acquire_new_lead_automatically')" class="mb-2" />
              <button type="button" x-on:click="auto_lead_enabled = !auto_lead_enabled"
                class="flex-shrink-0 group relative rounded-full inline-flex items-center justify-center h-5 w-10 cursor-pointer focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-slate-800"
                role="switch" :aria-checked="auto_lead_enabled.toString()"
                :class="{
                    'bg-indigo-600': auto_lead_enabled,
                    'bg-gray-300': !auto_lead_enabled
                }">
                <span class="sr-only">{{ t('toggle_switch') }}</span>
                <span aria-hidden="true"
                  class="pointer-events-none absolute w-full h-full rounded-full transition-colors ease-in-out duration-200"></span>
                   <span aria-hidden="true"
                    class="pointer-events-none absolute left-0 inline-block h-5 w-5 border border-slate-200 rounded-full bg-white shadow transform ring-0 transition-transform ease-in-out duration-200"
                    :class="auto_lead_enabled ? 'translate-x-5' : 'translate-x-0'">
                    <span
                        class="absolute inset-0 flex h-full w-full items-center justify-center transition-opacity"
                        :class="auto_lead_enabled ? 'opacity-0 ease-out duration-100' :
                            'opacity-100 ease-in duration-200'">
                        <x-heroicon-m-x-mark class="h-3 w-3 text-gray-400" />
                    </span>
                    <span
                        class="absolute inset-0 flex h-full w-full items-center justify-center transition-opacity"
                        :class="auto_lead_enabled ? 'opacity-100 ease-in duration-200' :
                            'opacity-0 ease-out duration-100'">
                        <x-heroicon-m-check class="h-3 w-3 text-indigo-600" />
                    </span>
                  </span>
              </button>
            </div>

            <div x-data="{ 'auto_lead_enabled': @entangle('auto_lead_enabled') }" class="grid grid-cols-1 gap-4 sm:grid-cols-3 mt-2">
              <div>
                <div wire:ignore>
                  <div class="flex items-center">
                    <span x-show="auto_lead_enabled" class="text-red-500 mr-1">*</span>
                    <x-label for="lead_status" :value="t('lead_status')" />
                  </div>
                  <x-select wire:model.defer="lead_status" id="lead_status"
                    class="mt-1 block w-full tom-select">

                    @foreach ($statuses as $index => $status)
                      <option value="{{ $status->id }}"
                        {{ $status->id == $lead_status ? 'selected' : '' }}> {{ $status->name }}
                      </option>
                    @endforeach
                  </x-select>
                </div>
                <x-input-error for="lead_status" class="mt-2" />
              </div>
              <div>
                <div wire:ignore>
                  <div class="flex items-center">
                    <span x-show="auto_lead_enabled" class="text-red-500 mr-1">*</span>
                    <x-label for="lead_source" :value="t('lead_source')" />
                  </div>
                  <x-select wire:model.defer="lead_source" id="lead_source"
                    class="mt-1 block w-full tom-select">

                    @foreach ($sources as $source)
                      <option value="{{ $source->id }}"
                        {{ $source->id == $lead_source ? 'selected' : '' }}> {{ $source->name }}
                      </option>
                    @endforeach
                  </x-select>
                </div>
                <x-input-error for="lead_source" class="mt-2" />
              </div>
              <div>
                <div wire:ignore class="mt-1">
                  <div class="flex items-center">
                    <x-label for="lead_assigned_to" :value="t('lead_assigned')" />
                  </div>
                  <x-select wire:model.defer="lead_assigned_to" id="lead_assigned_to"
                    class="mt-1 block w-full tom-select">

                    @foreach ($users as $user)
                      <option value="{{ $user->id }}"
                        {{ $user->id == $lead_assigned_to ? 'selected' : '' }}>
                        {{ $user->firstname . ' ' . $user->lastname }}
                      </option>
                    @endforeach
                  </x-select>
                </div>
                <x-input-error for="lead_assigned_to" class="mt-2" />
              </div>

            </div>

          </x-slot:content>
          <!-- Submit Button -->
          @if (checkPermission('whatsmark_settings.edit'))
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
