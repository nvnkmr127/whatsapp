<div class="px-4 md:px-0 max-w-7xl mx-auto py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">
            {{ isset($campaign->id) ? 'Edit Campaign' : 'Create Campaign' }}
        </h1>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 items-start" x-data="{
            scheduledDate: @entangle('send_now'),
            relationTypeDynamicData: @entangle('relation_type_dynamic'),
            campaignsSelected: false,
            campaignsTypeSelected: false,
            fileError: null,
            isDisabled: false,
            campaignHeader: '',
            isSaving: false,
            campaignBody: '',
            campaignFooter: '',
            buttons: [],
            inputType: 'item',
            inputAccept: '',
            headerInputs: @entangle('headerInputs'),
            bodyInputs: @entangle('bodyInputs'),
            footerInputs: @entangle('footerInputs'),
            mergeFields: @entangle('mergeFields'), // JSON string from backend
            editTemplateId: @entangle('template_id'),
            headerInputErrors: [],
            bodyInputErrors: [],
            footerInputErrors: [],
            headerParamsCount: 0,
            bodyParamsCount: 0,
            footerParamsCount: 0,
            selectedCount: 0,
            relType: '',
            previewUrl: '',
            previewFileName: '',
            file: @entangle('file'),
            
            initTribute() {
                 this.$nextTick(() => {
                    if (typeof window.Tribute === 'undefined') return;
                    
                    let tribute = new window.Tribute({
                        trigger: '@',
                        values: JSON.parse(this.mergeFields || '[]'),
                        selectTemplate: function (item) {
                            return '{{' + item.original.value + '}}';
                        },
                        menuItemTemplate: function (item) {
                            return item.string;
                        }
                    });

                    document.querySelectorAll('.mentionable').forEach((el) => {
                        if (!el.hasAttribute('data-tribute')) {
                            tribute.attach(el);
                            el.setAttribute('data-tribute', 'true');
                        }
                    });
                });
            },

            handleCampaignChange(event) {
                const selectedOption = event.target.selectedOptions[0];
                this.campaignsSelected = event.target.value !== '';
                
                if (selectedOption) {
                    this.campaignHeader = selectedOption.dataset.header || '';
                    this.campaignBody = selectedOption.dataset.body || '';
                    this.campaignFooter = selectedOption.dataset.footer || '';
                    this.buttons = JSON.parse(selectedOption.dataset.buttons || '[]');
                    this.inputType = selectedOption.dataset.headerFormat || 'TEXT';
                    this.headerParamsCount = parseInt(selectedOption.dataset.headerParamsCount || 0);
                    this.bodyParamsCount = parseInt(selectedOption.dataset.bodyParamsCount || 0);
                    this.footerParamsCount = parseInt(selectedOption.dataset.footerParamsCount || 0);
                    
                    // Set allowed file types
                    if (this.inputType === 'IMAGE') this.inputAccept = 'image/*';
                    else if (this.inputType === 'VIDEO') this.inputAccept = 'video/*';
                    else if (this.inputType === 'DOCUMENT') this.inputAccept = '.pdf,.doc,.docx,.txt';
                    else this.inputAccept = '';
                } else {
                     this.campaignHeader = '';
                     this.campaignBody = '';
                     this.campaignFooter = '';
                     this.buttons = [];
                }
                
                this.previewUrl = ''; // Reset preview on template change
                this.$nextTick(() => this.initTribute());
            },

            replaceVariables(template, inputs) {
                if (!template || !inputs) return '';
                // Simple replacement of {{1}}, {{2}} with inputs array
                return template.replace(/\{\{(\d+)\}\}/g, (match, p1) => {
                    const index = parseInt(p1, 10) - 1;
                    return `<span class='font-bold text-indigo-600'>${inputs[index] || match}</span>`;
                });
            },

            handleFilePreview(event) {
                const file = event.target.files[0];
                if (!file) return;
                this.previewUrl = URL.createObjectURL(file);
                this.previewFileName = file.name;
            },
            
            toggleSchedule() {
                this.scheduledDate = !this.scheduledDate; // Toggle send_now
                if (!this.scheduledDate) {
                     this.$nextTick(() => window.flatePickrWithTime());
                }
            }
        }" x-init="
            $watch('mergeFields', () => initTribute());
            // Init TomSelect for template if needed, though standard select is fine
            // window.initTomSelect('#template-select'); 
        ">
        <!-- Form Section -->
        <div class="lg:col-span-2 space-y-6">
            <x-card>
                <x-slot:header>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Campaign Details</h3>
                </x-slot:header>
                <div class="space-y-4">
                    <!-- Campaign Name -->
                    <div>
                        <x-label for="campaign_name" value="Campaign Name *" />
                        <x-input id="campaign_name" type="text" class="mt-1 block w-full" wire:model="campaign_name"
                            placeholder="e.g. Summer Sale" />
                        <x-input-error for="campaign_name" class="mt-2" />
                    </div>

                    <!-- Template Selection -->
                    <div>
                        <x-label for="template_id" value="Select Template *" />
                        <select id="template_id" wire:model.live="template_id"
                            x-on:change="handleCampaignChange($event)"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                            <option value="">-- Choose a Template --</option>
                            @foreach($this->templates as $template)
                                <option value="{{ $template->id }}" data-header="{{ $template->header_data_text }}"
                                    data-body="{{ $template->body_data }}" data-footer="{{ $template->footer_data }}"
                                    data-buttons="{{ $template->buttons_data }}"
                                    data-header-format="{{ $template->header_data_format }}"
                                    data-header-params-count="{{ $template->header_params_count }}"
                                    data-body-params-count="{{ $template->body_params_count }}"
                                    data-footer-params-count="{{ $template->footer_params_count }}">
                                    {{ $template->template_name }} ({{ $template->language }})
                                </option>
                            @endforeach
                        </select>
                        <x-input-error for="template_id" class="mt-2" />
                    </div>
                </div>
            </x-card>

            <!-- Variables Section -->
            <x-card x-show="campaignsSelected" x-cloak>
                <x-slot:header>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Variables & Media</h3>
                </x-slot:header>

                <div class="space-y-6">
                    <!-- Header Params -->
                    <template x-if="inputType !== 'TEXT' && inputType !== ''">
                        <!-- Media Upload -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Header Media (<span x-text="inputType"></span>)
                            </label>
                            <div class="border-dashed border-2 border-gray-300 rounded-md p-4 text-center hover:border-indigo-500 transition cursor-pointer focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                @click="$refs.fileInput.click()" @keydown.enter="$refs.fileInput.click()"
                                @keydown.space.prevent="$refs.fileInput.click()" tabindex="0" role="button"
                                aria-label="Upload file">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="1.5" stroke="currentColor" class="w-8 h-8 mx-auto text-gray-400">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 16.5V9.75m0 0l3 3.75m-3-3.75l-3 3.75M12 9.75V4.5m0 0a3.012 3.012 0 013 0v5.25m-3 0h3m-3 0h-3" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 16.5V9.75m0 0l3 3.75m-3-3.75l-3 3.75M12 9.75V4.5m0 0a3.012 3.012 0 013 0v5.25m-3 0h3m-3 0h-3" />
                                    <!-- Fallback generic upload cloud icon -->
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 16.5V9.75m0 0l3 3.75m-3-3.75l-3 3.75M12 9.75V4.5m0 0a3.012 3.012 0 013 0v5.25" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                </svg>
                                <span class="text-sm text-gray-500"
                                    x-text="previewFileName || 'Click to upload specific media'"></span>
                                <input type="file" x-ref="fileInput" wire:model="file" class="hidden"
                                    @change="handleFilePreview($event)" :accept="inputAccept" />
                            </div>
                            <x-input-error for="file" class="mt-2" />
                        </div>
                    </template>

                    <template x-if="inputType === 'TEXT' && headerParamsCount > 0">
                        <div class="space-y-2">
                            <h4 class="text-sm font-semibold text-gray-500">Header Variables</h4>
                            <template x-for="i in headerParamsCount" :key="'h'+i">
                                <div>
                                    <label class="text-xs text-gray-500">Variable {{ '{{' + i + '}}' }}</label>
                                    <input type="text" x-model="headerInputs[i-1]" @focus="initTribute"
                                        class="mentionable mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm" />
                                </div>
                            </template>
                        </div>
                    </template>

                    <!-- Body Params -->
                    <template x-if="bodyParamsCount > 0">
                        <div class="space-y-2">
                            <h4 class="text-sm font-semibold text-gray-500">Body Variables</h4>
                            <template x-for="i in bodyParamsCount" :key="'b'+i">
                                <div>
                                    <label class="text-xs text-gray-500">Variable {{ '{{' + i + '}}' }}</label>
                                    <input type="text" x-model="bodyInputs[i-1]" @focus="initTribute"
                                        class="mentionable mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm" />
                                </div>
                            </template>
                        </div>
                    </template>

                    <!-- Footer Params -->
                    <template x-if="footerParamsCount > 0">
                        <div class="space-y-2">
                            <h4 class="text-sm font-semibold text-gray-500">Footer Variables</h4>
                            <template x-for="i in footerParamsCount" :key="'f'+i">
                                <div>
                                    <label class="text-xs text-gray-500">Variable {{ '{{' + i + '}}' }}</label>
                                    <input type="text" x-model="footerInputs[i-1]" @focus="initTribute"
                                        class="mentionable mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm" />
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </x-card>

            <!-- Audience Section -->
            <x-card>
                <x-slot:header>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Target Audience</h3>
                </x-slot:header>

                <div class="space-y-4">
                    <!-- "All Contacts" Toggle -->
                    <div class="flex items-center">
                        <input id="is_checked" type="checkbox" wire:model.live="isChecked"
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <label for="is_checked" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">
                            Select All Contacts in Team
                        </label>
                    </div>

                    <div class="relative flex items-center py-2">
                        <div class="flex-grow border-t border-gray-300 dark:border-gray-600"></div>
                        <span class="flex-shrink-0 mx-4 text-gray-400">OR</span>
                        <div class="flex-grow border-t border-gray-300 dark:border-gray-600"></div>
                    </div>

                    <!-- Specific Contact Selection -->
                    <div wire:ignore>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Select Specific
                            Contacts</label>
                        <select id="contact-select" multiple placeholder="Search contacts..." x-init="
                                    window.initTomSelect('#contact-select', {
                                        onChange: function(value) {
                                            @this.set('relation_type_dynamic', value);
                                        }
                                    });
                                    $watch('isDisabled', (disabled) => {
                                         if(disabled) { 
                                             document.querySelector('#contact-select').tomselect.disable();
                                         } else {
                                             document.querySelector('#contact-select').tomselect.enable();
                                         }
                                    });
                                " x-bind:disabled="isChecked">
                            @foreach($contacts as $contact)
                                <option value="{{ $contact['id'] }}">{{ $contact['firstname'] }} {{ $contact['lastname'] }}
                                    ({{ $contact['phone'] }})</option>
                            @endforeach
                        </select>
                        <p x-show="isChecked" class="text-xs text-gray-500 mt-1">Specific selection disabled when
                            "Select All" is active.</p>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded-md text-center">
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            Total Recipients: <span class="font-bold text-indigo-600 text-lg">{{ $contactCount }}</span>
                        </p>
                    </div>
                    <x-input-error for="relation_type_dynamic" />
                </div>
            </x-card>

            <!-- Schedule Section -->
            <x-card>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Scheduling</h3>

                    <div class="flex items-center">
                        <label class="mr-2 text-sm text-gray-700 dark:text-gray-300">Send Now</label>
                        <button type="button" @click="toggleSchedule()"
                            :class="scheduledDate ? 'bg-indigo-600' : 'bg-gray-200'"
                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2">
                            <span :class="scheduledDate ? 'translate-x-5' : 'translate-x-0'"
                                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                        </button>
                    </div>
                </div>

                <div x-show="!scheduledDate" x-collapse>
                    <x-label for="scheduled_send_time" value="Schedule Date & Time" />
                    <div class="relative mt-1">
                        <input type="text" id="scheduled_send_time" wire:model="scheduled_send_time"
                            class="block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Select date and time" x-init="window.flatePickrWithTime()" />
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                            stroke="currentColor"
                            class="w-5 h-5 absolute right-3 top-2.5 text-gray-400 pointer-events-none">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                        </svg>
                    </div>
                    <x-input-error for="scheduled_send_time" class="mt-2" />
                </div>
            </x-card>

            <!-- Submit Actions -->
            <div class="flex justify-end space-x-3">
                <x-button.ghost href="{{ route('campaigns.index') }}">Cancel</x-button.ghost>
                <x-button class="bg-indigo-600 hover:bg-indigo-700 text-white" wire:click="save"
                    wire:loading.attr="disabled">
                    <span wire:loading.remove>
                        {{ isset($campaign->id) ? 'Update Campaign' : 'Launch Campaign' }}
                    </span>
                    <span wire:loading class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        Processing...
                    </span>
                </x-button>
            </div>
        </div>

        <!-- Preview Sidebar -->
        <div class="hidden lg:block space-y-6">
            <div class="sticky top-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Preview</h3>

                <!-- WhatsApp Preview Container -->
                <div class="bg-[#E5DDD5] dark:bg-[#0b141a] rounded-lg shadow-lg overflow-hidden border border-gray-200 dark:border-gray-800"
                    style="background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png'); opacity: 0.9;">

                    <div class="p-4">
                        <div class="bg-white dark:bg-[#202c33] rounded-lg p-2 shadow-sm max-w-[90%] relative">
                            <!-- Media Preview -->
                            <template x-if="previewUrl">
                                <div class="mb-2 rounded-lg overflow-hidden">
                                    <template x-if="inputType === 'IMAGE'">
                                        <img :src="previewUrl" class="w-full h-auto object-cover" />
                                    </template>
                                    <template x-if="inputType === 'VIDEO'">
                                        <video :src="previewUrl" controls class="w-full h-auto"></video>
                                    </template>
                                    <template x-if="inputType === 'DOCUMENT'">
                                        <div
                                            class="bg-gray-100 dark:bg-gray-700 p-3 flex items-center space-x-3 rounded">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                                fill="currentColor" class="w-8 h-8 text-gray-500">
                                                <path fill-rule="evenodd"
                                                    d="M5.625 1.5H9a3.75 3.75 0 013.75 3.75v1.875c0 1.036.84 1.875 1.875 1.875H16.5a3.75 3.75 0 013.75 3.75v7.875c0 1.035-.84 1.875-1.875 1.875H5.625a1.875 1.875 0 01-1.875-1.875V3.375c0-1.036.84-1.875 1.875-1.875zM12.75 12a.75.75 0 00-1.5 0V15a.75.75 0 003 0v-3a.75.75 0 00-1.5 0z"
                                                    clip-rule="evenodd" />
                                                <path
                                                    d="M14.25 5.25a5.23 5.23 0 00-1.279-3.434 9.768 9.768 0 016.963 6.963A5.23 5.23 0 0016.5 7.5h-1.875a.375.375 0 01-.375-.375V5.25z" />
                                            </svg>
                                            <span class="text-sm truncate" x-text="previewFileName"></span>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <!-- Header Text -->
                            <template x-if="inputType === 'TEXT' && campaignHeader">
                                <div class="font-bold text-gray-900 dark:text-gray-100 text-sm mb-1"
                                    x-html="replaceVariables(campaignHeader, headerInputs)"></div>
                            </template>

                            <!-- Body Text -->
                            <div class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap"
                                x-html="replaceVariables(campaignBody, bodyInputs)"></div>

                            <!-- Footer -->
                            <template x-if="campaignFooter">
                                <div class="text-[10px] text-gray-500 mt-1" x-text="campaignFooter"></div>
                            </template>

                            <div class="absolute bottom-1 right-2 text-[10px] text-gray-500">
                                {{ now()->format('H:i') }}
                            </div>
                        </div>

                        <!-- Buttons -->
                        <template x-if="buttons.length > 0">
                            <div class="mt-2 space-y-1">
                                <template x-for="btn in buttons">
                                    <div class="bg-white dark:bg-[#202c33] text-center text-blue-500 py-2 rounded shadow-sm text-sm font-medium cursor-pointer"
                                        x-text="btn.text"></div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>