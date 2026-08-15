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
            <h1 class="text-xs font-black text-slate-900 dark:text-white tracking-tight uppercase">{{ __('Profile') }}</h1>
        </div>
        <div class="flex items-center gap-2">
            <button @click="$dispatch('toggle-details')"
                aria-label="{{ __('Close details') }}" title="{{ __('Close details') }}"
                class="p-2 text-slate-400 hover:text-rose-500 transition-colors bg-slate-50 dark:bg-slate-800/50 rounded-lg">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    @if($contact)
        <!-- Tabs -->
        <div class="px-6 flex border-b border-slate-50 dark:border-slate-900 bg-slate-50/20 dark:bg-slate-950/20">
            @foreach(['profile' => 'Profile', 'timeline' => 'Timeline', 'files' => 'Files'] as $tab => $label)
                <button wire:click="$set('activeTab', '{{ $tab }}')"
                    class="px-4 py-3 text-tiny font-black uppercase tracking-widest border-b-2 transition-all
                    {{ $activeTab === $tab ? 'border-wa-teal text-wa-teal' : 'border-transparent text-slate-400 hover:text-slate-600' }}">
                    {{ __($label) }}
                </button>
            @endforeach
        </div>

        <div class="flex-1 overflow-y-auto custom-scrollbar p-0">
            @if($activeTab === 'profile')
                <!-- Profile Overview -->
                <div
                    class="p-6 flex flex-col items-center bg-slate-50/30 dark:bg-slate-900/20 border-b border-slate-100/50 dark:border-slate-900">
                    <div class="relative group">
                        <img src="https://api.dicebear.com/9.x/micah/svg?seed={{ $contact->name ?? 'Unknown' }}"
                            alt="{{ $contact->name }}"
                            class="relative h-16 w-16 rounded-2xl bg-white dark:bg-slate-800 object-cover shadow-md transition-transform group-hover:scale-105">
                    </div>

                    @if($editing)
                        <div class="mt-4 w-full space-y-2">
                            <div>
                                <label class="text-nano font-black text-slate-400 uppercase tracking-widest">{{ __('Name *') }}</label>
                                <input wire:model="editName" type="text"
                                    class="mt-1 w-full px-3 py-2 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold text-slate-900 dark:text-white focus:ring-wa-teal/30 focus:border-wa-teal outline-none transition" />
                                @error('editName') <span class="text-nano text-rose-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="text-nano font-black text-slate-400 uppercase tracking-widest">{{ __('Email') }}</label>
                                <input wire:model="editEmail" type="email"
                                    class="mt-1 w-full px-3 py-2 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold text-slate-900 dark:text-white focus:ring-wa-teal/30 focus:border-wa-teal outline-none transition" />
                                @error('editEmail') <span class="text-nano text-rose-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="text-nano font-black text-slate-400 uppercase tracking-widest">{{ __('Notes') }}</label>
                                <textarea wire:model="editNotes" rows="2"
                                    class="mt-1 w-full px-3 py-2 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold text-slate-900 dark:text-white focus:ring-wa-teal/30 focus:border-wa-teal outline-none transition resize-none"></textarea>
                            </div>
                            <div class="flex gap-2 pt-1">
                                <button wire:click="saveContact"
                                    class="flex-1 py-2 bg-wa-teal text-white text-tiny font-black uppercase tracking-widest rounded-xl hover:opacity-90 transition-all">
                                    {{ __('Save') }}
                                </button>
                                <button wire:click="cancelEdit"
                                    class="flex-1 py-2 bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 text-tiny font-black uppercase tracking-widest rounded-xl hover:opacity-90 transition-all">
                                    {{ __('Cancel') }}
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="mt-4 flex items-center gap-2">
                            <h4 class="text-sm font-black text-slate-800 dark:text-white tracking-tight text-center">
                                {{ $contact->name }}
                            </h4>
                            <button wire:click="startEdit" aria-label="{{ __('Edit Contact') }}" title="{{ __('Edit Contact') }}"
                                class="p-1 text-slate-400 hover:text-wa-teal transition-colors rounded-lg hover:bg-wa-teal/10">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                            </button>
                        </div>
                        <p class="text-tiny font-bold text-slate-500 mt-1 uppercase tracking-wider">
                            {{ $contact->phone_number }}
                        </p>
                    @endif

                    <div class="mt-3 flex items-center gap-3">
                        <button wire:click="toggleOptIn" wire:loading.attr="disabled" class="px-2 py-0.5 text-nano font-black uppercase tracking-widest rounded-md border transition-all hover:scale-105 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed
                                                                {{ $contact->opt_in_status === 'opted_in'
                ? 'bg-wa-teal/10 text-wa-teal border-wa-teal/20 hover:bg-rose-50 hover:text-rose-500 hover:border-rose-200'
                : 'bg-slate-100 dark:bg-slate-800 text-slate-500 border-slate-200 dark:border-slate-700 hover:bg-wa-teal/10 hover:text-wa-teal hover:border-wa-teal/20' 
                                                                }}">
                            <span class="block">
                                {{ $contact->opt_in_status === 'opted_in' ? __('OPTED IN') : __('OPTED OUT') }}
                            </span>
                        </button>

                        <livewire:chat.whatsapp-call-button :contact="$contact" :key="'call-btn-' . $contact->id" />

                        <button wire:click="downloadVCard" class="px-2 py-0.5 text-nano font-black uppercase tracking-widest rounded-md border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 transition-all flex items-center gap-1" title="{{ __('Download VCard (.vcf)') }}">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            <span>{{ __('VCARD') }}</span>
                        </button>

                        <span
                            class="w-1.5 h-1.5 rounded-full {{ $contact->opt_in_status === 'opted_in' ? 'bg-wa-teal animate-pulse' : 'bg-slate-300 dark:bg-slate-600' }}"></span>
                    </div>
                </div>

                <!-- Profile Sections -->
                <div class="p-6 space-y-8">
                    <!-- Assignment -->
                    <section>
                        <h5
                            class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-tight mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4 text-wa-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            {{ __('Assigned To') }}
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
                                        <span class="text-tiny font-bold text-slate-500 uppercase">{{ __('Agent') }}</span>
                                    </div>
                                </div>
                                <button wire:click="unassign"
                                    aria-label="{{ __('Unassign agent') }}" title="{{ __('Unassign agent') }}"
                                    class="p-2 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-500/10 rounded-xl transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            @else
                                <span class="text-xs font-black text-slate-400 italic uppercase tracking-wider">{{ __('Unassigned') }}</span>
                                <button wire:click="assignToSelf"
                                    class="px-4 py-2 bg-wa-teal text-white text-tiny font-black uppercase tracking-widest rounded-xl shadow-lg shadow-wa-teal/20 hover:scale-105 transition-all">
                                    {{ __('Assign to Me') }}
                                </button>
                            @endif
                        </div>
                    </section>

                    <section>
                        <h5
                            class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-tight mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4 text-wa-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                            {{ __('Conversation Tags') }}
                        </h5>
                            <div class="flex flex-wrap gap-2">
                                @forelse($this->activeTags as $tag)
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-tiny font-black uppercase tracking-wider group {{ $tag->color_code }}"
                                        >
                                        <span>{{ $tag->name }}</span>
                                        <button wire:click="toggleConversationTag({{ $tag->id }})"
                                            aria-label="{{ __('Remove tag') }}: {{ $tag->name }}" title="{{ __('Remove tag') }}"
                                            class="opacity-60 hover:opacity-100 transition-opacity">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </span>
                                @empty
                                    <div
                                        class="w-full py-4 bg-slate-50 dark:bg-slate-900/50 rounded-xl border border-dashed border-slate-100 dark:border-slate-800 flex items-center justify-center">
                                        <span class="text-tiny font-black text-slate-400 uppercase tracking-widest opacity-40">{{ __('No tags assigned') }}</span>
                                    </div>
                                @endforelse
                            </div>
                            
                            <!-- Tag Picker -->
                            @if(!$this->unassignedTags->isEmpty())
                                <div class="mt-3" x-data="{ open: false }">
                                    <button @click="open = !open" class="text-nano font-black text-wa-teal uppercase tracking-widest hover:underline flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                                        {{ __('Add Conversation Tag') }}
                                    </button>
                                    <div x-show="open" @click.away="open = false" class="mt-2 p-2 bg-white dark:bg-slate-900 rounded-xl shadow-xl border border-slate-100 dark:border-slate-800 flex flex-wrap gap-1.5">
                                        @foreach($this->unassignedTags as $tag)
                                            <button wire:click="toggleConversationTag({{ $tag->id }})" 
                                                class="px-2 py-1 rounded-lg text-nano font-black uppercase tracking-wider transition-all hover:scale-105 {{ $tag->color_code }}">
                                                {{ $tag->name }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                    </section>

                    <section>
                        <h5
                            class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-tight mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4 text-wa-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            {{ __('Contact Identity') }}
                        </h5>
                        <div class="flex flex-wrap gap-2">
                            @if($contact->category)
                                @php $catColor = $contact->category->color ?: '#64748b'; @endphp
                                <span class="px-2.5 py-1 rounded-lg text-tiny font-black uppercase tracking-wider"
                                    style="background-color: {{ $catColor }}20; color: {{ $catColor }}; border: 1px solid {{ $catColor }}40;">
                                    {{ $contact->category->name }}
                                </span>
                            @endif
                            @forelse($contact->tags as $tag)
                                @php $tagColor = $tag->color ?: '#64748b'; @endphp
                                <span class="px-2.5 py-1 rounded-lg text-tiny font-black uppercase tracking-wider"
                                    style="background-color: {{ $tagColor }}10; color: {{ $tagColor }}; border: 1px solid {{ $tagColor }}30;">
                                    {{ $tag->name }}
                                </span>
                            @empty
                                @if(!$contact->category)
                                    <span class="text-tiny font-bold text-slate-400 italic">{{ __('No permanent identity tags') }}</span>
                                @endif
                            @endforelse
                        </div>
                    </section>

                    <section x-data="{ showData: false }">
                        <div class="flex items-center justify-between mb-3">
                            <h5
                                class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-tight flex items-center gap-2">
                                <svg class="w-4 h-4 text-wa-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                                </svg>
                                {{ __('Attributes') }}
                            </h5>
                            <button @click="showData = !showData"
                                class="text-tiny font-black text-wa-teal uppercase tracking-widest hover:underline transition-all">
                                <span x-show="!showData">{{ __('View JSON') }}</span>
                                <span x-show="showData">{{ __('Hide') }}</span>
                            </button>
                        </div>
                        <div x-show="showData" x-collapse>
                            <div
                                class="bg-slate-900 rounded-2xl p-4 text-tiny font-mono text-slate-400 overflow-x-auto overflow-y-auto max-h-60 custom-scrollbar shadow-2xl">
                                @if($contact->custom_attributes)
                                    <pre
                                        class="p-0 m-0 text-wrap">{{ json_encode($contact->custom_attributes, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                @else
                                    <span class="italic text-slate-600">{{ __('No extra information found.') }}</span>
                                @endif
                            </div>
                        </div>
                    </section>

                    <section>
                         <h5 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-tight mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4 text-wa-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 00-2 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            {{ __('Internal Notes') }}
                        </h5>
                        <div class="mb-4 space-y-3 max-h-48 overflow-y-auto custom-scrollbar pr-2">
                            @forelse($conversation->notes as $note)
                                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-100 dark:border-yellow-900/30 rounded-xl p-3">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-nano font-black text-yellow-600 dark:text-yellow-500 uppercase">{{ $note->user->name ?? 'System' }}</span>
                                        <span class="text-nano font-medium text-yellow-500/70">{{ $note->created_at->diffForHumans() }}</span>
                                    </div>
                                    <p class="text-[11px] font-medium text-slate-700 dark:text-slate-300 leading-relaxed">{{ $note->content }}</p>
                                </div>
                            @empty
                                <div class="text-center py-4 opacity-50">
                                    <span class="text-tiny font-medium text-slate-500">{{ __('No notes yet') }}</span>
                                </div>
                            @endforelse
                        </div>
                        <form wire:submit.prevent="addNote" class="relative group">
                            <textarea wire:model="newNoteBody"
                                class="w-full p-4 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl text-[11px] font-medium text-slate-900 dark:text-white focus:ring-wa-teal/20 focus:border-wa-teal transition-all min-h-[100px]"
                                placeholder="{{ __('Add an internal note...') }}"></textarea>
                            <div class="absolute right-3 bottom-3">
                                <button type="submit"
                                    aria-label="{{ __('Add note') }}" title="{{ __('Add note') }}"
                                    class="p-2 bg-slate-900 dark:bg-wa-teal text-white rounded-xl shadow-lg hover:scale-110 active:scale-95 transition-all">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                    </svg>
                                </button>
                            </div>
                        </form>
                    </section>
                </div>

            @elseif($activeTab === 'timeline')
                <div class="p-6">
                    <div class="space-y-6">
                        @forelse($this->timeline as $item)
                            <div class="relative pl-8 pb-6 border-l border-slate-100 dark:border-slate-800 last:pb-0" wire:key="{{ $item['id'] }}">
                                <div class="absolute left-[-5.5px] top-0 w-3 h-3 rounded-full bg-white dark:bg-slate-950 border-2 border-wa-teal shadow-sm"></div>
                                <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-100 dark:border-slate-800/50 shadow-sm">
                                    <div class="flex justify-between items-start mb-1">
                                        <span class="text-tiny font-black uppercase text-wa-teal tracking-widest">{{ __($item['title']) }}</span>
                                        <span class="text-nano font-bold text-slate-400">{{ \Carbon\Carbon::parse($item['occurred_at'])->diffForHumans() }}</span>
                                    </div>
                                    <p class="text-[11px] font-medium text-slate-700 dark:text-slate-300 leading-relaxed">{{ __($item['description']) }}</p>
                                </div>
                            </div>
                        @empty
                            <div class="py-20 text-center opacity-30">
                                <p class="text-tiny font-black uppercase tracking-widest">{{ __('No timeline activity found') }}</p>
                            </div>
                        @endforelse
                    </div>
                </div>

            @elseif($activeTab === 'files')
                <div class="p-6 grid grid-cols-2 gap-3">
                    @forelse($this->mediaVault as $file)
                        <div class="group relative aspect-square rounded-2xl overflow-hidden bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
                            @if(in_array($file['media_type'] ?? 'image', ['image', 'video']))
                                <img src="{{ $file['media_url'] }}" class="w-full h-full object-cover opacity-80 group-hover:opacity-100 transition-all">
                            @else
                                <div class="w-full h-full flex flex-col items-center justify-center p-4">
                                    <svg class="w-8 h-8 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                    <span class="text-micro font-black text-slate-400 uppercase text-center truncate w-full px-2">{{ $file['caption'] ?: 'Document' }}</span>
                                </div>
                            @endif
                            <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                <a href="{{ $file['media_url'] }}" target="_blank" class="p-2 bg-white rounded-xl shadow-xl hover:scale-110 transition-transform">
                                     <svg class="w-4 h-4 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-2 py-20 text-center opacity-30">
                            <p class="text-tiny font-black uppercase tracking-widest">{{ __('No media files found') }}</p>
                        </div>
                    @endforelse
                </div>
            @endif
        </div>
    @else
        <div class="flex-1 flex flex-col items-center justify-center p-12 text-center">
            <h4 class="text-sm font-black text-slate-400 uppercase tracking-widest">{{ __('Select a contact') }}</h4>
        </div>
    @endif
</div>