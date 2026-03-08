<div class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow">
    <div class="mb-6">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Pipeline Stages</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage the stages of your sales pipeline.</p>
    </div>

    <ul wire:sortable="updateStageOrder" class="space-y-4">
        @foreach($stages as $stage)
            <li 
                wire:sortable.item="{{ $stage['id'] }}" 
                wire:key="stage-{{ $stage['id'] }}"
                class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 group"
            >
                <div class="flex items-center gap-3">
                    <div wire:sortable.handle class="cursor-move text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path></svg>
                    </div>
                    <div>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $stage['name'] }}</span>
                        <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">{{ $stage['probability'] }}% probability</span>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    @if(empty($stage['is_closed_won']) && empty($stage['is_closed_lost']))
                        <button 
                            wire:click="deleteStage({{ $stage['id'] }})"
                            class="text-red-500 hover:text-red-700 transition-colors opacity-0 group-hover:opacity-100"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    @endif
                </div>
            </li>
        @endforeach
    </ul>

    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
        <div class="flex gap-2">
            <x-input 
                wire:model="newStageName" 
                type="text" 
                placeholder="New stage name..." 
                class="block w-full"
                wire:keydown.enter="addStage"
            />
            <x-button wire:click="addStage">
                Add Stage
            </x-button>
        </div>
        <x-input-error for="newStageName" class="mt-2" />
    </div>
</div>
