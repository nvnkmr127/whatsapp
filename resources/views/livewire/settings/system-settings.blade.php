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
                
                <!-- Left Column: Branding -->
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

                            @if ($logo) <!-- Loading State -->
                                <div wire:loading wire:target="logo" class="absolute inset-0 bg-slate-900/50 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                </div>
                            @endif
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
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500 mb-2 block">Primary Color</label>
                                <div class="flex items-center gap-3">
                                    <input type="color" wire:model="primaryColor" 
                                        class="h-10 w-10 p-1 bg-slate-50 dark:bg-slate-800 border-none rounded-xl cursor-pointer">
                                    <input type="text" wire:model="primaryColor"
                                        class="flex-1 px-4 py-2 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold uppercase text-sm">
                                </div>
                                <p class="text-[10px] mt-2 text-slate-400 font-medium">Used for buttons, links, and accents.</p>
                                @error('primaryColor') <span class="text-rose-500 text-xs font-bold uppercase mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Settings Form -->
                <div class="lg:col-span-2 space-y-8">
                    <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
                        <div class="p-8 md:p-10 space-y-10">
                            
                            <!-- General Configuration -->
                            <div>
                                <h3 class="text-xs font-black uppercase tracking-widest text-slate-400 border-b border-slate-50 dark:border-slate-800 pb-2 mb-6">General Configuration</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <label class="text-xs font-black uppercase tracking-widest text-slate-500">Workspace Name</label>
                                        <input type="text" wire:model="teamName"
                                            class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-900 dark:text-white font-bold placeholder:text-slate-400 focus:ring-2 focus:ring-indigo-500/20 transition-all">
                                        @error('teamName') <span class="text-rose-500 text-xs font-bold uppercase">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="space-y-2">
                                        <label class="text-xs font-black uppercase tracking-widest text-slate-500">System Timezone</label>
                                        <div class="relative">
                                            <select wire:model="timezone" 
                                                class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-900 dark:text-white font-bold cursor-pointer focus:ring-2 focus:ring-indigo-500/20 transition-all appearance-none">
                                                @foreach($timezones as $tz => $label)
                                                    <option value="{{ $tz }}">{{ $label }} ({{ $tz }})</option>
                                                @endforeach
                                            </select>
                                            <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                            </div>
                                        </div>
                                        @error('timezone') <span class="text-rose-500 text-xs font-bold uppercase">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Localization -->
                            <div>
                                <h3 class="text-xs font-black uppercase tracking-widest text-slate-400 border-b border-slate-50 dark:border-slate-800 pb-2 mb-6">Localization & Formats</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <label class="text-xs font-black uppercase tracking-widest text-slate-500">Currency Symbol</label>
                                        <input type="text" wire:model="currencySymbol" placeholder="$"
                                            class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-900 dark:text-white font-bold placeholder:text-slate-400 focus:ring-2 focus:ring-indigo-500/20 transition-all">
                                        @error('currencySymbol') <span class="text-rose-500 text-xs font-bold uppercase">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="space-y-2">
                                        <label class="text-xs font-black uppercase tracking-widest text-slate-500">Date Format</label>
                                        <div class="relative">
                                            <select wire:model="dateFormat" 
                                                class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-900 dark:text-white font-bold cursor-pointer focus:ring-2 focus:ring-indigo-500/20 transition-all appearance-none">
                                                <option value="Y-m-d">YYYY-MM-DD (2024-12-31)</option>
                                                <option value="d/m/Y">DD/MM/YYYY (31/12/2024)</option>
                                                <option value="m/d/Y">MM/DD/YYYY (12/31/2024)</option>
                                                <option value="d-m-Y">DD-MM-YYYY (31-12-2024)</option>
                                            </select>
                                            <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                            </div>
                                        </div>
                                        @error('dateFormat') <span class="text-rose-500 text-xs font-bold uppercase">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Operational Control -->
                            <div>
                                <h3 class="text-xs font-black uppercase tracking-widest text-slate-400 border-b border-slate-50 dark:border-slate-800 pb-2 mb-6">Operational Control</h3>
                                <div class="space-y-6">
                                    <div class="flex flex-col md:flex-row gap-6">
                                        <div class="w-full space-y-2">
                                            <label class="text-xs font-black uppercase tracking-widest text-slate-500">Default Pagination Limit</label>
                                            <input type="number" wire:model="paginationLimit" min="5" max="100"
                                                class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-900 dark:text-white font-bold placeholder:text-slate-400 focus:ring-2 focus:ring-indigo-500/20 transition-all">
                                            @error('paginationLimit') <span class="text-rose-500 text-xs font-bold uppercase">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="w-full space-y-2">
                                            <label class="text-xs font-black uppercase tracking-widest text-slate-500">Support Email</label>
                                            <input type="email" wire:model="supportEmail" placeholder="support@company.com"
                                                class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-900 dark:text-white font-bold placeholder:text-slate-400 focus:ring-2 focus:ring-indigo-500/20 transition-all">
                                            @error('supportEmail') <span class="text-rose-500 text-xs font-bold uppercase">{{ $message }}</span> @enderror
                                        </div>
                                    </div>

                                    <div class="p-6 bg-slate-50 dark:bg-slate-800/50 rounded-2xl flex items-center justify-between">
                                        <div>
                                            <div class="font-bold text-slate-900 dark:text-white">Maintenance Mode</div>
                                            <div class="text-xs text-slate-500 mt-1">Temporarily disable system access for users</div>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" wire:model="maintenanceMode" class="sr-only peer">
                                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-rose-300 dark:peer-focus:ring-rose-800 rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-rose-600"></div>
                                        </label>
                                    </div>
                                    @if($maintenanceMode)
                                        <div class="p-4 bg-rose-50 dark:bg-rose-900/10 text-rose-600 dark:text-rose-400 text-xs font-bold rounded-xl border border-rose-100 dark:border-rose-900/20">
                                            Warning: Enabling maintenance mode will prevent regular users from logging in or accessing the dashboard.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="p-8 md:p-10 bg-slate-50/50 dark:bg-slate-800/50 flex justify-end gap-4 border-t border-slate-50 dark:border-slate-800">
                            <span class="mr-auto text-xs font-medium text-wa-green flex items-center gap-2" x-data="{ show: false }" x-show="show" x-transition.opacity.out.duration.1500ms x-init="@this.on('saved', () => { show = true; setTimeout(() => { show = false }, 2000) })" style="display: none;">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Settings Saved Successfully
                            </span>

                            <button type="submit" 
                                class="px-10 py-4 bg-indigo-600 text-white font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-indigo-600/20 hover:scale-[1.02] active:scale-95 transition-all">
                                Save Changes
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>