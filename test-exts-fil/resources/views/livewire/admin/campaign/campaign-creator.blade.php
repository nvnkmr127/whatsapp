<div class="px-4 md:px-0">
    <x-slot:title>
        {{ isset($campaign->id) ? t('edit_campaign') : t('create_campaign') }}
    </x-slot:title>

    <!-- Simplified header -->
    <div class="pb-3 font-display">
        <x-settings-heading class="font-display">
            {{ isset($campaign->id) ? t('edit_campaign') : t('create_campaign') }}
        </x-settings-heading>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 items-start" x-init="window.initTomSelect();"
        x-data="{
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
            inputType: 'text',
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
            previewUrl: '{{ !empty($filename) ? asset('storage/' . $filename) : '' }}', // Added for preview
            previewType: '', // Store file type (image, video, document)
            previewFileName: '{{ !empty($filename) ? basename($filename) : '' }}',
            filteredContacts: @entangle('contacts'),
            metaExtensions: {{ json_encode(get_meta_allowed_extension()) }},
            isUploading: false,
            progress: 0,
            uploadStarted() {
                this.isUploading = true;
                this.progress = 0;
                $dispatch('upload-started');
            },
            uploadFinished() {
                this.isUploading = false;
                this.progress = 100;
                $dispatch('upload-finished');
            },
            updateProgress(progress) {
                this.progress = progress;
            },
            handleTributeEvent() {
                setTimeout(() => {
                    if (typeof window.Tribute === 'undefined') {
                        return;
                    }
                    let tribute = new window.Tribute({
                        trigger: '@',
                        values: JSON.parse(this.mergeFields),
                    });
                    document.querySelectorAll('.mentionable').forEach((el) => {
                        if (!el.hasAttribute('data-tribute')) {
                            tribute.attach(el);
                            el.setAttribute('data-tribute', 'true'); // Mark as initialized
                        }
                    });
                }, 500);
            },
            initTribute() {
                this.$watch('mergeFields', (newValue) => {
                    this.handleTributeEvent();
                });
                this.handleTributeEvent();
            },
            handleCampaignChange(event) {
                const selectedOption = event.target.selectedOptions[0];
                this.campaignsSelected = event.target.value !== '';
                this.campaignHeader = selectedOption ? selectedOption.dataset.header : '';
                this.campaignBody = selectedOption ? selectedOption.dataset.body : '';
                this.campaignFooter = selectedOption ? selectedOption.dataset.footer : '';
                this.buttons = selectedOption ? JSON.parse(selectedOption.dataset.buttons || '[]') : [];
                this.inputType = selectedOption ? selectedOption.dataset.headerFormat || 'text' : 'text';
                this.headerParamsCount = selectedOption ? parseInt(selectedOption.dataset.headerParamsCount || 0) : 0;
                this.bodyParamsCount = selectedOption ? parseInt(selectedOption.dataset.bodyParamsCount || 0) : 0;
                this.footerParamsCount = selectedOption ? parseInt(selectedOption.dataset.footerParamsCount || 0) : 0;
                // Don't clear previewUrl if we're in edit mode and have an existing file
                if (!selectedOption || (selectedOption && !this.previewUrl.includes('{{ $filename }}'))) {
                    this.previewUrl = '';
                    this.previewFileName = '';
                }
                if (selectedOption) {
                    const format = selectedOption.dataset.headerFormat || 'text';
                    this.inputAccept =
                        format == 'IMAGE' ? 'image/*' :
                        format == 'DOCUMENT' ? '.pdf,.doc,.docx,.txt' :
                        format == 'VIDEO' ? 'video/*' : '';
                }
                if (this.metaExtensions[this.inputType.toLowerCase()]) {
                    this.inputAccept = this.metaExtensions[this.inputType.toLowerCase()].extension;
                } else {
                    this.inputAccept = ''; // Default if type not found
                }

                if (selectedOption.value != this.editTemplateId) {
                    this.previewUrl = '';
                    this.previewFileName = '';
                    this.bodyInputs = [];
                    this.footerInputs = [];
                    this.headerInputs = [];
                }
            },

            handleRelTypechange(e) {
                this.campaignsTypeSelected = e.target.value !== '';
            },
            replaceVariables(template, inputs) {
                if (!template || !inputs) return ''; // Prevent undefined error
                return template.replace(/\{\{(\d+)\}\}/g, (match, p1) => {
                    const index = parseInt(p1, 10) - 1;
                    return `<span class='text-indigo-600'>${inputs[index] || match}</span>`;
                });
            },
            handleFilePreview(event) {
                const file = event.target.files[0];
                this.fileError = null; // Clear previous errors

                if (!file) {
                    return;
                }

                // Get allowed extensions and max size from metaExtensions
                const typeKey = this.inputType.toLowerCase(); // Convert to lowercase for consistency
                const metaData = this.metaExtensions[typeKey];


                const allowedExtensions = metaData.extension.split(',').map(ext => ext.trim());
                const maxSizeMB = metaData.size || 0; // Default to 0 if not set
                const maxSizeBytes = maxSizeMB * 1024 * 1024; // Convert MB to bytes

                // Extract file extension
                const fileExtension = '.' + file.name.split('.').pop().toLowerCase();

                // Validate file extension (from metaExtensions)
                if (!allowedExtensions.includes(fileExtension)) {
                    this.fileError = `Invalid file type. Allowed types: ${allowedExtensions.join(', ')}`;
                    return;
                }

                // MIME type validation (strict check)
                const fileType = file.type.split('/')[0];

                if (this.inputType === 'DOCUMENT' && !['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'text/plain'].includes(file.type)) {
                    this.fileError = 'Invalid document type. Please upload a valid document.';
                    return;
                }

                if (this.inputType === 'IMAGE' && !file.type.startsWith('image/')) {
                    this.fileError = 'Invalid image file. Please upload an image.';
                    return;
                }

                if (this.inputType === 'VIDEO' && !file.type.startsWith('video/')) {
                    this.fileError = 'Invalid video file. Please upload a video.';
                    return;
                }

                if (this.inputType === 'AUDIO' && !file.type.startsWith('audio/')) {
                    this.fileError = 'Invalid audio file. Please upload an audio file.';
                    return;
                }

                if (this.inputType === 'STICKER' && file.type !== 'image/webp') {
                    this.fileError = 'Invalid sticker file. Only .webp format is allowed.';
                    return;
                }

                // Validate file size
                if (file.size > maxSizeBytes) {
                    this.fileError = `File size exceeds ${maxSizeMB} MB. Please upload a smaller file.`;
                    return;
                }

                // If validation passes, handle the file preview
                this.previewUrl = URL.createObjectURL(file);
                this.previewFileName = file.name;
            },
            validateInputs() {
                const hasTextInputs = this.headerParamsCount > 0 || this.bodyParamsCount > 0 || this.footerInputs.length > 0;
                const hasFileInput = ['IMAGE', 'VIDEO', 'DOCUMENT', 'AUDIO'].includes(this.inputType);

                if (!hasTextInputs && !hasFileInput) {
                    return true;
                }
                const validateInputGroup = (inputs, paramsCount) => {
                    // Ensure inputs is a properly unwrapped array
                    const unwrappedInputs = inputs ? JSON.parse(JSON.stringify(inputs)) : [];

                    // Ensure length matches paramsCount by filling missing values with empty strings
                    while (unwrappedInputs.length < paramsCount) {
                        unwrappedInputs.push('');
                    }

                    // Return errors if inputs are empty
                    return unwrappedInputs.map(value =>
                        value.trim() === '' ? '{{ t('this_field_is_required') }}' : ''
                    );
                };

                // Validate text inputs
                this.headerInputErrors = validateInputGroup(this.headerInputs, this.headerParamsCount);
                this.bodyInputErrors = validateInputGroup(this.bodyInputs, this.bodyParamsCount);
                this.footerInputErrors = validateInputGroup(this.footerInputs, this.footerInputs.length);

                if (hasFileInput && !this.previewFileName) {
                    this.fileError = '{{ t('this_field_is_required') }}';
                } else {
                    this.fileError = ''; // Reset file error if not needed
                }

                // Check if all inputs are valid
                const isTextValid = [this.headerInputErrors, this.bodyInputErrors, this.footerInputErrors]
                    .every(errors => errors.length === 0 || errors.every(error => error === ''));

                const isFileValid = !this.fileError; // No error means file validation passed

                return isTextValid && isFileValid;
            },
            handleSave() {
                const isValid = this.validateInputs();

                if (!isValid) return; // Stop if validation fails

                $wire.save();
            }
        }" x-on:livewire-upload-start="uploadStarted()" x-on:livewire-upload-finish="uploadFinished()"
        x-on:livewire-upload-error="isUploading = false"
        x-on:livewire-upload-progress="updateProgress($event.detail.progress)">
        <x-card class="rounded-lg">
            <x-slot:header>
                <h1 class="text-xl font-semibold text-slate-700 dark:text-slate-300 ">
                    {{ t('campaign') }}
                </h1>
            </x-slot:header>
            <form wire:submit.prevent="save">
                <x-slot:content>
                    <div>
                        <div class="flex item-centar justify-start ">
                            <span class="text-red-500 me-1 ">*</span>
                            <x-label for="campaign_name" :value="t('campaign_name')" />
                        </div>
                        <x-input wire:model.defer="campaign_name" type="text" id="campaign_name"
                            class="mt-1 block w-full" autocomplete="off" />
                        <x-input-error for="campaign_name" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <div class="flex items-center justify-start gap-1">
                            <span class="text-red-500">*</span>
                            <x-label for="rel_type" :value="t('relation_type')" />
                        </div>
                        <div class="relative" wire:ignore x-cloak>
                            <x-select wire:model="rel_type" id="parent-select" class="mt-1 block w-full"
                                x-data="{ isLoading: false }" x-on:change="handleRelTypechange($event);"
                                wire:change="$set('rel_type', $event.target.value)" x-ref="relTypeChange"
                                x-init="() => {
                                    handleRelTypechange({ target: $refs.relTypeChange });
                                }">
                                <option value="" selected>{{ t('nothing_selected') }}</option>
                                @foreach (\App\Enums\WhatsAppTemplateRelationType::getRelationType() as $key => $value)
                                    <option value="{{ $key }}">{{ ucfirst($value) }}</option>
                                @endforeach
                            </x-select>

                            {{-- Loading Spinner --}}
                            <div wire:loading wire:target="rel_type" class="top-[9px] absolute right-[34px]">
                                <div
                                    class="animate-spin rounded-full h-5 w-5 border-2 border-indigo-600 border-t-transparent">
                                </div>
                            </div>
                        </div>
                        <x-input-error for="rel_type" class="mt-2" />
                    </div>

                    {{-- template_name --}}
                    <div class="mt-4">
                        <div class="flex item-centar justify-start">
                            <span class="text-red-500 me-1 ">*</span>
                            <x-label for="template_id" :value="t('template')" />
                        </div>
                        <div wire:ignore x-cloak>
                            <x-select id="basic-select" class="mt-1 block w-full" wire:model.defer="template_id"
                                x-ref="campaignsChange"
                                x-on:change="handleCampaignChange({ target: $refs.campaignsChange });"
                                x-init="() => {
                                    handleCampaignChange({ target: $refs.campaignsChange });
                                }">
                                <option value="" selected>{{ t('nothing_selected') }}</option>
                                @foreach ($this->templates as $template)
                                    <option value="{{ $template['template_id'] }}"
                                        data-header="{{ $template['header_data_text'] }}"
                                        data-body="{{ $template['body_data'] }}"
                                        data-footer="{{ $template['footer_data'] }}"
                                        data-buttons="{{ $template['buttons_data'] }}"
                                        data-header-format="{{ $template['header_data_format'] }}"
                                        data-header-params-count="{{ $template['header_params_count'] }}"
                                        data-body-params-count="{{ $template['body_params_count'] }}"
                                        data-footer-params-count="{{ $template['footer_params_count'] }}">
                                        {{ $template['template_name'] . " (" .$template['language'] . ")" }}
                                    </option>
                                @endforeach
                            </x-select>
                        </div>

                        <x-input-error for="template_id" class="mt-2" />
                    </div>

                    <div x-show="campaignsTypeSelected" x-cloak>
                        <div class="mt-4">
                            <div
                                class="text-gray-600 dark:text-gray-400 border-b mt-8 mb-6 border-gray-300 dark:border-gray-600">
                            </div>
                            <div class="flex items-center">
                                <x-checkbox wire:model.live="isChecked" id="select_type" name="select_type"
                                    :value="t('select_all_relation_type')"
                                    x-on:change="isDisabled = $event.target.checked; updateTomSelectState(isDisabled);"
                                    x-ref="checkbox" x-init="$nextTick(() => {
                                        isDisabled = $refs.checkbox.checked;
                                        updateTomSelectState(isDisabled);
                                    });" />
                                <x-label for="select_type" :value="t('select_all_leads') . ' ' . t($rel_type)" class="ml-2" />
                            </div>
                        </div>
                        <div class="flex items-center my-5">
                            <div class="flex-grow border-t border-gray-300 dark:border-gray-600"></div>
                            <span class="px-4 text-sm text-gray-600 dark:text-gray-300"> {{ t('or') }} </span>
                            <div class="flex-grow border-t border-gray-300 dark:border-gray-600"></div>
                        </div>
                        <div wire:ignore>
                            <div class="w-full mt-4" x-cloak>
                                <x-label for="status_name" :value="t('status')"
                                    x-bind:class="{ 'text-gray-400': isDisabled, 'text-slate-700': !isDisabled ? '' : undefined }" />
                                <x-select class="mt-1 block w-full tom-select" wire:model="status_name"
                                    x-bind:disabled="isDisabled" wire:change="$set('status_name', $event.target.value)"
                                    id="status_name">
                                    <option value="">{{ t('nothing_selected') }}</option>
                                    @foreach ($this->statuses as $status)
                                        <option value="{{ $status->id }}">{{ $status->name }}</option>
                                    @endforeach
                                </x-select>
                            </div>

                            <div class="w-full mt-4" x-cloak>
                                <x-label for="source_name" :value="t('source')"
                                    x-bind:class="{ 'text-gray-400': isDisabled, 'text-slate-700': !isDisabled ? '' : undefined }" />
                                <x-select wire:change="$set('source_name', $event.target.value)" id="source_name"
                                    class="mt-1 block w-full tom-select-two" wire:model="source_name"
                                    x-bind:disabled="isDisabled">
                                    <option value="">{{ t('nothing_selected') }}</option>
                                    @foreach ($this->sources as $source)
                                        <option value="{{ $source->id }}">{{ $source->name }}</option>
                                    @endforeach
                                </x-select>
                            </div>
                        </div>

                        {{-- Dependent Select Dropdown --}}
                        <div class="mt-4" wire:ignore>
                            <div class="flex items-center justify-start gap-1">
                                <span class="text-red-500">*</span>
                                <x-label for="contacts" :value="t('contacts')"
                                    x-bind:class="{ 'text-gray-400': isDisabled, 'text-slate-700': !isDisabled ? '' : undefined }" />
                            </div>
                            <div>
                                <select
                                    x-on:change="$wire.call('updateContactCount', $refs.childSelect.tomselect.getValue())"
                                    x-ref="childSelect" id="child-select" class="mt-1 block w-full" multiple
                                    wire:model="relation_type_dynamic">
                                    <option value="">How cool is this?</option>
                                    @foreach ($contacts as $contact)
                                        <option value="{{ $contact['id'] }}">{{ $contact['firstname'] }}
                                            {{ $contact['lastname'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <x-input-error for="relation_type_dynamic" class="mt-1" />
                    </div>
                    <div class="mt-4">
                        <div class="flex items-center justify-start gap-1">
                            <x-heroicon-o-question-mark-circle class="w-4 h-4 text-gray-700 dark:text-gray-200" />
                            <x-label for="scheduled_send_time" :value="t('schedule_send_time')" />
                        </div>

                        <div class="inline-flex rounded-md mt-1 w-full relative">
                            <x-input type="text" wire:model="scheduled_send_time" x-bind:disabled="scheduledDate"
                                data-input id="scheduled_datepicker" x-on:click="flatePickrWithTime()"
                                autocomplete="off" aria-autocomplete="none" />
                            <button type="button" id="calendar-button" class="right-3 top-3 absolute"
                                x-on:click="flatePickrWithTime()">
                                <x-heroicon-o-calendar class="w-5 h-5 input-button" title="toggle" data-toggle />
                            </button>
                        </div>
                        <x-input-error for="scheduled_send_time" class="mt-1" />
                    </div>
                    <div class="mt-4">
                        <label for="scheduled_toggle"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ t('ignore_scheduled_time_and_send_now') }}
                        </label>
                        <button type="button" x-on:click="scheduledDate = !scheduledDate"
                            class="flex-shrink-0 group relative rounded-full inline-flex items-center justify-center h-5 w-10 cursor-pointer focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-slate-800"
                            role="switch" :aria-checked="scheduledDate.toString()"
                            :class="{
                                'bg-indigo-600': scheduledDate,
                                'bg-gray-300': !scheduledDate
                            }">
                            <span class="sr-only">{{ t('toggle_switch') }}</span>
                            <span aria-hidden="true"
                                class="pointer-events-none absolute w-full h-full rounded-full transition-colors ease-in-out duration-200"></span>
                            <span aria-hidden="true"
                                class="pointer-events-none absolute left-0 inline-block h-5 w-5 border border-slate-200 rounded-full bg-white shadow transform ring-0 transition-transform ease-in-out duration-200"
                                :class="scheduledDate ? 'translate-x-5' : 'translate-x-0'"></span>
                        </button>
                    </div>

                </x-slot:content>
        </x-card>

        {{-- Variables --}}
        <x-card class="rounded-lg" x-show="campaignsSelected" x-cloak>
            <x-slot:header>
                <h1 class="text-xl font-semibold text-slate-700 dark:text-slate-300 ">
                    {{ t('variables') }}
                </h1>
            </x-slot:header>
            <x-slot:content>
                <div>
                    <!-- Alert for missing variables -->
                    <div x-show="((inputType == 'TEXT' || inputType == '') && headerParamsCount === 0) && bodyParamsCount === 0 && footerParamsCount === 0"
                        class="bg-red-100 border-l-4 rounded border-red-500 text-red-800 px-2 py-3 dark:bg-gray-800 dark:border-red-800 dark:text-red-300"
                        role="alert">
                        <div class="flex justify-start items-center gap-2">
                            <p class="font-base text-sm">
                                {{ t('variable_not_available_for_this_template') }}
                            </p>
                        </div>
                    </div>

                    {{-- Header section --}}
                    <div x-show="inputType !== 'TEXT' || headerParamsCount > 0">
                        <div class="flex items-center justify-start">
                            <label for="dynamic_input" class="block font-medium text-slate-700 dark:text-slate-200">
                                <template x-if="inputType == 'TEXT' && headerParamsCount > 0">
                                    <span class="text-lg font-semibold">{{ t('header') }}</span>
                                </template>
                                <template x-if="inputType == 'IMAGE'">
                                    <span class="text-lg font-semibold">{{ t('image') }}</span>
                                </template>
                                <template x-if="inputType == 'DOCUMENT'">
                                    <span class="text-lg font-semibold">{{ t('document') }}</span>
                                </template>
                                <template x-if="inputType == 'VIDEO'">
                                    <span class="text-lg font-semibold">{{ t('video') }}</span>
                                </template>
                            </label>
                        </div>

                        <div>
                            <!-- Standard Input with Tailwind CSS -->
                            <template x-if="inputType == 'TEXT'">
                                <template x-for="(value, index) in headerParamsCount" :key="index">
                                    <div class="mt-2">
                                        <div class="flex justify-start gap-1">
                                            <span class="text-red-500">*</span>
                                            <label :for="'header_name_' + index"
                                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                {{ t('variable') }} <span x-text="index + 1"></span>
                                            </label>
                                        </div>
                                        <input x-bind:type="inputType" :id="'header_name_' + index"
                                            x-model="headerInputs[index]" x-init="initTribute()"
                                            class="mentionable block mt-1 w-full border-slate-300 rounded-md shadow-sm text-slate-900 sm:text-sm focus:ring-blue-500 focus:border-blue-500 disabled:opacity-50 dark:border-slate-500 dark:bg-slate-800 dark:placeholder-slate-500 dark:text-slate-200 dark:focus:ring-blue-500 dark:focus:border-blue-500 dark:focus:placeholder-slate-600"
                                            autocomplete="off" />
                                        <p x-show="headerInputErrors[index]" x-text="headerInputErrors[index]"
                                            class="text-red-500 text-sm mt-1"></p>
                                    </div>
                                </template>
                            </template>
                            @if ($errors->has('headerInputs.*'))
                                <x-dynamic-alert type="danger" :message="$errors->first('headerInputs.*')" class="mt-4"></x-dynamic-alert>
                            @endif
                            <!-- For DOCUMENT input type (file upload) -->
                            <template x-if="inputType == 'DOCUMENT'">
                                <div>
                                    <label for="document_upload"
                                        class="block text-sm font-medium text-gray-800 dark:text-gray-300">
                                        {{ t('select_document') }}
                                        <span x-text="metaExtensions.document.extension"></span>
                                    </label>

                                    <div class="relative mt-1 p-6 border-2 border-dashed rounded-lg cursor-pointer hover:border-blue-500 transition duration-300"
                                        x-on:click="$refs.documentUpload.click()">
                                        <div class="text-center">
                                            <x-heroicon-s-photo class="h-12 w-12 text-gray-400 mx-auto" />
                                            <p class="mt-2 text-sm text-gray-600"> {{ t('select_or_browse_to') }}
                                                <span class="text-blue-600 underline">{{ t('document') }}</span>
                                            </p>
                                        </div>
                                        <input type="file" x-ref="documentUpload" id="document_upload"
                                            x-bind:accept="inputAccept" wire:model="file"
                                            x-on:change="handleFilePreview($event)" class="hidden" />
                                    </div>
                                    <template x-if="fileError">
                                        <p class="text-red-500 text-sm mt-2" x-text="fileError"></p>
                                    </template>
                                </div>
                            </template>

                            <!-- For IMAGE input type (image file upload) -->
                            <template x-if="inputType === 'IMAGE'">
                                <div>
                                    <label for="image_upload"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ t('select_image') }}
                                        <span x-text="metaExtensions.image.extension"></span>
                                    </label>
                                    <div class="relative mt-1 p-6 border-2 border-dashed rounded-lg cursor-pointer hover:border-blue-500 transition duration-300"
                                        x-on:click="$refs.imageUpload.click()">
                                        <div class="text-center">
                                            <x-heroicon-s-photo class="h-12 w-12 text-gray-400 mx-auto" />
                                            <p class="mt-2 text-sm text-gray-600"> {{ t('select_or_browse_to') }}
                                                <span class="text-blue-600 underline">{{ t('image') }}</span>
                                            </p>
                                        </div>
                                        <input type="file" id="image_upload" x-ref="imageUpload"
                                            x-bind:accept="inputAccept" wire:model.defer="file"
                                            x-on:change="handleFilePreview($event)" class="hidden" />
                                    </div>

                                    @if ($errors->has('file'))
                                        <x-input-error class="mt-2" for="file" />
                                    @else
                                        <template x-if="fileError">
                                            <p class="text-red-500 text-sm mt-2" x-text="fileError"></p>
                                        </template>
                                    @endif
                                </div>
                            </template>

                            <!-- For VIDEO input type (video file upload) -->
                            <template x-if="inputType == 'VIDEO'">
                                <div>
                                    <label for="video_upload"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ t('select_video') }}
                                    </label>
                                    <span x-text="metaExtensions.video.extension"></span>
                                    <div class="relative mt-1 p-6 border-2 border-dashed rounded-lg cursor-pointer hover:border-blue-500 transition duration-300"
                                        x-on:click="$refs.videoUpload.click()">
                                        <div class="text-center">
                                            <x-heroicon-s-photo class="h-12 w-12 text-gray-400 mx-auto" />
                                            <p class="mt-2 text-sm text-gray-600"> {{ t('select_or_browse_to') }}
                                                <span class="text-blue-600 underline">{{ t('video') }}</span>
                                            </p>
                                        </div>
                                        <input type="file" id="video_upload" x-ref="videoUpload"
                                            x-bind:accept="inputAccept" wire:model.defer="file"
                                            x-on:change="handleFilePreview($event)" class="hidden" />
                                    </div>
                                    <template x-if="fileError">
                                        <p class="text-red-500 text-sm mt-2" x-text="fileError"></p>
                                    </template>
                                </div>
                            </template>
                            <div x-show="isUploading" class="relative mt-2">
                                <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                                    <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-300"
                                        :style="'width: ' + progress + '%'"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- Body section --}}
                    <div x-show="bodyParamsCount > 0">
                        <div class="flex items-center justify-start mt-2">
                            <label for="dynamic_input" class="block font-medium text-slate-700 dark:text-slate-200">
                                <span class="text-lg font-semibold">{{ t('body') }}</span>
                            </label>
                        </div>

                        <div>
                            <template x-for="(value, index) in bodyParamsCount" :key="index">
                                <div class="mt-2">
                                    <div class="flex justify-start gap-1">
                                        <span class="text-red-500">*</span>
                                        <label :for="'body_name_' + index"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            {{ t('variable') }} <span x-text="index + 1"></span>
                                        </label>
                                    </div>
                                    <input type="text" :id="'body_name_' + index" x-model="bodyInputs[index]"
                                        x-init='initTribute()'
                                        class="mentionable block mt-1 w-full border-slate-300 rounded-md shadow-sm text-slate-900 sm:text-sm focus:ring-blue-500 focus:border-blue-500 disabled:opacity-50 dark:border-slate-500 dark:bg-slate-800 dark:placeholder-slate-500 dark:text-slate-200 dark:focus:ring-blue-500 dark:focus:border-blue-500 dark:focus:placeholder-slate-600"
                                        autocomplete="off" />
                                    <p x-show="bodyInputErrors[index]" x-text="bodyInputErrors[index]"
                                        class="text-red-500 text-sm mt-1"></p>
                                </div>
                            </template>
                            @if ($errors->has('bodyInputs.*'))
                                <x-dynamic-alert type="danger" :message="$errors->first('bodyInputs.*')" class="mt-4"></x-dynamic-alert>
                            @endif
                        </div>
                    </div>
                    {{-- Footer section --}}
                    <div x-show="footerParamsCount > 0">
                        <div
                            class="text-gray-600 dark:text-gray-400 border-b mt-8 mb-6 border-gray-300 dark:border-gray-600">
                        </div>

                        {{-- Footer section --}}
                        <div class="flex items-center justify-start">
                            <label for="dynamic_input" class="block font-medium text-slate-700 dark:text-slate-200">
                                <span class="text-lg font-semibold">{{ t('footer') }}</span>
                            </label>
                        </div>

                        <div>
                            <template x-for="(value, index) in footerInputs" :key="index">
                                <div class="mt-2">
                                    <div class="flex justify-start gap-1">
                                        <span class="text-red-500">*</span>
                                        <label :for="'footer_name_' + index"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            {{ t('variable') }} <span x-text="index"></span>
                                        </label>
                                    </div>
                                    <input type="text" :id="'footer_name_' + index" x-model="footerInputs[index]"
                                        class="mentionable block mt-1 w-full border-slate-300 rounded-md shadow-sm text-slate-900 sm:text-sm focus:ring-blue-500 focus:border-blue-500 disabled:opacity-50 dark:border-slate-500 dark:bg-slate-800 dark:placeholder-slate-500 dark:text-slate-200 dark:focus:ring-blue-500 dark:focus:border-blue-500 dark:focus:placeholder-slate-600"
                                        autocomplete="off" />
                                    <p x-show="footerInputErrors[index]" x-text="footerInputErrors[index]"
                                        class="text-red-500 text-sm mt-1"></p>
                                </div>
                            </template>
                            @if ($errors->has('footerInputs.*'))
                                <x-dynamic-alert type="danger" :message="$errors->first('footerInputs.*')" class="mt-4"></x-dynamic-alert>
                            @endif
                        </div>
                    </div>
                </div>
            </x-slot:content>
        </x-card>

        <div x-show="campaignsSelected" x-cloak>
            {{-- Preview --}}
            <x-card class="rounded-lg">
                <x-slot:header>
                    <h1 class="text-xl font-semibold text-slate-700 dark:text-slate-300 ">
                        {{ t('preview') }}
                    </h1>
                </x-slot:header>
                <x-slot:content>
                    <div class="w-full p-6 border border-gray-200 rounded shadow-sm dark:border-gray-700"
                        style="background-image: url('{{ asset('img/chat/whatsapp_light_bg.png') }}');">
                        <!-- File Preview Section -->
                        <div class="mb-1" x-show="previewUrl">
                            <!-- Image Preview -->
                            <a x-show="inputType === 'IMAGE'" :href="previewUrl" class="glightbox" x-effect="if (previewUrl) { setTimeout(() => initGLightbox(), 100); }">
                            <img x-show="inputType === 'IMAGE'" :src="previewUrl"
                                class="w-full max-h-60 rounded-lg shadow bg-white dark:bg-gray-800" />
                            </a>

                            <!-- Video Preview -->
                            <video x-show="inputType === 'VIDEO'" :src="previewUrl" controls
                                class="w-full max-h-60 rounded-lg shadow bg-white dark:bg-gray-800 glightbox cursor-pointer"></video>

                            <!-- Document Preview -->
                            <div x-show="inputType === 'DOCUMENT'"
                                class="p-4 border border-gray-300 bg-white dark:bg-gray-800 rounded-lg">
                                <p class="text-sm text-gray-500 dark:text-gray-400"> {{ t('document_uploaded') }} <a
                                        :href="previewUrl" target="_blank"
                                        class="text-blue-500 underline break-all inline-block"
                                        x-text="previewFileName"></a></p>
                            </div>
                        </div>

                        <!-- Campaign Text Section -->
                        <div class="p-6 bg-white rounded-lg dark:bg-gray-800 dark:text-white">
                            <p class="mb-3 font-meduim text-gray-800 dark:text-gray-400"
                                x-html="replaceVariables(campaignHeader, headerInputs)"></p>
                            <p class="mb-3 font-normal text-sm text-gray-500 dark:text-gray-400"
                                x-html="replaceVariables(campaignBody, bodyInputs)"></p>
                            <div class="mt-4">
                                <p class="font-normal text-xs text-gray-500 dark:text-gray-400"
                                    x-text="campaignFooter">
                                </p>
                            </div>
                        </div>

                        <template x-if="buttons && buttons.length > 0"
                            class="bg-white rounded-lg py-2 dark:bg-gray-800 dark:text-white">
                            <!-- Check if buttons is defined and not empty -->
                            <div class="space-y-1">
                                <!-- Use space-y-2 for vertical spacing between buttons -->
                                <template x-for="(button, index) in buttons" :key="index">
                                    <div
                                        class="w-full px-4 py-2 bg-white text-gray-900 rounded-md dark:bg-gray-700 dark:text-white">
                                        <span x-text="button.text" class="text-sm block text-center"></span>
                                        <!-- Center the text inside the button -->
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </x-slot:content>
            </x-card>
            {{-- Send campaigns --}}
            <x-card class="rounded-lg mt-8">
                <x-slot:header>
                    <h1 class="text-xl font-semibold text-slate-700 dark:text-slate-300">
                        {{ t('send_campaign') }}
                    </h1>
                </x-slot:header>
                <x-slot:content>

                    <label for="send_to" id="contact-count"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ t('this_campaign_send_to') }}
                        {{ $contactCount > 0 ? $contactCount . ' ' : '' }}
                        {{ $rel_type == 'leads' ? t('leads') : ($rel_type == 'customers' ? t('customers') : ucfirst($rel_type) . ($contactCount > 1 ? 's' : '')) }}
                    </label>
                </x-slot:content>
                <x-slot:footer class="bg-slate-50 dark:bg-transparent rounded-b-lg">
                    <div class="flex justify-end">
                        <x-button.loading-button type="button" target="save" x-on:click="handleSave()"
                           x-bind:disabled="isUploading" x-bind:class="{ 'opacity-50 cursor-not-allowed': isUploading }">
                            {{ t('send_campaign') }}
                        </x-button.loading-button>
                    </div>
                </x-slot:footer>
                </form>
            </x-card>
        </div>
    </div>
