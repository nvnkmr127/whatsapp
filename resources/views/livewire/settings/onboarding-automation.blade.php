<div class="py-12 bg-slate-50/50 dark:bg-slate-900/50 min-h-screen">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-indigo-100 dark:bg-indigo-900/30 rounded-2xl">
                    <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">
                        Onboarding <span class="text-indigo-600">Automation</span>
                    </h1>
                    <p class="text-slate-500 font-medium">Configure WhatsApp templates and delays for automated user
                        re-engagement.</p>
                </div>
            </div>
            <a href="{{ route('settings.hub') }}"
                class="flex items-center gap-2 px-6 py-3 bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 font-black uppercase tracking-widest text-[10px] rounded-xl border border-slate-100 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Settings
            </a>
        </div>

        <!-- Analytics Dashboard -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            <!-- Signup Count -->
            <div
                class="bg-white dark:bg-slate-900 rounded-[2rem] p-8 shadow-sm border border-slate-100 dark:border-slate-800">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 mb-2">Total Signups</p>
                <div class="flex items-baseline gap-2">
                    <span class="text-4xl font-black text-slate-900 dark:text-white">{{ $stats['signups'] }}</span>
                    <span class="text-xs font-bold text-slate-400">Users</span>
                </div>
            </div>

            <!-- Completed Onboarding -->
            <div
                class="bg-white dark:bg-slate-900 rounded-[2rem] p-8 shadow-sm border border-slate-100 dark:border-slate-800">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-indigo-600 mb-2">Success Rate</p>
                <div class="flex items-baseline gap-2">
                    <span class="text-4xl font-black text-slate-900 dark:text-white">
                        {{ $stats['signups'] > 0 ? round(($stats['completed'] / $stats['signups']) * 100) : 0 }}%
                    </span>
                    <span class="text-xs font-bold text-slate-400">{{ $stats['completed'] }} Done</span>
                </div>
            </div>

            <!-- Recovered through Reminders -->
            <div class="bg-indigo-600 rounded-[2rem] p-8 shadow-xl shadow-indigo-600/20 text-white">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-indigo-200 mb-2">Recovered</p>
                <div class="flex items-baseline gap-2">
                    <span class="text-4xl font-black">{{ $stats['recovered'] }}</span>
                    <span class="text-xs font-bold text-indigo-200">via Automation</span>
                </div>
            </div>

            <!-- Lost Users -->
            <div
                class="bg-white dark:bg-slate-900 rounded-[2rem] p-8 shadow-sm border border-slate-100 dark:border-slate-800">
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-rose-500 mb-2">Lost Progress</p>
                <div class="flex items-baseline gap-2">
                    <span class="text-4xl font-black text-slate-900 dark:text-white">{{ $stats['lost'] }}</span>
                    <span class="text-xs font-bold text-slate-400">Users</span>
                </div>
            </div>
        </div>

        <form wire:submit.prevent="save">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <!-- Info Column -->
                <div class="lg:col-span-1 space-y-8">
                    <div
                        class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 p-8">
                        <h3 class="text-xs font-black uppercase tracking-widest text-slate-400 mb-6">Automation Sync
                        </h3>
                        <p class="text-sm text-slate-500 leading-relaxed mb-6">
                            These templates and delays are used by the <strong>Onboarding Safety Net</strong> workflow
                            and the background inactivity monitor.
                        </p>

                        <!-- Send Channel -->
                        <div class="space-y-4 pt-6 border-t border-slate-100 dark:border-slate-800">
                            <label class="text-xs font-black uppercase tracking-widest text-slate-400">Notification
                                Channel</label>
                            <div class="grid grid-cols-1 gap-2">
                                @foreach(['whatsapp' => 'WhatsApp Only', 'email' => 'Email Only', 'both' => 'Both (Omnichannel)'] as $value => $label)
                                    <button type="button" wire:click="$set('onboardingChannel', '{{ $value }}')"
                                        class="px-5 py-4 rounded-2xl text-left border-2 transition-all {{ $onboardingChannel === $value ? 'border-indigo-600 bg-indigo-50 dark:bg-indigo-900/10' : 'border-transparent bg-slate-50 dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-4 h-4 rounded-full border-2 {{ $onboardingChannel === $value ? 'border-indigo-600 bg-indigo-600' : 'border-slate-300 dark:border-slate-600' }}">
                                            </div>
                                            <span
                                                class="text-xs font-black uppercase tracking-widest {{ $onboardingChannel === $value ? 'text-indigo-600' : 'text-slate-500' }}">{{ $label }}</span>
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div
                            class="p-6 mt-8 bg-indigo-50 dark:bg-indigo-900/10 rounded-3xl border border-indigo-100 dark:border-indigo-900/20">
                            <h4
                                class="text-[10px] font-black text-indigo-900 dark:text-indigo-200 uppercase tracking-widest mb-2">
                                Compliance Note</h4>
                            <p class="text-[10px] text-indigo-800/80 dark:text-indigo-400/80 leading-relaxed italic">
                                Only "APPROVED" WhatsApp templates are shown here. Meta requires pre-approval for all
                                outbound business-initiated messages.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Settings Column -->
                <div class="lg:col-span-2 space-y-8">
                    <div
                        class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
                        <div class="p-8 md:p-10 space-y-12">

                            <!-- Remote Reminder (Stage 1) -->
                            <div class="space-y-6">
                                <div
                                    class="flex items-center justify-between border-b border-slate-50 dark:border-slate-800 pb-4">
                                    <div>
                                        <h3 class="text-xs font-black uppercase tracking-widest text-indigo-600">1.
                                            Welcome & Access Reminder</h3>
                                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">
                                            Triggered if WhatsApp is not connected</p>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <label
                                            class="text-[10px] font-black uppercase tracking-widest text-slate-400">Delay
                                            (Hours)</label>
                                        <input type="number" wire:model="firstDelay"
                                            class="w-20 px-3 py-2 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-black text-indigo-600">
                                    </div>
                                </div>
                                <div class="space-y-4">
                                    <div class="relative">
                                        <select wire:model="remoteReminderTemplate"
                                            class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-900 dark:text-white font-bold cursor-pointer focus:ring-2 focus:ring-indigo-500/20 transition-all appearance-none">
                                            <option value="">Select Template...</option>
                                            @foreach($templates as $template)
                                                <option value="{{ $template['name'] }}">{{ $template['name'] }}</option>
                                            @endforeach
                                        </select>
                                        <div
                                            class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <label
                                            class="text-[9px] font-black uppercase tracking-widest text-slate-400 ml-1">Positional
                                            Variables (e.g. {name}, {setup_link})</label>
                                        <input type="text" wire:model="remoteReminderVariables"
                                            class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-mono text-indigo-600 dark:text-indigo-400">
                                    </div>
                                </div>
                            </div>

                            <!-- Profile Reminder (Stage 2) -->
                            <div class="space-y-6">
                                <div
                                    class="flex items-center justify-between border-b border-slate-50 dark:border-slate-800 pb-4">
                                    <div>
                                        <h3 class="text-xs font-black uppercase tracking-widest text-indigo-600">2.
                                            Business Profile Guide</h3>
                                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">
                                            Triggered if profile is incomplete</p>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <label
                                            class="text-[10px] font-black uppercase tracking-widest text-slate-400">Delay
                                            (Hours)</label>
                                        <input type="number" wire:model="secondDelay"
                                            class="w-20 px-3 py-2 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-black text-indigo-600">
                                    </div>
                                </div>
                                <div class="space-y-4">
                                    <div class="relative">
                                        <select wire:model="profileReminderTemplate"
                                            class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-900 dark:text-white font-bold cursor-pointer focus:ring-2 focus:ring-indigo-500/20 transition-all appearance-none">
                                            <option value="">Select Template...</option>
                                            @foreach($templates as $template)
                                                <option value="{{ $template['name'] }}">{{ $template['name'] }}</option>
                                            @endforeach
                                        </select>
                                        <div
                                            class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <label
                                            class="text-[9px] font-black uppercase tracking-widest text-slate-400 ml-1">Positional
                                            Variables (e.g. {name}, {setup_link})</label>
                                        <input type="text" wire:model="profileReminderVariables"
                                            class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-mono text-indigo-600 dark:text-indigo-400">
                                    </div>
                                </div>
                            </div>

                            <!-- Campaign Tutorial (Stage 3) -->
                            <div class="space-y-6">
                                <div
                                    class="flex items-center justify-between border-b border-slate-50 dark:border-slate-800 pb-4">
                                    <div>
                                        <h3 class="text-xs font-black uppercase tracking-widest text-indigo-600">3.
                                            First Campaign Tutorial</h3>
                                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">
                                            Triggered if no campaign created</p>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <label
                                            class="text-[10px] font-black uppercase tracking-widest text-slate-400">Delay
                                            (Hours)</label>
                                        <input type="number" wire:model="thirdDelay"
                                            class="w-20 px-3 py-2 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-black text-indigo-600">
                                    </div>
                                </div>
                                <div class="space-y-4">
                                    <div class="relative">
                                        <select wire:model="campaignTutorialTemplate"
                                            class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-900 dark:text-white font-bold cursor-pointer focus:ring-2 focus:ring-indigo-500/20 transition-all appearance-none">
                                            <option value="">Select Template...</option>
                                            @foreach($templates as $template)
                                                <option value="{{ $template['name'] }}">{{ $template['name'] }}</option>
                                            @endforeach
                                        </select>
                                        <div
                                            class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <label
                                            class="text-[9px] font-black uppercase tracking-widest text-slate-400 ml-1">Positional
                                            Variables (e.g. {name})</label>
                                        <input type="text" wire:model="campaignTutorialVariables"
                                            class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-mono text-indigo-600 dark:text-indigo-400">
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- Footer Actions -->
                        <div
                            class="p-8 md:p-10 bg-slate-50/50 dark:bg-slate-800/50 flex items-center border-t border-slate-50 dark:border-slate-800">
                            <button type="submit"
                                class="ml-auto px-12 py-4 bg-indigo-600 text-white font-black uppercase tracking-widest text-xs rounded-2xl shadow-2xl shadow-indigo-600/30 hover:bg-indigo-700 hover:-translate-y-1 active:translate-y-0 transition-all">
                                Save Onboarding Flow Configuration
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>