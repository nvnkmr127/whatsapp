{{-- No @props needed for class-based components --}}

<div x-data="{ showModal: false }">

    {{-- Trigger (slot content) - Click to open modal --}}
    <div @click="showModal = true" class="cursor-pointer">
        {{ $slot }}
    </div>

    {{-- Large Modal --}}
    <div x-show="showModal" x-cloak x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" @click="showModal = false"
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">

        <div @click.stop x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-90" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-90"
            class="relative w-full max-w-4xl bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden max-h-[90vh] flex flex-col">

            {{-- Scrollable Container --}}
            <div class="flex-1 overflow-y-auto custom-scrollbar">

                {{-- Header with Avatar & Health Widget --}}
                <div
                    class="sticky top-0 z-10 p-8 pb-6 bg-white dark:bg-slate-900 bg-gradient-to-br from-wa-teal/10 to-emerald-500/5 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-4 flex-1">
                            <img src="https://api.dicebear.com/9.x/micah/svg?seed={{ $contact->name }}"
                                alt="{{ $contact->name }}"
                                class="w-20 h-20 rounded-[2rem] bg-slate-100 dark:bg-slate-800 object-cover shadow-2xl shadow-wa-teal/20">
                            <div class="flex-1">
                                <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-1">{{ $contact->name }}
                                </h3>
                                <p class="text-sm text-slate-500 font-medium mb-2">{{ $contact->phone_number }}</p>
                                <p class="text-xs text-slate-400 font-medium">{{ $contact->email ?: 'No email linked' }}
                                </p>
                            </div>
                        </div>
                        <button @click="showModal = false"
                            class="p-3 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- Health Widget --}}
                    <div class="flex items-center gap-4">
                        <x-contact-health-widget :contact="$contact" />
                    </div>
                </div>

                {{-- Content Grid --}}
                <div class="p-8 grid grid-cols-1 lg:grid-cols-2 gap-8">

                    {{-- Left Column: Contact Insights --}}
                    <div class="space-y-6 lg:col-span-1">

                        @php
    // $timeline, $mediaVault, $heatmap are now provided by the Component class
@endphp

                        {{-- Insight Tabs --}}
                        <div x-data="{ activeTab: 'messages' }" class="bg-white dark:bg-slate-800/50 rounded-[2rem] border border-slate-100 dark:border-slate-800 overflow-hidden shadow-sm">
                            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center gap-6 overflow-x-auto scrollbar-hide">
                                <button @click="activeTab = 'messages'" 
                                    :class="activeTab === 'messages' ? 'text-wa-teal border-b-2 border-wa-teal font-black pb-2' : 'text-slate-400 font-bold pb-2 hover:text-slate-600'"
                                    class="text-[10px] uppercase tracking-widest transition-all whitespace-nowrap">History</button>
                                <button @click="activeTab = 'timeline'" 
                                    :class="activeTab === 'timeline' ? 'text-wa-teal border-b-2 border-wa-teal font-black pb-2' : 'text-slate-400 font-bold pb-2 hover:text-slate-600'"
                                    class="text-[10px] uppercase tracking-widest transition-all whitespace-nowrap">Timeline</button>
                                <button @click="activeTab = 'vault'" 
                                    :class="activeTab === 'vault' ? 'text-wa-teal border-b-2 border-wa-teal font-black pb-2' : 'text-slate-400 font-bold pb-2 hover:text-slate-600'"
                                    class="text-[10px] uppercase tracking-widest transition-all whitespace-nowrap">Media</button>
                                <button @click="activeTab = 'heatmap'" 
                                    :class="activeTab === 'heatmap' ? 'text-wa-teal border-b-2 border-wa-teal font-black pb-2' : 'text-slate-400 font-bold pb-2 hover:text-slate-600'"
                                    class="text-[10px] uppercase tracking-widest transition-all whitespace-nowrap">Engagement</button>
                            </div>


                            <div class="p-6">
                                <!-- Messages Tab -->
                                <div x-show="activeTab === 'messages'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2">
                                    <div class="space-y-3 max-h-80 overflow-y-auto custom-scrollbar pr-2">
                                        @php
    // $directMessages is now provided by the Component class
