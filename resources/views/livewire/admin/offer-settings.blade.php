<div class="space-y-8" x-data="{ activeTab: 'global' }">

    {{-- ═══════════════════════════════════════════════════
    PAGE HEADER
    ════════════════════════════════════════════════════ --}}
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="p-2 bg-indigo-500/10 text-indigo-500 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                    </svg>
                </div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">
                    Launch <span class="text-indigo-500">Offer</span>
                </h1>
            </div>
            <p class="text-slate-500 font-medium">
                All settings are dynamic — every value comes from the database. No defaults are hidden in code.
            </p>
        </div>

        {{-- Live status badge --}}
        <div class="flex items-center gap-3">
            @if($settings['offer_enabled'] ?? false)
                <span
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-emerald-100 text-emerald-700 text-xs font-black uppercase tracking-widest">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Offer Active
                </span>
            @else
                <span
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-slate-100 text-slate-500 text-xs font-black uppercase tracking-widest">
                    <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                    Offer Paused
                </span>
            @endif
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         SAFETY REFERENCE CARD (always visible)
    ══════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-500/30 rounded-2xl p-4 flex gap-3">
            <span class="text-xl mt-0.5">⏳</span>
            <div>
                <p class="font-black text-amber-700 dark:text-amber-400 text-xs uppercase tracking-widest mb-1">New Signups Only</p>
                <p class="text-amber-700/80 dark:text-amber-300/70 text-xs leading-relaxed">
                    <strong>Trial duration</strong> &amp; <strong>initial credit</strong> are stamped once at signup.
                    Changing them does <em>not</em> alter any existing tenant's <code class="font-mono">trial_ends_at</code> or wallet balance.
                </p>
            </div>
        </div>
        <div class="bg-blue-50 dark:bg-blue-900/10 border border-blue-200 dark:border-blue-500/30 rounded-2xl p-4 flex gap-3">
            <span class="text-xl mt-0.5">⚡</span>
            <div>
                <p class="font-black text-blue-700 dark:text-blue-400 text-xs uppercase tracking-widest mb-1">Live — Trial Teams</p>
                <p class="text-blue-700/80 dark:text-blue-300/70 text-xs leading-relaxed">
                    <strong>Limits</strong> &amp; <strong>features</strong> are read on every request via EntitlementService.
                    Changes take effect immediately. Past usage data is never retroactively modified.
                </p>
            </div>
        </div>
        <div class="bg-indigo-50 dark:bg-indigo-900/10 border border-indigo-200 dark:border-indigo-500/30 rounded-2xl p-4 flex gap-3">
            <span class="text-xl mt-0.5">🌐</span>
            <div>
                <p class="font-black text-indigo-700 dark:text-indigo-400 text-xs uppercase tracking-widest mb-1">Global Toggle</p>
                <p class="text-indigo-700/80 dark:text-indigo-300/70 text-xs leading-relaxed">
                    <strong>offer_enabled</strong> pauses the offer for new registrations only.
                    Existing trial teams continue until their <code class="font-mono">trial_ends_at</code>.
                </p>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════
         FORM
    ══════════════════════════════════════════════════════ --}}
    <div
        class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
        <form wire:submit.prevent="save">

            {{-- ── Flash & impact messages ──────────────────── --}}
            <div class="px-10 pt-8">

                {{-- Impact classification banner (only after save) --}}
                @if (session()->has('impact_lines'))
                    <div class="mb-4 border border-slate-200 dark:border-slate-700 rounded-2xl overflow-hidden">
                        <div
                            class="px-4 py-2 bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Change Impact
                                Analysis</p>
                        </div>
                        <div class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach (session('impact_lines') as $line)
                                <div class="px-5 py-3 text-xs font-medium text-slate-700 dark:text-slate-300">{{ $line }}</div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if (session()->has('message'))
                    <div
                        class="mb-6 bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 font-bold px-6 py-4 rounded-2xl flex items-center gap-3">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ session('message') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div
                        class="mb-6 bg-rose-500/10 border border-rose-500/20 text-rose-600 font-bold px-6 py-4 rounded-2xl">
                        <p class="text-xs uppercase tracking-widest font-black mb-2">Please fix the following:</p>
                        <ul class="list-disc list-inside space-y-1 text-sm">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            {{-- ═══════════════════════════════════════════════════
            SECTION 1 – GLOBAL TOGGLE (prominent)
            ════════════════════════════════════════════════════ --}}
            <div class="px-10 pb-8">
                <div
                    class="p-6 bg-indigo-50 dark:bg-indigo-900/20 rounded-[2rem] flex items-center justify-between border border-indigo-100 dark:border-indigo-500/30">
                    <div class="flex items-start gap-4">
                        <div class="p-3 bg-white dark:bg-indigo-900/40 rounded-xl text-indigo-500 shadow-sm">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div>
                            <h3
                                class="text-lg font-black text-indigo-900 dark:text-indigo-100 uppercase tracking-tight">
                                Enable Launch Offer
                            </h3>
                            <p class="text-indigo-600/80 dark:text-indigo-300 font-medium text-sm mt-1">
                                New registrations will receive this trial plan automatically.
                                <br>
                                <span class="text-indigo-400/70 text-[11px] uppercase tracking-wide">
                                    DB key: <code class="font-mono">offer_enabled</code>
                                    · default: <code class="font-mono">true</code>
                                </span>
                            </p>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model="settings.offer_enabled" class="sr-only peer">
                        <div
                            class="w-14 h-7 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-500">
                        </div>
                    </label>
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════
            TABBED SECTIONS
            ════════════════════════════════════════════════════ --}}
            <div class="border-t border-slate-100 dark:border-slate-800">

                {{-- Tab nav --}}
                <nav class="flex gap-0 px-10 overflow-x-auto">
                    @php
                        $tabs = [
                            'trial' => ['icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'label' => 'Trial & Credits'],
                            'limits' => ['icon' => 'M13 10V3L4 14h7v7l9-11h-7z', 'label' => 'Usage Limits'],
                            'features' => ['icon' => 'M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM14 11a1 1 0 011 1v1h1a1 1 0 010 2h-1v1a1 1 0 01-2 0v-1h-1a1 1 0 010-2h1v-1a1 1 0 011-1z', 'label' => 'Features'],
                            'window' => ['icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'label' => 'Time Window'],
                            'identity' => ['icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'label' => 'Anti-Abuse'],
                        ];
                    @endphp

                    @foreach($tabs as $tabKey => $tab)
                        <button type="button" @click="activeTab = '{{ $tabKey }}'" :class="activeTab === '{{ $tabKey }}'
                                        ? 'border-b-2 border-indigo-500 text-indigo-600 dark:text-indigo-400 font-black bg-indigo-50/50 dark:bg-indigo-900/10'
                                        : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                            class="flex items-center gap-2 px-5 py-4 text-xs uppercase tracking-widest font-bold whitespace-nowrap transition-all">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="{{ $tab['icon'] }}" />
                            </svg>
                            {{ $tab['label'] }}
                        </button>
                    @endforeach
                </nav>

                {{-- ── TAB: Trial & Credits ─────────────────────────── --}}
                <div x-show="activeTab === 'trial'" x-transition class="p-10 grid grid-cols-1 md:grid-cols-2 gap-8">
                    @foreach(($schemaGroups['trial'] ?? []) as $param)
                        <x-offer-setting-field :param="$param" />
                    @endforeach
                </div>

                {{-- ── TAB: Usage Limits ────────────────────────────── --}}
                <div x-show="activeTab === 'limits'" x-transition class="p-10 space-y-6">
                    <p
                        class="text-xs font-black uppercase tracking-widest text-slate-400 bg-slate-50 dark:bg-slate-800/50 px-4 py-2 rounded-xl inline-block">
                        ℹ︎ Set to <code class="font-mono">0</code> for unlimited access
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                        @foreach(($schemaGroups['limits'] ?? []) as $param)
                            @php $isInt = $param['type'] === 'int'; @endphp
                            <div class="space-y-2">
                                <div class="flex justify-between items-center">
                                    <label class="text-xs font-black uppercase tracking-widest text-slate-400">
                                        {{ $param['label'] }}
                                    </label>
                                    @if($param['default'] === 0)
                                        <span
                                            class="text-[10px] uppercase font-bold text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30 px-2 py-0.5 rounded">
                                            0 = unlimited
                                        </span>
                                    @else
                                        <span
                                            class="text-[10px] uppercase font-bold text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded">
                                            default {{ $param['default'] }}
                                        </span>
                                    @endif
                                </div>
                                <input type="number" min="0" wire:model="settings.{{ $param['key'] }}"
                                    class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-indigo-500/20 text-lg transition-all"
                                    placeholder="{{ $param['default'] }}">
                                <p class="text-[10px] font-mono text-slate-400">{{ $param['key'] }}</p>
                                @error('settings.' . $param['key'])
                                    <span class="text-rose-500 text-[10px] font-bold uppercase">{{ $message }}</span>
                                @enderror
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- ── TAB: Features ────────────────────────────────── --}}
                <div x-show="activeTab === 'features'" x-transition class="p-10">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        {{-- Left: feature checkboxes --}}
                        <div class="space-y-4">
                            <div
                                class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4 mb-2">
                                <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-tight">
                                    Included Features
                                </h3>
                                <div class="flex gap-2">
                                    <button type="button"
                                        wire:click="$set('includedFeatures', {{ json_encode(array_keys($availableFeatures)) }})"
                                        class="text-[10px] uppercase font-black text-indigo-500 px-3 py-1 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg hover:bg-indigo-100 transition">
                                        All
                                    </button>
                                    <button type="button" wire:click="$set('includedFeatures', [])"
                                        class="text-[10px] uppercase font-black text-slate-400 px-3 py-1 bg-slate-100 dark:bg-slate-800 rounded-lg hover:bg-slate-200 transition">
                                        None
                                    </button>
                                </div>
                            </div>

                            <div class="bg-slate-50 dark:bg-slate-800/50 rounded-[2rem] p-4 space-y-2">
                                @foreach($availableFeatures as $featureKey => $featureLabel)
                                    <label
                                        class="flex items-center justify-between p-4 bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 cursor-pointer hover:border-indigo-500/30 transition-all group">
                                        <div>
                                            <span
                                                class="text-sm font-bold text-slate-700 dark:text-slate-200 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                                {{ $featureLabel }}
                                            </span>
                                            <p class="text-[10px] font-mono text-slate-400 mt-0.5">{{ $featureKey }}</p>
                                        </div>
                                        <input type="checkbox" value="{{ $featureKey }}" wire:model="includedFeatures"
                                            class="w-5 h-5 rounded-lg border-2 border-slate-200 dark:border-slate-600 text-indigo-500 focus:ring-indigo-500/20 transition-all">
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- Right: live preview --}}
                        <div class="space-y-4">
                            <div class="border-b border-slate-100 dark:border-slate-800 pb-4 mb-2">
                                <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-tight">
                                    Live Preview
                                </h3>
                                <p class="text-xs text-slate-400 font-medium mt-1">What trial users will see</p>
                            </div>
                            <div class="bg-gradient-to-br from-indigo-600 to-violet-700 rounded-[2rem] p-6 text-white">
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-black text-sm uppercase tracking-widest opacity-80">6 Months Free
                                            Trial</p>
                                        <p class="text-xl font-black">{{ $settings['offer_trial_months'] ?? 6 }} Months
                                            Included</p>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    @php
                                        $selected = $includedFeatures;
                                        $allFeats = $availableFeatures;
                                    @endphp
                                    @foreach($allFeats as $fk => $fl)
                                        <div
                                            class="flex items-center gap-2 py-1.5 px-3 rounded-xl {{ in_array($fk, $selected) ? 'bg-white/15' : 'opacity-30' }}">
                                            <svg class="w-4 h-4 {{ in_array($fk, $selected) ? 'text-emerald-300' : 'text-white/40' }}"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                    d="{{ in_array($fk, $selected) ? 'M5 13l4 4L19 7' : 'M6 18L18 6M6 6l12 12' }}" />
                                            </svg>
                                            <span class="text-xs font-bold">{{ $fl }}</span>
                                        </div>
                                    @endforeach
                                </div>

                                <div
                                    class="mt-4 pt-4 border-t border-white/20 grid grid-cols-3 gap-3 text-center text-xs font-black">
                                    <div>
                                        <div class="text-xl text-white">{{ $settings['offer_message_limit'] ?? 5000 }}
                                        </div>
                                        <div class="opacity-60 text-[10px] uppercase">Messages/mo</div>
                                    </div>
                                    <div>
                                        <div class="text-xl text-white">{{ $settings['offer_agent_limit'] ?? 5 }}</div>
                                        <div class="opacity-60 text-[10px] uppercase">Agents</div>
                                    </div>
                                    <div>
                                        <div class="text-xl text-emerald-300">
                                            {{ get_setting('currency_symbol', '$') }}{{ $settings['offer_initial_credit'] ?? 5.00 }}
                                        </div>
                                        <div class="opacity-60 text-[10px] uppercase">Free Credit</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── TAB: Time Window ─────────────────────────────── --}}
                <div x-show="activeTab === 'window'" x-transition class="p-10 space-y-6">
                    <div
                        class="bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-500/30 rounded-2xl px-5 py-4 flex items-start gap-3">
                        <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <p class="font-black text-amber-700 dark:text-amber-400 text-sm">Optional date window</p>
                            <p class="text-amber-600/80 dark:text-amber-300/80 text-xs mt-0.5">
                                Leave both fields blank to run the offer indefinitely. If set, new signups outside this
                                window will not receive the trial.
                                <br>These are stored in <code
                                    class="font-mono text-amber-700 dark:text-amber-400">offer_start_at</code> and <code
                                    class="font-mono text-amber-700 dark:text-amber-400">offer_end_at</code> settings.
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        @foreach(($schemaGroups['window'] ?? []) as $param)
                            <div class="space-y-2">
                                <label class="text-xs font-black uppercase tracking-widest text-slate-400">
                                    {{ $param['label'] }}
                                </label>
                                <input type="datetime-local" wire:model="settings.{{ $param['key'] }}"
                                    class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-indigo-500/20 text-base transition-all"
                                    placeholder="Leave blank = no boundary">
                                <p class="text-[10px] font-mono text-slate-400">{{ $param['key'] }}</p>
                                @error('settings.' . $param['key'])
                                    <span class="text-rose-500 text-[10px] font-bold uppercase">{{ $message }}</span>
                                @enderror
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- ── TAB: Anti-Abuse / Identity ───────────────────── --}}
                <div x-show="activeTab === 'identity'" x-transition class="p-10 space-y-6">
                    <div
                        class="bg-rose-50 dark:bg-rose-900/10 border border-rose-200 dark:border-rose-500/30 rounded-2xl px-5 py-4 flex items-start gap-3">
                        <svg class="w-5 h-5 text-rose-500 shrink-0 mt-0.5" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div>
                            <p class="font-black text-rose-700 dark:text-rose-400 text-sm">Anti-Abuse Controls</p>
                            <p class="text-rose-600/80 dark:text-rose-300/80 text-xs mt-0.5">
                                These settings prevent one person from claiming the offer multiple times via different
                                email domains.
                                All values are from the database — none are hardcoded.
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        @foreach(($schemaGroups['identity'] ?? []) as $param)
                            <div class="space-y-2">
                                <label class="text-xs font-black uppercase tracking-widest text-slate-400">
                                    {{ $param['label'] }}
                                </label>

                                @if($param['type'] === 'int')
                                    <input type="number" min="1" wire:model="settings.{{ $param['key'] }}"
                                        class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-indigo-500/20 text-lg transition-all"
                                        placeholder="{{ $param['default'] }}">
                                @else
                                    <textarea wire:model="settings.{{ $param['key'] }}" rows="3"
                                        class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-indigo-500/20 text-sm transition-all"
                                        placeholder="gmail.com,yahoo.com,hotmail.com"></textarea>
                                @endif

                                <p class="text-[10px] font-mono text-slate-400">{{ $param['key'] }}
                                    <span class="text-slate-300">· default: {{ json_encode($param['default']) }}</span>
                                </p>
                                @error('settings.' . $param['key'])
                                    <span class="text-rose-500 text-[10px] font-bold uppercase">{{ $message }}</span>
                                @enderror
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>{{-- end tabbed sections --}}

            {{-- ── Save button ────────────────────────────────────── --}}
            <div
                class="mx-10 mb-10 mt-4 flex items-center justify-between pt-8 border-t border-slate-100 dark:border-slate-800">
                {{-- Schema info strip --}}
                <p class="text-[10px] font-mono text-slate-400 uppercase tracking-widest hidden md:block">
                    {{ collect($schemaGroups)->flatten(1)->count() }} parameters · all persisted to
                    <code>settings</code> table
                </p>

                <button type="submit" wire:loading.attr="disabled"
                    class="px-10 py-4 bg-indigo-600 text-white font-black uppercase tracking-widest text-sm rounded-2xl shadow-xl shadow-indigo-500/20 hover:scale-[1.02] hover:bg-indigo-500 active:scale-95 transition-all flex items-center gap-3 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg wire:loading.remove class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <svg wire:loading class="w-4 h-4 animate-spin" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span wire:loading.remove>Save Configuration</span>
                    <span wire:loading>Saving…</span>
                </button>
            </div>

        </form>
    </div>

</div>