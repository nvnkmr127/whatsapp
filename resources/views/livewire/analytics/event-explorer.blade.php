<div class="p-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                Event Explorer
                @if($filterTraceId)
                    <span class="px-2 py-1 text-xs rounded bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-200">
                        Trace: {{ Str::limit($filterTraceId, 8) }}
                        <button wire:click="$set('filterTraceId', '')" class="ml-1 hover:text-indigo-900">&times;</button>
                    </span>
                @endif
            </h1>
            <p class="text-sm text-gray-500">System nervous system observability.</p>
        </div>
        
        <!-- Filters -->
        <div class="flex flex-wrap gap-2 items-center">
             <label class="inline-flex items-center cursor-pointer mr-2">
                <input type="checkbox" wire:model.live="showNoise" class="sr-only peer">
                <div class="relative w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                <span class="ms-2 text-xs font-medium text-gray-900 dark:text-gray-300">Show Noise</span>
            </label>

            <select wire:model.live="filterModule" class="border-gray-300 dark:bg-gray-800 dark:text-gray-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                <option value="">All Sources</option>
                @foreach($modules as $m)
                    <option value="{{ $m }}">{{ ucfirst($m) }}</option>
                @endforeach
            </select>

            <select wire:model.live="filterCategory" class="border-gray-300 dark:bg-gray-800 dark:text-gray-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                <option value="">All Categories</option>
                <option value="business">Business Signals</option>
                <option value="operational">Operational</option>
                <option value="debug">Debug</option>
            </select>
            
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search..." class="border-gray-300 dark:bg-gray-800 dark:text-gray-200 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" />
            
            @if($search || $filterModule || $filterCategory || $filterTraceId || $showNoise)
                <button wire:click="clearFilters" class="text-xs text-gray-500 underline hover:text-gray-700">Clear</button>
            @endif
        </div>
    </div>

    <!-- Events List -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-32">Time</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-24">Severity</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-40">Event Name</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Summary</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-24">Source</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-20">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($events as $event)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-750 transition duration-150 group">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ $event->occurred_at->diffForHumans() }}
                            <div class="hidden group-hover:block absolute bg-black text-white text-xs p-1 rounded mt-1 z-10">{{ $event->occurred_at->toIso8601String() }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ \App\Services\EventPresenter::badgeClass($event) }}">
                                {{ ucfirst($event->category) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100 font-medium">
                            <span title="{{ $event->event_type }}">{{ Str::limit(class_basename($event->event_type), 25) }}</span>
                        </td>
                         <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                           {{ \App\Services\EventPresenter::summary($event) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                           <span class="uppercase text-xs font-bold tracking-wider">{{ $event->source }}</span>
                        </td>
                         <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button wire:click="showDetails({{ $event->id }})" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-2">View</button>
                            @if($event->trace_id)
                                <button wire:click="viewTrace('{{ $event->trace_id }}')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200" title="Filter Trace"><svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg></button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                            No events found matching your criteria.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $events->links() }}
        </div>
    </div>

    <!-- Detail Modal (Trace Visualizer) -->
    @if($showTraceModal && $selectedEvent)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" wire:click="closeDetails"></div>
                
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-6xl sm:w-full h-[80vh] flex flex-col">
                    <!-- Header -->
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 flex justify-between items-center shrink-0">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="modal-title">
                            Event Details <span class="text-sm font-normal text-gray-500 ml-2">{{ $selectedEvent->event_type }}</span>
                        </h3>
                        <div class="text-sm text-gray-500">
                            Trace: <span class="font-mono bg-gray-200 dark:bg-gray-600 px-1 rounded">{{ $selectedEvent->trace_id ?? 'N/A' }}</span>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="flex-1 flex overflow-hidden">
                        <!-- Left: Journey Tree -->
                        <div class="w-1/3 border-r border-gray-200 dark:border-gray-700 flex flex-col bg-gray-50 dark:bg-gray-900">
                            <div class="p-3 border-b border-gray-200 dark:border-gray-700 font-semibold text-xs uppercase text-gray-500">Trace Timeline</div>
                            <div class="overflow-y-auto flex-1 p-2">
                                <ul class="space-y-0 relative">
                                    <!-- Vertical Line -->
                                    <div class="absolute left-4 top-2 bottom-2 w-0.5 bg-gray-200 dark:bg-gray-700"></div>

                                    @foreach($traceEvents as $te)
                                        <li class="relative pl-8 py-2 cursor-pointer group {{ $te->id === $selectedEvent->id ? 'bg-indigo-50 dark:bg-indigo-900/50 rounded' : '' }}" wire:click="showDetails({{ $te->id }})">
                                            <!-- Dot -->
                                            <div class="absolute left-[13px] top-4 w-2.5 h-2.5 rounded-full border-2 border-white dark:border-gray-800 {{ $te->category === 'business' ? 'bg-green-500' : 'bg-blue-400' }} z-10"></div>
                                            
                                            <div class="text-sm font-medium dark:text-gray-200 truncate pr-2 {{ $te->id === $selectedEvent->id ? 'text-indigo-700 dark:text-indigo-300' : '' }}">
                                                {{ class_basename($te->event_type) }}
                                            </div>
                                            <div class="text-xs text-gray-500 flex justify-between pr-2">
                                                <span>{{ $te->occurred_at->format('H:i:s.u') }}</span>
                                                @if($te->span_id === $te->event_id)<span class="text-[10px] bg-gray-200 px-1 rounded">ROOT</span>@endif
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        
                        <!-- Right: Inspector -->
                        <div class="w-2/3 flex flex-col bg-white dark:bg-gray-800 overflow-y-auto">
                            <div class="p-6">
                                <!-- Cards -->
                                <div class="grid grid-cols-2 gap-4 mb-6">
                                    <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded">
                                        <label class="block text-xs font-bold text-gray-500 uppercase">Actor</label>
                                        <div class="text-sm dark:text-gray-200">{{ $selectedEvent->actor_id ?? 'System' }}</div>
                                    </div>
                                    <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded">
                                        <label class="block text-xs font-bold text-gray-500 uppercase">Span ID</label>
                                        <div class="text-sm font-mono dark:text-gray-200">{{ $selectedEvent->span_id ?? '-' }}</div>
                                    </div>
                                </div>

                                <div x-data="{ tab: 'summary' }">
                                    <div class="border-b border-gray-200 dark:border-gray-700 mb-4">
                                        <nav class="-mb-px flex space-x-8">
                                            <button @click="tab = 'summary'" :class="{'border-indigo-500 text-indigo-600': tab === 'summary', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': tab !== 'summary'}" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                                Summary
                                            </button>
                                            <button @click="tab = 'raw'" :class="{'border-indigo-500 text-indigo-600': tab === 'raw', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': tab !== 'raw'}" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                                Raw Payload
                                            </button>
                                             <button @click="tab = 'meta'" :class="{'border-indigo-500 text-indigo-600': tab === 'meta', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': tab !== 'meta'}" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                                Metadata
                                            </button>
                                        </nav>
                                    </div>

                                    <div x-show="tab === 'summary'">
                                        <div class="bg-gray-50 dark:bg-gray-900 rounded p-4">
                                            <table class="min-w-full">
                                                @foreach($selectedEvent->payload as $key => $val)
                                                    @if(!is_array($val))
                                                    <tr>
                                                        <td class="px-2 py-1 text-xs font-bold text-gray-500 uppercase w-32">{{ $key }}</td>
                                                        <td class="px-2 py-1 text-sm dark:text-gray-300">{{ $val }}</td>
                                                    </tr>
                                                    @endif
                                                @endforeach
                                            </table>
                                        </div>
                                    </div>

                                    <div x-show="tab === 'raw'">
                                        <div class="bg-gray-900 text-green-400 p-4 rounded-md font-mono text-xs overflow-x-auto">
                                            <pre>{{ json_encode($selectedEvent->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                        </div>
                                    </div>
                                    
                                     <div x-show="tab === 'meta'">
                                        <div class="bg-gray-900 text-blue-300 p-4 rounded-md font-mono text-xs overflow-x-auto">
                                            <pre>{{ json_encode($selectedEvent->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Footer -->
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 flex justify-end shrink-0">
                         <button type="button" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" wire:click="closeDetails">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