@endphp
                                        @forelse($directMessages as $msg)
                                            <div class="flex {{ $msg->direction === 'outbound' ? 'justify-end' : 'justify-start' }}">
                                                <div class="max-w-[85%] {{ $msg->direction === 'outbound' ? 'bg-wa-teal text-white rounded-l-2xl rounded-tr-2xl' : 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-r-2xl rounded-tl-2xl' }} px-4 py-2 shadow-sm">
                                                    <p class="text-xs font-semibold leading-relaxed">{{ $msg->content ?: 'Media Object' }}</p>
                                                    <span class="text-[8px] opacity-70 block mt-1 text-right">{{ $msg->created_at->diffForHumans() }}</span>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="text-center py-8 text-slate-400 text-xs italic">No messages sent yet</div>
                                        @endforelse
                                    </div>
                                </div>
                                <!-- Timeline Tab -->
                                <div x-show="activeTab === 'timeline'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2">
                                    <div class="relative pl-4 space-y-6 before:absolute before:left-0 before:top-2 before:bottom-0 before:w-0.5 before:bg-slate-100 dark:before:bg-slate-800">
                                        @forelse($timeline as $item)
                                            <div class="relative">
                                                <div class="absolute -left-[1.375rem] top-1.5 w-3 h-3 rounded-full border-2 border-white dark:border-slate-900 shadow-sm
                                                    {{ $item['type'] === 'message' ? 'bg-wa-teal' : '' }}
                                                    {{ $item['type'] === 'note' ? 'bg-amber-400' : '' }}
                                                    {{ $item['type'] === 'order' ? 'bg-indigo-500' : '' }}
                                                    {{ $item['type'] === 'deal' ? 'bg-emerald-500' : '' }}
                                                    {{ $item['type'] === 'automation' ? 'bg-purple-500' : '' }}
                                                    {{ $item['type'] === 'event' || $item['type'] === 'activity_log' || $item['type'] === 'crm_activity' ? 'bg-slate-400' : '' }}
                                                "></div>
                                                <div class="flex flex-col gap-1">
                                                    <div class="flex items-center justify-between">
                                                        <span class="text-[9px] font-black uppercase tracking-wider text-slate-500">{{ $item['title'] }}</span>
                                                        <span class="text-[8px] font-bold text-slate-400">{{ $item['occurred_at']->diffForHumans() }}</span>
                                                    </div>
                                                    <p class="text-xs font-medium text-slate-600 dark:text-slate-300">{{ Str::limit($item['description'], 120) }}</p>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="text-center py-8 text-slate-400 text-xs italic">No activities yet</div>
                                        @endforelse
                                    </div>
                                </div>

                                <!-- Media Vault Tab -->
                                <div x-show="activeTab === 'vault'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2">
                                    <div class="grid grid-cols-3 gap-3">
                                        @forelse($mediaVault as $media)
                                            <div class="relative group aspect-square rounded-xl overflow-hidden bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-800 shadow-sm transition-all hover:scale-[1.05]">
                                                @if($media->type === 'image')
                                                    <img src="{{ $media->media_url }}" class="w-full h-full object-cover">
                                                @elseif($media->type === 'video')
                                                    <div class="w-full h-full flex items-center justify-center bg-slate-900">
                                                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                                    </div>
                                                @else
                                                    <div class="w-full h-full flex items-center justify-center">
                                                        <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                                    </div>
                                                @endif
                                            </div>
                                        @empty
                                            <div class="col-span-3 text-center py-12 text-slate-400 text-xs italic">No media assets found</div>
                                        @endforelse
                                    </div>
                                </div>

                                <!-- Heatmap Tab -->
                                <div x-show="activeTab === 'heatmap'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2">
                                    <div class="flex flex-col gap-1.5">
                                        {{-- Hour Labels --}}
                                        <div class="flex items-center gap-1 mb-1">
                                            <div class="w-8"></div>
                                            <div class="flex-1 flex justify-between px-0.5">
                                                <span class="text-[7px] text-slate-400 font-black">00h</span>
                                                <span class="text-[7px] text-slate-400 font-black">06h</span>
                                                <span class="text-[7px] text-slate-400 font-black">12h</span>
                                                <span class="text-[7px] text-slate-400 font-black">18h</span>
                                                <span class="text-[7px] text-slate-400 font-black">23h</span>
                                            </div>
                                        </div>
                                        @php $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']; @endphp
                                        @foreach($days as $index => $day)
                                            @php $dayNum = $index + 1; @endphp
                                            <div class="flex items-center gap-1 h-3">
                                                <div class="w-8 text-[8px] font-black text-slate-500 uppercase">{{ $day }}</div>
                                                <div class="flex-1 flex gap-0.5 h-full">
                                                    @for($h = 0; $h < 24; $h++)
                                                        @php
                                                            $count = $heatmap[$dayNum][$h] ?? 0;
                                                            $intensity = ($count > 0) ? min(1, 0.1 + ($count * 0.1)) : 0;
                                                            $color = $intensity > 0 ? "rgba(37, 211, 102, $intensity)" : "transparent";
                                                        @endphp
                                                        <div class="flex-1 rounded-[1px] border border-slate-100 dark:border-slate-800/10"
                                                            style="background-color: {{ $color }};"
                                                            title="{{ $day }} {{ $h }}:00 - {{ $count }} interactions"></div>
                                                    @endfor
                                                </div>
                                            </div>
                                        @endforeach
                                        <p class="mt-4 text-[8px] font-black text-slate-400 uppercase tracking-[0.3em] text-center">Engagement Map (24h Window)</p>
                                    </div>
                                </div>

                            </div>
                        </div>

                        {{-- Quick Metrics --}}
                        <div class="grid grid-cols-3 gap-3">
                            <div class="bg-slate-50 dark:bg-slate-800/30 rounded-2xl p-4 text-center border border-slate-100 dark:border-slate-800/50">
                                <div class="text-xl font-black text-slate-900 dark:text-white">{{ $contact->messages()->count() }}</div>
                                <div class="text-[8px] text-slate-400 font-black uppercase tracking-widest">Msgs</div>
                            </div>
                            <div class="bg-slate-50 dark:bg-slate-800/30 rounded-2xl p-4 text-center border border-slate-100 dark:border-slate-800/50">
                                <div class="text-xl font-black text-slate-900 dark:text-white">{{ $contact->conversations()->count() }}</div>
                                <div class="text-[8px] text-slate-400 font-black uppercase tracking-widest">Convos</div>
                            </div>
                            <div class="bg-slate-50 dark:bg-slate-800/30 rounded-2xl p-4 text-center border border-slate-100 dark:border-slate-800/50">
                                <div class="text-xl font-black text-slate-900 dark:text-white">{{ $contact->tags()->count() }}</div>
                                <div class="text-[8px] text-slate-400 font-black uppercase tracking-widest">Tags</div>
                            </div>
                        </div>
                    </div>

                    {{-- Right Column: Details & Tags --}}
                    <div class="space-y-6">
                        {{-- Tags & Classification --}}
                        <div class="bg-slate-50 dark:bg-slate-800/50 rounded-2xl p-6">
                            <h4 class="text-xs font-black uppercase tracking-widest text-slate-400 mb-4">Classification
                            </h4>

                            @if($contact->category)
                                <div class="mb-4">
                                    <span
                                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-black uppercase tracking-wider text-white shadow-lg"
                                        style="background-color: {{ $contact->category->color }}">
                                        @if($contact->category->icon)
                                            <i class="{{ $contact->category->icon }}"></i>
                                        @endif
                                        {{ $contact->category->name }}
                                    </span>
                                </div>
                            @endif

                            @if($contact->tags->isNotEmpty())
                                <div class="flex flex-wrap gap-2">
                                    @foreach($contact->tags as $tag)
                                        <span class="px-3 py-1.5 text-xs font-black uppercase tracking-wider rounded-lg"
                                            style="background-color: {{ $tag->color }}20; color: {{ $tag->color }}; border: 2px solid {{ $tag->color }}40">
                                            {{ $tag->name }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-4 text-slate-400 text-sm italic">No tags assigned</div>
                            @endif
                        </div>

                        {{-- Contact Status --}}
                        <div class="bg-slate-50 dark:bg-slate-800/50 rounded-2xl p-6">
                            <h4 class="text-xs font-black uppercase tracking-widest text-slate-400 mb-4">Status
                                Information
                            </h4>

                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-slate-600 dark:text-slate-400">Opt-in
                                        Status</span>
                                    <span class="flex items-center gap-2">
                                        <span
                                            class="w-2 h-2 rounded-full {{ $contact->opt_in_status === 'opted_in' ? 'bg-wa-teal' : 'bg-rose-500' }}"></span>
                                        <span
                                            class="text-sm font-black {{ $contact->opt_in_status === 'opted_in' ? 'text-wa-teal' : 'text-rose-500' }}">
                                            {{ ucfirst(str_replace('_', ' ', $contact->opt_in_status)) }}
                                        </span>
                                    </span>
                                </div>

                                @if($contact->last_interaction_at)
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-medium text-slate-600 dark:text-slate-400">Last
                                            Interaction</span>
                                        <span class="text-sm font-bold text-slate-900 dark:text-white">
                                            {{ $contact->last_interaction_at->diffForHumans() }}
                                        </span>
                                    </div>
                                @endif

                                @if($contact->has_pending_reply)
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-medium text-slate-600 dark:text-slate-400">Pending
                                            Reply</span>
                                        <span
                                            class="px-2 py-1 bg-wa-teal/10 text-wa-teal text-xs font-black uppercase rounded">Yes</span>
                                    </div>
                                @endif

                                @if($contact->is_within_24h_window)
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-medium text-slate-600 dark:text-slate-400">24h
                                            Window</span>
                                        <span
                                            class="px-2 py-1 bg-emerald-500/10 text-emerald-500 text-xs font-black uppercase rounded">Active</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Custom Attributes --}}
                        @if(!empty($contact->custom_attributes))
                            <div class="bg-slate-50 dark:bg-slate-800/50 rounded-2xl p-6">
                                <h4 class="text-xs font-black uppercase tracking-widest text-slate-400 mb-4">Additional
                                    Details
                                </h4>
                                <div class="space-y-3">
                                    @foreach($contact->custom_attributes as $key => $value)
                                        <div class="flex items-start justify-between">
                                            <span
                                                class="text-sm font-medium text-slate-600 dark:text-slate-400">{{ ucfirst(str_replace('_', ' ', $key)) }}</span>
                                            <span
                                                class="text-sm font-bold text-slate-900 dark:text-white text-right max-w-[60%]">{{ $value }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Action Footer --}}
                <div
                    class="sticky bottom-0 p-6 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 flex gap-3">
                    <button wire:click="edit({{ $contact->id }})" @click="showModal = false"
                        class="flex-1 px-6 py-4 bg-white dark:bg-slate-700 hover:bg-slate-50 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 text-sm font-bold uppercase tracking-wider rounded-2xl transition-all border border-slate-200 dark:border-slate-600 flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Edit Contact
                    </button>
                    <a href="{{ route('chat') }}?contact={{ $contact->id }}"
                        class="flex-[2] px-6 py-4 bg-wa-teal hover:bg-wa-dark text-white text-sm font-bold uppercase tracking-wider rounded-2xl transition-all shadow-xl shadow-wa-teal/20 hover:scale-[1.02] active:scale-95 flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z" />
                        </svg>
                        Open Chat
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
</div>