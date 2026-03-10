<div class="py-12 bg-slate-50/50 dark:bg-slate-900/50 min-h-screen">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-8">
            <div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">
                    Automation <span class="text-indigo-500">Workflows</span>
                </h1>
                <p class="max-w-xl text-sm font-medium text-slate-500 dark:text-slate-400 mt-2">Automate your CRM
                    processes with triggers and actions. Build powerful flows that run in the background.</p>
            </div>
            <div class="flex items-center gap-3">
                <button wire:click="openImportModal"
                    class="flex items-center justify-center gap-2 px-6 py-3 bg-white dark:bg-slate-800 text-slate-600 dark:text-white font-black uppercase tracking-widest text-xs rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 hover:scale-[1.02] active:scale-95 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    Import JSON
                </button>
                <button wire:click="create"
                    class="flex items-center justify-center gap-2 px-8 py-3 bg-indigo-600 text-white font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-indigo-600/20 hover:scale-[1.02] active:scale-95 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                    </svg>
                    New Workflow
                </button>
            </div>
        </div>

        <!-- Filters & Search -->
        <div class="flex flex-col md:flex-row gap-4 mb-8">
            <div class="flex-1 relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search workflows by name..."
                    class="w-full pl-11 pr-4 py-3 bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-2xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-indigo-500/20 shadow-sm text-sm">
            </div>
            <div class="w-full md:w-64">
                <select wire:model.live="filterFolder"
                    class="w-full px-4 py-3 bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-2xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-indigo-500/20 shadow-sm text-sm">
                    <option value="">All Folders</option>
                    @foreach(\App\Models\Workflow::whereNotNull('folder')->where('folder', '!=', '')->pluck('folder')->unique() as $f)
                        <option value="{{ $f }}">{{ $f }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($workflows as $workflow)
                <div
                    class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-8 shadow-xl border border-slate-50 dark:border-slate-800 flex flex-col h-full relative overflow-hidden group hover:scale-[1.02] transition-transform duration-300">
                    @if(!$workflow->team_id)
                        <div class="absolute top-6 right-6 z-20">
                            <span
                                class="px-3 py-1 bg-indigo-500 text-white text-[10px] font-black uppercase tracking-widest rounded-full shadow-lg">Template</span>
                        </div>
                    @endif
                    <!-- Decorative Circle -->
                    <div
                        class="absolute -right-12 -top-12 w-40 h-40 bg-indigo-50 dark:bg-indigo-900/10 rounded-full blur-3xl group-hover:bg-indigo-100 dark:group-hover:bg-indigo-900/20 transition-colors">
                    </div>

                    <div class="absolute top-6 right-6 z-10">
                        <span
                            class="px-3 py-1 text-[10px] font-black uppercase tracking-widest rounded-full {{ $workflow->is_active ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30' : 'bg-slate-100 text-slate-500 dark:bg-slate-800' }}">
                            {{ $workflow->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>

                    <div class="relative flex-1">
                        <h3 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight mb-2 pr-20">
                            {{ $workflow->name }}
                        </h3>
                        <p class="text-sm font-medium text-slate-500 dark:text-slate-400 mb-4 line-clamp-2">
                            {{ $workflow->description ?: 'No description provided.' }}
                        </p>

                        @if(!empty($workflow->tags))
                            <div class="flex flex-wrap gap-2 mb-6">
                                @foreach((is_array($workflow->tags) ? $workflow->tags : json_decode($workflow->tags ?? '[]', true) ?? []) as $tag)
                                    <span
                                        class="px-2 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 text-[10px] font-black uppercase tracking-widest rounded-md border border-slate-200 dark:border-slate-700">
                                        {{ $tag }}
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <div class="mb-6"></div>
                        @endif

                        <div class="space-y-3 mb-8">
                            <div class="flex items-center gap-3 p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl">
                                <div class="p-2 bg-white dark:bg-slate-800 rounded-xl text-indigo-500 shadow-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Trigger
                                        Event</div>
                                    <div class="font-bold text-slate-900 dark:text-white">
                                        {{ str_replace('_', ' ', $workflow->trigger_type) }}
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl">
                                <div class="p-2 bg-white dark:bg-slate-800 rounded-xl text-emerald-500 shadow-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Workflow
                                        Actions</div>
                                    <div class="font-bold text-slate-900 dark:text-white">
                                        {{ collect($workflow->definition ?? [])->count() ?: ($workflow->actions_count ?? 0) }}
                                        <span class="text-xs text-slate-500 font-medium">steps</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-8 mt-4 space-y-2">
                        <div
                            class="flex items-center justify-between text-[10px] font-black uppercase tracking-widest text-slate-400">
                            <span>Success Rate</span>
                            <span class="text-slate-900 dark:text-white">
                                {{ $workflow->execution_count > 0 ? round(($workflow->success_count / $workflow->execution_count) * 100) : 0 }}%
                            </span>
                        </div>
                        <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-1.5">
                            <div class="bg-emerald-500 h-1.5 rounded-full"
                                style="width: {{ $workflow->execution_count > 0 ? round(($workflow->success_count / $workflow->execution_count) * 100) : 0 }}%">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 pt-2">
                            <div>
                                <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Conversions
                                </div>
                                <div class="text-sm font-black text-slate-900 dark:text-emerald-400">
                                    {{ number_format($workflow->conversions_count ?? 0) }}
                                </div>
                            </div>
                            <div>
                                <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Time Saved
                                </div>
                                <div class="text-sm font-black text-slate-900 dark:text-indigo-400">
                                    {{ $workflow->time_saved_minutes >= 60 ? round($workflow->time_saved_minutes / 60, 1) . 'h' : ($workflow->time_saved_minutes ?? 0) . 'm' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="flex items-center justify-between p-8 pt-6 border-t border-slate-50 dark:border-slate-800 relative z-10 gap-3 mt-auto">
                        <div class="flex-1 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                            {{ number_format($workflow->execution_count ?? 0) }} Executions
                            @if($workflow->failure_count > 0)
                                <span class="text-rose-500 ml-1">({{ number_format($workflow->failure_count) }} failed)</span>
                            @endif
                        </div>
                        <div x-data="{ dropOpen: false }" class="relative flex-1">
                            <button @click="dropOpen = !dropOpen" @click.away="dropOpen = false"
                                class="w-full py-3 bg-slate-50 dark:bg-slate-800 text-slate-500 font-black uppercase tracking-widest text-[10px] rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors flex items-center justify-center gap-1">
                                Actions <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                        d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div x-show="dropOpen" style="display: none;"
                                class="absolute bottom-full left-0 mb-2 w-40 bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-xl shadow-xl overflow-hidden z-50">
                                <button wire:click="viewLogs({{ $workflow->id }})"
                                    class="w-full text-left px-4 py-3 text-[10px] font-black text-slate-600 dark:text-slate-300 uppercase tracking-widest hover:bg-slate-50 dark:hover:bg-slate-900 transition-colors border-b border-slate-100 dark:border-slate-700 flex items-center gap-2">
                                    <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                            d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                                    </svg> View Logs
                                </button>
                                <button wire:click="duplicate({{ $workflow->id }})"
                                    class="w-full text-left px-4 py-3 text-[10px] font-black text-slate-600 dark:text-slate-300 uppercase tracking-widest hover:bg-slate-50 dark:hover:bg-slate-900 transition-colors border-b border-slate-100 dark:border-slate-700 flex items-center gap-2">
                                    <svg class="w-3 h-3 text-emerald-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z">
                                        </path>
                                    </svg> Duplicate
                                </button>
                                <button wire:click="export({{ $workflow->id }})"
                                    class="w-full text-left px-4 py-3 text-[10px] font-black text-slate-600 dark:text-slate-300 uppercase tracking-widest hover:bg-slate-50 dark:hover:bg-slate-900 transition-colors flex items-center gap-2">
                                    <svg class="w-3 h-3 text-indigo-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                    </svg> Export JSON
                                </button>
                            </div>
                        </div>
                        <button wire:click="edit({{ $workflow->id }})"
                            class="flex-[2] py-3 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-black uppercase tracking-widest text-[10px] rounded-xl hover:bg-indigo-100 dark:hover:bg-indigo-900/40 transition-colors">
                            Edit Builder
                        </button>
                    </div>
                </div>
            @empty
                <div class="col-span-full">
                    <div
                        class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-12 text-center border border-slate-50 dark:border-slate-800 shadow-xl">
                        <div
                            class="w-20 h-20 bg-slate-50 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-6 text-slate-300">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-black text-slate-900 dark:text-white mb-2">No Workflows Built</h3>
                        <p class="text-slate-500 font-medium mb-8">Start automating your tasks by creating your first
                            workflow.</p>
                        <button wire:click="create"
                            class="px-8 py-3 bg-indigo-600 text-white font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-indigo-600/20 hover:scale-[1.02] active:scale-95 transition-all">
                            Create Workflow
                        </button>
                    </div>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <x-dialog-modal wire:model="showCreateModal" maxWidth="5xl">
        <x-slot name="title">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-indigo-50 dark:bg-slate-800 rounded-xl text-indigo-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                        {{ $activeWorkflowId ? 'Edit' : 'Create' }} <span class="text-indigo-500">Workflow</span>
                    </h2>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">Configure logic details
                    </p>
                </div>
            </div>
        </x-slot>

        <x-slot name="content">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 h-[600px]">
                <!-- Sidebar: Config -->
                <div class="col-span-1 border-r border-slate-100 dark:border-slate-800 pr-6 space-y-6">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">Workflow
                            Name</label>
                        <input type="text" wire:model="name" placeholder="e.g. Lead Follow-up"
                            class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-indigo-500/20">
                        @error('name') <span class="text-rose-500 text-[10px] uppercase font-bold">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label
                            class="text-[10px] font-black uppercase tracking-widest text-slate-500">Description</label>
                        <textarea wire:model="description" rows="3" placeholder="What does this workflow do?"
                            class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-indigo-500/20"></textarea>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">Folder</label>
                        <input type="text" wire:model="folder" placeholder="e.g. Sales, Support"
                            class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-indigo-500/20">
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">Tags <span
                                class="text-slate-400 lowercase">(comma separated)</span></label>
                        <input type="text" wire:model="tagsText" placeholder="e.g. VIP, Priority, Onboarding"
                            class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-indigo-500/20">
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">Trigger
                            Event</label>
                        <select wire:model="triggerType"
                            class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-indigo-500/20">
                            <optgroup label="Event Triggers">
                                <option value="message_received">Message Received</option>
                                <option value="form_submitted">Form Submitted</option>
                                <option value="payment_received">Payment Received</option>
                                <option value="order_placed">Order Placed</option>
                                <option value="contact_created">Contact / Lead Created</option>
                                <option value="contact_updated">Contact Updated</option>
                                <option value="deal_stage_changed">Deal Stage Changed</option>
                                <option value="tag_added">Tag Added</option>
                            </optgroup>
                            <optgroup label="Time Triggers">
                                <option value="schedule_workflow">Schedule Workflow (One-off)</option>
                                <option value="run_daily">Run Daily</option>
                                <option value="run_weekly">Run Weekly</option>
                                <option value="run_monthly">Run Monthly</option>
                            </optgroup>
                            <optgroup label="Manual Triggers">
                                <option value="manual_run">Run Workflow Manually</option>
                                <option value="dashboard_trigger">Dashboard Trigger Button</option>
                            </optgroup>
                            <optgroup label="External Triggers">
                                <option value="webhook_incoming">Webhook (Incoming)</option>
                                <option value="api_call">API Call</option>
                                <option value="crm_event">Third-Party CRM Event</option>
                                <option value="ecommerce_event">E-commerce Event</option>
                            </optgroup>
                            <optgroup label="Onboarding Events">
                                <option value="user_signed_up">Account Created (Signup)</option>
                                <option value="whatsapp_connected">WhatsApp API Linked</option>
                                <option value="profile_completed">Business Profile Finished</option>
                                <option value="onboarding_completed">Onboarding Fully Completed</option>
                            </optgroup>
                        </select>
                        @error('triggerType') <span
                        class="text-rose-500 text-[10px] uppercase font-bold">{{ $message }}</span> @enderror
                    </div>

                    <!-- Trigger Config -->
                    @if($triggerType === 'deal_stage_changed')
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">Target
                                Stage</label>
                            <select wire:model="triggerConfig.stage_id"
                                class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-indigo-500/20">
                                <option value="">Any Stage</option>
                                @foreach($stages as $stage)
                                    <option value="{{ $stage->id }}">{{ $stage->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if($triggerType === 'tag_added')
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">Specific
                                Tag</label>
                            <select wire:model="triggerConfig.tag_id"
                                class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-indigo-500/20">
                                <option value="">Any Tag</option>
                                @foreach($tags as $tag)
                                    <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if($triggerType === 'webhook_incoming')
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">Your Webhook
                                URL</label>
                            <div
                                class="flex items-center gap-2 bg-slate-50 dark:bg-slate-800 px-3 py-2 rounded-xl border border-slate-100 dark:border-slate-700">
                                <code
                                    class="text-xs text-indigo-500 flex-1 overflow-hidden text-ellipsis">{{ url('/api/webhooks/workflow/' . ($activeWorkflowId ?? 'new')) }}</code>
                                <button type="button" class="text-slate-400 hover:text-indigo-500" title="Copy">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z">
                                        </path>
                                    </svg>
                                </button>
                            </div>
                            <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest leading-relaxed mt-1">
                                Send POST requests to this URL. The JSON body will be passed as context tags.</p>
                        </div>
                    @endif

                    @if(in_array($triggerType, ['run_daily', 'run_weekly', 'run_monthly', 'schedule_workflow']))
                        <div class="space-y-4">
                            @if(in_array($triggerType, ['run_weekly', 'run_monthly', 'schedule_workflow']))
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">
                                        @if($triggerType === 'run_weekly') Day of Week
                                        @elseif($triggerType === 'run_monthly') Day of Month
                                        @else Date
                                        @endif
                                    </label>
                                    @if($triggerType === 'run_weekly')
                                        <select wire:model="triggerConfig.day"
                                            class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-indigo-500/20">
                                            <option value="1">Monday</option>
                                            <option value="2">Tuesday</option>
                                            <option value="3">Wednesday</option>
                                            <option value="4">Thursday</option>
                                            <option value="5">Friday</option>
                                            <option value="6">Saturday</option>
                                            <option value="0">Sunday</option>
                                        </select>
                                    @elseif($triggerType === 'run_monthly')
                                        <input type="number" wire:model="triggerConfig.day" min="1" max="31" placeholder="e.g. 1"
                                            class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-indigo-500/20">
                                    @else
                                        <input type="date" wire:model="triggerConfig.date"
                                            class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-indigo-500/20">
                                    @endif
                                </div>
                            @endif
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">Execution
                                    Time</label>
                                <input type="time" wire:model="triggerConfig.time"
                                    class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-indigo-500/20">
                            </div>
                        </div>
                    @endif

                    <div class="pt-6 border-t border-slate-100 dark:border-slate-800">
                        <label
                            class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-800 rounded-xl cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                            <div class="relative flex items-center">
                                <input type="checkbox" wire:model="isActive"
                                    class="peer w-5 h-5 rounded-lg border-2 border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500/20 transition-all checked:bg-indigo-600 checked:border-indigo-600">
                            </div>
                            <span
                                class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest select-none">Active
                                Workflow</span>
                        </label>
                    </div>
                </div>

                <!-- Main: Actions -->
                <div
                    class="col-span-2 pl-4 overflow-y-auto relative bg-slate-50/50 dark:bg-slate-900/50 rounded-2xl border border-slate-100 dark:border-slate-800 p-8 shadow-inner">
                    <!-- Start Node -->
                    <div class="flex flex-col items-center mb-6 relative z-10">
                        <div
                            class="bg-indigo-600 text-white px-8 py-4 rounded-2xl text-xs font-black uppercase tracking-widest shadow-xl shadow-indigo-600/20 border border-indigo-500 overflow-hidden group">
                            <div
                                class="absolute inset-0 bg-gradient-to-r from-indigo-500/0 via-white/10 to-indigo-500/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000">
                            </div>
                            Trigger: {{ str_replace('_', ' ', $triggerType) }}
                        </div>
                        <div
                            class="h-10 w-1 bg-gradient-to-b from-indigo-600 to-slate-200 dark:to-slate-700 rounded-full my-1">
                        </div>
                        <div
                            class="flex gap-3 relative z-10 bg-white dark:bg-slate-800 p-2 rounded-2xl shadow-lg border border-slate-100 dark:border-slate-700">
                            <button wire:click="addAction"
                                class="flex items-center justify-center gap-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 rounded-xl px-4 py-2 hover:border-indigo-500 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-all font-bold text-[10px] uppercase tracking-widest"
                                title="Add Action">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                        d="M12 4v16m8-8H4"></path>
                                </svg>
                                Action
                            </button>
                            <button wire:click="addCondition"
                                class="flex items-center justify-center gap-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 rounded-xl px-4 py-2 hover:border-amber-500 hover:text-amber-600 dark:hover:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-all font-bold text-[10px] uppercase tracking-widest"
                                title="Add Condition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                        d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                                    </path>
                                </svg>
                                Condition
                            </button>
                        </div>
                    </div>

                    <div class="space-y-0 relative">
                        <!-- Vertical Connection Line -->
                        <div
                            class="absolute left-1/2 top-0 bottom-0 w-1 bg-slate-200 dark:bg-slate-700 -translate-x-1/2 rounded-full z-0">
                        </div>

                        @foreach($actions as $index => $action)
                            @if(($action['type'] ?? 'action') === 'condition')
                                <!-- Condition Block -->
                                <div class="relative z-10 py-6">
                                    <div
                                        class="bg-white dark:bg-slate-900 p-8 rounded-[2rem] border border-amber-200/50 dark:border-amber-500/20 shadow-xl shadow-amber-500/5 relative group hover:border-amber-300 dark:hover:border-amber-500/50 transition-all">

                                        <!-- Node Number Badge -->
                                        <div
                                            class="absolute -left-3 -top-3 w-8 h-8 bg-amber-500 text-white rounded-xl shadow-lg shadow-amber-500/30 flex items-center justify-center font-black text-sm border-2 border-white dark:border-slate-900 z-20">
                                            {{ $index + 1 }}
                                        </div>

                                        <button wire:click="removeAction({{ $index }})"
                                            class="absolute top-4 right-4 p-2 bg-slate-50 dark:bg-slate-800 rounded-xl text-slate-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-500/10 opacity-0 group-hover:opacity-100 transition-all border border-transparent hover:border-rose-100 dark:hover:border-rose-500/20">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>

                                        <div class="mb-6">
                                            <div class="flex items-center gap-3 mb-4">
                                                <div
                                                    class="bg-amber-100 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400 p-2.5 rounded-xl border border-amber-200 dark:border-amber-500/30">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                                                        </path>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <h4
                                                        class="text-xs font-black uppercase tracking-widest text-amber-600 dark:text-amber-400">
                                                        Condition Split</h4>
                                                    <p class="text-[10px] text-slate-500 font-bold mt-0.5">Evaluates context to
                                                        route workflow</p>
                                                </div>
                                            </div>
                                            <div
                                                class="flex items-center gap-4 mb-4 bg-amber-50 dark:bg-amber-900/10 p-3 rounded-xl border border-amber-100 dark:border-amber-900/30">
                                                <span
                                                    class="text-[10px] font-black uppercase tracking-widest text-amber-600 dark:text-amber-500">Match
                                                    Rule</span>
                                                <select wire:model="actions.{{ $index }}.config.match_type"
                                                    class="flex-1 px-4 py-2 text-xs font-bold bg-white dark:bg-slate-900 border-none rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500/20 shadow-sm">
                                                    <option value="all">Match ALL conditions (AND)</option>
                                                    <option value="any">Match ANY condition (OR)</option>
                                                </select>
                                            </div>

                                            <div class="space-y-3">
                                                @php $conditionsArray = $actions[$index]['config']['conditions'] ?? [['field' => '', 'operator' => '=', 'value' => '']]; @endphp
                                                @foreach($conditionsArray as $subIndex => $cond)
                                                    <div class="relative group/cond">
                                                        <div
                                                            class="grid grid-cols-3 gap-3 bg-slate-50 dark:bg-slate-800 p-3 rounded-2xl border border-slate-100 dark:border-slate-700">
                                                            <input type="text"
                                                                wire:model="actions.{{ $index }}.config.conditions.{{ $subIndex }}.field"
                                                                placeholder="Field (e.g. context.order_amount)"
                                                                class="w-full px-4 py-2.5 text-xs font-bold bg-white dark:bg-slate-900 border-none rounded-xl text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500/20 shadow-sm">
                                                            <select
                                                                wire:model="actions.{{ $index }}.config.conditions.{{ $subIndex }}.operator"
                                                                class="w-full px-4 py-2.5 text-xs font-bold bg-white dark:bg-slate-900 border-none rounded-xl text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500/20 shadow-sm">
                                                                <option value="=">Equals</option>
                                                                <option value="!=">Not Equals</option>
                                                                <option value="contains">Contains</option>
                                                                <option value="not_contains">Not Contains</option>
                                                                <option value=">">Greater Than</option>
                                                                <option value="<">Less Than</option>
                                                            </select>
                                                            <input type="text"
                                                                wire:model="actions.{{ $index }}.config.conditions.{{ $subIndex }}.value"
                                                                placeholder="Value"
                                                                class="w-full px-4 py-2.5 text-xs font-bold bg-white dark:bg-slate-900 border-none rounded-xl text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500/20 shadow-sm">
                                                        </div>
                                                        @if(count($conditionsArray) > 1)
                                                            <button
                                                                wire:click.prevent="removeSubCondition({{ $index }}, {{ $subIndex }})"
                                                                class="absolute -right-2 -top-2 bg-rose-500 text-white w-5 h-5 rounded-full flex items-center justify-center opacity-0 group-hover/cond:opacity-100 transition-opacity drop-shadow-md text-[10px]">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                                    viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="3" d="M6 18L18 6M6 6l12 12"></path>
                                                                </svg>
                                                            </button>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                            <div class="mt-4 flex items-center justify-between pl-1">
                                                <button wire:click.prevent="addSubCondition({{ $index }})"
                                                    class="text-[10px] font-black text-amber-600 hover:text-amber-500 uppercase tracking-widest flex items-center gap-1">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                            d="M12 4v16m8-8H4"></path>
                                                    </svg> Add Condition
                                                </button>
                                                <p class="text-[9px] text-slate-400 font-bold">Use <code
                                                        class="bg-amber-50 dark:bg-amber-500/10 text-amber-500 px-1 py-0.5 rounded">context.KEY</code>
                                                    to evaluate webhook payload keys or event data (e.g., <code
                                                        class="text-amber-500">context.message_content</code>).</p>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-2 gap-6 relative">
                                            <!-- Visual branch paths -->
                                            <div
                                                class="absolute top-0 left-1/4 w-1/2 h-4 border-t-2 border-l-2 border-r-2 border-slate-200 dark:border-slate-700 rounded-t-xl -mt-4">
                                            </div>

                                            <!-- True Branch -->
                                            <div
                                                class="bg-emerald-50/50 dark:bg-emerald-900/10 p-5 rounded-2xl border border-emerald-100 dark:border-emerald-900/30 space-y-4">
                                                <div class="flex items-center justify-between">
                                                    <h5
                                                        class="text-[10px] font-black tracking-widest text-emerald-600 dark:text-emerald-400 uppercase flex items-center gap-2 bg-emerald-100/50 dark:bg-emerald-500/20 px-3 py-1.5 rounded-lg border border-emerald-200/50 dark:border-emerald-500/30">
                                                        <span
                                                            class="w-2 h-2 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.8)]"></span>
                                                        If True
                                                    </h5>
                                                    <span
                                                        class="text-[10px] font-bold text-slate-400">{{ count($action['true_nodes']) }}
                                                        steps</span>
                                                </div>

                                                <div class="space-y-3">
                                                    @foreach($action['true_nodes'] as $childIndex => $child)
                                                        <div
                                                            class="bg-white dark:bg-slate-800 p-4 rounded-xl shadow-sm border border-emerald-100 dark:border-emerald-900/50 relative group/child hover:shadow-md transition-all">
                                                            <button
                                                                wire:click="removeNestedAction({{ $index }}, 'true_nodes', {{ $childIndex }})"
                                                                class="absolute top-2 right-2 p-1.5 bg-slate-50 dark:bg-slate-900 rounded-lg text-slate-300 hover:text-rose-500 opacity-0 group-hover/child:opacity-100 transition-all border border-transparent hover:border-rose-100 dark:hover:border-rose-900/50">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                                    viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                                </svg>
                                                            </button>

                                                            <select
                                                                wire:model="actions.{{ $index }}.true_nodes.{{ $childIndex }}.action_type"
                                                                class="w-full px-3 py-2 text-[10px] font-bold uppercase tracking-wide bg-slate-50 dark:bg-slate-900 border-none rounded-lg text-slate-700 dark:text-slate-300 mb-2 focus:ring-2 focus:ring-emerald-500/20">
                                                                <option value="add_tag">Add Tag</option>
                                                                <option value="remove_tag">Remove Tag</option>
                                                                <option value="create_task">Create Task</option>
                                                                <option value="send_email">Send Email</option>
                                                                <option value="send_whatsapp">Send WhatsApp</option>
                                                                <option value="webhook">Webhook (API)</option>
                                                                <option value="assign_user">Assign User</option>
                                                                <option value="slack_notification">Slack Notification</option>
                                                                <optgroup label="Native Integrations">
                                                                    <option value="google_sheets_append">Google Sheets: Append Row
                                                                    </option>
                                                                    <option value="shopify_update_order">Shopify: Update Order
                                                                    </option>
                                                                </optgroup>
                                                            </select>

                                                            @if($child['action_type'] === 'send_whatsapp')
                                                                <input type="text"
                                                                    wire:model="actions.{{ $index }}.true_nodes.{{ $childIndex }}.config.template_name"
                                                                    placeholder="Template Name"
                                                                    class="w-full px-3 py-2 text-xs font-bold border-none rounded-lg bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500/20">
                                                            @elseif($child['action_type'] === 'assign_user')
                                                                <select
                                                                    wire:model="actions.{{ $index }}.true_nodes.{{ $childIndex }}.config.user_id"
                                                                    class="w-full px-3 py-2 text-xs font-bold border-none rounded-lg bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500/20">
                                                                    <option value="">Select User</option>
                                                                    @foreach($users as $user) <option value="{{ $user->id }}">
                                                                        {{ $user->name }}
                                                                    </option> @endforeach
                                                                </select>
                                                            @elseif($child['action_type'] === 'slack_notification')
                                                                <input type="text"
                                                                    wire:model="actions.{{ $index }}.true_nodes.{{ $childIndex }}.config.message"
                                                                    placeholder="Slack Message"
                                                                    class="w-full px-3 py-2 text-xs font-bold border-none rounded-lg bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500/20">
                                                            @elseif($child['action_type'] === 'webhook')
                                                                <div class="flex gap-2 mb-1">
                                                                    <select
                                                                        wire:model="actions.{{ $index }}.true_nodes.{{ $childIndex }}.config.method"
                                                                        class="w-1/3 px-3 py-2 text-xs font-bold border-none rounded-lg bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500/20">
                                                                        <option value="POST">POST</option>
                                                                        <option value="GET">GET</option>
                                                                    </select>
                                                                    <input type="url"
                                                                        wire:model="actions.{{ $index }}.true_nodes.{{ $childIndex }}.config.url"
                                                                        placeholder="https://"
                                                                        class="w-2/3 px-3 py-2 text-xs font-bold border-none rounded-lg bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500/20">
                                                                </div>
                                                            @elseif($child['action_type'] === 'google_sheets_append')
                                                                <input type="text"
                                                                    wire:model="actions.{{ $index }}.true_nodes.{{ $childIndex }}.config.spreadsheet_id"
                                                                    placeholder="Spreadsheet ID"
                                                                    class="w-full px-3 py-2 text-xs font-bold border-none rounded-lg bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500/20 mb-1">
                                                                <input type="text"
                                                                    wire:model="actions.{{ $index }}.true_nodes.{{ $childIndex }}.config.row_data"
                                                                    placeholder='["{{contact . name}}", "Data"]'
                                                                    class="w-full px-3 py-2 text-xs font-bold border-none rounded-lg bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500/20 font-mono">
                                                            @elseif($child['action_type'] === 'shopify_update_order')
                                                                <input type="text"
                                                                    wire:model="actions.{{ $index }}.true_nodes.{{ $childIndex }}.config.order_id"
                                                                    placeholder="Order ID (Optional)"
                                                                    class="w-full px-3 py-2 text-xs font-bold border-none rounded-lg bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500/20 mb-1">
                                                            @elseif($child['action_type'] === 'add_tag')
                                                                <select
                                                                    wire:model="actions.{{ $index }}.true_nodes.{{ $childIndex }}.config.tag_id"
                                                                    class="w-full px-3 py-2 text-xs font-bold border-none rounded-lg bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500/20">
                                                                    <option value="">Select Tag</option>
                                                                    @foreach($tags as $tag) <option value="{{ $tag->id }}">
                                                                        {{ $tag->name }}
                                                                    </option> @endforeach
                                                                </select>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>

                                                <button wire:click="addNestedAction({{ $index }}, 'true_nodes')"
                                                    class="w-full py-3 flex items-center justify-center gap-2 text-[10px] font-black uppercase tracking-widest text-emerald-600 dark:text-emerald-400 bg-white dark:bg-slate-800 border-2 border-dashed border-emerald-200 dark:border-emerald-900/50 rounded-xl hover:bg-emerald-50 hover:border-emerald-300 dark:hover:bg-emerald-900/30 dark:hover:border-emerald-700 transition-all">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                            d="M12 4v16m8-8H4"></path>
                                                    </svg>
                                                    Add True Action
                                                </button>
                                            </div>

                                            <!-- False Branch -->
                                            <div
                                                class="bg-rose-50/50 dark:bg-rose-900/10 p-5 rounded-2xl border border-rose-100 dark:border-rose-900/30 space-y-4">
                                                <div class="flex items-center justify-between">
                                                    <h5
                                                        class="text-[10px] font-black tracking-widest text-rose-600 dark:text-rose-400 uppercase flex items-center gap-2 bg-rose-100/50 dark:bg-rose-500/20 px-3 py-1.5 rounded-lg border border-rose-200/50 dark:border-rose-500/30">
                                                        <span
                                                            class="w-2 h-2 rounded-full bg-rose-500 shadow-[0_0_8px_rgba(244,63,94,0.8)]"></span>
                                                        If False
                                                    </h5>
                                                    <span
                                                        class="text-[10px] font-bold text-slate-400">{{ count($action['false_nodes']) }}
                                                        steps</span>
                                                </div>

                                                <div class="space-y-3">
                                                    @foreach($action['false_nodes'] as $childIndex => $child)
                                                        <div
                                                            class="bg-white dark:bg-slate-800 p-4 rounded-xl shadow-sm border border-rose-100 dark:border-rose-900/50 relative group/child hover:shadow-md transition-all">
                                                            <button
                                                                wire:click="removeNestedAction({{ $index }}, 'false_nodes', {{ $childIndex }})"
                                                                class="absolute top-2 right-2 p-1.5 bg-slate-50 dark:bg-slate-900 rounded-lg text-slate-300 hover:text-rose-500 opacity-0 group-hover/child:opacity-100 transition-all border border-transparent hover:border-rose-100 dark:hover:border-rose-900/50">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                                    viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                                </svg>
                                                            </button>

                                                            <select
                                                                wire:model="actions.{{ $index }}.false_nodes.{{ $childIndex }}.action_type"
                                                                class="w-full px-3 py-2 text-[10px] font-bold uppercase tracking-wide bg-slate-50 dark:bg-slate-900 border-none rounded-lg text-slate-700 dark:text-slate-300 mb-2 focus:ring-2 focus:ring-rose-500/20">
                                                                <option value="add_tag">Add Tag</option>
                                                                <option value="remove_tag">Remove Tag</option>
                                                                <option value="create_task">Create Task</option>
                                                                <option value="send_email">Send Email</option>
                                                                <option value="send_whatsapp">Send WhatsApp</option>
                                                                <option value="webhook">Webhook (API)</option>
                                                                <option value="assign_user">Assign User</option>
                                                                <option value="slack_notification">Slack Notification</option>
                                                                <optgroup label="Native Integrations">
                                                                    <option value="google_sheets_append">Google Sheets: Append Row
                                                                    </option>
                                                                    <option value="shopify_update_order">Shopify: Update Order
                                                                    </option>
                                                                </optgroup>
                                                            </select>

                                                            @if($child['action_type'] === 'send_whatsapp')
                                                                <input type="text"
                                                                    wire:model="actions.{{ $index }}.false_nodes.{{ $childIndex }}.config.template_name"
                                                                    placeholder="Template Name"
                                                                    class="w-full px-3 py-2 text-xs font-bold border-none rounded-lg bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-rose-500/20">
                                                            @elseif($child['action_type'] === 'assign_user')
                                                                <select
                                                                    wire:model="actions.{{ $index }}.true_nodes.{{ $childIndex }}.config.user_id"
                                                                    class="w-full px-3 py-2 text-xs font-bold border-none rounded-lg bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500/20">
                                                                    <option value="">Select User</option>
                                                                    @foreach($users as $user) <option value="{{ $user->id }}">
                                                                        {{ $user->name }}
                                                                    </option> @endforeach
                                                                </select>
                                                            @elseif($child['action_type'] === 'slack_notification')
                                                                <input type="text"
                                                                    wire:model="actions.{{ $index }}.true_nodes.{{ $childIndex }}.config.message"
                                                                    placeholder="Slack Message"
                                                                    class="w-full px-3 py-2 text-xs font-bold border-none rounded-lg bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500/20">
                                                            @elseif($child['action_type'] === 'webhook')
                                                                <div class="flex gap-2 mb-1">
                                                                    <select
                                                                        wire:model="actions.{{ $index }}.true_nodes.{{ $childIndex }}.config.method"
                                                                        class="w-1/3 px-3 py-2 text-xs font-bold border-none rounded-lg bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500/20">
                                                                        <option value="POST">POST</option>
                                                                        <option value="GET">GET</option>
                                                                    </select>
                                                                    <input type="url"
                                                                        wire:model="actions.{{ $index }}.true_nodes.{{ $childIndex }}.config.url"
                                                                        placeholder="https://"
                                                                        class="w-2/3 px-3 py-2 text-xs font-bold border-none rounded-lg bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500/20">
                                                                </div>
                                                            @elseif($child['action_type'] === 'assign_user')
                                                                <select
                                                                    wire:model="actions.{{ $index }}.true_nodes.{{ $childIndex }}.config.user_id"
                                                                    class="w-full px-3 py-2 text-xs font-bold border-none rounded-lg bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500/20">
                                                                    <option value="">Select User</option>
                                                                    @foreach($users as $user) <option value="{{ $user->id }}">
                                                                        {{ $user->name }}
                                                                    </option> @endforeach
                                                                </select>
                                                            @elseif($child['action_type'] === 'slack_notification')
                                                                <input type="text"
                                                                    wire:model="actions.{{ $index }}.true_nodes.{{ $childIndex }}.config.message"
                                                                    placeholder="Slack Message"
                                                                    class="w-full px-3 py-2 text-xs font-bold border-none rounded-lg bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500/20">
                                                            @elseif($child['action_type'] === 'assign_user')
                                                                <select
                                                                    wire:model="actions.{{ $index }}.false_nodes.{{ $childIndex }}.config.user_id"
                                                                    class="w-full px-3 py-2 text-xs font-bold border-none rounded-lg bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-rose-500/20">
                                                                    <option value="">Select User</option>
                                                                    @foreach($users as $user) <option value="{{ $user->id }}">
                                                                        {{ $user->name }}
                                                                    </option> @endforeach
                                                                </select>
                                                            @elseif($child['action_type'] === 'slack_notification')
                                                                <input type="text"
                                                                    wire:model="actions.{{ $index }}.false_nodes.{{ $childIndex }}.config.message"
                                                                    placeholder="Slack Message"
                                                                    class="w-full px-3 py-2 text-xs font-bold border-none rounded-lg bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-rose-500/20">
                                                            @elseif($child['action_type'] === 'webhook')
                                                                <div class="flex gap-2 mb-1">
                                                                    <select
                                                                        wire:model="actions.{{ $index }}.false_nodes.{{ $childIndex }}.config.method"
                                                                        class="w-1/3 px-3 py-2 text-xs font-bold border-none rounded-lg bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-rose-500/20">
                                                                        <option value="POST">POST</option>
                                                                        <option value="GET">GET</option>
                                                                    </select>
                                                                    <input type="url"
                                                                        wire:model="actions.{{ $index }}.false_nodes.{{ $childIndex }}.config.url"
                                                                        placeholder="https://"
                                                                        class="w-2/3 px-3 py-2 text-xs font-bold border-none rounded-lg bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-rose-500/20">
                                                                </div>
                                                            @elseif($child['action_type'] === 'google_sheets_append')
                                                                <input type="text"
                                                                    wire:model="actions.{{ $index }}.false_nodes.{{ $childIndex }}.config.spreadsheet_id"
                                                                    placeholder="Spreadsheet ID"
                                                                    class="w-full px-3 py-2 text-xs font-bold border-none rounded-lg bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-rose-500/20 mb-1">
                                                                <input type="text"
                                                                    wire:model="actions.{{ $index }}.false_nodes.{{ $childIndex }}.config.row_data"
                                                                    placeholder='["{{contact . name}}", "Data"]'
                                                                    class="w-full px-3 py-2 text-xs font-bold border-none rounded-lg bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-rose-500/20 font-mono">
                                                            @elseif($child['action_type'] === 'shopify_update_order')
                                                                <input type="text"
                                                                    wire:model="actions.{{ $index }}.false_nodes.{{ $childIndex }}.config.order_id"
                                                                    placeholder="Order ID (Optional)"
                                                                    class="w-full px-3 py-2 text-xs font-bold border-none rounded-lg bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-rose-500/20 mb-1">
                                                            @elseif($child['action_type'] === 'add_tag')
                                                                <select
                                                                    wire:model="actions.{{ $index }}.false_nodes.{{ $childIndex }}.config.tag_id"
                                                                    class="w-full px-3 py-2 text-xs font-bold border-none rounded-lg bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-rose-500/20">
                                                                    <option value="">Select Tag</option>
                                                                    @foreach($tags as $tag) <option value="{{ $tag->id }}">
                                                                        {{ $tag->name }}
                                                                    </option> @endforeach
                                                                </select>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>

                                                <button wire:click="addNestedAction({{ $index }}, 'false_nodes')"
                                                    class="w-full py-3 flex items-center justify-center gap-2 text-[10px] font-black uppercase tracking-widest text-rose-600 dark:text-rose-400 bg-white dark:bg-slate-800 border-2 border-dashed border-rose-200 dark:border-rose-900/50 rounded-xl hover:bg-rose-50 hover:border-rose-300 dark:hover:bg-rose-900/30 dark:hover:border-rose-700 transition-all">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                            d="M12 4v16m8-8H4"></path>
                                                    </svg>
                                                    Add False Action
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="relative z-10 py-6">
                                    <div
                                        class="bg-white dark:bg-slate-900 p-8 rounded-[2rem] border border-slate-100 dark:border-slate-800 shadow-xl shadow-slate-200/20 dark:shadow-none relative group hover:border-indigo-300 dark:hover:border-indigo-500/50 transition-all">

                                        <!-- Node Number Badge -->
                                        <div
                                            class="absolute -left-3 -top-3 w-8 h-8 bg-indigo-500 text-white rounded-xl shadow-lg shadow-indigo-500/30 flex items-center justify-center font-black text-sm border-2 border-white dark:border-slate-900 z-20">
                                            {{ $index + 1 }}
                                        </div>

                                        <button wire:click="removeAction({{ $index }})"
                                            class="absolute top-4 right-4 p-2 bg-slate-50 dark:bg-slate-800 rounded-xl text-slate-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-500/10 opacity-0 group-hover:opacity-100 transition-all border border-transparent hover:border-rose-100 dark:hover:border-rose-500/20">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>

                                        <div class="flex gap-6 mb-6">
                                            <div class="flex-1">
                                                <label
                                                    class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Action
                                                    Type</label>
                                                <select wire:model="actions.{{ $index }}.action_type"
                                                    class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-indigo-500/20 text-sm">
                                                    <option value="add_tag">Add Tag</option>
                                                    <option value="remove_tag">Remove Tag</option>
                                                    <option value="update_deal_stage">Update Deal Stage</option>
                                                    <option value="create_task">Create Task</option>
                                                    <option value="send_email">Send Email</option>
                                                    <option value="send_whatsapp">Send WhatsApp (System)</option>
                                                    <option value="webhook">Outbound Webhook (API)</option>
                                                    <option value="assign_user">Assign User</option>
                                                    <option value="slack_notification">Slack Notification</option>
                                                    <optgroup label="Native Integrations">
                                                        <option value="google_sheets_append">Google Sheets: Append Row</option>
                                                        <option value="shopify_update_order">Shopify: Update Order</option>
                                                    </optgroup>
                                                </select>
                                            </div>
                                            <div class="w-1/3">
                                                <label
                                                    class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Delay
                                                    (Minutes)</label>
                                                <div class="relative">
                                                    <input type="number" wire:model="actions.{{ $index }}.delay"
                                                        class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-indigo-500/20 text-sm">
                                                    <div
                                                        class="absolute inset-y-0 right-4 flex items-center pointer-events-none">
                                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Dynamic Config Fields -->
                                        <div class="space-y-4 pt-6 border-t border-slate-100 dark:border-slate-800/80">
                                            @if(($actions[$index]['action_type'] ?? '') === 'assign_user')
                                                <div>
                                                    <label
                                                        class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Assign
                                                        To User</label>
                                                    <select wire:model="actions.{{ $index }}.config.user_id"
                                                        class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-indigo-500/20 text-sm">
                                                        <option value="">Select User...</option>
                                                        @foreach($users as $user)
                                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            @endif

                                            @if(($actions[$index]['action_type'] ?? '') === 'slack_notification')
                                                <div class="space-y-4">
                                                    <div>
                                                        <label
                                                            class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Slack
                                                            Webhook URL (Optional)</label>
                                                        <input type="url" wire:model="actions.{{ $index }}.config.webhook_url"
                                                            placeholder="https://hooks.slack.com/... (Leaves blank to use global default)"
                                                            class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-indigo-500/20 text-sm">
                                                    </div>
                                                    <div>
                                                        <label
                                                            class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Message
                                                            Content</label>
                                                        <textarea wire:model="actions.{{ $index }}.config.message" rows="3"
                                                            placeholder="New Deal Created! {subject_name}"
                                                            class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-indigo-500/20 text-sm"></textarea>
                                                        <p class="text-[10px] text-slate-400 mt-2 font-bold leading-relaxed">
                                                            Available variables: <code
                                                                class="bg-indigo-50 dark:bg-indigo-500/10 px-1 py-0.5 rounded text-indigo-500">{subject_name}</code>,
                                                            <code
                                                                class="bg-indigo-50 dark:bg-indigo-500/10 px-1 py-0.5 rounded text-indigo-500">{subject_id}</code>
                                                        </p>
                                                    </div>
                                                </div>
                                            @endif

                                            @if(($actions[$index]['action_type'] ?? '') === 'webhook')
                                                <div class="space-y-4">
                                                    <div class="grid grid-cols-4 gap-4">
                                                        <div class="col-span-1">
                                                            <label
                                                                class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Method</label>
                                                            <select wire:model="actions.{{ $index }}.config.method"
                                                                class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-indigo-500/20 text-sm">
                                                                <option value="POST">POST</option>
                                                                <option value="GET">GET</option>
                                                                <option value="PUT">PUT</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-span-3">
                                                            <label
                                                                class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Endpoint
                                                                URL</label>
                                                            <input type="url" wire:model="actions.{{ $index }}.config.url"
                                                                placeholder="https://api.example.com/v1/webhook"
                                                                class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white-500 font-bold focus:ring-2 focus:ring-indigo-500/20 text-sm font-mono text-indigo-500 placeholder-slate-300">
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <label
                                                            class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 flex items-center gap-2">JSON
                                                            Payload <span
                                                                class="bg-indigo-100 text-indigo-600 px-2 py-0.5 rounded text-[8px]">Optional</span></label>
                                                        <textarea wire:model="actions.{{ $index }}.config.payload" rows="3"
                                                            placeholder='{"custom_key": "value"}'
                                                            class="w-full px-4 py-3 bg-slate-900 border-none rounded-xl text-emerald-400 font-bold focus:ring-2 focus:ring-indigo-500/20 text-xs font-mono"></textarea>
                                                        <p class="text-[10px] text-slate-400 mt-2 font-bold leading-relaxed">System
                                                            automatically injects <code
                                                                class="bg-slate-100 dark:bg-slate-800 px-1 py-0.5 rounded text-indigo-500">_subject_id</code>
                                                            and <code
                                                                class="bg-slate-100 dark:bg-slate-800 px-1 py-0.5 rounded text-indigo-500">_context</code>
                                                            variables carrying the record info.</p>
                                                    </div>
                                                </div>
                                            @endif

                                            @if(($actions[$index]['action_type'] ?? '') === 'google_sheets_append')
                                                <div class="space-y-4">
                                                    <div class="grid grid-cols-3 gap-4">
                                                        <div class="col-span-2">
                                                            <label
                                                                class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Google
                                                                Spreadsheet ID</label>
                                                            <input type="text"
                                                                wire:model="actions.{{ $index }}.config.spreadsheet_id"
                                                                placeholder="1BxiMVs0XRY..."
                                                                class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-emerald-500/20 text-sm font-mono text-emerald-500">
                                                        </div>
                                                        <div class="col-span-1">
                                                            <label
                                                                class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Range</label>
                                                            <input type="text" wire:model="actions.{{ $index }}.config.range"
                                                                placeholder="Sheet1!A1"
                                                                class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-emerald-500/20 text-sm">
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <label
                                                            class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Row
                                                            Values (JSON Array)</label>
                                                        <textarea wire:model="actions.{{ $index }}.config.row_data" rows="2"
                                                            placeholder='["{{contact . name}}", "{{context . order_date}}", "Lead"]'
                                                            class="w-full px-4 py-3 bg-slate-900 border-none rounded-xl text-emerald-400 font-bold focus:ring-2 focus:ring-emerald-500/20 text-xs font-mono"></textarea>
                                                    </div>
                                                </div>
                                            @endif

                                            @if(($actions[$index]['action_type'] ?? '') === 'shopify_update_order')
                                                <div class="space-y-4">
                                                    <div class="grid grid-cols-3 gap-4">
                                                        <div class="col-span-1">
                                                            <label
                                                                class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2 text-nowrap">Order
                                                                ID (Optional)</label>
                                                            <input type="text" wire:model="actions.{{ $index }}.config.order_id"
                                                                placeholder="Auto-detected if empty"
                                                                class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-amber-500/20 text-sm">
                                                        </div>
                                                        <div class="col-span-2">
                                                            <label
                                                                class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Apply
                                                                Tags (JSON Array)</label>
                                                            <input type="text" wire:model="actions.{{ $index }}.config.tags"
                                                                placeholder='["vip", "urgent"]'
                                                                class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-amber-500 font-bold focus:ring-2 focus:ring-amber-500/20 text-sm font-mono">
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif

                                            @if(($actions[$index]['action_type'] ?? '') === 'send_whatsapp')
                                                <div class="grid grid-cols-3 gap-4">
                                                    <div class="col-span-2">
                                                        <label
                                                            class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Template
                                                            Name</label>
                                                        <input type="text" wire:model="actions.{{ $index }}.config.template_name"
                                                            placeholder="e.g. onboarding_welcome"
                                                            class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-indigo-500/20 text-sm">
                                                        <p class="text-[10px] text-indigo-500 font-bold mt-2">Must match a template
                                                            in your System WABA.</p>
                                                    </div>
                                                    <div>
                                                        <label
                                                            class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Language</label>
                                                        <input type="text" wire:model="actions.{{ $index }}.config.language"
                                                            placeholder="en_US"
                                                            class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-indigo-500/20 text-sm">
                                                    </div>
                                                </div>
                                            @endif

                                            @if(($actions[$index]['action_type'] ?? '') === 'add_tag' || ($actions[$index]['action_type'] ?? '') === 'remove_tag')
                                                <div>
                                                    <label
                                                        class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Select
                                                        Tag</label>
                                                    <select wire:model="actions.{{ $index }}.config.tag_id"
                                                        class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-indigo-500/20 text-sm">
                                                        <option value="">Select Tag...</option>
                                                        @foreach($tags as $tag)
                                                            <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            @endif

                                            @if(($actions[$index]['action_type'] ?? '') === 'update_deal_stage')
                                                <div>
                                                    <label
                                                        class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Target
                                                        Stage</label>
                                                    <select wire:model="actions.{{ $index }}.config.stage_id"
                                                        class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-indigo-500/20 text-sm">
                                                        <option value="">Select Stage...</option>
                                                        @foreach($stages as $stage)
                                                            <option value="{{ $stage->id }}">{{ $stage->name }}
                                                                ({{ $stage->pipeline->name }})</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            @endif

                                            @if(($actions[$index]['action_type'] ?? '') === 'create_task')
                                                <div class="grid grid-cols-2 gap-4">
                                                    <!-- ... (already there, let's keep it complete so we don't break HTML) ... -->
                                                    <div class="col-span-2">
                                                        <label
                                                            class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Task
                                                            Title</label>
                                                        <input type="text" wire:model="actions.{{ $index }}.config.title"
                                                            placeholder="What needs to be done?"
                                                            class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-indigo-500/20 text-sm">
                                                    </div>
                                                    <div>
                                                        <label
                                                            class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Priority</label>
                                                        <select wire:model="actions.{{ $index }}.config.priority"
                                                            class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-indigo-500/20 text-sm">
                                                            <option value="low">Low Priority</option>
                                                            <option value="medium">Medium Priority</option>
                                                            <option value="high">High Priority</option>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label
                                                            class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Assignee</label>
                                                        <select wire:model="actions.{{ $index }}.config.assigned_to_id"
                                                            class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-indigo-500/20 text-sm">
                                                            <option value="">Unassigned</option>
                                                            @foreach($users as $user) <option value="{{ $user->id }}">
                                                                {{ $user->name }}
                                                            </option> @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            @endif

                                            @if(in_array(($actions[$index]['action_type'] ?? ''), ['webhook', 'google_sheets_append', 'shopify_update_order']))
                                                <div class="mt-8 pt-6 border-t border-slate-200 dark:border-slate-800/80">
                                                    <h5
                                                        class="flex items-center gap-2 text-xs font-black uppercase tracking-widest text-rose-500 dark:text-rose-400 mb-4">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                                                            </path>
                                                        </svg>
                                                        Advanced Error Handling (Optional)
                                                    </h5>
                                                    <div class="grid grid-cols-2 gap-4">
                                                        <div>
                                                            <label
                                                                class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Max
                                                                Retries</label>
                                                            <input type="number"
                                                                wire:model="actions.{{ $index }}.config.retry_count"
                                                                placeholder="0 = No Retries"
                                                                class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-rose-500/20 text-sm">
                                                        </div>
                                                        <div>
                                                            <label
                                                                class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Retry
                                                                Delay (Minutes)</label>
                                                            <input type="number"
                                                                wire:model="actions.{{ $index }}.config.retry_delay_minutes"
                                                                placeholder="Wait time between retries"
                                                                class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-slate-900 dark:text-white font-bold focus:ring-2 focus:ring-rose-500/20 text-sm">
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach

                        @if(empty($actions))
                            <div class="text-center py-16 relative z-10 w-full max-w-md mx-auto">
                                <div
                                    class="bg-white/50 dark:bg-slate-800/50 backdrop-blur-sm border-2 border-dashed border-indigo-200 dark:border-indigo-500/30 rounded-[2rem] p-10 shadow-sm transition-all hover:bg-white dark:hover:bg-slate-800 hover:border-indigo-400">
                                    <div
                                        class="w-16 h-16 bg-indigo-50 dark:bg-indigo-500/20 text-indigo-500 dark:text-indigo-400 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-indigo-100 dark:border-indigo-500/30">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z">
                                            </path>
                                        </svg>
                                    </div>
                                    <h3
                                        class="text-lg font-black text-slate-800 dark:text-white uppercase tracking-tight mb-2">
                                        Blank Canvas</h3>
                                    <p
                                        class="text-[10px] font-bold text-slate-500 uppercase tracking-widest leading-relaxed">
                                        No actions defined in this workflow. Use the buttons beneath the trigger node to
                                        begin constructing your logic.</p>
                                </div>
                            </div>
                        @else
                            <!-- End Node -->
                            <div class="flex flex-col items-center mt-6 pb-8 relative z-10">
                                <div
                                    class="bg-slate-900 border border-slate-700 text-slate-400 px-6 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-lg flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-slate-500"></span>
                                    End of Workflow
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <button type="button" wire:click="$set('showCreateModal', false)" wire:loading.attr="disabled"
                class="px-6 py-3 bg-white dark:bg-slate-800 text-slate-400 font-black uppercase tracking-widest text-xs rounded-2xl hover:text-slate-600 transition-all border border-slate-100 dark:border-slate-700">
                Cancel
            </button>
            <button wire:click="save" wire:loading.attr="disabled"
                class="ml-3 px-8 py-3 bg-indigo-600 text-white font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-indigo-600/20 hover:scale-[1.02] active:scale-95 transition-all">
                Save Workflow
            </button>
        </x-slot>
    </x-dialog-modal>

    <!-- Execution Logs Modal -->
    <x-dialog-modal wire:model="showLogsModal" maxWidth="7xl">
        <x-slot name="title">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-slate-100 dark:bg-slate-800 rounded-xl text-slate-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                        Execution <span class="text-indigo-500">Logs</span>
                    </h2>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">
                        {{ $activeLogWorkflowName }}
                    </p>
                </div>
            </div>
        </x-slot>

        <x-slot name="content">
            <div class="h-[600px] overflow-y-auto pr-2 custom-scrollbar">
                @if(empty($workflowLogs))
                    <div class="text-center py-20 text-slate-400">
                        <svg class="w-12 h-12 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-sm font-bold uppercase tracking-widest">No execution logs found yet.</p>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach($workflowLogs as $log)
                            <div
                                class="bg-slate-50 dark:bg-slate-800/50 rounded-2xl p-6 border border-slate-100 dark:border-slate-800">
                                <div class="flex items-start justify-between mb-4 flex-wrap gap-4">
                                    <div class="flex items-center gap-3">
                                        @if($log->status === 'completed')
                                            <div
                                                class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 text-emerald-500 flex items-center justify-center shrink-0">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                        d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </div>
                                        @elseif($log->status === 'failed')
                                            <div
                                                class="w-10 h-10 rounded-xl bg-rose-100 dark:bg-rose-900/30 text-rose-500 flex items-center justify-center shrink-0">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                        d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </div>
                                        @else
                                            <div
                                                class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/30 text-amber-500 flex items-center justify-center shrink-0">
                                                <svg class="animate-spin w-5 h-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                                    </path>
                                                </svg>
                                            </div>
                                        @endif

                                        <div>
                                            <h4
                                                class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-widest">
                                                {{ $log->status }}
                                            </h4>
                                            <p class="text-[10px] font-bold text-slate-400 mt-1">
                                                {{ $log->started_at ? $log->started_at->format('M d, Y h:i:s A') : 'Unknown Start' }}
                                                @if($log->completed_at)
                                                    <span class="text-slate-300 mx-1">&rarr;</span>
                                                    {{ $log->completed_at->format('h:i:s A') }}
                                                    ({{ $log->completed_at->diffInSeconds($log->started_at) }}s)
                                                @endif
                                            </p>
                                        </div>
                                    </div>

                                    <div class="text-right">
                                        <div
                                            class="inline-block px-3 py-1 bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-700 rounded-lg text-[10px] font-black uppercase tracking-widest text-slate-500">
                                            Sub: {{ class_basename($log->subject_type) }} #{{ $log->subject_id }}
                                        </div>
                                    </div>
                                </div>

                                @if($log->error_message)
                                    <div
                                        class="mt-4 bg-rose-50 dark:bg-rose-900/20 border border-rose-100 dark:border-rose-900/50 rounded-xl p-4">
                                        <p class="text-xs font-mono font-bold text-rose-600 dark:text-rose-400 break-words">
                                            {{ $log->error_message }}
                                        </p>
                                    </div>
                                @endif

                                @if($log->execution_data)
                                    <div class="mt-4" x-data="{ expanded: false }">
                                        <button @click="expanded = !expanded"
                                            class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-slate-400 hover:text-indigo-500 transition-colors">
                                            <svg class="w-3 h-3 transition-transform" :class="{'rotate-90': expanded}" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                    d="M9 5l7 7-7 7"></path>
                                            </svg>
                                            View Context Payload
                                        </button>
                                        <div x-show="expanded"
                                            class="mt-3 bg-slate-900 rounded-xl p-4 overflow-x-auto overflow-y-auto max-h-96 custom-scrollbar"
                                            style="display: none;">
                                            <pre class="text-[10px] leading-relaxed text-emerald-400 font-mono">@php
                                                try {
                                                    echo json_encode(is_string($log->execution_data) ? json_decode($log->execution_data) : $log->execution_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                                                } catch (\Exception $e) {
                                                    echo "Failed to parse JSON context.";
                                                }
                                            @endphp</pre>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </x-slot>

        <x-slot name="footer">
            <button type="button" wire:click="closeLogs"
                class="px-6 py-3 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-black uppercase tracking-widest text-xs rounded-2xl hover:bg-slate-200 transition-all">
                Close
            </button>
        </x-slot>
    </x-dialog-modal>

    <!-- Import JSON Modal -->
    <x-dialog-modal wire:model="showImportModal" maxWidth="3xl">
        <x-slot name="title">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl text-indigo-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                        Import <span class="text-indigo-500">JSON</span>
                    </h2>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">Paste exported workflow
                        configuration</p>
                </div>
            </div>
        </x-slot>

        <x-slot name="content">
            <div class="mt-4">
                <textarea wire:model="importJson" rows="12" placeholder="Paste JSON here..."
                    class="w-full px-4 py-3 bg-slate-900 border-none rounded-xl text-emerald-400 font-bold focus:ring-2 focus:ring-indigo-500/20 text-xs font-mono"></textarea>
                @error('importJson') <span
                class="text-rose-500 text-[10px] uppercase font-bold mt-2 block">{{ $message }}</span> @enderror
            </div>
        </x-slot>

        <x-slot name="footer">
            <button type="button" wire:click="$set('showImportModal', false)"
                class="px-6 py-3 bg-white dark:bg-slate-800 text-slate-400 font-black uppercase tracking-widest text-xs rounded-2xl hover:text-slate-600 transition-all border border-slate-100 dark:border-slate-700">
                Cancel
            </button>
            <button wire:click="processImport"
                class="ml-3 px-8 py-3 bg-indigo-600 text-white font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-indigo-600/20 hover:scale-[1.02] active:scale-95 transition-all">
                Run Import
            </button>
        </x-slot>
    </x-dialog-modal>
</div>