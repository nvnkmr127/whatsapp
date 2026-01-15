<div class="space-y-8 animate-in fade-in duration-700">
    {{-- Page Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-2xl">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z">
                    </path>
                </svg>
            </div>
            <div>
                <h1 class="text-3xl font-bold text-slate-900 dark:text-white uppercase tracking-tight">
                    API <span class="text-blue-500">TOKENS</span>
                </h1>
                <p class="text-slate-500 dark:text-slate-400 font-medium">Manage authentication tokens for programmatic
                    access</p>
            </div>
        </div>
        <div class="flex items-center gap-6">
            <a href="{{ route('developer.docs') }}"
                class="text-sm font-bold text-blue-600 hover:text-blue-700 uppercase tracking-widest transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
                View Docs
            </a>
            <a href="{{ route('developer.overview') }}"
                class="text-sm font-bold text-slate-400 hover:text-slate-600 uppercase tracking-widest transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7 7-7" />
                </svg>
                Back to Portal
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Create Token Form --}}
        <div class="lg:col-span-1">
            <div
                class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden sticky top-8">
                <div
                    class="px-8 py-6 border-b border-slate-50 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/10">
                    <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-tight">Create New
                        Token</h3>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Setup a new API access key
                    </p>
                </div>

                <form wire:submit.prevent="createToken" class="p-8 space-y-6">
                    <div class="space-y-2">
                        <x-label value="Token Name"
                            class="uppercase text-[10px] tracking-widest font-black text-slate-400" />
                        <x-input wire:model="name" type="text"
                            class="w-full !rounded-2xl border-2 border-slate-50 dark:border-slate-800 focus:border-blue-500/30 focus:ring-4 focus:ring-blue-500/10 transition-all"
                            placeholder="e.g. Mobile App" />
                        <x-input-error for="name" />
                    </div>

                    <div class="space-y-4">
                        <x-label value="Abilities / Permissions"
                            class="uppercase text-[10px] tracking-widest font-black text-slate-400" />
                        <div class="grid grid-cols-1 gap-2 max-h-60 overflow-y-auto pr-2 custom-scrollbar">
                            @foreach($availablePermissions as $permission)
                                <label
                                    class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl cursor-pointer hover:bg-blue-50 dark:hover:bg-blue-900/10 transition-colors group">
                                    <input type="checkbox" wire:model="permissions" value="{{ $permission }}"
                                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                    <span
                                        class="text-xs font-bold text-slate-600 dark:text-slate-400 group-hover:text-blue-600 transition-colors">{{ $permission }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <button type="submit"
                        class="w-full py-4 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-[1.5rem] font-black uppercase tracking-widest text-xs shadow-xl shadow-slate-900/20 hover:scale-[1.02] active:scale-95 transition-all">
                        Generate Token
                    </button>
                </form>
            </div>
        </div>

        {{-- Tokens List --}}
        <div class="lg:col-span-2">
            <div
                class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
                <div
                    class="px-8 py-6 border-b border-slate-50 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/10">
                    <div>
                        <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-tight">Active
                            API Tokens</h3>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Existing
                            authentication keys</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-slate-50 dark:border-slate-800/50">
                                <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                    Token Name</th>
                                <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                    Last Used</th>
                                <th
                                    class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-slate-800/30">
                            @forelse($tokens as $token)
                                <tr class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                                    <td class="px-8 py-6">
                                        <div class="text-sm font-black text-slate-900 dark:text-white">{{ $token->name }}
                                        </div>
                                        <div class="flex flex-wrap gap-1 mt-2">
                                            @foreach($token->abilities as $ability)
                                                <span
                                                    class="text-[8px] font-black bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 px-2 py-0.5 rounded uppercase tracking-tighter">{{ $ability }}</span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-8 py-6">
                                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                            {{ $token->last_used_at ? $token->last_used_at->diffForHumans() : 'Never used' }}
                                        </div>
                                    </td>
                                    <td class="px-8 py-6 text-right">
                                        <button wire:click="deleteToken({{ $token->id }})"
                                            wire:confirm="Are you sure you want to revoke this token?"
                                            class="p-2 text-slate-300 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/20 rounded-xl transition-all">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-8 py-20 text-center">
                                        <div class="flex flex-col items-center gap-4">
                                            <div
                                                class="w-16 h-16 bg-slate-50 dark:bg-slate-800 rounded-3xl flex items-center justify-center text-slate-300">
                                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                                </svg>
                                            </div>
                                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">No
                                                active tokens found</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Token Reveal Modal --}}
    @if($showTokenModal)
        <div
            class="fixed inset-0 bg-slate-900/60 backdrop-blur-md flex items-center justify-center z-[110] p-4 animate-in fade-in duration-300">
            <div
                class="bg-white dark:bg-slate-900 rounded-[3rem] max-w-lg w-full shadow-3xl overflow-hidden border border-white/20">
                <div class="p-10 text-center space-y-6">
                    <div
                        class="w-20 h-20 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 rounded-[2rem] flex items-center justify-center mx-auto shadow-xl shadow-emerald-500/20">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>

                    <div>
                        <h3 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">Token
                            Created!</h3>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-2 px-8">This is the only
                            time we'll show your token. Please copy and store it securely.</p>
                    </div>

                    <div class="relative group">
                        <div class="bg-slate-900 rounded-3xl p-6 border border-white/5">
                            <code class="text-blue-400 font-mono text-sm break-all"
                                id="token-display">{{ $plainTextToken }}</code>
                        </div>
                        <button
                            onclick="navigator.clipboard.writeText('{{ $plainTextToken }}'); this.innerText = 'COPIED!';"
                            class="absolute -bottom-4 right-6 bg-emerald-500 text-white px-6 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-emerald-500/30 hover:scale-105 active:scale-95 transition-all">
                            Copy Token
                        </button>
                    </div>

                    <div class="pt-6">
                        <button wire:click="$set('showTokenModal', false)"
                            class="w-full py-4 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-2xl font-black uppercase tracking-widest text-[10px] hover:bg-slate-200 transition-colors">
                            I've Saved My Token
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>