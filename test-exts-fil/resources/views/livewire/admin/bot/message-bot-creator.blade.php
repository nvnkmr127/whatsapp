<div class="mx-auto px-4 md:px-0">

  <x-slot:title>
    {{ t('create_message_bot') }}
  </x-slot:title>

  <div class="py-3 font-display">
    <x-page-heading>
      {{ $message_bot->exists ? t('edit_bot') : t('add_message_bot') }}
    </x-page-heading>
  </div>
  <div x-data="{ replyType: '' }" class="flex-1 space-y-5">
    <form wire:submit.prevent="save" enctype="multipart/form-data" class="space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start" x-data="{
          mergeFields: @entangle('mergeFields'),
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
      }">
        {{-- Left Side --}}
        <x-card class="rounded-lg">
          <x-slot:header>
            <h1 class="text-xl font-semibold text-slate-700 dark:text-slate-300 ">
              {{ t('message_bot') }}
            </h1>
          </x-slot:header>
          <x-slot:content>
            <div class="col-span-3">
              <div class="flex items-center justify-start gap-1">
                <span class="text-red-500">*</span>
                <x-label class="mt-[2px]" for="bot_name" :value="t('bot_name')" class="mt-[2px]" />
              </div>
              <x-input wire:model.defer="bot_name" id="bot_name" type="text"
                class="block w-full" autocomplete="off" />
              <x-input-error for="bot_name" class="mt-2" />
            </div>

            <span x-data="{ relationType: @entangle('relation_type') }">
              <div class="mt-4">
                <div class="flex item-centar justify-start ">
                  <span class="text-red-500 me-1 ">*</span>
                  <x-label class="mt-[2px]" for="relation_type" :value="t('relation_type')" />
                </div>
                <div wire:ignore>
                  <x-select x-model="relationType" id="relation_type_select"
                    x-on:change="handleTributeEvent()" class="tom-select mt-1 block w-full"
                    wire:model="relation_type"
                    wire:change="$set('relation_type', $event.target.value)">
                    @foreach (\App\Enums\WhatsAppTemplateRelationType::getRelationType() as $key => $relationType)
                      <option value="{{ $key }}">{{ ucfirst($relationType) }}</option>
                    @endforeach

                  </x-select>
                </div>
                <x-input-error for="relation_type" class="mt-2" />
              </div>

              <div x-data="{ text: @entangle('reply_text') }" class="col-span-3 mt-3">
                <div class="flex justify-between">
                  <div class="flex items-center justify-start gap-1">
                    <x-heroicon-o-question-mark-circle
                      class="me-1 dark:text-slate-200 hidden h-5 lg:h-5 sm:block"
                      data-tippy-content="{{ t('data_tippy_content') }}" />
                    <span class="text-red-500">*</span>
                    <x-label class="mt-[2px]" for="reply_text" :value="t('reply_text')" />
                  </div>
                  <!-- Live character count -->
                  <span class="text-sm text-slate-700 dark:text-slate-200">
                    <span x-text="text.length"></span>/1024
                  </span>
                </div>
                <x-textarea x-model="text" x-init='handleTributeEvent()' wire:model="reply_text"
                   type="text" rows="7" id="reply_text"
                  class="mentionable mt-1" />
                <x-input-error for="reply_text" class="mt-2" />
              </div>
              <span x-data="{ replyType: @entangle('reply_type') }">
                <div class="mt-4">
                  <div class="flex item-centar justify-start ">
                    <span class="text-red-500 me-1 ">*</span>
                    <x-label for="reply_type" :value="t('reply_type')" />
                  </div>
                  <div wire:ignore>
                    <x-select x-model="replyType" id="reply_type"
                      class=" mt-1 block w-full subtext-select" wire:model.defer="reply_type">
                      @foreach (\App\Enums\WhatsAppTemplateRelationType::getReplyType() as $key => $replyType)
                        <option value="{{ $key }}"
                          data-subtext="{{ $replyType['subtext'] }}">
                          {{ ucfirst($replyType['label']) }} </option>
                      @endforeach
                    </x-select>
                  </div>
                  <x-input-error for="reply_type" class="mt-2" />
                </div>
                <template x-if="replyType==1 || replyType==2">
                  <div class="col-span-3  mt-3">
                    <div class="flex items-center justify-start gap-1">
                      <span class="text-red-500">*</span>
                      <x-label class="mt-[2px]" for="trigger_keyword" :value="t('trigger_keyword')" />
                    </div>
                    <div x-data="{
                        tags: @entangle('trigger_keyword'),
                        newTag: '',
                        errorMessage: '',

                        purifyInput(input) {
                            let tempDiv = document.createElement('div');
                            tempDiv.textContent = input; // Remove potential HTML tags
                            return tempDiv.innerHTML.trim();
                        },

                        addTag() {
                            let tag = this.purifyInput(this.newTag);

                            // Prevent empty input
                            if (!tag) return;

                            // Patterns for SQL & JSON injection
                            let upper = tag.toUpperCase();
                            let sqlKeywords = ['SELECT', 'INSERT', 'DELETE', 'DROP', 'UNION', 'WHERE', 'HAVING'];
                            let injectionPattern = /(\{.*\}|\[.*\])|[\<\>\&\'\\\;]/;

                            // Check for SQL injection, JSON injection, or unsafe characters
                            if (sqlKeywords.some(k => upper.includes(k)) || injectionPattern.test(tag)) {
                                this.errorMessage = '{{ t('sql_injection_error') }}';
                                return;
                            }

                            // Prevent duplicate entries
                            if (this.tags.includes(tag)) {
                                this.errorMessage = '{{ t('this_trigger_already_exists') }}';
                                return;
                            }

                            // Add the valid tag
                            this.tags.push(tag);
                            this.errorMessage = '';
                            this.newTag = '';
                        }
                    }">
                      <x-input type="text" x-model="newTag" x-on:keydown.enter.prevent="addTag()"
                        x-on:compositionend="addTag()" x-on:blur="addTag()"
                        placeholder="{{ t('type_and_press_enter') }}" autocomplete="off"
                        class="block w-full mt-1 border p-2" />

                      <div class="mt-2">
                        <template x-for="(tag, index) in tags" :key="index">
                          <span
                            class="bg-indigo-500 dark:bg-indigo-800 text-white mb-2 dark:text-gray-100 rounded-xl px-2 py-1 text-sm mr-2 inline-flex items-center">
                            <span x-text="tag"></span>
                            <button x-on:click="tags.splice(index, 1)"
                              class="ml-2 text-white dark:text-gray-100">&times;</button>
                          </span>
                        </template>
                      </div>
                      <!-- Error Message -->
                      <p x-show="errorMessage" class="text-red-500 text-sm mt-1"
                        x-text="errorMessage"></p>
                    </div>
                    <x-input-error for="trigger_keyword" class="mt-2" />
                  </div>
                </template>
                <template x-if="replyType==4">
                  <x-dynamic-alert type="warning" class="w-full mt-3">
                    <p class="text-sm">
                      <span class="font-medium"> {{ t('note') }}</span>
                      {{ t('increase_webhook_note') }} <a
                        href="https://docs.corbitaltech.dev/products/whatsmark/" target="blank"
                        class="underline">{{ t('link') }}</a>
                    </p>
                  </x-dynamic-alert>
                </template>

                <div x-data="{ headerText: @entangle('header') }" class="col-span-3 mt-3">
                  <div class="flex justify-between">
                    <div class="flex">
                      <x-heroicon-o-question-mark-circle class="me-1 dark:text-slate-200"
                        height="20px"
                        data-tippy-content="{{ t('max_allowed_character_60') }}" />
                      <x-label class="mt-[2px]" for="header" :value="t('header')" />
                    </div>
                    <!-- Live character counter -->
                    <span class="text-sm text-slate-700 dark:text-slate-200">
                      <span x-text="headerText.length"></span>/60
                    </span>
                  </div>
                  <!-- Input field with live binding -->
                  <x-input x-model="headerText" wire:model.defer="header" id="header"
                    type="text" class="block w-full mt-1" autocomplete="off" />
                  <x-input-error for="header" class="mt-2" />
                </div>
                <div x-data="{ footerText: @entangle('footer') }" class="col-span-3 mt-3">
                  <div class="flex justify-between">
                    <div class="flex">
                      <x-heroicon-o-question-mark-circle class="me-1 dark:text-slate-200"
                        height="20px"
                        data-tippy-content="{{ t('max_allowed_character_60') }}" />
                      <x-label class="mt-[2px]" for="footer" :value="t('footer')" />
                    </div>
                    <span class="text-sm text-slate-700 dark:text-slate-200">
                      <span x-text="footerText.length"></span>/60
                    </span>
                  </div>
                  <x-input x-model="footerText" wire:model.defer="footer" id="footer"
                    type="text" class="block w-full mt-1" autocomplete="off" />
                  <x-input-error for="footer" class="mt-2" />
                </div>

          </x-slot:content>
        </x-card>

        {{-- Right Side --}}
        <x-card class="rounded-lg">
          <x-slot:header>
            <h1 class="text-xl font-semibold text-slate-700 dark:text-slate-300 ">
              {{ t('message_bot_options') }}
            </h1>
          </x-slot:header>
          <x-slot:content>
            <div x-data="{ activeTab: 'option1' }" class="space-y-6">
              <!-- Tab Navigation -->
              <div class="border-b dark:border-slate-600 flex items-center relative">
                <div id="leftscroll" class="dark:text-white sm:hidden">
                  <x-heroicon-s-chevron-left class="w-5 h-5"
                    x-on:click="$refs.tabList.scrollBy({ left: -150, behavior: 'smooth' })" />
                </div>
                <nav class="flex pr-12 gap-4 overflow-hidden whitespace-nowrap flex-nowrap"
                  x-ref="tabList"
                  x-on:keydown.right.prevent="$refs.tabList.scrollBy({ left: 150, behavior: 'smooth' })"
                  x-on:keydown.left.prevent="$refs.tabList.scrollBy({ left: -150, behavior: 'smooth' })">
                  <!-- Navigation buttons -->
                  <button type="button" x-on:click="activeTab = 'option1'"
                    :class="{
                        'border-b-2 border-indigo-500 text-indigo-500  dark:text-indigo-500': activeTab === 'option1',
                        'border-b-2 border-red-500 text-red-600': activeTab !== 'option1' &&
                            {{ $errors->hasAny([
                                'button1',
                                'button2',
                                'button3',
                                'button1_id',
                                'button2_id',
                                'button3_id',
                            ])
                                ? 'true'
                                : 'false' }}
                    }"
                    class="px-4 py-2 text-sm font-medium dark:text-slate-300 hover:text-primary-500">
                    {{ t('reply_button') }}
                  </button>
                  <button type="button" x-on:click="activeTab = 'option2'"
                    :class="{
                        'border-b-2 border-indigo-500 text-indigo-500 dark:text-indigo-500': activeTab === 'option2',
                        'border-b-2 border-red-500 text-red-600': activeTab !== 'option2' &&
                            {{ $errors->hasAny(['button_name']) ? 'true' : 'false' }}
                    }"
                    class="px-4 py-2 text-sm font-medium dark:text-slate-300 hover:text-primary-500">
                    {{ t('cta_url') }}
                  </button>
                  <button type="button" x-on:click="activeTab = 'option3'"
                    :class="{
                        'border-b-2 border-indigo-500 text-indigo-500 dark:text-indigo-500': activeTab === 'option3',
                        'border-b-2 border-red-500 text-red-600': activeTab !== 'option3' &&
                            {{ $errors->hasAny(['file_upload']) ? 'true' : 'false' }}
                    }"
                    class="px-4 py-2 text-sm font-medium dark:text-slate-300 hover:text-primary-500">
                    {{ t('file_upload') }}
                  </button>
                </nav>
                <div id="rightscroll" class="dark:text-white sm:hidden ml-auto">
                  <x-heroicon-s-chevron-right class="w-5 h-5"
                    x-on:click="$refs.tabList.scrollBy({ left: 150, behavior: 'smooth' })" />
                </div>
              </div>

              <!-- Tab Contents -->
              <div>
                <!-- Option 1 - Reply Buttons -->
                <div x-show="activeTab === 'option1'" class="space-y-4 ">
                  <!-- Original Option 1 Content Here -->
                  <div class="text-slate-700 dark:text-slate-200">
                    <h1>{{ t('reply_button_option1') }} </h1>
                    <div x-data="{ button1Text: @entangle('button1'), button1IdText: @entangle('button1_id') }"
                      class="grid gap-4 mt-3 sm:grid-cols-1 md:grid-cols-1 lg:grid-cols-2">
                      <div>
                        <div class="flex justify-between">
                          <div class="flex">
                            <x-heroicon-o-question-mark-circle
                              class="me-1 sm:mt-2 dark:text-slate-200" height="20px"
                              data-tippy-content="{{ t('max_allowed_char_20') }}" />
                            <x-label class="mt-[2px]" for="button1" :value="t('button1')"
                              class="sm:mt-px sm:pt-2" />
                          </div>
                          <span class="text-sm text-slate-700 dark:text-slate-200">
                            <span x-text="button1Text.length"></span>/20
                          </span>
                        </div>
                        <x-input x-model="button1Text" wire:model.defer="button1" id="button1"
                          type="text" class="mt-2 sm:mt-1" autocomplete="off" />

                        <x-input-error for="button1" class="mt-2" />
                      </div>

                      <!-- Button 1 ID Input with Counter -->
                      <div>
                        <div class="flex justify-between">
                          <div class="flex">
                            <x-heroicon-o-question-mark-circle
                              class="me-1 sm:mt-2 dark:text-slate-200" height="20px"
                              data-tippy-content="{{ t('max_allow_char_256') }}" />
                            <x-label class="mt-[2px]" for="button1_id" :value="t('button1_id')"
                              class="sm:mt-px sm:pt-2" />
                          </div>
                          <span class="text-sm text-slate-700 dark:text-slate-200">
                            <span x-text="button1IdText.length"></span>/256
                          </span>
                        </div>
                        <x-input x-model="button1IdText" wire:model.defer="button1_id"
                          id="button1_id" type="text" class="mt-2 sm:mt-1"
                          autocomplete="off" />
                        <x-input-error for="button1_id" class="mt-2" />
                      </div>
                    </div>
                    <div x-data="{ button2Text: @entangle('button2'), button2IdText: @entangle('button2_id') }"
                      class="grid gap-4 mt-3 sm:grid-cols-1 md:grid-cols-1 lg:grid-cols-2">
                      <!-- Button 2 Input with Counter -->
                      <div>
                        <div class="flex justify-between">
                          <div class="flex">
                            <x-heroicon-o-question-mark-circle
                              class="me-1 sm:mt-2 dark:text-slate-200" height="20px"
                              data-tippy-content="{{ t('max_allowed_char_20') }}" />
                            <x-label class="mt-[2px]" for="button2" :value="t('button2')"
                              class="sm:mt-px sm:pt-2" />
                          </div>
                          <span class="text-sm text-slate-700 dark:text-slate-200">
                            <span x-text="button2Text.length"></span>/20
                          </span>
                        </div>
                        <x-input x-model="button2Text" wire:model.defer="button2" id="button2"
                          type="text" class="mt-2 sm:mt-1" autocomplete="off" />
                        <x-input-error for="button2" class="mt-2" />
                      </div>

                      <!-- Button 2 ID Input with Counter -->
                      <div>
                        <div class="flex justify-between">
                          <div class="flex">
                            <x-heroicon-o-question-mark-circle
                              class="me-1 sm:mt-2 dark:text-slate-200" height="20px"
                              data-tippy-content="{{ t('max_allow_char_256') }}" />
                            <x-label class="mt-[2px]" for="button2_id" :value="t('button2_id')"
                              class="sm:mt-px sm:pt-2" />
                          </div>
                          <span class="text-sm text-slate-700 dark:text-slate-200">
                            <span x-text="button2IdText.length"></span>/256
                          </span>
                        </div>
                        <x-input x-model="button2IdText" wire:model.defer="button2_id"
                          id="button2_id" type="text" class="mt-2 sm:mt-1"
                          autocomplete="off" />
                        <x-input-error for="button2_id" class="mt-2" />
                      </div>
                    </div>

                    <div x-data="{ button3Text: @entangle('button3'), button3IdText: @entangle('button3_id') }"
                      class="grid gap-4 mt-3 sm:grid-cols-1 md:grid-cols-1 lg:grid-cols-2">
                      <!-- Button 3 Input with Counter -->
                      <div>
                        <div class="flex justify-between">
                          <div class="flex">
                            <x-heroicon-o-question-mark-circle
                              class="me-1 sm:mt-2 dark:text-slate-200" height="20px"
                              data-tippy-content="{{ t('max_allowed_char_20') }}" />
                            <x-label class="mt-[2px]" for="button3" :value="t('button3')"
                              class="sm:mt-px sm:pt-2" />
                          </div>
                          <span class="text-sm text-slate-700 dark:text-slate-200">
                            <span x-text="button3Text.length"></span>/20
                          </span>
                        </div>
                        <x-input x-model="button3Text" wire:model.defer="button3" id="button3"
                          type="text" class="mt-2 sm:mt-1" />
                        <x-input-error for="button3" class="mt-2" />
                      </div>

                      <!-- Button 3 ID Input with Counter -->
                      <div>
                        <div class="flex justify-between">
                          <div class="flex">
                            <x-heroicon-o-question-mark-circle
                              class="me-1 sm:mt-2 dark:text-slate-200" height="20px"
                              data-tippy-content="{{ t('max_allow_char_256') }}" />
                            <x-label class="mt-[2px]" for="button3_id" :value="t('button3_id')"
                              class="sm:mt-px sm:pt-2" />
                          </div>
                          <span class="text-sm text-slate-700 dark:text-slate-200">
                            <span x-text="button3IdText.length"></span>/256
                          </span>
                        </div>
                        <x-input x-model="button3IdText" wire:model.defer="button3_id"
                          id="button3_id" type="text" class="mt-2 sm:mt-1"
                          autocomplete="off" />
                        <x-input-error for="button3_id" class="mt-2" />
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Option 2 - Single Button -->
                <div x-show="activeTab === 'option2'">
                  <div class=" text-slate-700 dark:text-slate-200">
                    </h6> {{ t('option2_button_name') }}</h6>
                  </div>
                  <div class="border-slate-200  dark:border-slate-600 ">
                    <div x-data="{ buttonNameText: @entangle('button_name') }" class="col-span-3 mt-3">
                      <div class="flex justify-between">
                        <div class="flex">
                          <x-heroicon-o-question-mark-circle
                            class="me-1 sm:mt-2 dark:text-slate-200" height="20px"
                            data-tippy-content="{{ t('max_allowed_char_20') }}" />
                          <x-label class="mt-[2px]" for="button_name" :value="t('button_name')"
                            class="sm:mt-px sm:pt-2" />
                        </div>
                        <span class="text-sm text-slate-700 dark:text-slate-200">
                          <span x-text="buttonNameText.length"></span>/20
                        </span>
                      </div>
                      <x-input x-model="buttonNameText" wire:model.defer="button_name"
                        id="button_name" type="text" class="block w-full mt-1"
                        autocomplete="off" />
                      <x-input-error for="button_name" class="mt-2" />
                    </div>
                    <div class="col-span-3 mt-3">
                      <x-label class="mt-[2px]" for="button_link" :value="t('button_link')" />
                      <x-input wire:model.defer="button_link" id="button_link" type="text"
                        class="block w-full mt-1" placeholder="https://" autocomplete="off" />
                      <x-input-error for="button_link" class="mt-2" />
                    </div>
                  </div>
                </div>

                <div x-show="activeTab === 'option3'" class="mb-9">
                  <div x-data="{
                      fileType: @entangle('fileType'),
                      previewUrl: '{{ !empty($file_upload) && is_string($file_upload) ? asset('storage/' . $file_upload) : '' }}',
                      fileError: null,
                      fileName: '',
                      metaExtensions: {{ json_encode(get_meta_allowed_extension()) }}, // Fetch allowed extensions & sizes

                      isUploading: @entangle('isUploading'),
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

                      handleFilePreview(event) {
                          const file = event.target.files[0];
                          this.fileError = null;
                          this.previewUrl = '';
                          this.fileName = '';

                          if (!file) return;

                          // Get metadata based on file type
                          const metaData = this.metaExtensions[this.fileType];
                          if (!metaData) {
                              this.fileError = 'Invalid file type.';
                              return;
                          }

                          // Extract allowed extensions and max file size
                          const allowedExtensions = metaData.extension.split(',').map(ext => ext.trim());
                          const maxSizeMB = metaData.size || 0; // Default to 0MB if not set
                          const maxSizeBytes = maxSizeMB * 1024 * 1024; // Convert MB to bytes

                          // Extract file extension
                          const fileExtension = '.' + file.name.split('.').pop().toLowerCase();

                          // Validate file extension
                          if (!allowedExtensions.includes(fileExtension)) {
                              this.fileError = `Invalid file type. Allowed: ${allowedExtensions.join(', ')}`;
                              return;
                          }

                          // Validate file size
                          if (file.size > maxSizeBytes) {
                              this.fileError = `File size exceeds ${maxSizeMB} MB. Please upload a smaller file.`;
                              return;
                          }

                          // Set preview URL and filename
                          this.previewUrl = URL.createObjectURL(file);
                          this.fileName = file.name;

                          this.$wire.$set('file_upload', file);
                      },

                      resetPreview() {
                          this.previewUrl = '';
                          this.fileError = null;
                          this.fileName = '';

                          if (this.$refs.fileInput) {
                              this.$refs.fileInput.value = null;
                          }

                          if (this.$wire.file_upload) {
                              this.$wire.$set('file_upload', null);
                          }
                      }
                  }"
                    class="border-slate-200 space-y-4 dark:border-slate-600"
                    x-on:livewire-upload-start="uploadStarted()"
                    x-on:livewire-upload-finish="uploadFinished()"
                    x-on:livewire-upload-error="isUploading = false"
                    x-on:livewire-upload-progress="updateProgress($event.detail.progress)">

                    <!-- File Type Selection -->
                    <div class="col-span-3">
                      <x-label class="mt-[2px]" for="file_type" :value="t('choose_file_type')" />
                      <div wire:ignore>
                        <x-select id="file_type" class="block w-full mt-1 tom-select"
                          x-model="fileType" x-on:change="resetPreview()">
                          <option value="image">{{ t('image') }}</option>
                          <option value="document">{{ t('document') }}</option>
                          <option value="video">{{ t('video') }}</option>
                        </x-select>
                      </div>
                      <x-input-error for="file_type" class="mt-2" />
                    </div>

                    <!-- File Upload Section -->
                    <div class="col-span-3">
                      <x-label class="mt-[2px]" for="file_upload" x-show="!previewUrl"
                        x-text="`Allowed: ${metaExtensions[fileType] ? metaExtensions[fileType].extension : ''}`">
                      </x-label>
                      <div>
                        <div
                          class="relative mt-1 p-6 border-2 border-dashed rounded-lg cursor-pointer hover:border-blue-500 transition duration-300"
                          x-show="!previewUrl" x-on:click="$refs.fileInput.click()">
                          <div class="text-center">
                            <x-heroicon-s-photo class="h-12 w-12 text-gray-400 mx-auto" />
                            <p class="mt-2 text-sm text-gray-600">
                              {{ t('select_or_browse_to') }} <span
                                class="text-blue-600 underline">
                                <span x-text="fileType"></span>
                              </span>
                            </p>
                          </div>
                          <input type="file" id="file_upload" x-ref="fileInput"
                            x-on:change="handleFilePreview"
                            :accept="fileType === 'image' ? '.jpeg,.png' :
                                fileType === 'document' ?
                                '.pdf, .doc, .docx, .txt, .xls, .xlsx, .ppt, .pptx' :
                                '.mp4, .3gp'"
                            wire:model="file_upload" class="hidden" />
                        </div>
                        <p x-show="fileError" class="text-red-500 text-sm mt-1"
                          x-text="fileError"></p>
                      </div>

                      <!-- File Preview Section -->
                      <div class="mt-3 w-[320px]" x-show="previewUrl">
                        <x-label for="filePreview" :value="t('preview')" />
                        <div class="relative">
                          <template x-if="fileType === 'image'" >
                            <a :href="previewUrl" class="glightbox" x-effect="if (previewUrl) { setTimeout(() => initGLightbox(), 100); }">
                              <img :src="previewUrl" alt="Preview"
                                  class="max-w-[200px] rounded-lg shadow-md cursor-pointer" />
                          </a>
                          </template>
 
                          <template x-if="fileType === 'video'">
                            <video :src="previewUrl" controls
                              class="max-w-[300px] rounded-lg shadow-md cursor-pointer"></video>
                          </template>

                          <template x-if="fileType === 'document'">
                            <div class="flex items-center space-x-2 p-2 rounded-lg">
                              <x-heroicon-s-document class="h-6 w-6 text-gray-500" />
                              <a :href="previewUrl" target="_blank"
                                class="text-blue-600 underline">
                                {{ t('preview_document') }}
                              </a>
                            </div>
                          </template>
                          <div x-show="isUploading" class="relative mt-2">
                            <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                              <div
                                class="bg-blue-600 h-2.5 rounded-full transition-all duration-300"
                                :style="'width: ' + progress + '%'"></div>
                            </div>
                          </div>
                          <button type="button" x-on:click="resetPreview()"
                            class="absolute left-full top-0">
                            <x-feathericon-x-circle class="w-5 h-5 text-red-500" />
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </x-slot:content>
          <x-slot:footer>
            <div x-data="{ isUploading: @entangle('isUploading') }" class="dark:bg-transparent rounded-b-lg flex justify-end">
                <x-button.loading-button type="submit" target="save" x-bind:disabled="isUploading" x-bind:class="{ 'opacity-50 cursor-not-allowed': isUploading }">
                    {{ $message_bot->exists ? t('update_button') : t('add_button') }}
                </x-button.loading-button>
            </div>
          </x-slot:footer>
        </x-card>
      </div>
    </form>
  </div>
</div>
@push('scripts')
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      let selectElement = document.querySelector(".subtext-select");
      if (selectElement) {
        window.initTomSelect(".subtext-select", {
          allowEmptyOption: true,
          render: {
            option: function(data, escape) {
              return `
                        <div>
                            <span class="font-medium text-sm">${escape(data.text)}</span>
                            <div class="text-gray-500 text-xs">${escape(data.subtext || "")}</div>
                        </div>
                    `;
            },
            item: function(data, escape) {
              return `<div>${escape(data.text)}</div>`;
            }
          }
        });
      }
    });
  </script>
@endpush
