<x-app-layout>
    <div class="space-y-8 p-6">
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <div class="p-2 bg-emerald-500/10 text-emerald-500 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">Consent
                        <span class="text-emerald-500">Registry</span>
                    </h1>
                </div>
                <p class="text-slate-500 font-medium">Track and manage customer consent status for compliance.</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('compliance.registry', ['status' => 'opted_in']) }}"
                    class="flex items-center justify-center gap-2 px-6 py-3 bg-white dark:bg-slate-800 text-slate-500 font-black uppercase tracking-widest text-xs rounded-2xl hover:bg-slate-50 dark:hover:bg-slate-700 transition-all border border-slate-100 dark:border-slate-800">
                    <span class="w-2 h-2 rounded-full bg-wa-teal"></span>
                    Opted In
                </a>
                <a href="{{ route('compliance.registry', ['status' => 'opted_out']) }}"
                    class="flex items-center justify-center gap-2 px-6 py-3 bg-white dark:bg-slate-800 text-slate-500 font-black uppercase tracking-widest text-xs rounded-2xl hover:bg-slate-50 dark:hover:bg-slate-700 transition-all border border-slate-100 dark:border-slate-800">
                    <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                    Opted Out
                </a>
                <a href="{{ route('compliance.registry') }}"
                    class="flex items-center justify-center gap-2 px-6 py-3 bg-slate-900 dark:bg-wa-teal text-white dark:text-slate-900 font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-slate-900/10 dark:shadow-wa-teal/20 hover:scale-[1.02] active:scale-95 transition-all">
                    All Contacts
                </a>
            </div>
        </div>

        <!-- Table Card -->
        <div
            class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
            <!-- Table Content -->
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-slate-50 dark:border-slate-800/50">
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                Contact
                                Identity</th>
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                Communication</th>
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                Consent
                                Status</th>
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Source
                            </th>
                            <th class="px-8 py-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Last
                                Changed</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-slate-800/30">
                        @forelse($contacts as $contact)
                            <tr class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/20 transition-colors">
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-4">
                                        <img src="https://api.dicebear.com/9.x/micah/svg?seed={{ $contact->name }}"
                                            alt="{{ $contact->name }}"
                                            class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-800 object-cover"
                                            loading="lazy">
                                        <div>
                                            <div class="text-sm font-black text-slate-900 dark:text-white">
                                                {{ $contact->name }}
                                            </div>
                                            <div class="text-xs text-slate-500 font-medium">
                                                {{ $contact->email ?: 'No email linked' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <span
                                        class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-xs font-black tabular-nums rounded-lg border border-slate-200/50 dark:border-slate-700/50">
                                        {{ $contact->phone_number }}
                                    </span>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="w-2 h-2 rounded-full {{ $contact->opt_in_status === 'opted_in' ? 'bg-wa-teal shadow-lg shadow-wa-teal/40' : 'bg-rose-500 shadow-lg shadow-rose-500/40' }}"></span>
                                        <span
                                            class="text-xs font-black uppercase tracking-widest {{ $contact->opt_in_status === 'opted_in' ? 'text-wa-teal' : 'text-rose-500' }}">
                                            {{ str_replace('_', ' ', $contact->opt_in_status) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <span class="text-sm text-slate-500 dark:text-slate-400 font-medium">
                                        {{ $contact->opt_in_source ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-8 py-6">
                                    <span class="text-sm text-slate-500 dark:text-slate-400 font-medium">
                                        {{ $contact->opt_in_at ? $contact->opt_in_at->format('M d, Y H:i') : '-' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-8 py-20 text-center">
                                    <div class="flex flex-col items-center gap-4">
                                        <div
                                            class="w-16 h-16 bg-slate-50 dark:bg-slate-800 rounded-2xl flex items-center justify-center text-slate-300">
                                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                            </svg>
                                        </div>
                                        <div class="text-slate-400 font-bold">No consent records found.</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($contacts->hasPages())
                <div class="p-8 border-t border-slate-50 dark:border-slate-800/50 bg-slate-50/50 dark:bg-slate-800/10">
                    {{ $contacts->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>