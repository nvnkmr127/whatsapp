<div class="h-full section-left-sidebar w-72 flex-none bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex flex-col z-40 transition-all duration-300"
     x-data="{ search: '' }">
    <div class="p-4 border-b border-slate-100 dark:border-slate-800">
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            <input type="text" placeholder="Search components..." x-model="search"
                class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded-xl text-xs py-2.5 pl-9 pr-3 focus:ring-2 focus:ring-wa-teal focus:border-transparent transition-shadow">
        </div>
    </div>

    <div class="flex-1 min-h-0 overflow-y-auto p-4 space-y-6 custom-scrollbar pb-20">
         @foreach([
                'Messages' => [
                    ['type' => 'text',             'label' => 'Text Message',       'icon' => 'M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z',             'color' => 'text-blue-500',    'bg' => 'bg-blue-50 dark:bg-blue-900/20'],
                    ['type' => 'image',            'label' => 'Send Image',         'icon' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z', 'color' => 'text-purple-500',  'bg' => 'bg-purple-50 dark:bg-purple-900/20'],
                    ['type' => 'video',            'label' => 'Send Video',         'icon' => 'M15 10l4.553-2.069A1 1 0 0121 8.879V15.12a1 1 0 01-1.447.89L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z', 'color' => 'text-rose-500',    'bg' => 'bg-rose-50 dark:bg-rose-900/20'],
                    ['type' => 'audio',            'label' => 'Send Audio',         'icon' => 'M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3',                                              'color' => 'text-emerald-500', 'bg' => 'bg-emerald-50 dark:bg-emerald-900/20'],
                    ['type' => 'file',             'label' => 'Send Document',      'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',                                           'color' => 'text-amber-500',   'bg' => 'bg-amber-50 dark:bg-amber-900/20'],
                    ['type' => 'template',         'label' => 'WA Template',        'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',                                                                                 'color' => 'text-green-500',   'bg' => 'bg-green-50 dark:bg-green-900/20'],
                    ['type' => 'interactive_button','label' => 'Reply Buttons',     'icon' => 'M13 10V3L4 14h7v7l9-11h-7z',                                                                                                                                               'color' => 'text-orange-500',  'bg' => 'bg-orange-50 dark:bg-orange-900/20'],
                    ['type' => 'interactive_list', 'label' => 'List Message',       'icon' => 'M4 6h16M4 10h16M4 14h16M4 18h16',                                                                                                                                          'color' => 'text-pink-500',    'bg' => 'bg-pink-50 dark:bg-pink-900/20'],
                    ['type' => 'carousel',         'label' => 'Carousel Cards',     'icon' => 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6z',                                   'color' => 'text-indigo-500',  'bg' => 'bg-indigo-50 dark:bg-indigo-900/20'],
                    ['type' => 'send_flow',        'label' => 'WhatsApp Flow Form', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',                                    'color' => 'text-wa-teal',     'bg' => 'bg-wa-teal/10'],
                    ['type' => 'send_email',       'label' => 'Send Email',         'icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',                                                                  'color' => 'text-sky-500',     'bg' => 'bg-sky-50 dark:bg-sky-900/20'],
                    ['type' => 'catalog_message',  'label' => 'Product Catalog',    'icon' => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z',                                                                                                                                'color' => 'text-lime-600',    'bg' => 'bg-lime-50 dark:bg-lime-900/20'],
                    ['type' => 'location_request', 'label' => 'Ask Location',       'icon' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z',                                                                                      'color' => 'text-red-500',     'bg' => 'bg-red-50 dark:bg-red-900/20'],
                ],
                'Collect Input' => [
                    ['type' => 'user_input',       'label' => 'Ask Question',       'icon' => 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z',                                                             'color' => 'text-cyan-500',    'bg' => 'bg-cyan-50 dark:bg-cyan-900/20'],
                    // Hidden: 'wait_for_event' is not implemented in the engine (AutomationService::executeNode falls through to default → skipped at runtime). Re-add when built.
                    ['type' => 'send_flow',        'label' => 'WA Form (Flow)',     'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',                                               'color' => 'text-teal-500',    'bg' => 'bg-teal-50 dark:bg-teal-900/20'],
                ],
                'CRM & Sales' => [
                    ['type' => 'create_deal',      'label' => 'Create Deal',        'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'text-emerald-500', 'bg' => 'bg-emerald-50 dark:bg-emerald-900/20'],
                    ['type' => 'assign_to_agent',  'label' => 'Assign Agent',       'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',                                                                                                     'color' => 'text-indigo-500',  'bg' => 'bg-indigo-50 dark:bg-indigo-900/20'],
                    ['type' => 'create_crm_task',  'label' => 'Create Task',        'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',                       'color' => 'text-blue-500',    'bg' => 'bg-blue-50 dark:bg-blue-900/20'],
                    ['type' => 'crm_sync',         'label' => 'External CRM Sync',  'icon' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',                                                            'color' => 'text-purple-500',  'bg' => 'bg-purple-50 dark:bg-purple-900/20'],
                    ['type' => 'tag_contact',      'label' => 'Tag / Untag',        'icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z',                                          'color' => 'text-teal-500',    'bg' => 'bg-teal-50 dark:bg-teal-900/20'],
                    ['type' => 'update_contact',   'label' => 'Update Contact',     'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',                                               'color' => 'text-orange-500',  'bg' => 'bg-orange-50 dark:bg-orange-900/20'],
                ],
                'Logic & Variables' => [
                    ['type' => 'split_by_condition','label' => 'Condition Branch',  'icon' => 'M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316',                                  'color' => 'text-amber-500',   'bg' => 'bg-amber-50 dark:bg-amber-900/20'],
                    ['type' => 'set_variable',     'label' => 'Set Variable',       'icon' => 'M7 20l4-16m2 16l4-16M6 9h14M4 15h14',                                                                                                                                      'color' => 'text-cyan-500',    'bg' => 'bg-cyan-50 dark:bg-cyan-900/20'],
                    ['type' => 'loop_over_items',  'label' => 'Loop / Iterate',     'icon' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',                                                            'color' => 'text-indigo-500',  'bg' => 'bg-indigo-50 dark:bg-indigo-900/10'],
                    ['type' => 'ab_split',         'label' => 'A/B Test Split',     'icon' => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4-4m-4 4l4 4',                                                                                                                       'color' => 'text-wa-teal',     'bg' => 'bg-wa-teal/10'],
                    // Hidden: 'retry' node is not implemented in the engine (skipped at runtime). Per-node retry config lives on other nodes; re-add this palette item when the standalone retry node is built.
                ],
                'Timing' => [
                    ['type' => 'delay',            'label' => 'Delay / Wait',       'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',                                                                                                                              'color' => 'text-slate-500',   'bg' => 'bg-slate-100 dark:bg-slate-800'],
                    ['type' => 'wait_until',       'label' => 'Wait Until Time',    'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',                                                                                'color' => 'text-sky-500',     'bg' => 'bg-sky-50 dark:bg-sky-900/20'],
                    ['type' => 'rate_limit_gate',  'label' => 'Frequency Cap',      'icon' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',                                                                  'color' => 'text-rose-500',    'bg' => 'bg-rose-50 dark:bg-rose-900/10'],
                ],
                'AI & Integrations' => [
                    ['type' => 'openai',           'label' => 'AI Agent / GPT',     'icon' => 'M13 10V3L4 14h7v7l9-11h-7z',                                                                                                                                               'color' => 'text-emerald-500', 'bg' => 'bg-emerald-50 dark:bg-emerald-900/20'],
                    ['type' => 'google_sheets',    'label' => 'Google Sheets',      'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',                                       'color' => 'text-green-600',   'bg' => 'bg-green-100 dark:bg-green-900/30'],
                    ['type' => 'webhook',          'label' => 'HTTP Webhook / API', 'icon' => 'M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9',                             'color' => 'text-pink-500',    'bg' => 'bg-pink-50 dark:bg-pink-900/20'],
                    // Hidden: 'payment' (Collect Payment) is not implemented in the engine (skipped at runtime) — needs a payment-gateway integration. Re-add when built.
                    ['type' => 'sub_flow',         'label' => 'Run Sub-Flow',       'icon' => 'M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14',                                                                                           'color' => 'text-slate-400',   'bg' => 'bg-slate-100'],
                ],
                'System' => [
                    ['type' => 'handover',         'label' => 'Human Handoff',      'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',                                                                                                     'color' => 'text-indigo-500',  'bg' => 'bg-indigo-50 dark:bg-indigo-900/20'],
                    ['type' => 'stop_flow',        'label' => 'End Flow',           'icon' => 'M6 18L18 6M6 6l12 12',                                                                                                                                                      'color' => 'text-rose-500',    'bg' => 'bg-rose-50 dark:bg-rose-900/20'],
                    ['type' => 'note',             'label' => 'Canvas Note',        'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',                                               'color' => 'text-yellow-500',  'bg' => 'bg-yellow-50 dark:bg-yellow-900/20'],
                ],
            ] as $group => $items)
                                        <div x-show="!search || '{{ strtolower($group) }}'.includes(search.toLowerCase()) || {{ count($items) }} > 0">
                                            <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-3 ml-1"
                                                x-show="!search">{{ $group }}</h3>
                                            <div class="space-y-2">
                                                @foreach($items as $item)
                                                    <button @click="$wire.addNode('{{ $item['type'] }}')"
                                                        x-show="!search || '{{ strtolower($item['label']) }}'.includes(search.toLowerCase())"
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
