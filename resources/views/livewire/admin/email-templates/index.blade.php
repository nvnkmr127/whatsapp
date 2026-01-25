<div class="py-12 bg-slate-50/50 dark:bg-slate-900/50 min-h-screen">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
            <div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">
                    Email <span class="text-indigo-600">Engine</span>
                </h1>
                <p class="text-slate-500 font-medium tracking-tight">Manage system-wide email notifications, marketing blasts, and delivery performance.
                </p>
            </div>

            <div class="flex items-center gap-3 bg-white dark:bg-slate-800 p-1.5 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700">
                <a href="{{ route('admin.email-templates.index') }}" 
                   class="px-4 py-2 text-xs font-black uppercase tracking-widest rounded-xl transition-all {{ request()->routeIs('admin.email-templates.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-200' : 'text-slate-500 hover:bg-slate-50' }}">
                    Templates
                </a>
                <a href="{{ route('admin.email-logs.index') }}" 
                   class="px-4 py-2 text-xs font-black uppercase tracking-widest rounded-xl transition-all {{ request()->routeIs('admin.email-logs.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-200' : 'text-slate-500 hover:bg-slate-50' }}">
                    Tracking Logs
                </a>
            </div>

            <a href="{{ route('admin.email-templates.create') }}"
                class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-black uppercase tracking-widest text-xs rounded-xl shadow-lg shadow-indigo-200 transition-all active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Create Template
            </a>
        </div>

        <!-- List -->
        <div
            class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-slate-50 dark:border-slate-800/50">
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                Template Name</th>
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Use
                                Case</th>
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Key
                                (Slug)</th>
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Status
                            </th>
                            <th
                                class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-slate-800/30">
                        @forelse($templates as $template)
                            <tr class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-4">
                                        @if($template->is_locked)
                                            <div class="p-2 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 rounded-lg"
                                                title="System Locked">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                                </svg>
                                            </div>
                                        @else
                                            <div class="p-2 bg-slate-100 dark:bg-slate-800 text-slate-500 rounded-lg">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                        @endif
                                        <div>
                                            <div class="text-sm font-black text-slate-900 dark:text-white">
                                                {{ $template->name }}</div>
                                            <div class="text-xs text-slate-500 font-medium">
                                                {{ Str::limit($template->subject, 40) }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <span
                                        class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-xs font-black uppercase tracking-wider rounded-lg border border-slate-200/50 dark:border-slate-700/50">
                                        {{ $template->type?->value ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-8 py-6">
                                    <code
                                        class="text-xs bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded text-red-500 font-mono">
                                            {{ $template->slug }}
                                        </code>
                                </td>
                                <td class="px-8 py-6">
                                    <span
                                        class="flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest {{ $template->is_active ? 'text-wa-green' : 'text-slate-400' }}">
                                        <span
                                            class="w-1.5 h-1.5 rounded-full {{ $template->is_active ? 'bg-wa-green' : 'bg-slate-400' }}"></span>
                                        {{ $template->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <div class="flex items-center justify-end gap-4">
                                        <a href="{{ route('admin.email-templates.edit', $template->id) }}"
                                            class="inline-flex items-center gap-2 text-indigo-600 hover:text-indigo-800 font-bold text-xs uppercase tracking-widest">
                                            Manage
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 5l7 7-7 7" />
                                            </svg>
                                        </a>

                                        @if(!$template->is_locked)
                                            <button wire:click="delete({{ $template->id }})"
                                                wire:confirm="Are you sure you want to delete this template?"
                                                class="text-slate-400 hover:text-rose-500 transition-colors"
                                                title="Delete Template">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5"
                                    class="px-8 py-20 text-center text-slate-400 font-bold uppercase tracking-widest text-xs">
                                    No templates found
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>