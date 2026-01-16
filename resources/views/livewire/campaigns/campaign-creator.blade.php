<div class="space-y-8">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="p-2 bg-wa-teal/10 text-wa-teal rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                    </svg>
                </div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">
                    {{ isset($campaign->id) ? 'Edit' : 'Create' }} <span class="text-wa-teal">Campaign</span>
                </h1>
            </div>
            <p class="text-slate-500 font-medium">Design and launch your WhatsApp broadcast campaign.</p>
        </div>
        <div class="flex gap-3">
            <x-button.ghost href="{{ route('campaigns.index') }}" class="px-8 py-3 rounded-2xl">Cancel</x-button.ghost>
            <button wire:click="save" wire:loading.attr="disabled"
                class="flex items-center justify-center gap-2 px-8 py-3 bg-wa-teal text-white font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-wa-teal/20 hover:scale-[1.02] active:scale-95 transition-all">
                <span wire:loading.remove>
                    {{ isset($campaign->id) ? 'Update Campaign' : 'Launch Campaign' }}
                </span>
                <span wire:loading class="flex items-center">
                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                </span>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start" x-data="{
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
            mergeFields: @entangle('mergeFields'),
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
                
                this.previewUrl = '';
                this.$nextTick(() => this.initTribute());
            },

            replaceVariables(template, inputs) {
                if (!template || !inputs) return '';
                return template.replace(/\{\{(\d+)\}\}/g, (match, p1) => {
                    const index = parseInt(p1, 10) - 1;
                    return `<span class='font-bold text-wa-teal'>${inputs[index] || match}</span>`;
                });
            },

            handleFilePreview(event) {
                const file = event.target.files[0];
                if (!file) return;
                this.previewUrl = URL.createObjectURL(file);
                this.previewFileName = file.name;
            },
            
            toggleSchedule() {
                this.scheduledDate = !this.scheduledDate;
                if (!this.scheduledDate) {
                     this.$nextTick(() => window.flatePickrWithTime());
                }
            }
        }" x-init="
            $watch('mergeFields', () => initTribute());
        ">
        <!-- Form Section -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Campaign Details -->
            <div
                class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 p-8">
                <div class="mb-6">
                    <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Campaign
                        Details</h2>
                    <p class="text-sm text-slate-500 font-medium mt-1">Basic information and template</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-slate-400">Campaign Name *</label>
                        <input type="text" wire:model="campaign_name" placeholder="Summer Flash Sale 2024"
                            class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-sm font-bold focus:ring-2 focus:ring-wa-teal/20 text-slate-900 dark:text-white">
                        <x-input-error for="campaign_name" />
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase text-slate-400">Select Template *</label>
                        <select wire:model.live="template_id" x-on:change="handleCampaignChange($event)"
                            class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-sm font-bold focus:ring-2 focus:ring-wa-teal/20 text-slate-900 dark:text-white cursor-pointer">
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
                        <x-input-error for="template_id" />
                    </div>
                </div>
            </div>

            <!-- Variables & Media Section -->
            <div x-show="campaignsSelected" x-cloak
                class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 p-8">
                <div class="mb-6">
                    <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Variables &
                        Media</h2>
                    <p class="text-sm text-slate-500 font-medium mt-1">Dynamic content and attachments</p>
                </div>

                <div class="space-y-8">
                    <!-- Media Upload -->
                    <template x-if="inputType !== 'TEXT' && inputType !== ''">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase text-slate-400">Header Media (<span
                                    x-text="inputType"></span>)</label>
                            <div class="relative group">
                                <div class="border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-[2rem] p-8 text-center hover:border-wa-teal hover:bg-wa-teal/5 transition-all cursor-pointer group"
                                    @click="$refs.fileInput.click()">
                                    <div
                                        class="bg-wa-teal/10 text-wa-teal w-12 h-12 rounded-2xl flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1M16 8l-4-4m0 0L8 8m4-4v12" />
                                        </svg>
                                    </div>
                                    <div class="text-sm font-bold text-slate-900 dark:text-white"
                                        x-text="previewFileName || 'Drag and drop or click to upload'"></div>
                                    <p class="text-xs text-slate-500 mt-1">Supported: <span x-text="inputType"></span>
                                    </p>
                                    <input type="file" x-ref="fileInput" wire:model="file" class="hidden"
                                        @change="handleFilePreview($event)" :accept="inputAccept" />
                                </div>
                                <x-input-error for="file" class="mt-2" />
                            </div>
                        </div>
                    </template>

                    <!-- Variables Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Header Params -->
                        <template x-if="inputType === 'TEXT' && headerParamsCount > 0">
                            <div class="space-y-4">
                                <h4 class="text-xs font-black uppercase tracking-widest text-slate-400">Header Variables
                                </h4>
                                <div class="space-y-4">
                                    <template x-for="i in headerParamsCount" :key="'h'+i">
                                        <div class="space-y-1">
                                            <label class="text-[10px] font-bold text-slate-500 uppercase">Variable
                                                {{'{{' + i + '}}'}}</label>
                                            <input type="text" x-model="headerInputs[i-1]" @focus="initTribute"
                                                class="mentionable w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-sm font-medium focus:ring-2 focus:ring-wa-teal/20">
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- Body Params -->
                        <template x-if="bodyParamsCount > 0">
                            <div class="space-y-4">
                                <h4 class="text-xs font-black uppercase tracking-widest text-slate-400">Body Variables
                                </h4>
                                <div class="space-y-4">
                                    <template x-for="i in bodyParamsCount" :key="'b'+i">
                                        <div class="space-y-1">
                                            <label class="text-[10px] font-bold text-slate-500 uppercase">Variable
                                                {{'{{' + i + '}}'}}</label>
                                            <input type="text" x-model="bodyInputs[i-1]" @focus="initTribute"
                                                class="mentionable w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-sm font-medium focus:ring-2 focus:ring-wa-teal/20">
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- Footer Params -->
                        <template x-if="footerParamsCount > 0">
                            <div class="space-y-4">
                                <h4 class="text-xs font-black uppercase tracking-widest text-slate-400">Footer Variables
                                </h4>
                                <div class="space-y-4">
                                    <template x-for="i in footerParamsCount" :key="'f'+i">
                                        <div class="space-y-1">
                                            <label class="text-[10px] font-bold text-slate-500 uppercase">Variable
                                                {{'{{' + i + '}}'}}</label>
                                            <input type="text" x-model="footerInputs[i-1]" @focus="initTribute"
                                                class="mentionable w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-sm font-medium focus:ring-2 focus:ring-wa-teal/20">
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Audience Section -->
            <div
                class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 p-8">
                <div class="mb-6">
                    <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Target
                        Audience</h2>
                    <p class="text-sm text-slate-500 font-medium mt-1">Select your campaign recipients</p>
                </div>

                <div class="space-y-6">
                    <div class="flex items-center justify-between p-6 bg-slate-50 dark:bg-slate-800 rounded-[2rem]">
                        <div>
                            <span class="block text-sm font-bold text-slate-900 dark:text-white">Broadcast to All
                                Contacts</span>
                            <span class="text-xs text-slate-500">Send this campaign to everyone in your team</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model.live="isChecked" class="sr-only peer">
                            <div
                                class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-wa-teal">
                            </div>
                        </label>
                    </div>

                    <div x-show="!isChecked" x-collapse>
                        <div class="relative py-4 text-center">
                            <div class="absolute inset-0 flex items-center" aria-hidden="true">
                                <div class="w-full border-t border-slate-100 dark:border-slate-800"></div>
                            </div>
                            <span
                                class="relative px-4 bg-white dark:bg-slate-900 text-[10px] font-black uppercase text-slate-300 tracking-widest">OR
                                SELECT SPECIFIC</span>
                        </div>

                        <div class="space-y-2" wire:ignore>
                            <label class="text-[10px] font-black uppercase text-slate-400">Search & Select
                                Contacts</label>
                            <select id="contact-select" multiple placeholder="Type names or numbers..." x-init="
                                window.initTomSelect('#contact-select', {
                                    onChange: function(value) { @this.set('relation_type_dynamic', value); }
                                });
                            ">
                                @foreach($contacts as $contact)
                                    <option value="{{ $contact['id'] }}">{{ $contact['firstname'] }}
                                        {{ $contact['lastname'] }} ({{ $contact['phone'] }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 p-4 bg-wa-teal/5 rounded-2xl border border-wa-teal/10">
                        <div class="p-3 bg-wa-teal text-white rounded-xl shadow-lg shadow-wa-teal/20">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <div>
                            <span class="block text-2xl font-black text-wa-teal">{{ $contactCount }}</span>
                            <span class="text-[10px] font-black uppercase tracking-widest text-slate-400">Total
                                Recipients</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Schedule Section -->
            <div
                class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 p-8">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                            Scheduling</h2>
                        <p class="text-sm text-slate-500 font-medium mt-1">Set the delivery time</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xs font-bold text-slate-500 uppercase">Send Now</span>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" @click="toggleSchedule()" :checked="scheduledDate"
                                class="sr-only peer">
                            <div
                                class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-wa-teal">
                            </div>
                        </label>
                    </div>
                </div>

                <div x-show="!scheduledDate" x-collapse>
                    <div class="space-y-4">
                        <div class="relative">
                            <label class="text-[10px] font-black uppercase text-slate-400 mb-2 block">Schedule Date &
                                Time</label>
                            <div class="relative">
                                <input type="text" id="scheduled_send_time" wire:model="scheduled_send_time"
                                    class="w-full pl-12 pr-4 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-sm font-bold focus:ring-2 focus:ring-wa-teal/20"
                                    placeholder="Select date and time" x-init="window.flatePickrWithTime()" />
                                <div class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            </div>
                            <x-input-error for="scheduled_send_time" class="mt-2" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preview Sidebar -->
        <div class="hidden lg:block space-y-6">
            <div class="sticky top-8">
                <div class="mb-6 flex items-center justify-between">
                    <h3 class="text-lg font-black text-slate-900 dark:text-white uppercase tracking-tight">Live <span
                            class="text-wa-teal">Preview</span></h3>
                    <div
                        class="px-2 py-1 rounded-md bg-slate-100 dark:bg-slate-800 text-[9px] font-black uppercase tracking-widest text-slate-400">
                        WhatsApp Desktop</div>
                </div>

                <!-- Phone Frame Appearance -->
                <div class="relative mx-auto w-[320px] bg-[#0b141a] rounded-[3rem] p-3 shadow-2xl border-[8px] border-slate-900 overflow-hidden min-h-[580px]"
                    style="background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png'); background-size: cover; background-position: center;">

                    <div class="mt-8 space-y-3">
                        <div
                            class="bg-white dark:bg-[#202c33] rounded-tr-2xl rounded-bl-2xl rounded-br-2xl p-2.5 shadow-md relative animate-in fade-in slide-in-from-left-4 duration-500">
                            <!-- Media Preview -->
                            <template x-if="previewUrl">
                                <div
                                    class="mb-2 rounded-xl overflow-hidden shadow-inner bg-slate-100 dark:bg-slate-800">
                                    <template x-if="inputType === 'IMAGE'"><img :src="previewUrl"
                                            class="w-full h-auto" /></template>
                                    <template x-if="inputType === 'VIDEO'"><video :src="previewUrl"
                                            class="w-full h-auto"></video></template>
                                    <template x-if="inputType === 'DOCUMENT'">
                                        <div class="p-3 flex items-center gap-3">
                                            <div
                                                class="w-10 h-10 bg-wa-teal rounded-lg flex items-center justify-center text-white">
                                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                                    <path
                                                        d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" />
                                                </svg>
                                            </div>
                                            <span class="text-xs font-bold truncate max-w-[140px]"
                                                x-text="previewFileName"></span>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <!-- Content -->
                            <div class="space-y-1 px-1">
                                <template x-if="inputType === 'TEXT' && campaignHeader">
                                    <div class="font-black text-slate-900 dark:text-white text-xs"
                                        x-html="replaceVariables(campaignHeader, headerInputs)"></div>
                                </template>
                                <div class="text-[13px] text-slate-700 dark:text-slate-200 leading-relaxed whitespace-pre-wrap"
                                    x-html="replaceVariables(campaignBody, bodyInputs)"></div>
                                <template x-if="campaignFooter">
                                    <div class="text-[10px] text-slate-400 pt-1" x-text="campaignFooter"></div>
                                </template>
                            </div>

                            <div class="flex justify-end pr-1 mt-1">
                                <span class="text-[10px] text-slate-400">{{ now()->format('H:i') }}</span>
                            </div>
                        </div>

                        <!-- Buttons Preview -->
                        <template x-if="buttons.length > 0">
                            <div class="space-y-1 w-[90%]">
                                <template x-for="btn in buttons">
                                    <div class="bg-white/95 dark:bg-[#202c33]/95 backdrop-blur shadow-sm text-center text-wa-teal py-2.5 rounded-xl text-[13px] font-bold border-t border-slate-100 dark:border-slate-700/50"
                                        x-text="btn.text"></div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="mt-6 p-4 bg-slate-100 dark:bg-slate-800/50 rounded-2xl flex items-start gap-3">
                    <svg class="w-5 h-5 text-slate-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-[10px] font-bold text-slate-500 uppercase leading-relaxed">Preview renders
                        approximations. Variables highlighted in <span class="text-wa-teal">Green</span> will be
                        replaced dynamically.</p>
                </div>
            </div>
        </div>
    </div>
</div>