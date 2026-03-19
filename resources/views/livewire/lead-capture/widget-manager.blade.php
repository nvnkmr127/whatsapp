@php
    $selectedWidget = $this->selectedWidget;
    $widgetLink = $selectedWidget ? route('qr.show', $selectedWidget->slug) : '';
    $leadEndpoint = $selectedWidget ? route('qr.lead', $selectedWidget->slug) : '';
    $widgetColor = $selectedWidget ? $selectedWidget->widget_color : '#25D366';
    $buttonText = $selectedWidget ? $selectedWidget->button_text : 'Chat with us';
    $collectName = $selectedWidget ? $selectedWidget->collect_name : false;
    $collectEmail = $selectedWidget ? $selectedWidget->collect_email : false;
    $welcomeMessage = $selectedWidget ? $selectedWidget->welcome_message : 'Hi, How can I help you?';
    $footerText = $selectedWidget ? $selectedWidget->footer_text : 'Powered by Growth Tools';
    $placeholderName = $selectedWidget ? $selectedWidget->placeholder_name : 'Full Name';
    $placeholderEmail = $selectedWidget ? $selectedWidget->placeholder_email : 'Email Address';
@endphp

<div class="space-y-8 animate-in fade-in duration-500" x-data="{ previewMode: 'mobile' }">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <div class="p-2 bg-green-100 text-wa-teal rounded-lg dark:bg-blue-500/10 dark:text-wa-teal">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                    </svg>
                </div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">WhatsApp
                    <span class="text-wa-teal">Widgets</span>
                </h1>
            </div>
            <p class="text-slate-500 font-medium tracking-tight">Manage and deploy customizable WhatsApp contact buttons and lead engines.</p>
        </div>

        <div class="flex items-center gap-3">
            <button wire:click="openCreateModal"
                class="flex items-center gap-2 px-6 py-3 bg-wa-teal text-white font-black uppercase tracking-widest text-[10px] rounded-2xl shadow-xl transition-all hover:scale-[1.02] active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Create New Widget
            </button>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-100 dark:border-green-800/30 rounded-2xl flex items-center gap-3 text-green-700 dark:text-green-400 font-bold text-xs uppercase tracking-widest animate-in slide-in-from-top duration-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ session('message') }}
        </div>
    @endif

    <!-- Widgets Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        @foreach($widgets as $widget)
            <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] border border-slate-100 dark:border-slate-800 shadow-xl overflow-hidden group transition-all hover:shadow-2xl hover:-translate-y-1">
                <!-- QR Preview Area -->
                <div class="relative aspect-square bg-slate-50 dark:bg-slate-950 flex items-center justify-center p-8 group-hover:bg-slate-100 dark:group-hover:bg-slate-800/50 transition-colors">
                    <div class="relative">
                        <div class="absolute -inset-4 bg-wa-teal/10 blur-2xl rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <img src="{{ route('qr.image', $widget->slug) }}?t={{ time() }}" alt="QR Code" class="w-48 h-48 relative z-10 rounded-2xl shadow-2xl border-4 border-white dark:border-slate-800">
                    </div>
                </div>

                <div class="p-8">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">{{ $widget->name }}</h3>
                            <div class="mt-1 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-wa-teal animate-pulse"></span>
                                <span class="text-[10px] font-black text-wa-teal uppercase tracking-widest">Active Widget</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                             <button wire:click="openCodeModal({{ $widget->id }})" class="p-2 bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-wa-teal rounded-xl transition-colors" title="Get Installation Snippet">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                </svg>
                            </button>
                             <button wire:click="edit({{ $widget->id }})" class="p-2 bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-blue-500 rounded-xl transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-2xl border border-slate-100 dark:border-slate-800 text-center">
                            <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Popup Opens</div>
                            <div class="text-xl font-black text-slate-900 dark:text-white">{{ number_format($widget->click_count) }}</div>
                        </div>
                        <div class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-2xl border border-slate-100 dark:border-slate-800 text-center">
                            <div class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Converted Leads</div>
                            <div class="text-xl font-black text-wa-teal">{{ number_format($widget->conversion_count) }}</div>
                        </div>
                    </div>
                    
                    <div class="mb-8 px-4 py-2 bg-slate-50 dark:bg-slate-800 rounded-xl flex justify-between items-center">
                         <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">QR Scans</span>
                         <span class="text-sm font-black text-slate-900 dark:text-white">{{ number_format($widget->scan_count) }}</span>
                    </div>

                    <button onclick="copyToClipboard('{{ route('qr.show', $widget->slug) }}', this)"
                        class="w-full flex items-center justify-center gap-2 py-4 bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-black uppercase tracking-widest text-[10px] rounded-2xl group/btn active:scale-95 transition-all">
                        <svg class="w-4 h-4 transition-transform group-hover/btn:rotate-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <span>Copy Widget Link</span>
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    @if(count($widgets) === 0)
        <div class="flex flex-col items-center justify-center py-20 bg-white dark:bg-slate-900 rounded-[3rem] border-2 border-dashed border-slate-200 dark:border-slate-800">
            <div class="p-6 bg-slate-50 dark:bg-slate-800 rounded-full mb-6">
                <svg class="w-12 h-12 text-slate-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4" />
                </svg>
            </div>
            <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">No Widgets Created</h3>
            <p class="text-slate-500 font-medium mt-2">Create your first WhatsApp widget to start engaging with visitors.</p>
        </div>
    @endif

    <!-- Configuration Modal -->
    <div x-data="{ show: @entangle('showCreateModal'), tab: 'basic' }" x-show="show" x-cloak class="fixed inset-0 z-[100] overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="fixed inset-0 transition-opacity bg-slate-950/80 backdrop-blur-sm"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                class="inline-block w-full max-w-7xl p-0 my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-slate-900 shadow-2xl rounded-[2.5rem] border border-slate-100 dark:border-slate-800">
                
                <div class="grid grid-cols-1 lg:grid-cols-2 min-h-[750px]">
                    <!-- Settings Panel -->
                    <div class="p-10 border-r border-slate-50 dark:border-slate-800 max-h-[85vh] overflow-y-auto custom-scrollbar">
                        <div class="flex items-center justify-between mb-8">
                            <h3 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                                {{ $editingWidgetId ? 'Edit' : 'Create' }} <span class="text-wa-teal">Widget</span>
                            </h3>
                        </div>

                        <!-- Tabs -->
                        <div class="flex flex-wrap gap-2 mb-8">
                            <button @click="tab = 'basic'" :class="tab === 'basic' ? 'bg-slate-900 text-white' : 'bg-slate-50 dark:bg-slate-800 text-slate-400'" class="px-5 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">Button Info</button>
                            <button @click="tab = 'content'" :class="tab === 'content' ? 'bg-slate-900 text-white' : 'bg-slate-50 dark:bg-slate-800 text-slate-400'" class="px-5 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">Content & Forms</button>
                            <button @click="tab = 'brand'" :class="tab === 'brand' ? 'bg-slate-900 text-white' : 'bg-slate-50 dark:bg-slate-800 text-slate-400'" class="px-5 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">Style & Brand</button>
                            <button @click="tab = 'triggers'" :class="tab === 'triggers' ? 'bg-slate-900 text-white' : 'bg-slate-50 dark:bg-slate-800 text-slate-400'" class="px-5 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">Display Triggers</button>
                            <button @click="tab = 'qr'" :class="tab === 'qr' ? 'bg-slate-900 text-white' : 'bg-slate-50 dark:bg-slate-800 text-slate-400'" class="px-5 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">QR Customization</button>
                        </div>

                        <form wire:submit.prevent="save" class="space-y-6">
                            <!-- Basic Tab -->
                            <div x-show="tab === 'basic'" class="space-y-6">
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Internal Widget Name</label>
                                    <input type="text" wire:model.live="name" placeholder="e.g. Website Hero Button" class="w-full px-6 py-4 bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800 rounded-2xl focus:ring-2 focus:ring-wa-teal transition-all text-slate-900 dark:text-white font-bold">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Floating Button Text (CTA)</label>
                                    <input type="text" wire:model.live="button_text" placeholder="Chat with us" class="w-full px-6 py-4 bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800 rounded-2xl focus:ring-2 focus:ring-wa-teal transition-all text-slate-900 dark:text-white font-bold">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">WhatsApp Pre-filled Message</label>
                                    <textarea wire:model.live="prefilled_message" placeholder="Hi, I'm interested in..." class="w-full px-6 py-4 bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800 rounded-2xl focus:ring-2 focus:ring-wa-teal transition-all text-slate-900 dark:text-white font-bold h-24"></textarea>
                                </div>
                            </div>

                            <!-- Content Tab -->
                            <div x-show="tab === 'content'" class="space-y-6">
                                <div class="grid grid-cols-2 gap-4">
                                     <div>
                                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Collect Name</label>
                                        <label class="flex items-center gap-3 cursor-pointer">
                                            <input type="checkbox" wire:model.live="collect_name" class="sr-only peer">
                                            <div class="w-10 h-6 bg-slate-200 dark:bg-slate-700 rounded-full peer peer-checked:bg-wa-teal transition-colors"></div>
                                        </label>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Collect Email</label>
                                        <label class="flex items-center gap-3 cursor-pointer">
                                            <input type="checkbox" wire:model.live="collect_email" class="sr-only peer">
                                            <div class="w-10 h-6 bg-slate-200 dark:bg-slate-700 rounded-full peer peer-checked:bg-wa-teal transition-colors"></div>
                                        </label>
                                    </div>
                                </div>
                                @if($collect_name)
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Name Field Placeholder</label>
                                    <input type="text" wire:model.live="placeholder_name" placeholder="Full Name" class="w-full px-6 py-4 bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800 rounded-2xl focus:ring-2 focus:ring-wa-teal transition-all text-slate-900 dark:text-white font-bold">
                                </div>
                                @endif
                                @if($collect_email)
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Email Field Placeholder</label>
                                    <input type="text" wire:model.live="placeholder_email" placeholder="Email Address" class="w-full px-6 py-4 bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800 rounded-2xl focus:ring-2 focus:ring-wa-teal transition-all text-slate-900 dark:text-white font-bold">
                                </div>
                                @endif
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Chat Window Welcome Text</label>
                                    <textarea wire:model.live="welcome_message" placeholder="Hi, How can I help you?" class="w-full px-6 py-4 bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800 rounded-2xl focus:ring-2 focus:ring-wa-teal transition-all text-slate-900 dark:text-white font-bold h-20"></textarea>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Footer Branding / Reference</label>
                                    <input type="text" wire:model.live="footer_text" placeholder="Powered by [Your Brand]" class="w-full px-6 py-4 bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800 rounded-2xl focus:ring-2 focus:ring-wa-teal transition-all text-slate-900 dark:text-white font-bold">
                                </div>
                            </div>

                            <!-- Branding Tab -->
                            <div x-show="tab === 'brand'" class="space-y-6">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Display Name</label>
                                        <input type="text" wire:model.live="brand_name" class="w-full px-6 py-4 bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800 rounded-2xl focus:ring-2 focus:ring-wa-teal transition-all text-slate-900 dark:text-white font-bold">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Subtitle / Status Text</label>
                                        <input type="text" wire:model.live="brand_subtitle" placeholder="typically replies in minutes" class="w-full px-6 py-4 bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800 rounded-2xl focus:ring-2 focus:ring-wa-teal transition-all text-slate-900 dark:text-white font-bold">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Brand Logo Image URL</label>
                                    <input type="text" wire:model.live="brand_logo_url" placeholder="https://..." class="w-full px-6 py-4 bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800 rounded-2xl focus:ring-2 focus:ring-wa-teal transition-all text-slate-900 dark:text-white font-bold">
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Theme Color</label>
                                        <div class="flex items-center gap-4">
                                            <input type="color" wire:model.live="widget_color" class="w-12 h-12 rounded-xl border-none p-0 cursor-pointer bg-transparent">
                                            <span class="text-xs font-mono font-bold">{{ $widget_color }}</span>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Corner Radius (px)</label>
                                        <input type="number" wire:model.live="border_radius" class="w-full px-6 py-4 bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800 rounded-2xl text-slate-900 dark:text-white font-bold">
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Screen Position</label>
                                        <select wire:model.live="position" class="w-full px-6 py-4 bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800 rounded-2xl text-slate-900 dark:text-white font-bold outline-none">
                                            <option value="right">Bottom Right</option>
                                            <option value="left">Bottom Left</option>
                                        </select>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <label class="flex flex-col gap-2 p-2 bg-slate-50 dark:bg-slate-800 rounded-2xl cursor-pointer">
                                            <span class="text-[8px] font-black uppercase text-slate-400">Mobile</span>
                                            <input type="checkbox" wire:model.live="show_on_mobile" class="w-4 h-4 rounded text-wa-teal">
                                        </label>
                                        <label class="flex flex-col gap-2 p-2 bg-slate-50 dark:bg-slate-800 rounded-2xl cursor-pointer">
                                            <span class="text-[8px] font-black uppercase text-slate-400">Desktop</span>
                                            <input type="checkbox" wire:model.live="show_on_desktop" class="w-4 h-4 rounded text-wa-teal">
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Triggers Tab -->
                            <div x-show="tab === 'triggers'" class="space-y-6">
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Page Targeting Patterns</label>
                                    <input type="text" wire:model.live="page_targeting" placeholder="e.g. /pricing, /offers/*" class="w-full px-6 py-4 bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800 rounded-2xl focus:ring-2 focus:ring-wa-teal transition-all text-slate-900 dark:text-white font-bold">
                                </div>

                                <div class="grid grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">Display Delay (Seconds)</label>
                                        <input type="number" wire:model.live="time_on_page" class="w-full px-6 py-4 bg-slate-50 dark:bg-slate-950 border border-slate-100 dark:border-slate-800 rounded-2xl text-slate-900 dark:text-white font-bold">
                                    </div>
                                    <div class="flex flex-col justify-end">
                                        <label class="flex items-center gap-3 p-4 bg-slate-50 dark:bg-slate-800 rounded-2xl cursor-pointer group">
                                            <input type="checkbox" wire:model.live="exit_intent" class="w-4 h-4 rounded text-wa-teal">
                                            <span class="text-[10px] font-black uppercase text-slate-400 group-hover:text-slate-600 transition-colors">Exit Intent Trigger</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="p-6 bg-slate-50 dark:bg-slate-800 rounded-[2rem] border border-slate-100 dark:border-slate-800">
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4">Availability Schedule</label>
                                    <div class="grid grid-cols-2 gap-4">
                                        @foreach(['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'] as $day)
                                            <div class="flex items-center gap-2">
                                                <span class="text-[9px] font-black uppercase text-slate-400 w-8">{{ $day }}</span>
                                                <input type="time" wire:model.live="business_hours.{{ $day }}.start" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg p-1 text-[10px]">
                                                <span class="text-slate-300">-</span>
                                                <input type="time" wire:model.live="business_hours.{{ $day }}.end" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg p-1 text-[10px]">
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            
                            <!-- QR Tab -->
                            <div x-show="tab === 'qr'" class="space-y-6">
                                <div class="grid grid-cols-2 gap-8">
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4">QR Dots Color</label>
                                        <div class="flex items-center gap-4 p-4 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-100 dark:border-slate-800">
                                            <input type="color" wire:model.live="qr_color" class="w-12 h-12 rounded-xl border-none p-0 cursor-pointer bg-transparent">
                                            <span class="text-sm font-mono font-black">{{ $qr_color }}</span>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4">QR Background Color</label>
                                        <div class="flex items-center gap-4 p-4 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-100 dark:border-slate-800">
                                            <input type="color" wire:model.live="qr_bg_color" class="w-12 h-12 rounded-xl border-none p-0 cursor-pointer bg-transparent">
                                            <span class="text-sm font-mono font-black">{{ $qr_bg_color }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-8 bg-slate-900 rounded-[2.5rem] flex flex-col items-center justify-center">
                                    <h4 class="text-[9px] font-black text-slate-500 uppercase tracking-[0.3em] mb-6">QR Studio Preview</h4>
                                    <!-- Dynamic QR Preview would go here, for now show static with color indicator -->
                                    <div class="w-48 h-48 rounded-2xl shadow-2xl p-4 flex items-center justify-center relative" style="background: {{ $qr_bg_color }}">
                                        <svg class="w-full h-full" viewBox="0 0 24 24" fill="{{ $qr_color }}"><path d="M3 11h8V3H3v8zm2-6h4v4H5V5zM3 21h8v-8H3v8zm2-6h4v4H5v-4zM13 3v8h8V3h-8zm6 6h-4V5h4v4zM13 13h2v2h-2v-2zm2 2h2v2h-2v-2zm2-2h2v2h-2v-2zm2 2h2v2h-2v-2zm-4 2h2v2h-2v-2zm2 2h2v2h-2v-2zm-2 2h2v2h-2v-2zm0-2h2v2h-2v-2zm-8-3h2v2H5v-2zm2 2h2v2H7v-2zm2-2h2v2H9v-2zm2 2h2v2h-2v-2z" /></svg>
                                    </div>
                                    <p class="mt-6 text-[10px] font-black text-white/50 uppercase tracking-widest">Colors applied to high-res download</p>
                                </div>
                            </div>

                            <div class="pt-8 border-t border-slate-50 dark:border-slate-800 flex justify-end gap-4">
                                <button type="button" @click="show = false" class="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400 hover:text-slate-600 transition-colors">Discard</button>
                                <button type="submit" class="px-10 py-4 bg-wa-teal text-white font-black uppercase tracking-widest text-[10px] rounded-2xl shadow-xl hover:scale-[1.02] active:scale-95 transition-all">
                                    Save Widget
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Preview Panel -->
                    <div class="bg-slate-50 dark:bg-slate-950 p-10 flex flex-col items-center justify-center relative overflow-hidden">
                        <div class="absolute inset-0 opacity-10 pointer-events-none" style="background-image: radial-gradient(circle at 2px 2px, rgba(0,0,0,0.2) 1px, transparent 0); background-size: 24px 24px;"></div>
                        
                        <div class="relative z-10 w-full flex flex-col items-center">
                            <!-- Preview Controls -->
                            <div class="flex bg-slate-200 dark:bg-slate-800 p-1 rounded-2xl mb-8">
                                <button @click="previewMode = 'mobile'" :class="previewMode === 'mobile' ? 'bg-white dark:bg-slate-700 shadow-md' : ''" class="flex items-center gap-2 px-6 py-2 rounded-xl transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                                    <span class="text-[10px] font-black uppercase tracking-widest">Mobile</span>
                                </button>
                                <button @click="previewMode = 'desktop'" :class="previewMode === 'desktop' ? 'bg-white dark:bg-slate-700 shadow-md' : ''" class="flex items-center gap-2 px-6 py-2 rounded-xl transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                    <span class="text-[10px] font-black uppercase tracking-widest">Desktop</span>
                                </button>
                            </div>
                            
                            <!-- Mobile Mockup -->
                            <div x-show="previewMode === 'mobile'" x-transition class="w-[320px] h-[620px] bg-white border-[12px] border-slate-900 rounded-[3.5rem] shadow-2xl relative overflow-hidden transition-all">
                                <div class="h-6 bg-slate-900 w-32 mx-auto rounded-b-2xl"></div>
                                <div class="px-6 py-12 text-center opacity-20">
                                    <div class="w-12 h-2px bg-slate-300 mx-auto mb-4"></div>
                                    <div class="h-4 bg-slate-200 w-3/4 mx-auto rounded mb-2"></div>
                                    <div class="h-3 bg-slate-100 w-1/2 mx-auto rounded"></div>
                                </div>
                                @include('livewire.lead-capture.preview-widget', ['source' => 'mobile'])
                            </div>

                            <!-- Desktop Mockup -->
                            <div x-show="previewMode === 'desktop'" x-transition class="w-full max-w-2xl h-[450px] bg-white border-[1px] border-slate-200 rounded-3xl shadow-2xl relative overflow-hidden transition-all">
                                <div class="h-8 bg-slate-100 border-b flex items-center px-4 gap-2">
                                    <div class="flex gap-1.5">
                                        <div class="w-2.5 h-2.5 rounded-full bg-red-400"></div>
                                        <div class="w-2.5 h-2.5 rounded-full bg-yellow-400"></div>
                                        <div class="w-2.5 h-2.5 rounded-full bg-green-400"></div>
                                    </div>
                                    <div class="bg-white rounded-md px-4 py-0.5 text-[8px] text-slate-400 w-64 mx-auto border truncate">https://yourwebsite.com</div>
                                </div>
                                <div class="p-12 text-center opacity-20">
                                    <div class="h-8 bg-slate-200 w-48 rounded mb-6 mx-auto"></div>
                                    <div class="grid grid-cols-2 gap-8">
                                        <div class="h-32 bg-slate-100 rounded-2xl"></div>
                                        <div class="h-32 bg-slate-100 rounded-2xl"></div>
                                    </div>
                                </div>
                                @include('livewire.lead-capture.preview-widget', ['source' => 'desktop'])
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Integration Code Modal -->
    <div x-data="{ show: @entangle('showCodeModal') }" x-show="show" x-cloak class="fixed inset-0 z-[100] overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="fixed inset-0 transition-opacity bg-slate-950/80 backdrop-blur-sm"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                class="inline-block w-full max-w-3xl p-10 my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-slate-900 shadow-2xl rounded-[3rem] border border-slate-100 dark:border-slate-800">
                
                <h3 class="text-3xl font-black text-slate-900 dark:text-white uppercase tracking-tight mb-8">
                    Installation <span class="text-wa-teal">Code</span>
                </h3>

                <div class="p-6 bg-blue-50 dark:bg-blue-900/10 rounded-[2rem] border border-blue-100 flex gap-4 mb-8">
                    <svg class="w-6 h-6 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <p class="text-sm text-blue-800 dark:text-blue-300 font-medium tracking-tight">Copy and paste this snippet into your website's <code>&lt;body&gt;</code> section.</p>
                </div>

                <div class="relative group">
                    <button onclick="copyToClipboard(document.getElementById('integrationCode').innerText, this)" class="absolute top-4 right-4 px-4 py-2 bg-slate-800 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-700 transition-all border border-white/5">Copy Snippet</button>
                    <div id="integrationCode" class="w-full bg-slate-950 rounded-[2rem] p-10 pt-16 font-mono text-sm text-green-400 overflow-x-auto select-all border border-slate-800">
&lt;!-- WhatsApp Widget Integration --&gt;
&lt;script 
  type="text/javascript"
  src="{{ url('/js/growth-widget.js') }}"
  id="wa-growth-tool"
  widget-id="{{ $selectedWidget->slug ?? '' }}"
&gt;&lt;/script&gt;
                    </div>
                </div>

                <div class="mt-8 flex justify-center">
                    <button @click="show = false" class="px-10 py-4 bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-black uppercase tracking-widest text-[10px] rounded-2xl transition-all hover:scale-[1.05] active:scale-95">Close Installation Guide</button>
                </div>
            </div>
        </div>
    </div>

</div>