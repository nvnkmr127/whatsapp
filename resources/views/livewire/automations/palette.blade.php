<div class="h-full section-left-sidebar w-72 flex-none bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex flex-col z-40 transition-all duration-300">
    <div class="p-4 border-b border-slate-100 dark:border-slate-800">
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            <input type="text" placeholder="Search components..."
                class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-xs py-2.5 pl-9 pr-3 focus:ring-2 focus:ring-wa-teal focus:border-transparent transition-shadow">
        </div>
    </div>

    <div class="flex-1 min-h-0 overflow-y-auto p-4 space-y-6 custom-scrollbar pb-20">
         @foreach([
                'Messages' => [
                    ['type' => 'text', 'label' => 'Text Message', 'icon' => 'M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z', 'color' => 'text-blue-500', 'bg' => 'bg-blue-50 dark:bg-blue-900/20'],
                    ['type' => 'image', 'label' => 'Image', 'icon' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z', 'color' => 'text-purple-500', 'bg' => 'bg-purple-50 dark:bg-purple-900/20'],
                    ['type' => 'video', 'label' => 'Video', 'icon' => 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z', 'color' => 'text-pink-500', 'bg' => 'bg-pink-50 dark:bg-pink-900/20'],
                    ['type' => 'audio', 'label' => 'Audio', 'icon' => 'M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z', 'color' => 'text-cyan-500', 'bg' => 'bg-cyan-50 dark:bg-cyan-900/20'],
                    ['type' => 'file', 'label' => 'Document', 'icon' => 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z', 'color' => 'text-slate-500', 'bg' => 'bg-slate-100 dark:bg-slate-800'],
                    ['type' => 'template', 'label' => 'WhatsApp Template', 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'color' => 'text-green-500', 'bg' => 'bg-green-50 dark:bg-green-900/20'],
                    ['type' => 'interactive_button', 'label' => 'Reply Buttons', 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z', 'color' => 'text-orange-500', 'bg' => 'bg-orange-50 dark:bg-orange-900/20'],
                    ['type' => 'interactive_list', 'label' => 'List Menu', 'icon' => 'M4 6h16M4 10h16M4 14h16M4 18h16', 'color' => 'text-orange-500', 'bg' => 'bg-orange-50 dark:bg-orange-900/20'],
                    ['type' => 'carousel', 'label' => 'Carousel', 'icon' => 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6z', 'color' => 'text-purple-500', 'bg' => 'bg-purple-50 dark:bg-purple-900/20'],
                    ['type' => 'send_flow', 'label' => 'Send Flow', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'color' => 'text-indigo-500', 'bg' => 'bg-indigo-50 dark:bg-indigo-900/20'],
                    ['type' => 'location_request', 'label' => 'Location Request', 'icon' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z', 'color' => 'text-red-500', 'bg' => 'bg-red-50 dark:bg-red-900/20'],
                    ['type' => 'contact', 'label' => 'Send Contact', 'icon' => 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4', 'color' => 'text-blue-500', 'bg' => 'bg-blue-50 dark:bg-blue-900/20'],
                ],
                'Logic & Flow' => [
                    ['type' => 'condition', 'label' => 'Condition', 'icon' => 'M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z', 'color' => 'text-amber-500', 'bg' => 'bg-amber-50 dark:bg-amber-900/20'],
                    ['type' => 'ab_split', 'label' => 'A/B Split', 'icon' => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4-4m-4 4l4 4', 'color' => 'text-wa-teal', 'bg' => 'bg-wa-teal/10'],
                    ['type' => 'tag_contact', 'label' => 'Assign Tag', 'icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z', 'color' => 'text-emerald-500', 'bg' => 'bg-emerald-50 dark:bg-emerald-900/20'],
                    ['type' => 'delay', 'label' => 'Delay / Wait', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'text-slate-500', 'bg' => 'bg-slate-100 dark:bg-slate-800'],
                ],
                'Inputs' => [
                    ['type' => 'user_input', 'label' => 'Ask Question', 'icon' => 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093', 'color' => 'text-cyan-500', 'bg' => 'bg-cyan-50 dark:bg-cyan-900/20'],
                ],
                'Integrations' => [
                    ['type' => 'openai', 'label' => 'AI Assistant', 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z', 'color' => 'text-emerald-500', 'bg' => 'bg-emerald-50 dark:bg-emerald-900/20'],
                    ['type' => 'webhook', 'label' => 'Webhook', 'icon' => 'M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9', 'color' => 'text-pink-500', 'bg' => 'bg-pink-50 dark:bg-pink-900/20'],
                    ['type' => 'crm_sync', 'label' => 'CRM Sync', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'color' => 'text-indigo-500', 'bg' => 'bg-indigo-50 dark:bg-indigo-900/20'],
                ]
            ] as $group => $items)
                                        <div>
                                            <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-3 ml-1">{{ $group }}</h3>
                                            <div class="space-y-2">
                                                @foreach($items as $item)
                                                    <button @click="$wire.addNode('{{ $item['type'] }}')"
                                                        class="w-full flex items-center gap-3 px-3 py-2.5 bg-white dark:bg-slate-800/50 hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700/50 hover:border-wa-teal/30 dark:hover:border-wa-teal/30 rounded-xl transition-all group shadow-sm hover:shadow-md hover:scale-[1.02]">
                                                        <div class="p-2 rounded-lg {{ $item['bg'] }} {{ $item['color'] }} transition-colors">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}" />
                                                            </svg>
                                                        </div>
                                                        <div class="flex flex-col items-start">
                                                            <span class="text-xs font-bold text-slate-700 dark:text-slate-200 group-hover:text-wa-teal transition-colors">{{ $item['label'] }}</span>
                                                        </div>
                                                        <div class="ml-auto opacity-0 group-hover:opacity-100 transition-opacity">
                                                            <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                                        </div>
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
         @endforeach
    </div>
</div>
