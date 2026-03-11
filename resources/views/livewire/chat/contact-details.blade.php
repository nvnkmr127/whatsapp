<div
    class="h-full flex flex-col bg-white dark:bg-slate-950 border-l border-slate-100 dark:border-slate-900 transition-colors">
    <!-- Header -->
    <div class="px-6 py-4 border-b border-slate-50 dark:border-slate-800 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <div class="p-1.5 bg-wa-teal/10 text-wa-teal rounded-lg">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
            </div>
            <h1 class="text-xs font-black text-slate-900 dark:text-white tracking-tight uppercase">Profile</h1>
        </div>
        <div class="flex items-center gap-2">
            <button @click="$dispatch('toggle-details')"
                class="p-2 text-slate-400 hover:text-rose-500 transition-colors bg-slate-50 dark:bg-slate-800/50 rounded-lg">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    @if($contact)
        <div class="flex-1 overflow-y-auto custom-scrollbar p-0">
            <!-- Hero Profile Section -->
            <div
                class="p-6 flex flex-col items-center bg-slate-50/30 dark:bg-slate-900/20 border-b border-slate-100/50 dark:border-slate-900">
                <div class="relative group">
                    <div
                        class="absolute -inset-2 bg-gradient-to-tr from-wa-teal to-wa-teal rounded-full blur opacity-20 group-hover:opacity-40 transition-opacity">
                    </div>
                    <img src="https://api.dicebear.com/9.x/micah/svg?seed={{ $contact->name ?? 'Unknown' }}"
                        alt="{{ $contact->name }}"
                        class="relative h-16 w-16 rounded-2xl bg-white dark:bg-slate-800 object-cover shadow-xl transition-transform group-hover:scale-105">
                </div>

                <h4 class="mt-4 text-sm font-black text-slate-800 dark:text-white tracking-tight text-center">
                    {{ $contact->name }}
                </h4>
                <p class="text-[10px] font-bold text-slate-500 mt-1 uppercase tracking-wider">
                    {{ $contact->phone_number }}
                </p>

                <div class="mt-3 flex items-center gap-3">
                    <button wire:click="toggleOptIn" wire:loading.attr="disabled" class="px-2 py-0.5 text-[9px] font-black uppercase tracking-widest rounded-md border transition-all hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed
                                                            {{ $contact->opt_in_status === 'opted_in'
            ? 'bg-wa-teal/10 text-wa-teal border-wa-teal/20 hover:bg-rose-50 hover:text-rose-500 hover:border-rose-200'
            : 'bg-slate-100 dark:bg-slate-800 text-slate-500 border-slate-200 dark:border-slate-700 hover:bg-wa-teal/10 hover:text-wa-teal hover:border-wa-teal/20' 
                                                            }}">
                        <!-- Default Text -->
                        <span class="block {{ $contact->opt_in_status === 'opted_in' ? 'group-hover:hidden' : '' }}">
                            {{ $contact->opt_in_status === 'opted_in' ? 'OPTED IN' : 'OPTED OUT' }}
                        </span>
                    </button>

                    <livewire:chat.whatsapp-call-button :contact="$contact" :key="'call-btn-' . $contact->id" />

                    <span
                        class="w-1.5 h-1.5 rounded-full {{ $contact->opt_in_status === 'opted_in' ? 'bg-wa-teal animate-pulse' : 'bg-slate-300 dark:bg-slate-600' }}"></span>
                </div>
            </div>

            <!-- Dynamic Content Area -->
            <div class="p-6 space-y-8">
                <!-- Operational Assignment -->
                <section>
                    <h5
                        class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-tight mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        Assigned To
                    </h5>
                    <div
                        class="bg-slate-50 dark:bg-slate-900 rounded-2xl p-4 border-none flex items-center justify-between">
                        @if($conversation->assignee)
                            <div class="flex items-center gap-3">
                                <div
                                    class="h-8 w-8 rounded-xl bg-wa-teal/10 text-wa-teal flex items-center justify-center text-xs font-black uppercase">
                                    {{ substr($conversation->assignee->name, 0, 1) }}
                                </div>
                                <div class="flex flex-col">
                                    <span
                                        class="text-xs font-black text-slate-900 dark:text-white">{{ $conversation->assignee->name }}</span>
                                    <span class="text-[10px] font-bold text-slate-500 uppercase">Agent</span>
                                </div>
                            </div>
                            <button wire:click="unassign"
                                class="p-2 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-500/10 rounded-xl transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        @else
                            <span class="text-xs font-black text-slate-400 italic uppercase tracking-wider">Unassigned</span>
                            <button wire:click="assignToSelf"
                                class="px-4 py-2 bg-wa-teal text-white text-[10px] font-black uppercase tracking-widest rounded-xl shadow-lg shadow-wa-teal/20 hover:scale-105 transition-all">
                                Assign to Me
                            </button>
                        @endif
                    </div>
                </section>

                <section>
                    <h5
                        class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-tight mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                        Conversation Tags
                    </h5>
                    @php
                        $conversationTags = $conversation?->metadata['tags'] ?? [];
                    @endphp
                    <div class="space-y-3">
                        <!-- Active Tags Display -->
                        <div class="flex flex-wrap gap-2">
                            @forelse($conversation?->metadata['tags'] ?? [] as $tagId)
                                @php
                                    $category = \App\Models\Category::find($tagId);
                                @endphp
                                @if($category)
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider group"
                                        style="background-color: {{ $category->color }}20; color: {{ $category->color }}; border: 1px solid {{ $category->color }}40;">
                                        <span>{{ $category->name }}</span>
                                        <button wire:click="toggleConversationTag({{ $category->id }})"
                                            class="opacity-60 hover:opacity-100 transition-opacity">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </span>
                                @endif
                            @empty
                                <div
                                    class="w-full py-4 bg-slate-50 dark:bg-slate-900/50 rounded-xl border border-dashed border-slate-100 dark:border-slate-800 flex items-center justify-center">
                                    <span class="text-[10px] font-black text-slate-400 uppercase">No tags assigned</span>
                                </div>
                            @endforelse
                        </div>

                        <!-- Available Tags to Add -->
                        @php
                            $availableTags = \App\Models\Category::where('team_id', auth()->user()->currentTeam->id)
                                ->whereIn('target_module', ['chat', 'all'])
                                ->where('is_active', true)
                                ->whereNotIn('id', $conversationTags)
                                ->get();
                        @endphp
                        @if($availableTags->count() > 0)
                            <div class="pt-2 border-t border-slate-100 dark:border-slate-800">
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Add Tag</p>
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach($availableTags as $category)
                                        <button wire:click="toggleConversationTag({{ $category->id }})"
                                            class="px-2 py-1 rounded-lg text-[9px] font-bold uppercase tracking-wide transition-all hover:scale-105 active:scale-95"
                                            style="background-color: {{ $category->color }}10; color: {{ $category->color }}; border: 1px solid {{ $category->color }}20;">
                                            + {{ $category->name }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </section>

                <section x-data="{ showData: false }">
                    <div class="flex items-center justify-between mb-3">
                        <h5
                            class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-tight flex items-center gap-2">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                            </svg>
                            Custom Fields
                        </h5>
                        <button @click="showData = !showData"
                            class="text-[10px] font-black text-wa-teal uppercase tracking-widest hover:underline transition-all">
                            <span x-show="!showData">View Raw</span>
                            <span x-show="showData">Conceal</span>
                        </button>
                    </div>
                    <div x-show="showData" x-collapse>
                        <div
                            class="bg-slate-900 rounded-2xl p-4 text-[10px] font-mono text-slate-400 overflow-x-auto overflow-y-auto max-h-60 custom-scrollbar shadow-2xl">
                            @if($contact->custom_attributes)
                                <pre
                                    class="p-0 m-0">{{ json_encode($contact->custom_attributes, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                            @else
                                <span class="italic text-slate-600">No extended payload attributes.</span>
                            @endif
                        </div>
                    </div>
                    <div x-show="!showData"
                        class="py-4 bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-dotted border-slate-200 dark:border-slate-800 flex items-center justify-center gap-2">
                        <svg class="w-3 h-3 text-slate-300" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                                clip-rule="evenodd" />
                        </svg>
                        <span class="text-[10px] font-black text-slate-300 uppercase tracking-widest">Metadata
                            Encrypted</span>
                    </div>
                </section>

                    <div
                        class="bg-slate-50 dark:bg-slate-900 rounded-2xl p-4 border border-slate-100 dark:border-slate-800 flex items-center justify-center gap-2">
                        <svg class="w-3.5 h-3.5 text-slate-300" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                                clip-rule="evenodd" />
                        </svg>
                        <span class="text-[10px] font-black text-slate-300 uppercase tracking-widest">Metadata
                            Encrypted</span>
                    </div>
                </section>

                <!-- Profile Intelligence Tabs -->
                <section x-data="{ activeTab: @entangle('activeTab') }">
                    <div class="flex items-center gap-4 mb-6 border-b border-slate-100 dark:border-slate-800 pb-1">
                        <button @click="activeTab = 'timeline'"
                            :class="activeTab === 'timeline' ? 'text-wa-teal border-b-2 border-wa-teal font-black pb-2' : 'text-slate-400 font-bold pb-2 hover:text-slate-600'"
                            class="text-[10px] uppercase tracking-widest transition-all">Timeline</button>
                        <button @click="activeTab = 'vault'"
                            :class="activeTab === 'vault' ? 'text-wa-teal border-b-2 border-wa-teal font-black pb-2' : 'text-slate-400 font-bold pb-2 hover:text-slate-600'"
                            class="text-[10px] uppercase tracking-widest transition-all">Media Vault</button>
                        <button @click="activeTab = 'heatmap'"
                            :class="activeTab === 'heatmap' ? 'text-wa-teal border-b-2 border-wa-teal font-black pb-2' : 'text-slate-400 font-bold pb-2 hover:text-slate-600'"
                            class="text-[10px] uppercase tracking-widest transition-all">Heatmap</button>
                    </div>

                    <!-- Timeline Tab Content -->
                    <div x-show="activeTab === 'timeline'" x-cloak x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-y-2">
                        <div
                            class="relative pl-4 space-y-6 before:absolute before:left-0 before:top-2 before:bottom-0 before:w-0.5 before:bg-slate-100 dark:before:bg-slate-800">
                            @forelse($timeline as $item)
                                <div class="relative">
                                    <!-- Dot -->
                                    <div class="absolute -left-[1.375rem] top-1.5 w-3 h-3 rounded-full border-2 border-white dark:border-slate-950 shadow-sm
                                                                        {{ $item['type'] === 'message' ? 'bg-wa-teal' : '' }}
                                                                        {{ $item['type'] === 'note' ? 'bg-amber-400' : '' }}
                                                                        {{ $item['type'] === 'order' ? 'bg-indigo-500' : '' }}
                                                                        {{ $item['type'] === 'deal' ? 'bg-emerald-500' : '' }}
                                                                        {{ $item['type'] === 'automation' ? 'bg-purple-500' : '' }}
                                                                        {{ $item['type'] === 'event' || $item['type'] === 'activity_log' || $item['type'] === 'crm_activity' ? 'bg-slate-400' : '' }}
                                                                    "></div>

                                    <div class="flex flex-col gap-1">
                                        <div class="flex items-center justify-between">
                                            <span class="text-[10px] font-black uppercase tracking-wider
                                                                            {{ $item['type'] === 'message' ? 'text-wa-teal' : '' }}
                                                                            {{ $item['type'] === 'note' ? 'text-amber-500' : '' }}
                                                                            {{ $item['type'] === 'order' ? 'text-indigo-500' : '' }}
                                                                            {{ $item['type'] === 'deal' ? 'text-emerald-500' : '' }}
                                                                            {{ $item['type'] === 'automation' ? 'text-purple-500' : '' }}
                                                                            {{ $item['type'] === 'event' || $item['type'] === 'activity_log' || $item['type'] === 'crm_activity' ? 'text-slate-500' : 'text-slate-600' }}
                                                                        ">
                                                {{ $item['title'] }}
                                            </span>
                                            <span class="text-[9px] font-bold text-slate-400">
                                                {{ $item['occurred_at']->diffForHumans() }}
                                            </span>
                                        </div>

                                        @if($item['type'] === 'message')
                                            <div
                                                class="p-2.5 bg-slate-50 dark:bg-slate-900/50 rounded-xl border border-slate-100 dark:border-slate-800">
                                                <p class="text-xs font-medium text-slate-600 dark:text-slate-300 leading-relaxed italic">
                                                    "{{ Str::limit($item['description'], 100) }}"
                                                </p>
                                            </div>
                                        @elseif($item['type'] === 'note')
                                            <div
                                                class="p-3 bg-amber-50/50 dark:bg-amber-900/10 rounded-xl border border-amber-100/50 dark:border-amber-900/20">
                                                <p class="text-xs font-semibold text-slate-700 dark:text-slate-200">
                                                    {{ $item['description'] }}
                                                </p>
                                                @if($item['user'] ?? false)
                                                    <div class="mt-2 flex items-center gap-1.5">
                                                        <div
                                                            class="w-4 h-4 rounded-full bg-amber-200 flex items-center justify-center text-[8px] font-black text-amber-700">
                                                            {{ substr($item['user'], 0, 1) }}
                                                        </div>
                                                        <span
                                                            class="text-[9px] font-black text-amber-600 uppercase">{{ $item['user'] }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                        @elseif($item['type'] === 'order')
                                            <div
                                                class="p-3 bg-indigo-50/50 dark:bg-indigo-900/10 rounded-xl border border-indigo-100/50 dark:border-indigo-900/20 flex items-center justify-between">
                                                <div class="flex flex-col">
                                                    <span
                                                        class="text-xs font-bold text-indigo-700 dark:text-indigo-300">{{ $item['description'] }}</span>
                                                    <span class="text-[9px] font-black text-indigo-400 uppercase">Revenue Event</span>
                                                </div>
                                                <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                                </svg>
                                            </div>
                                        @elseif($item['type'] === 'deal')
                                            <div
                                                class="p-3 bg-emerald-50/50 dark:bg-emerald-900/10 rounded-xl border border-emerald-100/50 dark:border-emerald-900/20 flex items-center justify-between">
                                                <div class="flex flex-col">
                                                    <span
                                                        class="text-xs font-bold text-emerald-700 dark:text-emerald-300">{{ $item['description'] }}</span>
                                                    <span class="text-[9px] font-black text-emerald-400 uppercase">Pipeline Progress</span>
                                                </div>
                                                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                        @else
                                            <p class="text-xs font-medium text-slate-500 dark:text-slate-400">
                                                {{ $item['description'] }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div
                                    class="py-8 text-center bg-slate-50/50 dark:bg-slate-900/30 rounded-3xl border border-slate-100 dark:border-slate-800">
                                    <span class="text-[10px] font-black text-slate-300 uppercase italic tracking-widest">No
                                        activities
                                        detected.</span>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Media Vault Tab Content -->
                    <div x-show="activeTab === 'vault'" x-cloak x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-y-2">
                        <div class="grid grid-cols-3 gap-2">
                            @forelse($mediaVault as $media)
                                @if(in_array($media->type, ['image', 'video', 'document']))
                                    <div class="relative group aspect-square rounded-xl overflow-hidden bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-800 cursor-pointer hover:shadow-lg transition-all"
                                        title="{{ $media->caption }}">
                                        @if($media->type === 'image')
                                            <img src="{{ $media->media_url }}" class="w-full h-full object-cover">
                                        @elseif($media->type === 'video')
                                            <div class="w-full h-full flex items-center justify-center bg-slate-900">
                                                <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M8 5v14l11-7z" />
                                                </svg>
                                            </div>
                                        @else
                                            <div class="w-full h-full flex items-center justify-center">
                                                <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                        @endif
                                        <!-- Overlay -->
                                        <div
                                            class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                            <span class="text-[8px] font-black text-white uppercase">{{ $media->created_at->format('M d') }}</span>
                                        </div>
                                    </div>
                                @endif
                            @empty
                                <div
                                    class="col-span-3 py-12 text-center bg-slate-50/50 dark:bg-slate-900/30 rounded-3xl border border-slate-100 dark:border-slate-800">
                                    <span class="text-[10px] font-black text-slate-300 uppercase italic tracking-widest">No
                                        media found.</span>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Heatmap Tab Content -->
                    <div x-show="activeTab === 'heatmap'" x-cloak x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-y-2">
                        <div class="flex flex-col gap-1">
                            @php
                                $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                            @endphp
                            <div class="flex items-center gap-1 mb-2">
                                <div class="w-8"></div>
                                @foreach([0, 6, 12, 18] as $h)
                                    <div class="flex-1 text-[8px] text-slate-400 font-black uppercase text-center">{{ $h }}:00
                                    </div>
                                @endforeach
                            </div>
                            @foreach($days as $index => $day)
                                @php
                                    $dayNum = $index + 1; // 1 (Sun) to 7 (Sat)
                                @endphp
                                <div class="flex items-center gap-1 h-3">
                                    <div class="w-8 text-[8px] font-black text-slate-500 uppercase">{{ $day }}</div>
                                    <div class="flex-1 flex gap-0.5 h-full">
                                        @for($h = 0; $h < 24; $h++)
                                            @php
                                                $count = $heatmap[$dayNum][$h] ?? 0;
                                                $intensity = ($count > 0) ? min(100, $count * 20 + 10) : 0;
                                                $color = $intensity > 0 ? "rgba(34, 197, 94, " . ($intensity / 100) . ")" : "transparent";
                                                if ($intensity > 0 && $intensity < 20) $color = "rgba(34, 197, 94, 0.1)";
                                            @endphp
                                            <div class="flex-1 rounded-[1px] border border-slate-100 dark:border-slate-800/20"
                                                style="background-color: {{ $color }};"
                                                title="{{ $count }} events at {{ $h }}:00 on {{ $day }}"></div>
                                        @endfor
                                    </div>
                                </div>
                            @endforeach
                            <p class="mt-4 text-[9px] font-bold text-slate-400 uppercase tracking-widest text-center">Peak
                                Engagement Signal Intensity</p>
                        </div>
                    </div>

                    <div class="mt-8">
                        <h6 class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-3">Quick Note</h6>
                        <form wire:submit.prevent="addNote" class="relative group">
                            <textarea wire:model="newNoteBody"
                                class="w-full p-4 bg-slate-50 dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-800 rounded-2xl text-xs font-medium text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-wa-teal/20 focus:border-wa-teal transition-all min-h-[80px]"
                                placeholder="Add a note to this profile..."></textarea>
                            <div class="absolute right-3 bottom-3">
                                <button type="submit"
                                    class="p-2 bg-slate-900 dark:bg-wa-teal text-white dark:text-slate-900 rounded-xl shadow-lg hover:scale-110 active:scale-95 transition-all">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                    </svg>
                                </button>
                            </div>
                        </form>
                    </div>
                </section>
            </div>
        </div>
    @else
        <div class="flex-1 flex flex-col items-center justify-center p-12 text-center">
            <div
                class="w-20 h-20 bg-slate-50 dark:bg-slate-900 rounded-[2.5rem] flex items-center justify-center text-slate-200 dark:text-slate-800 mb-6">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
            </div>
            <h4 class="text-sm font-black text-slate-400 uppercase tracking-widest">Awaiting Contact Selection</h4>
            <p class="mt-2 text-xs font-medium text-slate-300 dark:text-slate-600">Select an active transmission to
                initialize intelligence profile synchronization.</p>
        </div>
    @endif
</div>