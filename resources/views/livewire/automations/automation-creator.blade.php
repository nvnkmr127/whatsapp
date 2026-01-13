<div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-5xl mx-auto">
    <div class="mb-8">
        <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Create {{ ucfirst($type) }} Bot</h1>
        <a href="{{ route('automations.index') }}" class="text-indigo-500 hover:text-indigo-600">&larr; Back to List</a>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow-lg rounded-sm border border-gray-200 dark:border-gray-700 p-6">
        <form wire:submit.prevent="save">
            <!-- Common Fields -->
            <div class="grid grid-cols-1 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">Bot Name <span
                            class="text-red-500">*</span></label>
                    <input wire:model="bot_name" type="text"
                        class="form-input w-full bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700 rounded-md"
                        required />
                    @error('bot_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">Trigger Keywords
                        <span class="text-red-500">*</span></label>
                    <div class="flex gap-2 mb-2">
                        <input x-data x-ref="keywordInput" type="text"
                            class="form-input w-full bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700 rounded-md"
                            placeholder="Type keyword and press Enter or Add"
                            @keydown.enter.prevent="$wire.addKeyword($refs.keywordInput.value); $refs.keywordInput.value = ''" />
                        <button type="button"
                            class="btn bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-md px-4"
                            @click="$wire.addKeyword($refs.keywordInput.value); $refs.keywordInput.value = ''">Add</button>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach($trigger_keywords as $index => $keyword)
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                                {{ $keyword }}
                                <button type="button" wire:click="removeKeyword({{ $index }})"
                                    class="ml-1 text-indigo-600 dark:text-indigo-400 hover:text-indigo-800">&times;</button>
                            </span>
                        @endforeach
                    </div>
                    @error('trigger_keywords') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="flex items-center">
                        <input type="checkbox" wire:model="is_active" class="form-checkbox" />
                        <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Active</span>
                    </label>
                </div>
            </div>

            <hr class="border-gray-200 dark:border-gray-700 my-6" />

            <!-- Type Specific Fields -->
            @if($type === 'keyword')
                <div class="grid grid-cols-1 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">Reply Text <span
                                class="text-red-500">*</span></label>
                        <textarea wire:model="reply_text" rows="4"
                            class="form-textarea w-full bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700 rounded-md"
                            required></textarea>
                        @error('reply_text') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        <p class="text-xs text-gray-500 mt-1">Accepts standard text.</p>
                    </div>
                </div>
            @elseif($type === 'template')
                <div class="grid grid-cols-1 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">Select Template <span
                                class="text-red-500">*</span></label>
                        <select wire:model.live="template_id"
                            class="form-select w-full bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700 rounded-md"
                            required>
                            <option value="">Select a template...</option>
                            @foreach($templates as $template)
                                <option value="{{ $template->template_id }}">{{ $template->template_name }}
                                    ({{ $template->language }})</option>
                            @endforeach
                        </select>
                        @error('template_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    @if($selectedTemplate)
                        <!-- Template Params (Simplified for MVP) -->
                        @if($selectedTemplate->header_params_count > 0)
                            <div>
                                <h4 class="text-sm font-bold mb-2">Header Variables</h4>
                                @for($i = 1; $i <= $selectedTemplate->header_params_count; $i++)
                                    <div class="mb-2">
                                        <label class="text-xs">Variable {{ $i }}</label>
                                        <input type="text" wire:model="header_params.{{ $i }}" class="form-input w-full text-sm"
                                            placeholder="Value for {{ '{{'.$i.'}}' }}" />
                                    </div>
                                @endfor
                            </div>
                        @endif

                        @if($selectedTemplate->body_params_count > 0)
                            <div>
                                <h4 class="text-sm font-bold mb-2">Body Variables</h4>
                                @for($i = 1; $i <= $selectedTemplate->body_params_count; $i++)
                                    <div class="mb-2">
                                        <label class="text-xs">Variable {{ $i }}</label>
                                        <input type="text" wire:model="body_params.{{ $i }}" class="form-input w-full text-sm"
                                            placeholder="Value for {{ '{{'.$i.'}}' }}" />
                                    </div>
                                @endfor
                            </div>
                        @endif
                    @endif
                </div>
            @endif

            <div class="flex justify-end mt-6">
                <button type="submit" class="btn bg-indigo-500 hover:bg-indigo-600 text-white rounded-md px-6 py-2">Save
                    Bot</button>
            </div>
        </form>
    </div>
</div>