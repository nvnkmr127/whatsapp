<div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-5xl mx-auto">
    <div class="mb-8">
        <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Stop Bot Settings</h1>
        <a href="{{ route('automations.index') }}" class="text-indigo-500 hover:text-indigo-600">&larr; Back to Bots</a>
    </div>

    <div class="bg-white dark:bg-gray-800 shadow-lg rounded-sm border border-gray-200 dark:border-gray-700 p-6">
        <form wire:submit.prevent="save">
            <!-- Stop Bots Keyword -->
            <div class="mb-6">
                <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">
                    Stop Keywords <span class="text-red-500">*</span>
                </label>
                <div x-data="{ tags: @entangle('stop_bots_keyword'), newTag: '' }">
                    <div class="flex gap-2">
                        <input type="text" x-model="newTag"
                            x-on:keydown.enter.prevent="if(newTag) { tags.push(newTag); newTag = ''; }"
                            x-on:keydown.space.prevent="if(newTag) { tags.push(newTag); newTag = ''; }"
                            placeholder="Type keyword and press Enter"
                            class="form-input w-full bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700 rounded-md" />
                        <button type="button" x-on:click="if(newTag) { tags.push(newTag); newTag = ''; }"
                            class="btn bg-indigo-500 text-white rounded-md px-4">Add</button>
                    </div>

                    <div class="mt-2 flex flex-wrap gap-2">
                        <template x-for="(tag, index) in tags" :key="index">
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                <span x-text="tag"></span>
                                <button type="button" x-on:click="tags.splice(index, 1)"
                                    class="ml-1 text-red-600 dark:text-red-400 hover:text-red-800">&times;</button>
                            </span>
                        </template>
                    </div>
                </div>
                @error('stop_bots_keyword') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <!-- Restart Bots After -->
            <div class="mb-6 w-full md:w-1/2">
                <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">
                    Restart Bots After (Hours)
                </label>
                <div class="relative">
                    <input type="number" wire:model="restart_bots_after" min="0" step="0.1"
                        class="form-input w-full bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-700 rounded-md pr-12" />
                    <div
                        class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-gray-500 text-sm">
                        Hours
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-1">Leave empty to never auto-restart.</p>
                @error('restart_bots_after') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div class="flex justify-end">
                <button type="submit" class="btn bg-indigo-500 hover:bg-indigo-600 text-white rounded-md px-6 py-2">
                    Save Settings
                </button>
            </div>
        </form>
    </div>
</div>