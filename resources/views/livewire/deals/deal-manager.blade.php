<div class="h-full flex flex-col bg-gray-50 dark:bg-gray-900" x-data="{ 
    draggingDeal: null,
    dragOverStage: null 
}">
    <!-- Header -->
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex justify-between items-center">
        <div class="flex items-center gap-4">
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Deals</h1>
            
            <select wire:model.live="pipelineId" class="text-sm border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach($pipelines as $p)
                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex items-center gap-3">
            <div class="relative">
                <input type="text" wire:model.live.debounce.300ms="searchTerm" placeholder="Search deals..." class="w-64 pl-10 pr-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <div class="absolute left-3 top-2.5 text-gray-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </div>

            <button wire:click="$dispatch('openDealForm')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                New Deal
            </button>
        </div>
    </div>

    <!-- Kanban Board -->
    <div class="flex-1 overflow-x-auto overflow-y-hidden p-6">
        <div class="flex h-full gap-6 min-w-max">
            @foreach($stages as $stage)
                <div 
                    class="w-80 flex flex-col bg-gray-100 dark:bg-gray-800/50 rounded-xl border border-gray-200 dark:border-gray-700/50"
                    @dragover.prevent="dragOverStage = {{ $stage->id }}"
                    @dragleave="dragOverStage = null"
                    @drop="
                        $wire.updateDealStage(draggingDeal, {{ $stage->id }});
                        dragOverStage = null;
                        draggingDeal = null;
                    "
                    :class="{ 'ring-2 ring-indigo-500 bg-indigo-50 dark:bg-indigo-900/20': dragOverStage === {{ $stage->id }} }"
                >
                    <!-- Stage Header -->
                    <div class="p-3 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center bg-white dark:bg-gray-800 rounded-t-xl sticky top-0 z-10">
                        <div class="flex items-center gap-2">
                            <h3 class="font-semibold text-gray-700 dark:text-gray-200">{{ $stage->name }}</h3>
                            <span class="bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-xs px-2 py-0.5 rounded-full">{{ $stage->deals->count() }}</span>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            {{ money($stage->total_value) }}
                        </div>
                    </div>

                    <!-- Deals List -->
                    <div class="flex-1 overflow-y-auto p-3 space-y-3 custom-scrollbar">
                        @foreach($stage->deals as $deal)
                            <div 
                                draggable="true"
                                @dragstart="draggingDeal = {{ $deal->id }}"
                                @dragend="draggingDeal = null"
                                wire:key="deal-{{ $deal->id }}"
                                wire:click="$dispatch('openDealForm', { dealId: {{ $deal->id }} })"
                                class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 cursor-move hover:shadow-md transition-shadow group relative"
                            >
                                <div class="flex justify-between items-start mb-2">
                                    <h4 class="font-medium text-gray-900 dark:text-white group-hover:text-indigo-600 transition-colors">{{ $deal->title }}</h4>
                                    @if($deal->priority === 'high')
                                        <span class="w-2 h-2 rounded-full bg-red-500" title="High Priority"></span>
                                    @endif
                                </div>
                                
                                <div class="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-2">
                                    {{ $deal->contact->name ?? 'No Contact' }}
                                </div>

                                <div class="flex justify-between items-center text-xs border-t border-gray-100 dark:border-gray-700 pt-3 mt-2">
                                    <span class="font-semibold text-gray-900 dark:text-gray-200">
                                        {{ money($deal->value) }}
                                    </span>
                                    
                                    @if($deal->owner)
                                        <img src="{{ $deal->owner->profile_photo_url }}" alt="{{ $deal->owner->name }}" class="w-6 h-6 rounded-full" title="{{ $deal->owner->name }}">
                                    @endif
                                </div>
                                
                                @if($deal->expected_close_date)
                                    <div class="mt-2 flex items-center gap-1 text-xs {{ $deal->expected_close_date->isPast() ? 'text-red-500' : 'text-gray-400' }}">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        {{ $deal->expected_close_date->format('M d') }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                        
                        <!-- Quick Add Button -->
                        <button 
                            wire:click="$dispatch('openDealForm', { stageId: {{ $stage->id }} })"
                            class="w-full py-2 border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-lg text-gray-500 hover:border-indigo-500 hover:text-indigo-500 transition-colors flex justify-center items-center gap-1 text-sm font-medium"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Add Deal
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Deal Form Modal -->
    <livewire:deals.deal-form />
</div>
