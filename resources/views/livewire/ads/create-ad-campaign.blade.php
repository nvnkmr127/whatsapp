<div class="py-12 bg-gray-50/50 min-h-screen relative">
    <!-- Launching Overlay -->
    @if($isLaunching)
        <div
            class="fixed inset-0 bg-slate-900/60 backdrop-blur-md z-50 flex flex-col items-center justify-center text-white">
            <div class="relative w-24 h-24 mb-6">
                <div class="absolute inset-0 border-4 border-white/20 rounded-full"></div>
                <div class="absolute inset-0 border-4 border-wa-teal rounded-full animate-spin border-t-transparent"></div>
                <div class="absolute inset-0 flex items-center justify-center">
                    <svg class="w-10 h-10 text-wa-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                </div>
            </div>
            <h3 class="text-2xl font-black tracking-tight">Launching Campaign...</h3>
            <p class="text-white/60 mt-2 font-medium">Syncing with Meta Ads Manager</p>
        </div>
    @endif

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header -->
        <div class="mb-12 flex flex-col md:flex-row md:items-end justify-between gap-6">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <a href="{{ route('ads.manager') }}"
                        class="text-wa-teal font-bold text-xs uppercase tracking-widest hover:underline">Ads Manager</a>
                    <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <span class="text-gray-400 text-xs font-bold uppercase tracking-widest">Wizard</span>
                </div>
                <h2 class="text-4xl font-black text-slate-900 tracking-tight">New Ad Campaign</h2>
                <p class="text-slate-500 mt-2 text-lg">Create a "Click-to-WhatsApp" campaign in 4 easy steps.</p>
            </div>

            <!-- Horizontal Stepper -->
            <div class="flex items-center gap-3 bg-white p-2 rounded-2xl shadow-sm border border-gray-100">
                @foreach($steps as $step => $label)
                    <div
                        class="flex items-center gap-2 px-3 py-1.5 rounded-xl transition-all {{ $currentStep === $step ? 'bg-slate-900 text-white shadow-lg' : ($currentStep > $step ? 'text-wa-teal bg-wa-soft' : 'text-slate-400') }}">
                        <span
                            class="w-6 h-6 flex items-center justify-center rounded-lg text-xs font-black {{ $currentStep === $step ? 'bg-white/20' : ($currentStep > $step ? 'bg-wa-teal/10' : 'bg-slate-100') }}">
                            @if($currentStep > $step)
                                <svg class="w-4 h-4 text-wa-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            @else
                                {{ $step }}
                            @endif
                        </span>
                        <span
                            class="text-xs font-black uppercase tracking-wider whitespace-nowrap hidden lg:block">{{ $label }}</span>
                    </div>
                    @if(!$loop->last)
                        <div class="w-4 h-px bg-slate-200"></div>
                    @endif
                @endforeach
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">

            <!-- Wizard Body -->
            <div class="lg:col-span-12">
                <div
                    class="bg-white rounded-[40px] shadow-sm border border-gray-100 overflow-hidden min-h-[500px] flex flex-col">

                    <div class="flex-1 p-8 lg:p-12 overflow-y-auto">
                        <!-- Step 1: Campaign Details -->
                        @if($currentStep === 1)
                            <div class="max-w-2xl animate-in fade-in slide-in-from-bottom-4 duration-500">
                                <h3 class="text-2xl font-black text-slate-900 mb-2">Basic Info</h3>
                                <p class="text-slate-500 mb-8">Let's start with some general information about your
                                    campaign.</p>

                                <div class="space-y-8">
                                    <div class="group">
                                        <label
                                            class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-3 group-focus-within:text-wa-teal transition-colors">Campaign
                                            Display Name</label>
                                        <input type="text" wire:model="campaignName"
                                            class="w-full bg-slate-50 border-gray-200 rounded-2xl px-5 py-4 text-slate-700 focus:ring-4 focus:ring-wa-teal/10 focus:border-wa-teal focus:bg-white transition-all text-lg font-bold"
                                            placeholder="e.g. Summer Flash Sale 2024">
                                        @error('campaignName') <span
                                            class="text-rose-500 text-xs font-bold mt-2 block">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                        <div>
                                            <label
                                                class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-3">Objective</label>
                                            <div
                                                class="bg-wa-soft border border-wa-teal/20 rounded-2xl p-4 flex items-center gap-4">
                                                <div
                                                    class="w-12 h-12 bg-wa-teal text-white rounded-xl flex items-center justify-center shadow-lg">
                                                    <svg class="w-6 h-6" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <div class="font-black text-slate-900 text-sm">WhatsApp Traffic</div>
                                                    <div
                                                        class="text-[10px] text-wa-dark font-bold uppercase tracking-tight">
                                                        Optimized for Chat</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div>
                                            <label
                                                class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-3">Conversion
                                                Level</label>
                                            <div
                                                class="bg-gray-50 border border-gray-200 rounded-2xl p-4 flex items-center gap-4">
                                                <div
                                                    class="w-12 h-12 bg-slate-200 text-slate-600 rounded-xl flex items-center justify-center">
                                                    <svg class="w-6 h-6" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <div class="font-black text-slate-900 text-sm">Messaging Apps</div>
                                                    <div
                                                        class="text-[10px] text-slate-400 font-bold uppercase tracking-tight">
                                                        Direct to Business</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Step 2: Targeting -->
                        @if($currentStep === 2)
                            <div
                                class="grid grid-cols-1 lg:grid-cols-2 gap-12 animate-in fade-in slide-in-from-bottom-4 duration-500">
                                <div class="space-y-10">
                                    <div>
                                        <h3 class="text-2xl font-black text-slate-900 mb-2">Budget & Schedule</h3>
                                        <p class="text-slate-500 mb-6">How much would you like to spend?</p>

                                        <div class="flex gap-4 p-1.5 bg-slate-100 rounded-2xl mb-6">
                                            <button wire:click="$set('budgetType', 'DAILY')"
                                                class="flex-1 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all {{ $budgetType === 'DAILY' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-400 hover:text-slate-600' }}">Daily
                                                Budget</button>
                                            <button wire:click="$set('budgetType', 'LIFETIME')"
                                                class="flex-1 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all {{ $budgetType === 'LIFETIME' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-400 hover:text-slate-600' }}">Lifetime</button>
                                        </div>

                                        <div class="relative group">
                                            <div
                                                class="absolute inset-y-0 left-0 pl-6 flex items-center pointer-events-none">
                                                <span class="text-2xl font-black text-slate-300">$</span>
                                            </div>
                                            <input type="number" wire:model="dailyBudget"
                                                class="w-full bg-slate-50 border-gray-200 rounded-3xl pl-12 pr-6 py-5 text-3xl font-black text-slate-900 focus:ring-4 focus:ring-wa-teal/10 focus:border-wa-teal focus:bg-white transition-all shadow-inner"
                                                placeholder="0.00">
                                        </div>
                                        @error('dailyBudget') <span
                                            class="text-rose-500 text-xs font-bold mt-2 block">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div>
                                        <h3 class="text-2xl font-black text-slate-900 mb-2">Audience</h3>
                                        <p class="text-slate-500 mb-6">Who do you want to see your ad?</p>

                                        <div class="space-y-6">
                                            <div class="group">
                                                <label
                                                    class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3">Country
                                                    Targeting</label>

                                                <!-- Selected Countries Tags -->
                                                <div class="flex flex-wrap gap-2 mb-4">
                                                    @foreach($selectedCountries as $code)
                                                        @php $c = collect($countries)->firstWhere('code', $code); @endphp
                                                        <div
                                                            class="flex items-center gap-2 bg-wa-soft border border-wa-teal/20 text-wa-dark px-3 py-1.5 rounded-xl text-xs font-black group/tag transition-all">
                                                            {{ $c['name'] }}
                                                            <button wire:click="removeCountry('{{ $code }}')"
                                                                class="hover:text-rose-500">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                                    viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    @endforeach
                                                </div>

                                                <div class="relative">
                                                    <div
                                                        class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-300">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                        </svg>
                                                    </div>
                                                    <input type="text" wire:model.live="countrySearch"
                                                        class="w-full bg-slate-50 border-slate-200 rounded-2xl pl-12 py-3.5 text-sm font-bold placeholder-slate-300 focus:ring-wa-teal/20 focus:border-wa-teal transition-all"
                                                        placeholder="Search countries...">

                                                    @if($countrySearch)
                                                        <div
                                                            class="absolute z-30 mt-2 w-full bg-white border border-slate-100 rounded-2xl shadow-2xl max-h-48 overflow-y-auto p-2 space-y-1">
                                                            @foreach($countries as $c)
                                                                @if(stripos($c['name'], $countrySearch) !== false && !in_array($c['code'], $selectedCountries))
                                                                    <button
                                                                        wire:click="addCountry('{{ $c['code'] }}'); $set('countrySearch', '')"
                                                                        class="w-full text-left px-4 py-2 hover:bg-wa-soft rounded-xl text-sm font-bold text-slate-700 transition-colors">
                                                                        {{ $c['name'] }}
                                                                    </button>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                                @error('selectedCountries') <span
                                                    class="text-rose-500 text-xs font-bold mt-2 block">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div class="grid grid-cols-2 gap-6">
                                                <div>
                                                    <label
                                                        class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3">Age
                                                        Range</label>
                                                    <div class="flex items-center gap-2">
                                                        <input type="number" wire:model.live="ageMin"
                                                            class="w-full bg-slate-50 border-slate-200 rounded-xl py-2.5 text-center text-sm font-bold">
                                                        <span class="text-slate-300">—</span>
                                                        <input type="number" wire:model.live="ageMax"
                                                            class="w-full bg-slate-50 border-slate-200 rounded-xl py-2.5 text-center text-sm font-bold">
                                                    </div>
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3">Gender</label>
                                                    <select wire:model.live="gender"
                                                        class="w-full bg-slate-50 border-slate-200 rounded-xl py-2.5 text-sm font-bold">
                                                        <option value="ALL">All Genders</option>
                                                        <option value="MALE">Male</option>
                                                        <option value="FEMALE">Female</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Audience Insight Card -->
                                <div class="bg-slate-900 rounded-[35px] p-8 text-white relative overflow-hidden group">
                                    <div
                                        class="absolute top-0 right-0 w-32 h-32 bg-wa-teal opacity-10 rounded-full blur-3xl -mr-16 -mt-16 group-hover:opacity-20 transition-opacity">
                                    </div>

                                    <div class="relative z-10">
                                        <div class="flex items-center justify-between mb-8">
                                            <h4 class="text-xs font-black text-wa-teal uppercase tracking-[0.3em]">Potential
                                                Reach</h4>
                                            <div
                                                class="flex h-2 w-2 rounded-full {{ $audienceReach > 100000 ? 'bg-wa-teal' : 'bg-rose-500' }} animate-pulse shadow-[0_0_8px_rgba(37,211,102,0.8)]">
                                            </div>
                                        </div>

                                        <div class="space-y-8">
                                            <div>
                                                <div class="flex justify-between items-end mb-2">
                                                    <span
                                                        class="text-sm font-bold text-slate-400 uppercase tracking-widest">Est.
                                                        People</span>
                                                    <span
                                                        class="text-3xl font-black tabular-nums">{{ number_format($audienceReach) }}</span>
                                                </div>
                                                <div class="w-full h-2 bg-slate-800 rounded-full overflow-hidden">
                                                    <div class="h-full bg-gradient-to-r from-wa-teal/50 to-wa-teal rounded-full transition-all duration-700 ease-out"
                                                        style="width: {{ min(100, ($audienceReach / 10000000) * 100) }}%">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="bg-white/5 border border-white/10 rounded-3xl p-6 space-y-4">
                                                <div class="flex items-center gap-3">
                                                    <svg class="w-5 h-5 text-wa-teal" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    <p class="text-[11px] font-medium leading-relaxed text-slate-300">Your
                                                        total audience size is <span
                                                            class="text-white font-bold">Great</span>. Ads on messaging apps
                                                        perform better with broader targeting.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Step 3: Creative -->
                        @if($currentStep === 3)
                            <div
                                class="grid grid-cols-1 lg:grid-cols-2 gap-16 animate-in fade-in slide-in-from-bottom-4 duration-500">
                                <div class="space-y-10">
                                    <div>
                                        <h3 class="text-2xl font-black text-slate-900 mb-2">Ad Creative</h3>
                                        <p class="text-slate-500 mb-8">Make your ad stand out with great copy and visuals.
                                        </p>

                                        <!-- Media Switcher -->
                                        <div class="flex gap-4 p-1.5 bg-slate-100 rounded-2xl mb-8">
                                            <button wire:click="$set('mediaType', 'IMAGE')"
                                                class="flex-1 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all {{ $mediaType === 'IMAGE' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-400 hover:text-slate-600' }}">Image</button>
                                            <button wire:click="$set('mediaType', 'VIDEO')"
                                                class="flex-1 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all {{ $mediaType === 'VIDEO' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-400 hover:text-slate-600' }}">Video</button>
                                        </div>

                                        <div class="space-y-8">
                                            <div>
                                                <label
                                                    class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3">Upload
                                                    {{ $mediaType }}</label>
                                                <div
                                                    class="relative group cursor-pointer border-2 border-dashed border-slate-200 rounded-[30px] p-2 hover:border-wa-teal/50 hover:bg-wa-soft/10 transition-all">
                                                    @if($mediaType === 'IMAGE')
                                                        <input type="file" wire:model="adImage"
                                                            class="absolute inset-0 opacity-0 cursor-pointer z-10">
                                                        <div
                                                            class="bg-slate-50 rounded-[25px] py-12 flex flex-col items-center justify-center text-center px-6">
                                                            @if($adImage)
                                                                <img src="{{ $adImage->temporaryUrl() }}"
                                                                    class="h-40 w-full object-cover rounded-2xl shadow-xl">
                                                            @else
                                                                <div
                                                                    class="w-16 h-16 bg-white rounded-2xl shadow-sm flex items-center justify-center text-wa-teal mb-4 group-hover:scale-110 transition-transform">
                                                                    <svg class="w-8 h-8" fill="none" stroke="currentColor"
                                                                        viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                                            stroke-width="2"
                                                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                                    </svg>
                                                                </div>
                                                                <span class="text-slate-900 font-bold">Drop Image Here</span>
                                                                <span
                                                                    class="text-xs text-slate-400 mt-1 uppercase font-black tracking-widest">1080x1080
                                                                    (Square)</span>
                                                            @endif
                                                        </div>
                                                    @else
                                                        <input type="file" wire:model="adVideo"
                                                            class="absolute inset-0 opacity-0 cursor-pointer z-10">
                                                        <div
                                                            class="bg-slate-50 rounded-[25px] py-12 flex flex-col items-center justify-center text-center px-6">
                                                            @if($adVideo)
                                                                <div class="flex flex-col items-center">
                                                                    <svg class="w-16 h-16 text-wa-teal mb-2" fill="currentColor"
                                                                        viewBox="0 0 20 20">
                                                                        <path fill-rule="evenodd"
                                                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"
                                                                            clip-rule="evenodd" />
                                                                    </svg>
                                                                    <span class="text-slate-900 font-bold">Video Uploaded!</span>
                                                                    <span
                                                                        class="text-xs text-slate-400 truncate max-w-[200px]">{{ $adVideo->getClientOriginalName() }}</span>
                                                                </div>
                                                            @else
                                                                <div
                                                                    class="w-16 h-16 bg-white rounded-2xl shadow-sm flex items-center justify-center text-rose-500 mb-4 group-hover:scale-110 transition-transform">
                                                                    <svg class="w-8 h-8" fill="none" stroke="currentColor"
                                                                        viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                                            stroke-width="2"
                                                                            d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2-2v8a2 2 0 002 2z" />
                                                                    </svg>
                                                                </div>
                                                                <span class="text-slate-900 font-bold">Upload MP4 Video</span>
                                                                <span
                                                                    class="text-xs text-slate-400 mt-1 uppercase font-black tracking-widest">Vertical
                                                                    9:16 or Square 1:1</span>
                                                            @endif
                                                        </div>
                                                    @endif
                                                </div>
                                                @error('adImage') <span
                                                    class="text-rose-500 text-xs font-bold mt-2 block">{{ $message }}</span>
                                                @enderror
                                                @error('adVideo') <span
                                                    class="text-rose-500 text-xs font-bold mt-2 block">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div class="space-y-6">
                                                <div class="group">
                                                    <label
                                                        class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 group-focus-within:text-wa-teal">Primary
                                                        Text</label>
                                                    <textarea wire:model.live="primaryText" rows="3"
                                                        class="w-full bg-slate-50 border-slate-200 rounded-2xl px-5 py-4 text-sm font-medium focus:ring-wa-teal/20 focus:border-wa-teal transition-all"
                                                        placeholder="Tell people what your ad is about..."></textarea>
                                                </div>

                                                <div class="group">
                                                    <label
                                                        class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 group-focus-within:text-wa-teal">Headline</label>
                                                    <input type="text" wire:model.live="headline"
                                                        class="w-full bg-slate-50 border-slate-200 rounded-2xl px-5 py-4 text-sm font-bold focus:ring-wa-teal/20 focus:border-wa-teal transition-all"
                                                        placeholder="Catchy headline">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="bg-blue-50 border border-blue-100 rounded-[30px] p-8">
                                        <div class="flex items-center gap-4 mb-4">
                                            <div
                                                class="w-10 h-10 bg-blue-600 text-white rounded-xl flex items-center justify-center shadow-lg">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                                                </svg>
                                            </div>
                                            <h4 class="font-black text-slate-900 leading-tight">Post-Click Automation</h4>
                                        </div>
                                        <p class="text-slate-500 text-sm mb-6 leading-relaxed">Choose which automation flow
                                            starts when someone clicks your ad.</p>

                                        <select wire:model="selectedAutomationId"
                                            class="w-full bg-white border-blue-200 rounded-xl px-5 py-3.5 text-sm font-bold text-slate-700 shadow-sm focus:ring-4 focus:ring-blue-100 focus:border-blue-400 transition-all">
                                            <option value="">Select a Bot Flow...</option>
                                            @foreach($automations as $auto)
                                                <option value="{{ $auto->id }}">{{ $auto->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <!-- Meta Ad Preview -->
                                <div class="flex flex-col items-center sticky top-8">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] mb-6">Live
                                        Meta Preview</span>

                                    <div
                                        class="bg-white w-full max-w-[340px] rounded-[32px] shadow-2xl border border-slate-100 overflow-hidden">
                                        <!-- Post Header -->
                                        <div class="p-4 flex items-center gap-3">
                                            <div
                                                class="w-10 h-10 bg-slate-200 rounded-full flex items-center justify-center text-slate-400 overflow-hidden">
                                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                                    <path
                                                        d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="text-sm font-bold text-slate-900 leading-none mb-0.5">Your
                                                    Business Page</div>
                                                <div
                                                    class="text-[10px] text-slate-400 font-bold uppercase tracking-widest flex items-center gap-1">
                                                    Sponsored • <svg class="w-2.5 h-2.5" fill="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path
                                                            d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z" />
                                                    </svg>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Post Body -->
                                        <div class="px-4 pb-3 text-sm text-slate-800 leading-relaxed min-h-[40px]">
                                            {{ $primaryText ?: 'Your ad text will appear here. Write something compelling to encourage clicks.' }}
                                        </div>

                                        <!-- Visual -->
                                        <div
                                            class="aspect-square bg-slate-100 flex items-center justify-center text-slate-300 relative overflow-hidden">
                                            @if($mediaType === 'IMAGE')
                                                @if($adImage)
                                                    <img src="{{ $adImage->temporaryUrl() }}" class="w-full h-full object-cover">
                                                @else
                                                    <svg class="w-16 h-16 opacity-20" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                @endif
                                            @else
                                                @if($adVideo)
                                                    <video src="{{ $adVideo->temporaryUrl() }}" autoplay muted loop
                                                        class="w-full h-full object-cover"></video>
                                                @else
                                                    <svg class="w-16 h-16 opacity-20" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2-2v8a2 2 0 002 2z" />
                                                    </svg>
                                                @endif
                                            @endif
                                        </div>

                                        <!-- CTA Bar -->
                                        <div
                                            class="p-3 bg-slate-50 flex justify-between items-center border-t border-slate-100">
                                            <div class="max-w-[70%]">
                                                <div
                                                    class="text-[9px] text-slate-400 font-black uppercase tracking-[0.1em] mb-0.5">
                                                    WHATSAPP</div>
                                                <div class="text-[13px] font-black text-slate-900 truncate">
                                                    {{ $headline ?: 'Talk to us' }}</div>
                                            </div>
                                            <div class="flex flex-col items-center">
                                                <button
                                                    class="bg-gray-200 text-slate-800 px-4 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-tight flex items-center gap-1.5">
                                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                                                        <path
                                                            d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L0 24l6.335-1.662c1.72.937 3.659 1.432 5.631 1.433h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                                                    </svg>
                                                    Chat
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div
                                        class="mt-8 flex items-center gap-1.5 p-2 bg-slate-100 rounded-full border border-slate-200">
                                        <div class="w-1.5 h-1.5 bg-wa-teal rounded-full animate-pulse"></div>
                                        <span
                                            class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Interactive
                                            Preview</span>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Step 4: Final Review -->
                        @if($currentStep === 4)
                            <div class="max-w-2xl mx-auto text-center animate-in zoom-in-95 duration-500">
                                <div
                                    class="w-24 h-24 bg-green-50 text-green-500 rounded-[40px] flex items-center justify-center mx-auto mb-8 shadow-xl shadow-green-500/10 rotate-3">
                                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <h3 class="text-4xl font-black text-slate-900 tracking-tight">Ready to Launch?</h3>
                                    <p class="text-slate-500 mt-4 text-lg">Review your campaign details one last time before we push it to Meta.</p>

                                    <div class="mt-12 bg-gray-50 rounded-[40px] p-10 text-left border border-gray-100 grid grid-cols-1 md:grid-cols-2 gap-10 relative overflow-hidden">
                                        <div class="absolute -top-10 -right-10 w-40 h-40 bg-slate-200/50 rounded-full blur-3xl"></div>

                                        <div class="space-y-6 relative z-10">
                                            <div>
                                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block">Campaign Name</label>
                                                <div class="text-lg font-black text-slate-900">{{ $campaignName }}</div>
                                            </div>
                                            <div>
                                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block">Budget</label>
                                                <div class="text-lg font-black text-slate-900">${{ $dailyBudget }} <span class="text-slate-400 font-bold text-sm">/ {{ strtolower($budgetType) }}</span></div>
                                            </div>
                                            <div>
                                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block">Targeting</label>
                                                <div class="flex flex-wrap gap-1.5 mt-2">
                                                    @foreach($selectedCountries as $code)
                                                        <span class="px-2 py-0.5 bg-white border border-slate-200 rounded-md text-[10px] font-bold text-slate-600 uppercase">{{ $code }}</span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>

                                        <div class="space-y-6 relative z-10">
                                            <div>
                                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block">Ad Assets</label>
                                                <div class="bg-white p-3 rounded-2xl border border-slate-200 flex items-center gap-3">
                                                    <div class="w-12 h-12 bg-slate-100 rounded-lg overflow-hidden">
                                                        @if($mediaType === 'IMAGE' && $adImage)
                                                            <img src="{{ $adImage->temporaryUrl() }}" class="w-full h-full object-cover">
                                                        @elseif($mediaType === 'VIDEO' && $adVideo)
                                                            <div class="w-full h-full flex items-center justify-center bg-slate-900 text-wa-teal">
                                                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h12a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0013 8v4a1 1 0 001.553.894l2-1a1 1 0 000-1.788l-2-1z"/></svg>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div>
                                                        <div class="text-sm font-black text-slate-900">{{ $headline ?: 'Click to WhatsApp' }}</div>
                                                        <div class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">{{ $mediaType }} Ad</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div>
                                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 block">Automation Bot</label>
                                                <div class="flex items-center gap-2">
                                                    <div class="w-1.5 h-1.5 bg-blue-500 rounded-full"></div>
                                                    <div class="text-sm font-black text-blue-600">
                                                        @php $bot = collect($automations)->firstWhere('id', $selectedAutomationId); @endphp
                                                        {{ $bot ? $bot->name : 'No bot connected' }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                        @endif
                    </div>

                    <!-- Footer Controls -->
                    <div class="p-8 bg-gray-50/80 border-t border-gray-100 backdrop-blur-sm flex justify-between items-center relative z-20">
                        @if($currentStep > 1)
                            <button wire:click="previousStep" class="flex items-center gap-2 text-slate-400 hover:text-slate-700 font-black transition-colors px-6 py-3 rounded-2xl group">
                                <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                Back
                            </button>
                        @else
                            <a href="{{ route('ads.manager') }}" class="text-slate-400 hover:text-slate-700 font-black px-6">Cancel</a>
                        @endif

                        <div class="flex items-center gap-4">
                            @if($currentStep < 4)
                                <button wire:click="nextStep" class="bg-slate-900 hover:bg-black text-white px-10 py-4 rounded-[20px] font-black shadow-xl shadow-slate-900/10 transition-all flex items-center gap-3 active:scale-95 group">
                                    Next Step
                                    <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                                </button>
                            @else
                                <button wire:click="launchCampaign" class="bg-wa-teal hover:bg-wa-dark text-white px-12 py-5 rounded-[22px] font-black shadow-2xl shadow-wa-teal/20 transition-all flex items-center gap-3 active:scale-95 hover:-translate-y-1">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    Launch Ad Campaign
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>