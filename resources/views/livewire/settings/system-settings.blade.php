<div class="py-12 bg-slate-50/50 dark:bg-slate-900/50 min-h-screen">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">
                System <span class="text-indigo-500">Settings</span>
            </h1>
            <p class="text-slate-500 font-medium">Manage global workspace configurations, branding, and system defaults.</p>
        </div>

        <form wire:submit.prevent="save">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Left Column: Branding & Appearance -->
                <div class="lg:col-span-1 space-y-8">
                    <!-- Logo Upload -->
                    <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden p-8 text-center group">
                        <h3 class="text-xs font-black uppercase tracking-widest text-slate-400 mb-6">Workspace Logo</h3>
                        
                        <div class="relative w-40 h-40 mx-auto mb-6 bg-slate-50 dark:bg-slate-800 rounded-2xl flex items-center justify-center border-2 border-dashed border-slate-200 dark:border-slate-700 overflow-hidden group-hover:border-indigo-500/50 transition-colors">
                            @if ($logo)
                                <img src="{{ $logo->temporaryUrl() }}" class="w-full h-full object-cover">
                            @elseif ($currentLogoPath)
                                <img src="{{ Storage::url($currentLogoPath) }}" class="w-full h-full object-cover">
                            @else
                                <div class="text-slate-300 dark:text-slate-600 flex flex-col items-center gap-2">
                                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <span class="text-[10px] font-bold uppercase tracking-wider">No Logo</span>
                                </div>
                            @endif

                            <div wire:loading wire:target="logo" class="absolute inset-0 bg-slate-900/50 flex items-center justify-center">
                                <svg class="w-6 h-6 text-white animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            </div>
                        </div>

                        <div class="flex flex-col gap-3">
                            <label class="cursor-pointer">
                                <span class="w-full flex items-center justify-center gap-2 px-6 py-3 bg-indigo-50 dark:bg-indigo-900/10 text-indigo-600 dark:text-indigo-400 font-black uppercase tracking-widest text-[10px] rounded-xl hover:bg-indigo-100 dark:hover:bg-indigo-900/20 transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                    Upload New
                                </span>
                                <input type="file" wire:model="logo" class="hidden">
                            </label>

                            @if ($currentLogoPath && !$logo)
                                <button type="button" wire:click="removeLogo" class="w-full flex items-center justify-center gap-2 px-6 py-3 bg-slate-50 dark:bg-slate-800 text-rose-500 font-black uppercase tracking-widest text-[10px] rounded-xl hover:bg-rose-50 dark:hover:bg-rose-900/10 transition-all">
                                    Remove Logo
                                </button>
                            @endif
                        </div>
                        
                        @error('logo') <span class="text-rose-500 text-xs font-bold uppercase mt-2 block">{{ $message }}</span> @enderror
                    </div>

                    <!-- Appearance -->
                    <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden p-8">
                        <h3 class="text-xs font-black uppercase tracking-widest text-slate-400 mb-6">Appearance</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500 mb-2 block">Primary Identity Color</label>
                                <div class="flex items-center gap-3">
                                    <input type="color" wire:model.live="primaryColor" 
                                        class="h-12 w-12 p-1 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl cursor-pointer">
                                    <input type="text" wire:model.live="primaryColor"
                                        class="flex-1 px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-900 dark:text-white font-black uppercase text-sm tracking-wider">
                                </div>
                                <p class="text-[10px] mt-2 text-slate-400 font-medium">Used for core UI accents and branding.</p>
                                @error('primaryColor') <span class="text-rose-500 text-xs font-bold uppercase mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Reorganized Settings -->
                <div class="lg:col-span-2 space-y-8">
                    
                    <!-- Regional & Localization Card -->
                    <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
                        <div class="p-8 md:p-10 space-y-8">
                            <div>
                                <h3 class="text-xs font-black uppercase tracking-widest text-indigo-500 border-b border-slate-50 dark:border-slate-800 pb-2 mb-6">Regional & Localization</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Target Market -->
                                    <div class="space-y-2">
                                        <label class="text-xs font-black uppercase tracking-widest text-slate-500">Target Market / Country</label>
                                        <div class="relative">
                                            <select wire:model.live="selectedCountry" 
                                                class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-900 dark:text-white font-bold cursor-pointer focus:ring-2 focus:ring-indigo-500/20 transition-all appearance-none">
                                                <option value="">Choose Country...</option>
                                                @foreach($countries as $code => $data)
                                                    <option value="{{ $code }}">{{ $data['label'] }}</option>
                                                @endforeach
                                            </select>
                                            <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                            </div>
                                        </div>
                                        @error('selectedCountry') <span class="text-rose-500 text-xs font-bold uppercase">{{ $message }}</span> @enderror
                                    </div>

                                    <!-- Primary Language -->
                                    <div class="space-y-2">
                                        <label class="text-xs font-black uppercase tracking-widest text-slate-500">System Language</label>
                                        <div class="relative">
                                            <select wire:model="language" 
                                                class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-900 dark:text-white font-bold cursor-pointer focus:ring-2 focus:ring-indigo-500/20 transition-all appearance-none">
                                                <option value="en">English (US)</option>
                                                <option value="hi">Hindi (India)</option>
                                                <option value="ar">Arabic (Gulf)</option>
                                                <option value="es">Spanish (International)</option>
                                                <option value="ku">Kurdish (Northern Iraq)</option>
                                            </select>
                                            <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Currency Symbol -->
                                    <div class="space-y-2">
                                        <label class="text-xs font-black uppercase tracking-widest text-slate-500">Transaction Currency</label>
                                        <input type="text" wire:model="currencySymbol" placeholder="$"
                                            class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-900 dark:text-white font-black placeholder:text-slate-400 focus:ring-2 focus:ring-indigo-500/20 transition-all">
                                    </div>

                                    <!-- Timezone Override -->
                                    <div class="space-y-2">
                                        <label class="text-xs font-black uppercase tracking-widest text-slate-500">System Timezone</label>
                                        <div class="relative">
                                            <select wire:model="timezone" 
                                                class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-900 dark:text-white font-bold cursor-pointer focus:ring-2 focus:ring-indigo-500/20 transition-all appearance-none">
                                                @foreach($timezones as $tz => $label)
                                                    <option value="{{ $tz }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Date Format -->
                                    <div class="space-y-2 md:col-span-2">
                                        <label class="text-xs font-black uppercase tracking-widest text-slate-500">International Date Format</label>
                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                            @foreach(['Y-m-d' => 'YYYY-MM-DD', 'd/m/Y' => 'DD/MM/YYYY', 'm/d/Y' => 'MM/DD/YYYY', 'd-m-Y' => 'DD-MM-YYYY'] as $value => $label)
                                                <button type="button" wire:click="$set('dateFormat', '{{ $value }}')"
                                                    class="px-4 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all {{ $dateFormat === $value ? 'bg-indigo-600 text-white shadow-lg' : 'bg-slate-50 dark:bg-slate-800 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700' }}">
                                                    {{ $label }}
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                @if($metaPolicyInfo)
                                <!-- Meta Policy Insight Alert (Contextual) -->
                                <div class="mt-8 p-6 bg-amber-50 dark:bg-amber-900/10 border border-amber-100 dark:border-amber-900/20 rounded-3xl flex gap-5 animate-in fade-in slide-in-from-top-4 duration-500">
                                    <div class="flex-shrink-0 w-14 h-14 bg-amber-100 dark:bg-amber-900/30 rounded-2xl flex items-center justify-center text-amber-600 dark:text-amber-400 shadow-sm">
                                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-black text-amber-900 dark:text-amber-200 uppercase tracking-[0.2em] mb-2">Meta Compliance Monitor: {{ $countries[$selectedCountry]['label'] ?? 'General Policy' }}</h4>
                                        <p class="text-xs font-bold text-amber-800/80 dark:text-amber-400/80 leading-loose italic">"{{ $metaPolicyInfo }}"</p>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Operational & Workspace Card -->
                    <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
                        <div class="p-8 md:p-10 space-y-8">
                            <div>
                                <h3 class="text-xs font-black uppercase tracking-widest text-slate-400 border-b border-slate-50 dark:border-slate-800 pb-2 mb-6">Workspace Configuration</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-2 md:col-span-2">
                                        <label class="text-xs font-black uppercase tracking-widest text-slate-500">Official Workspace Name</label>
                                        <input type="text" wire:model="teamName"
                                            class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-900 dark:text-white font-black placeholder:text-slate-400 focus:ring-2 focus:ring-indigo-500/20 transition-all">
                                    </div>

                                    <div class="space-y-2">
                                        <label class="text-xs font-black uppercase tracking-widest text-slate-500">Support Desk Email</label>
                                        <input type="email" wire:model="supportEmail" placeholder="support@company.com"
                                            class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-900 dark:text-white font-bold placeholder:text-slate-400 focus:ring-2 focus:ring-indigo-500/20 transition-all">
                                    </div>

                                    <div class="space-y-2">
                                        <label class="text-xs font-black uppercase tracking-widest text-slate-500">UI Pagination Limit</label>
                                        <input type="number" wire:model="paginationLimit" min="5" max="100"
                                            class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-900 dark:text-white font-black placeholder:text-slate-400 focus:ring-2 focus:ring-indigo-500/20 transition-all">
                                    </div>
                                </div>
                            </div>

                            <!-- Maintenance Control -->
                            <div class="pt-6 border-t border-slate-50 dark:border-slate-800">
                                <div class="p-6 bg-slate-50 dark:bg-slate-800/50 rounded-3xl flex items-center justify-between">
                                    <div>
                                        <div class="text-sm font-black uppercase tracking-widest text-slate-900 dark:text-white">System Maintenance Mode</div>
                                        <div class="text-[10px] text-slate-500 font-bold mt-1 uppercase tracking-wider">Restrict regular user access for updates</div>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" wire:model="maintenanceMode" class="sr-only peer">
                                        <div class="w-14 h-7 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-rose-300 dark:peer-focus:ring-rose-800 rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[4px] after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-gray-600 peer-checked:bg-rose-600"></div>
                                    </label>
                                </div>
                                @if($maintenanceMode)
                                    <div class="mt-4 p-4 bg-rose-50 dark:bg-rose-900/10 text-rose-600 dark:text-rose-400 text-[10px] font-black uppercase tracking-widest rounded-2xl border border-rose-100 dark:border-rose-900/20 flex items-center gap-3">
                                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        System access restricted to administrators.
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Footer Actions -->
                        <div class="p-8 md:p-10 bg-slate-50/50 dark:bg-slate-800/50 flex items-center justify-between border-t border-slate-50 dark:border-slate-800">
                            <div x-data="{ show: false }" x-show="show" x-transition x-init="@this.on('saved', () => { show = true; setTimeout(() => { show = false }, 3000) })" style="display: none;"
                                class="text-xs font-black text-wa-green uppercase tracking-widest flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                configuration Updated
                            </div>

                            <button type="submit" 
                                class="ml-auto px-12 py-4 bg-indigo-600 text-white font-black uppercase tracking-widest text-xs rounded-2xl shadow-2xl shadow-indigo-600/30 hover:bg-indigo-700 hover:-translate-y-1 active:translate-y-0 transition-all">
                                Deploy Changes
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>