</div>
@push('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const timezone = window.dateTimeSettings.timezone; // Example timezone
            const curruntTime = new Date(new Date().toLocaleString("en-US", {
                timeZone: timezone
            }));


            if (typeof window.TomSelect === "undefined") {
                return;
            }

            const parentSelect = new TomSelect("#parent-select");
            const childSelect = new TomSelect("#child-select");

            // Listen for the 'contacts-updated' event from Livewire
            Livewire.on("contacts-updated", (children) => {
                let childSelect = document.querySelector("#child-select")?.tomselect;
                if (!childSelect) return;

                childSelect.clear();
                childSelect.clearOptions();

                // Add new options to the child select
                children.forEach((child) => {
                    child.forEach((subChild) => {
                        if (subChild?.id && subChild?.firstname && subChild?.lastname) {
                            childSelect.addOption({
                                value: subChild.id.toString(),
                                text: `${subChild.firstname} ${subChild.lastname}`,
                            });
                        }
                    });
                });
                // Refresh the child select to apply changes
                childSelect.refreshOptions();
            });
            window.addEventListener("load", function() {
                let initialData = @json($this->contacts);
                let selectedData = @json($this->relation_type_dynamic);


                let childSelect = document.querySelector("#child-select")?.tomselect;
                if (!childSelect) return;

                // Clear existing options in the child select
                childSelect.clearOptions();


                // Append initial data to the select dropdown
                if (Array.isArray(initialData) && initialData.length > 0) {
                    initialData.forEach((contact) => {
                        if (contact?.id && contact?.firstname && contact?.lastname) {
                            childSelect.addOption({
                                value: contact.id.toString(),
                                text: `${contact.firstname} ${contact.lastname}`,
                            });
                        }
                    });

                    // Refresh Tom Select to apply changes
                    childSelect.refreshOptions();
                }
                // Pre-select the contacts that match selectedData
                if (Array.isArray(selectedData) && selectedData.length > 0) {
                    let selectedValues = selectedData.map(id => id.toString()); // Convert IDs to string
                    childSelect.setValue(selectedValues); // Set pre-selected values
                }
            });

            // Clean up Tom Select instances when the component is destroyed
            Livewire.on("$disconnect", () => {
                parentSelect.destroy();
                childSelect.destroy();
            });

            window.flatePickrWithTime = function() {
                const datePicker = flatpickr("#scheduled_datepicker", {
                    dateFormat: `${date_format} ${time_format}`,
                    enableTime: true,
                    allowInput: true,
                    disableMobile: true,
                    time_24hr: is24Hour,
                    minDate: "today",

                });
                datePicker.open();
                document.getElementById("scheduled_datepicker").focus();
            };

        });


        function updateTomSelectState(isDisabled) {
            document.querySelectorAll(".tom-select, .tom-select-two, #child-select").forEach(select => {
                if (select.tomselect) {
                    if (isDisabled) {
                        select.tomselect.disable(); // Disable Tom Select
                    } else {
                        select.tomselect.enable(); // Enable Tom Select
                    }
                }
            });
        }
    </script>
@endpush
