<div>
  <x-slot:title>
    {{ $contact->exists ? t('edit_contact_title') : t('add_contact_title') }}
  </x-slot:title>
  <div>
    <form wire:submit.prevent="save" class="px-4">
      <div class="pb-3 font-display">
        <x-page-heading>
          {{ $contact->exists ? t('edit_contact_title') : t('add_contact_title') }}
        </x-page-heading>
      </div>
      <x-card class="relative rounded-lg lg:w-3/4" x-cloak>
        <x-slot:content>
          <div x-data="{ tab: @entangle('tab') }">
            <div class="grid grid-cols-3 lg:grid-cols-3 gap-2 border-b dark:border-slate-500 w-full">
              <button type="button" x-on:click="tab = 'contact-details'"
                :class="{
                    'border-b-2 border-indigo-500 text-indigo-500 dark:text-indigo-400': tab === 'contact-details',
                    'border-b-2 border-red-500 text-red-600 dark:text-red-500': tab !== 'contact-details' &&
                        {{ $errors->hasAny([
                            'contact.firstname',
                            'contact.lastname',
                            'contact.company',
                            'contact.email',
                            'contact.phone',
                            'contact.type',
                            'contact.status_id',
                            'contact.source_id',
                            'contact.assigned_id',
                            'contact.website',
                        ])
                            ? 'true'
                            : 'false' }},
                    'dark:text-slate-200': tab !== 'contact-details'
                }"
                class="lg:text-base px-4 py-2 sm:px-6 sm:py-4 text-sm w-full">
                {{ t('contact_details') }}
              </button>

              <button type="button" x-on:click="tab = 'other-details'"
                :class="{
                    'border-b-2 border-indigo-500 text-indigo-500 dark:text-indigo-400': tab === 'other-details',
                    'border-b-2 border-red-500 text-red-600 dark:text-red-500': tab !== 'other-details' &&
                        {{ $errors->hasAny([
                            'contact.city',
                            'contact.state',
                            'contact.country_id',
                            'contact.address',
                            'contact.zip',
                            'contact.description',
                        ])
                            ? 'true'
                            : 'false' }},
                    'dark:text-slate-200': tab !== 'other-details'
                }"
                class="lg:text-base px-4 py-2 sm:px-6 sm:py-4 text-sm w-full">
                {{ t('other_details') }}
              </button>

              <button type="button" x-on:click="tab = 'notes'"
                :class="{
                    'border-b-2 border-indigo-500 text-indigo-500 dark:text-indigo-400': tab === 'notes',
                    'border-b-2 border-red-500 text-red-600 dark:text-red-500': tab !== 'notes' &&
                        {{ $errors->hasAny(['notes']) ? 'true' : 'false' }},
                    'dark:text-slate-200': tab !== 'notes'
                }"
                class="lg:text-base px-4 py-2 sm:px-6 sm:py-4 text-sm w-full">
                {{ t('notes_title') }}
              </button>
            </div>

            <div x-show="tab === 'contact-details'" class="mt-6">
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 mb-6">
                {{-- Status, Source, and Assigned --}}
                <div class="col-span-1">
                  <div wire:ignore>
                    <div class="flex items-center gap-1">
                      <span class="text-red-500">*</span>
                      <x-label for="contact.status_id" :value="t('status')" />
                    </div>
                    <x-select wire:model.defer="contact.status_id" id="contact.status_id"
                      class="block w-full mt-1 tom-select">
                      <option value="">{{ t('select_status') }}</option>
                      @foreach ($statuses as $status)
                        <option value="{{ $status->id }}">{{ $status->name }}</option>
                      @endforeach
                    </x-select>
                  </div>
                  <x-input-error for="contact.status_id" class="mt-1" />
                </div>

                <div class="col-span-1">
                  <div wire:ignore>
                    <div class="flex items-center gap-1">
                      <span class="text-red-500">*</span>
                      <x-label for="contact.source_id" :value="t('source')" />
                    </div>
                    <x-select wire:model.defer="contact.source_id" id="contact.source_id"
                      class="block w-full mt-1 tom-select">
                      <option value="">{{ t('select_source') }}</option>
                      @foreach ($sources as $source)
                        <option value="{{ $source->id }}">{{ $source->name }}</option>
                      @endforeach
                    </x-select>
                  </div>
                  <x-input-error for="contact.source_id" class="mt-1" />
                </div>

                <div class="col-span-1">
                  <div wire:ignore>
                    <x-label for="contact.assigned_id" :value="t('assigned')" class="mb-2" />
                    <x-select wire:model.defer="contact.assigned_id" id="contact.assigned_id"
                      class="block w-full tom-select">
                      <option value="">{{ t('select_assign') }}</option>
                      @foreach ($users as $user)
                        <option value="{{ $user->id }}">
                          {{ $user->firstname . ' ' . $user->lastname }}
                        </option>
                      @endforeach
                    </x-select>
                  </div>
                  <x-input-error for="contact.assigned_id" class="mt-1" />
                </div>
              </div>
              <div class="grid grid-cols-1 sm:gap-4 sm:grid-cols-2">
                {{-- First Name and Last Name --}}
                <div class="col-span-1 mb-6">
                  <div class="flex items-center gap-1">
                    <span class="text-red-500">*</span>
                    <x-label for="contact.firstname" :value="t('firstname')" />
                  </div>
                  <x-input wire:model.defer="contact.firstname" type="text"
                    id="contact.firstname" class="block w-full mt-1" />
                  <x-input-error for="contact.firstname" class="mt-1" />
                </div>

                <div class="col-span-1 mb-6">
                  <div class="flex items-center gap-1">
                    <span class="text-red-500">*</span>
                    <x-label for="contact.lastname" :value="t('lastname')" />
                  </div>
                  <x-input wire:model.defer="contact.lastname" type="text" id="contact.lastname"
                    class="block w-full mt-1" />
                  <x-input-error for="contact.lastname" class="mt-1" />
                </div>
              </div>
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 mb-6">
                {{-- Company and Type --}}
                <div class="col-span-1">
                  <x-label for="contact.company" :value="t('company')" />
                  <x-input wire:model.defer="contact.company" type="text" id="contact.company"
                    class="mt-1 block w-full" />
                  <x-input-error for="contact.company" class="mt-2" />
                </div>

                <div class="col-span-1">
                  <div wire:ignore>
                    <div class="flex items-center gap-1">
                      <span class="text-red-500">*</span>
                      <x-label for="contact.type" :value="t('type')" />
                    </div>
                    <div class="relative max-w-full">
                      <x-select class="tom-select" wire:model.defer="contact.type"
                        id="contact.type">
                        <option value="">{{ t('select_type') }}</option>
                        <option value="lead">{{ t('type_lead') }}</option>
                        <option value="customer">{{ t('type_customer') }}</option>
                      </x-select>
                    </div>
                  </div>
                  <x-input-error for="contact.type" class="mt-1" />
                </div>
              </div>
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 mb-6">
                {{-- Email and Phone --}}
                <div class="col-span-1">
                  <x-label for="contact.email" :value="t('email')" />
                  <x-input wire:model.defer="contact.email" id="contact.email"
                    class="block w-full" />
                  <x-input-error for="contact.email" class="mt-1" />
                </div>
                <div class="col-span-1">
                  <div>
                    <div class="flex items-center gap-1">
                      <span class="text-red-500">*</span>
                      <x-label for="phoneNumberInput" :value="t('phone')" />
                    </div>
                    <div wire:ignore x-data="{ phone: @entangle('contact.phone'), errorMessage: '' }">
                      <x-input class="phone-input mt-[2px]" x-ref="phone" id="phone"
                        type="tel" wire:model.defer="contact.phone" maxlength="18"
                        x-model="phone"
                        x-on:change="
                                                    if (phone.length == 18) {
                                                        errorMessage = 'You can only enter up to 18 digits';
                                                        phone = phone.slice(0, 18);
                                                    } else {
                                                        errorMessage = '';
                                                    }
                                                " />
                      <p x-show="errorMessage" class="text-sm text-red-600 dark:text-red-400 mt-1"
                        x-text="errorMessage"></p>
                    </div>
                    <x-input-error class="mt-1" for="contact.phone" />
                  </div>
                </div>
              </div>

              {{-- Default Language & Website --}}
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 mb-6">
                <div>
                  <x-label for="contact.website" :value="t('website')" />
                  <x-input wire:model.defer="contact.website" type="text" id="contact.website"
                    class="mt-1 block w-full" />
                  <x-input-error for="contact.website" class="mt-2" />
                </div>
                <div wire:ignore>
                  <x-label for="contact.default_language" :value="t('default_language')" />
                  <x-select wire:model.defer="contact.default_language"
                    id="contact.default_language" class="block w-full mt-1 tom-select">
                    <option value=""> {{ t('select_language') }} </option>
                    @foreach (getLanguage(null, ['code', 'name']) as $language)
                      <option value="{{ $language->code }}"> {{ $language->name }}</option>
                    @endforeach
                  </x-select>
                </div>
                <x-input-error for="contact.default_language" class="mt-1" />
              </div>
            </div>

            <div x-show="tab === 'other-details'" class="mt-6">
              {{-- City & State --}}
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 mb-6">
                <div>
                  <x-label for="contact.city" :value="t('city')" />
                  <x-input wire:model.defer="contact.city" type="text" id="contact.city"
                    class="block w-full mt-1" />
                  <x-input-error for="contact.city" class="mt-1" />
                </div>

                <div>
                  <x-label for="contact.state" :value="t('state')" />
                  <x-input wire:model.defer="contact.state" type="text" id="contact.state"
                    class="block w-full mt-1" />
                  <x-input-error for="contact.state" class="mt-2" />
                </div>
              </div>
              {{-- Zip Code & Country --}}
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 mb-6">
                <div>
                  <x-label for="contact.country_id" :value="t('country')" />
                  <div wire:ignore>
                    <x-select wire:model.defer="contact.country_id" id="contact.country_id"
                      class="block w-full mt-1 tom-select">
                      <option value="">{{ t('country_select') }}</option>
                      @foreach ($countries as $country)
                        <option value="{{ $country['id'] }}">
                          {{ $country['short_name'] }}
                        </option>
                      @endforeach
                    </x-select>
                  </div>
                  <x-input-error for="contact.country_id" class="mt-1" />
                </div>
                <div>
                  <x-label for="contact.zip" :value="t('zip_code')" />
                  <x-input wire:model.defer="contact.zip" type="text" id="contact.zip"
                    class="block w-full mt-1" />
                  <x-input-error for="contact.zip" class="mt-1" />
                </div>
              </div>

              {{-- Description & Address --}}
              <div class="mb-6">
                <x-label for="contact.address" :value="t('address')" />
                <x-textarea wire:model.defer="contact.address" id="contact.address"
                  rows="3" class="block w-full mt-1" />
                <x-input-error for="contact.address" class="mt-1" />
              </div>
              <div class="mb-6">
                <x-label for="contact.description" :value="t('description')" />
                <x-textarea wire:model.defer="contact.description" id="contact.description"
                  rows="3" class="block w-full mt-1" />
                <x-input-error for="contact.description" class="mt-1" />
              </div>
            </div>

            <div x-show="tab === 'notes'" class="mt-6">
              @if (!$contact->exists)
                <div
                  class="text-slate-700 dark:text-slate-300 text-sm p-4 border border-gray-300 dark:border-gray-600 rounded-md">
                  {{ t('note_will_be_available_in_contact') }}
                </div>
              @else
                <div class="col-span-1">
                  <div>
                    <x-label for="notes_description" :value="t('add_notes_title')" />
                    <div class="flex space-x-3 items-start">
                      <x-textarea wire:model.defer="notes_description" id="notes_description"
                        wire:blur="validateNotesDescription" class="block w-full"
                        rows="3" />
                    </div>
                    <x-input-error for="notes_description" class="mt-1" />
                    <div class="flex justify-end">
                      <x-button.primary class="mt-3 flex-shrink-0" wire:click.prevent="addNote">
                        {{ t('add') }}
                      </x-button.primary>
                    </div>
                    <div
                      class="mt-4 relative px-1 h-80 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-200 dark:scrollbar-thumb-gray-600 dark:scrollbar-track-gray-800">
                      <ol class="relative border-s border-gray-300 dark:border-gray-700">
                        @foreach ($notes as $note)
                          <li class="mb-6 ms-4 relative">
                            <div
                              class="absolute w-2 h-2 bg-indigo-600 dark:bg-indigo-400 rounded-full -left-5 top-4">
                            </div>

                            <div
                              class="flex-1 p-2 border-b border-gray-300 dark:border-gray-600 text-sm space-y-1 ml-4">
                              <span class="text-xs text-gray-500 dark:text-gray-400 block relative"
                                data-tippy-content="{{ format_date_time($note['created_at']) }}"
                                style="cursor: pointer; display: inline-block; text-decoration: underline dotted;">
                                {{ \Carbon\Carbon::parse($note['created_at'])->diffForHumans(['options' => \Carbon\Carbon::JUST_NOW]) }}
                              </span>
                              <div class="flex justify-between items-start flex-nowrap">
                                <span class="text-gray-800 dark:text-gray-200 flex-1 break-words">
                                  {{ $note['notes_description'] }}
                                </span>
                                <x-feathericon-trash-2
                                  class="text-red-400 dark:text-red-300 cursor-pointer h-7 w-7 min-w-7 min-h-7 p-1 shrink-0 ml-2"
                                  wire:click="confirmDelete({{ $note['id'] }})">
                                </x-feathericon-trash-2>
                              </div>
                            </div>
                          </li>
                        @endforeach
                      </ol>
                    </div>
                  </div>
                </div>
              @endif
            </div>
          </div>
        </x-slot:content>

        <!-- Submit Button -->
        <x-slot:footer class="rounded-b-lg">
          <div class="flex justify-end space-x-3">
            <x-button.secondary wire:click="cancel">
              {{ t('cancel') }}
            </x-button.secondary>
            <x-button.loading-button type="submit" target="save" wire:loading.attr="disabled">
              <span wire:loading.remove wire:target="save">
                {{ $contact->exists ? t('update_button') : t('add_button') }}
              </span>
            </x-button.loading-button>
          </div>
        </x-slot:footer>
      </x-card>
    </form>
  </div>

  <!-- Delete Confirmation Modal -->
  <x-modal.confirm-box :maxWidth="'lg'" :id="'delete-note-modal'" title="{{ t('delete_notes_title') }}"
    wire:model.defer="confirmingDeletion" description="{{ t('delete_message') }} ">
    <div
      class="border-neutral-200 border-neutral-500/30 flex justify-end items-center sm:block space-x-3 bg-gray-100 dark:bg-gray-700 ">
      <x-button.cancel-button wire:click="$set('confirmingDeletion', false)">
        {{ t('cancel') }}
      </x-button.cancel-button>
      <x-button.delete-button wire:click="removeNote" class="mt-3 sm:mt-0">
        {{ t('delete') }}
      </x-button.delete-button>
    </div>
  </x-modal.confirm-box>
</div>
