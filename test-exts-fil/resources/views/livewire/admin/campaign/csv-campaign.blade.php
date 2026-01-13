<div class="px-4 md:px-0">
    <x-slot:title>
        {{ t('bulk_campaign') }}
    </x-slot:title>
    <div class="py-3 font-display">
        <x-settings-heading>{{ t('campaign_for_csv_file') }}</x-settings-heading>
    </div>
    <form wire:submit.prevent="save">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 items-start" x-data="{
            scheduledDate: false,
            campaignsSelected: false,
            campaignsTypeSelected: false,
            isDisabled: false,
            campaignHeader: '',
            campaignBody: '',
            campaignFooter: '',
            fileError: '',
            buttons: [],
            inputType: 'text',
            inputAccept: '',
            headerInputs: @entangle('headerInputs'),
            bodyInputs: @entangle('bodyInputs'),
            footerInputs: @entangle('footerInputs'),
            mergeFields: @entangle('mergeFields'),
            headerInputErrors: [],
            bodyInputErrors: [],
            footerInputErrors: [],
            headerParamsCount: 0,
            bodyParamsCount: 0,
            footerParamsCount: 0,
            relType: '',
            previewUrl: '', // Added for preview
            previewType: '', // Store file type (image, video, document)
            previewFileName: '',
            processingProgress: 0,
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
                this.previewUrl = '';
                this.previewType = '';
                if (this.metaExtensions[this.inputType.toLowerCase()]) {
                    this.inputAccept = this.metaExtensions[this.inputType.toLowerCase()].extension;
                } else {
                    this.inputAccept = ''; // Default if type not found
                }
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
                if (!file) return;

                // Get allowed extensions and max size from metaExtensions
                const typeKey = this.inputType.toLowerCase();
                const metaData = this.metaExtensions[typeKey];

                // Validate configuration exists for this file type
                if (!metaData) {
                    this.fileError = 'File upload configuration error. Please try another format.';
                    return;
                }

                const allowedExtensions = metaData.extension.split(',').map(ext => ext.trim());
                const maxSizeMB = metaData.size || 0;
                const maxSizeBytes = maxSizeMB * 1024 * 1024;

                // Handle files with multiple/non-standard extensions
                const fileNameParts = file.name.split('.');
                const fileExtension = fileNameParts.length > 1 ?
                    '.' + fileNameParts.pop().toLowerCase() :
                    '';

                // Validate file extension
                if (!allowedExtensions.includes(fileExtension)) {
                    this.fileError = `Invalid file type. Allowed types: ${allowedExtensions.join(', ')}`;
                    return;
                }

                // Validate MIME type based on category
                const fileType = file.type;
                let isValidMime = true;

                switch (this.inputType) {
                    case 'DOCUMENT':
                        isValidMime = [
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-powerpoint',
                            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                            'text/plain'
                        ].includes(fileType);
                        break;
                    case 'IMAGE':
                        isValidMime = fileType.startsWith('image/');
                        break;
                    case 'VIDEO':
                        isValidMime = fileType.startsWith('video/');
                        break;
                    case 'AUDIO':
                        isValidMime = fileType.startsWith('audio/');
                        break;
                    case 'STICKER':
                        isValidMime = fileType === 'image/webp';
                        break;
                }
                if (!isValidMime) {
                    this.fileError = `Invalid ${this.inputType.toLowerCase()} file format.`;
                    return;
                }
                // Validate file size only if size limit is specified
                if (maxSizeMB > 0 && file.size > maxSizeBytes) {
                    this.fileError = `File size exceeds ${maxSizeMB} MB (${(file.size/1024/1024).toFixed(2)} MB uploaded).`;
                    return;
                }
                URL.revokeObjectURL(this.previewUrl);

                // Create new preview
                this.previewUrl = URL.createObjectURL(file);
                this.previewFileName = file.name;
            },
            setupProgressEvents() {
                window.addEventListener('importStarted', () => {
                    this.processingProgress = 0;
                });

                window.addEventListener('importProgress', (e) => {
                    this.processingProgress = e.detail.percent;
                });

                window.addEventListener('importComplete', () => {
                    this.processingProgress = 100;
                    setTimeout(() => {
                        this.processingProgress = 0;
                    }, 2000);
                });

                window.addEventListener('importFailed', () => {
                    this.processingProgress = 0;
                });
            },
            validateInputs() {
                const hasTextInputs = this.headerParamsCount > 0 || this.bodyParamsCount > 0 || this.footerInputs.length > 0;
                const hasFileInput = ['IMAGE', 'VIDEO', 'DOCUMENT', 'AUDIO'].includes(this.inputType);

                if (!hasTextInputs && !hasFileInput) return true;

                const invalidPatterns = [
                    /(?<!@)\{[^}]*?\}|\[.*?\]/s, // JSON-like structures (excluding @{name})
                    /('|\')\s*:\s*('|\')/, // Key-value pairs
                    /<script\b[^>]*>(.*?)<\/script>/is, // Inline script injection
                    /<[^>]*>/g, // **NEW: Blocks any HTML tags like <div>, <p>, <input>**
                    /\\\\'/, // Excessive escaping
                    /(\\\u[0-9a-fA-F]{4})/, // Unicode escapes
                ];

                const isInvalidContent = (value) => {
                    if (!value || typeof value !== 'string') return false;

                    // Check for dangerous patterns
                    if (invalidPatterns.some(pattern => pattern.test(value))) return true;

                    // Normalize input for case-insensitive matching
                    const upperValue = value.toUpperCase();

                    // Detect SQL Injection patterns
                    const sqlInjectionPatterns = [
                        /(;|\-\-|\#)/, // Detects comment markers (e.g., -- or # to ignore parts of a query)
                        /\b(SELECT|INSERT|UPDATE|DELETE|DROP|ALTER|TRUNCATE|EXEC|UNION)\b/i, // SQL Keywords
                    ];

                    if (sqlInjectionPatterns.some(pattern => pattern.test(upperValue))) {
                        return true;
                    }

                    return false;
                };

                const validateInputGroup = (inputs, paramsCount) => {
                    return [...inputs, ...Array(Math.max(0, paramsCount - inputs.length)).fill('')].map(value =>
                        value.trim() === '' ? '{{ t('this_field_is_required') }}' :
                        isInvalidContent(value) ? '{{ t('dynamic_input_error') }}' :
                        ''
                    );
                };

                this.headerInputErrors = validateInputGroup(this.headerInputs, this.headerParamsCount);
                this.bodyInputErrors = validateInputGroup(this.bodyInputs, this.bodyParamsCount);
                this.footerInputErrors = validateInputGroup(this.footerInputs, this.footerInputs.length);

                this.fileError = hasFileInput && !this.previewFileName ? '{{ t('this_field_is_required') }}' : '';

                return [this.headerInputErrors, this.bodyInputErrors, this.footerInputErrors].every(errors => errors.every(error => error === '')) && !this.fileError;
            },
            handleSave() {
                const isValid = this.validateInputs();

                if (!isValid) {
                    return;
                }

                $dispatch('open-loading-modal');
                $wire.save();
            }

        }"
            x-init="setupProgressEvents()" x-on:livewire-upload-start="uploadStarted()"
            x-on:livewire-upload-finish="uploadFinished()" x-on:livewire-upload-error="isUploading = false"
            x-on:livewire-upload-progress="updateProgress($event.detail.progress)">
            <x-card class="rounded-lg">
                <x-slot:header>
                    <h1 class="text-xl font-semibold text-slate-700 dark:text-slate-300 ">
                        {{ t('campaign') }}
                    </h1>
                </x-slot:header>
                <x-slot:content>

                    <div class="col-span-3">
                        <div class="flex items-center justify-start gap-1">
                            <span class="text-red-500">*</span>
                            <x-label class="mt-[2px]" for="csv_campaign_name" :value="t('campaign_name')" />
                        </div>
                        <x-input wire:model.defer="csv_campaign_name" id="csv_campaign_name" type="text"
                            class="block w-full" autocomplete="off" />
                        <x-input-error for="csv_campaign_name" class="mt-2" />
                    </div>
                    <div class="col-span-3 mt-3">
                        <div class="flex flex-col 2xl:flex-row 2xl:items-center 2xl:justify-between">
                            <x-label class="mt-[2px]" :value="t('choose_csv_file')" class="mt-[2px]" />
                            <p class="text-sm cursor-pointer text-blue-500 hover:underline"
                                x-on:click="$dispatch('open-modal', 'csv-campaign-modal')">
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
                                            <p class="mt-2 text-sm text-gray-500">{{ t('drag_and_drop_description') }}
                                            </p>
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
                        <x-input name="json_file_path" id="json_file_path" type="hidden" class="block w-full"
                            value="{{ $json_file_path }}" />

                        <div x-data="{ disabled: @entangle('totalRecords') }" class="dark:bg-transparent rounded-b-lg flex justify-end mt-3">
                            <x-button.primary wire:click.prevent="processImportCsv" wire:loading.attr="disabled"
                                x-bind:disabled="disabled || $wire.importInProgress || processingProgress > 0"
                                @class(['opacity-50 cursor-not-allowed' => $importInProgress]) :disabled="$importInProgress">

                                <span wire:loading.remove wire:target="processImportCsv">
                                    {{ t('upload') }}
                                </span>

                                <span wire:loading wire:target="processImportCsv"
                                    class="flex items-center justify-center min-w-12 min-h-2">
                                    <x-heroicon-o-arrow-path class="animate-spin w-4 h-4 my-1 ms-3.5" />
                                </span>
                            </x-button.primary>
                        </div>

                        @if ($json_file_path)
                            <div class="mt-3">
                                <x-dynamic-alert type="primary">
                                    <x-slot:title> {{ t('note') }} </x-slot:title>
                                    {{ t('out_of_the') . ' ' . $totalRecords }}
                                    {{ t('records_in_your_csv_file') . ' ' . $validRecords }}
                                    {{ t('records_are_valid') }}
                                    {{ t('campaign_successfully_sent_to_these') . ' ' . $validRecords }}
                                    {{ t('user') }}
                                </x-dynamic-alert>
                            </div>
                        @endif
                    </div>

                    {{-- template_name --}}
                    @if ($json_file_path)
                        <div class="mt-4" x-data x-init="window.initTomSelect('#basic-select')">
                            <div class="flex items-center justify-start">
                                <span class="text-red-500 me-1 ">*</span>
                                <x-label for="template_name" :value="t('template')" />
                            </div>
                            <div wire:ignore>
                                <x-select id="basic-select" class="mt-1 block w-full" x-ref="campaignsChange"
                                    x-on:change="handleCampaignChange({ target: $refs.campaignsChange })"
                                    x-init="handleCampaignChange({ target: $refs.campaignsChange })" wire:model="template_name"
                                    wire:change="$set('template_name', $event.target.value)">
                                    <option value="" selected>{{ t('Nothing Selected') }}</option>
                                    @foreach ($templates as $template)
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
                            <x-input-error for="template_name" class="mt-2" />
                        </div>
                    @endif
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
                                <label for="dynamic_input"
                                    class="block font-medium text-slate-700 dark:text-slate-200">
                                    <template x-if="inputType =='TEXT' && headerParamsCount > 0">
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

                                <!-- For DOCUMENT input type -->
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
                                            <input type="file" id="document_upload" x-bind:accept="inputAccept"
                                                x-on:change="handleFilePreview($event)" wire:model="file"
                                                x-ref="documentUpload" class="hidden" />
                                        </div>
                                        <template x-if="fileError">
                                            <p class="text-red-500 text-sm mt-2" x-text="fileError"></p>
                                        </template>
                                    </div>
                                </template>

                                <!-- For IMAGE input type -->
                                <template x-if="inputType == 'IMAGE'">
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
                                                x-bind:accept="inputAccept" wire:model="file"
                                                x-on:change="handleFilePreview($event)" class="hidden" />
                                        </div>
                                        <template x-if="fileError">
                                            <p class="text-red-500 text-sm mt-2" x-text="fileError"></p>
                                        </template>
                                    </div>
                                </template>

                                <!-- For VIDEO input type -->
                                <template x-if="inputType == 'VIDEO'">
                                    <div>
                                        <label for="video_upload"
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            {{ t('select_video') }}
                                            <span x-text="metaExtensions.video.extension"></span>
                                        </label>
                                        <div class="relative mt-1 p-6 border-2 border-dashed rounded-lg cursor-pointer hover:border-blue-500 transition duration-300"
                                            x-on:click="$refs.videoUpload.click()">
                                            <div class="text-center">
                                                <x-heroicon-s-photo class="h-12 w-12 text-gray-400 mx-auto" />
                                                <p class="mt-2 text-sm text-gray-600"> {{ t('select_or_browse_to') }}
                                                    <span class="text-blue-600 underline">{{ t('video') }}</span>
                                                </p>
                                            </div>
                                            <input type="file" id="video_upload" x-ref="videoUpload"
                                                x-bind:accept="inputAccept" wire:model="file"
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
                                <label for="dynamic_input"
                                    class="block font-medium text-slate-700 dark:text-slate-200">
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
                            </div>
                        </div>
                        {{-- Footer section --}}
                        <div x-show="footerParamsCount > 0">

                            {{-- Footer section --}}
                            <div class="flex items-center justify-start">
                                <label for="dynamic_input"
                                    class="block font-medium text-slate-700 dark:text-slate-200">
                                    <span class="text-lg font-semibold">{{ t('footer') }}</span>
                                </label>
                            </div>

                            <div>
                                <template x-for="index in Math.max(footerParamsCount, 1)" :key="index">
                                    <div class="mt-2">
                                        <div class="flex justify-start gap-1">
                                            <span class="text-red-500">*</span>
                                            <label :for="'footer_name_' + index"
                                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                {{ t('variables') }} <span x-text="index"></span>
                                            </label>
                                        </div>
                                        <input type="text" :id="'footer_name_' + index"
                                            x-model="footerInputs[index]"
                                            class="mentionable block mt-1 w-full border-slate-300 rounded-md shadow-sm text-slate-900 sm:text-sm focus:ring-blue-500 focus:border-blue-500 disabled:opacity-50 dark:border-slate-500 dark:bg-slate-800 dark:placeholder-slate-500 dark:text-slate-200 dark:focus:ring-blue-500 dark:focus:border-blue-500 dark:focus:placeholder-slate-600"
                                            autocomplete="off" />
                                        <p x-show="footerInputErrors[index]" x-text="footerInputErrors[index]"
                                            class="text-red-500 text-sm mt-1"></p>
                                    </div>
                                </template>
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
                        <div
                            class="w-full p-6 border border-gray-200 rounded shadow-sm dark:border-gray-700 chat-conversation-box">
                            <!-- File Preview Section -->
                            <div class="mb-1" x-show="previewUrl">
                                <!-- Image Preview -->
                                <a x-show="inputType === 'IMAGE'" :href="previewUrl" class="glightbox" x-effect="if (previewUrl) { setTimeout(() => initGLightbox(), 100); }">
                                <img x-show="inputType === 'IMAGE'" :src="previewUrl"
                                    class="w-full max-h-60 rounded-lg shadow bg-white dark:bg-gray-800 cursor-pointer"/>
                                </a>
                                <!-- Video Preview -->
                                <video x-show="inputType === 'VIDEO'" :src="previewUrl" controls
                                    class="w-full max-h-60 rounded-lg shadow bg-white dark:bg-gray-800"></video>

                                <!-- Document Preview -->
                                <div x-show="inputType === 'DOCUMENT'"
                                    class="p-4 border border-gray-300 bg-white dark:bg-gray-800 rounded-lg">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('document_uploaded') }}
                                        <a :href="previewUrl" target="_blank" class="text-blue-500 underline"
                                            x-text="previewFileName"></a>
                                    </p>
                                </div>
                            </div>
                            <div class="p-6 bg-white rounded-lg dark:bg-gray-800 dark:text-white">
                                <p class="mb-3 font-semibold text-gray-800 break-all dark:text-gray-400"
                                    x-html="replaceVariables(campaignHeader, headerInputs)"></p>
                                <p class="mb-3 font-normal text-gray-500 break-all dark:text-gray-400"
                                    x-html="replaceVariables(campaignBody, bodyInputs)"></p>
                                <div class="mt-4">
                                    <p class="font-normal text-xs break-all text-gray-500 dark:text-gray-400"
                                        x-html="campaignFooter">
                                    </p>
                                </div>
                            </div>
                            <template x-if="buttons && buttons.length > 0"
                                class="bg-white rounded-lg py-2 dark:bg-gray-800 dark:text-white">
                                <div class="space-y-1">
                                    <template x-for="(button, index) in buttons" :key="index">
                                        <div
                                            class="w-full px-4 py-2 bg-white text-gray-900 rounded-md dark:bg-gray-700 dark:text-white">
                                            <span x-text="button.text" class="text-sm block text-center"></span>
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
                        <div
                            class="flex flex-wrap sm:flex-nowrap items-center justify-center sm:justify-between gap-4">
                            <div class="text-sm text-gray-500 dark:text-gray-400 text-center sm:text-left">
                                {{ t('sending_to') }} <span class="font-semibold">{{ $validRecords }}</span>
                                {{ t('recipients') }}
                            </div>
                            <div class="w-full sm:w-auto flex justify-center sm:justify-end">
                                <x-button.loading-button type="button" target="save" x-on:click="handleSave()"
                                    wire:loading.attr="disabled" x-bind:disabled="isUploading" x-bind:class="{ 'opacity-50 cursor-not-allowed': isUploading }">
                                    {{ t('send_campaign') }}
                                </x-button.loading-button>
                            </div>
                        </div>
                    </x-slot:content>
                </x-card>

            </div>
        </div>
    </form>

    {{-- Download campaign sample file modal : Start --}}
    <x-modal name="csv-campaign-modal" :show="false" maxWidth="5xl">
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
                    <p class="text-xl text-slate-700 dark:text-slate-200">{{ t('campaign') }}</p>
                    <p wire:click="sampledownload"
                        class="px-4 py-2 bg-gradient-to-r from-green-500  to-green-500 text-white rounded-md cursor-pointer transition duration-150 ease-in-out dark:bg-gradient-to-r dark:from-green-800  dark:to-green-800">
                        {{ t('download_sample') }}
                    </p>
                </div>

                <div class="relative overflow-x-auto border border-3 rounded-sm my-4">
                    <table class="w-full text-sm text-left text-slate-700 dark:text-slate-200">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th class="border-r px-4 py-2"><span
                                        class="text-red-500 me-1">*</span>{{ t('firstname') }}</th>
                                <th class="border-r px-4 py-2"><span
                                        class="text-red-500 me-1">*</span>{{ t('lastname') }}</th>
                                <th class="border-r px-4 py-2">
                                    <span class="text-red-500 me-1">*</span>{{ t('phone') }}
                                </th>
                                <th class="border-r px-4 py-2">{{ t('email') }}</th>
                                <th class="px-4 py-2">{{ t('country') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                <td class="border-r border-t px-4 py-2">{{ t('sample_data') }}</td>
                                <td class="border-r border-t px-4 py-2">{{ t('sample_data') }}</td>
                                <td class="border-r border-t px-4 py-2">{{ t('phone_sample') }}</td>
                                <td class="border-r border-t px-4 py-2">{{ t('abc@gmail.com') }}</td>
                                <td class="px-4 border-t py-2">{{ t('sample_data') }}</td>
                            </tr>
                            <tr class="bg-gray-50 border-b dark:bg-gray-900 dark:border-gray-700">
                                <!-- Additional rows as needed -->
                            </tr>
                        </tbody>
                    </table>
                </div>
            </x-slot:content>
            <x-slot:footer>
                <div class="flex justify-end">
                    <x-button.secondary x-on:click="$dispatch('close-modal', 'csv-campaign-modal')">
                        {{ t('cancel') }}
                    </x-button.secondary>
                </div>
            </x-slot:footer>
        </x-card>
    </x-modal>
    {{-- Download campaign sample file modal : Over --}}

    <!-- Loading Modal -->
    <div x-data="{ isOpen: false }">
        <div x-on:open-loading-modal.window="isOpen = true" x-on:close-loading-modal.window="isOpen = false"
            x-show="isOpen" class="fixed inset-0 flex items-center justify-center bg-black/50 backdrop-blur-sm z-50"
            x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
            style="display: none;">

            <div
                class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 w-11/12 sm:w-full max-w-xs sm:max-w-sm md:max-w-md lg:max-w-lg text-center">
                <!-- Loading Spinner -->
                <div
                    class="w-10 h-10 sm:w-12 sm:h-12 border-4 border-gray-300 dark:border-gray-600 border-t-indigo-500 dark:border-t-indigo-400 rounded-full animate-spin mx-auto">
                </div>

                <!-- Message -->
                <p class="mt-4 text-base sm:text-lg font-medium text-gray-700 dark:text-gray-200">
                    {{ t('sending_campaign') }}
                </p>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    {{ t('this_may_take_a_few_moments') }}
                </p>
            </div>
        </div>
    </div>

    <!-- Error message toast -->
    <div x-data="{ errorMessage: '', showError: false }"
        x-on:campaign-error.window="errorMessage = $event.detail.message; showError = true; setTimeout(() => showError = false, 5000)"
        x-show="showError" x-transition.opacity
        class="fixed bottom-4 right-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-lg"
        style="display: none; max-width: 400px; z-index: 9999;">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                    fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                        clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm" x-text="errorMessage"></p>
            </div>
            <div class="ml-auto pl-3">
                <div class="-mx-1.5 -my-1.5">
                    <button x-on:click="showError = false"
                        class="inline-flex rounded-md p-1.5 text-red-500 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
