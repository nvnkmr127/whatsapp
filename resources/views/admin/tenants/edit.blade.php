<x-app-layout>
    <div class="py-12 bg-slate-50/50 dark:bg-slate-900/50 min-h-screen">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">

            <!-- Page Header -->
            <div class="mb-8">
                <a href="{{ route('admin.dashboard') }}"
                    class="inline-flex items-center gap-2 text-sm font-bold text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors mb-4">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Dashboard
                </a>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase">
                    Edit <span class="text-indigo-500">Workspace</span>
                </h1>
                <p class="text-slate-500 font-medium">Update configuration for {{ $team->name }}.</p>
            </div>

            <!-- Form Card -->
            <div
                class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
                <form action="{{ route('admin.tenants.update', $team->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="p-8 md:p-10 space-y-8">
                        <!-- Company Details -->
                        <div class="space-y-6">
                            <h3
                                class="text-xs font-black uppercase tracking-widest text-slate-400 border-b border-slate-50 dark:border-slate-800 pb-2">
                                Workspace Configuration</h3>

                            <div class="space-y-2">
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500">Company Name
                                    <span class="text-rose-500">*</span></label>
                                <input type="text" name="company_name" value="{{ old('company_name', $team->name) }}"
                                    required
                                    class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-900 dark:text-white font-bold placeholder:text-slate-400 focus:ring-2 focus:ring-indigo-500/20 transition-all">
                                @error('company_name') <span
                                class="text-rose-500 text-xs font-bold uppercase">{{ $message }}</span> @enderror
                            </div>

                            <!-- Billing & Plan -->
                            <div class="space-y-6">
                                <h3
                                    class="text-xs font-black uppercase tracking-widest text-indigo-500 border-b border-indigo-50 dark:border-slate-800 pb-2 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                    </svg>
                                    Billing & Plan Configuration
                                </h3>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <label
                                            class="text-xs font-black uppercase tracking-widest text-slate-500">Subscription
                                            Status</label>
                                        <select name="subscription_status"
                                            class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-sm font-bold">
                                            <option value="active" {{ old('subscription_status', $team->subscription_status) === 'active' ? 'selected' : '' }}>Active (Full
                                                Access)</option>
                                            <option value="inactive" {{ old('subscription_status', $team->subscription_status) === 'inactive' ? 'selected' : '' }}>Inactive
                                                (Read-Only)</option>
                                            <option value="cancelled" {{ old('subscription_status', $team->subscription_status) === 'cancelled' ? 'selected' : '' }}>Cancelled
                                                (Suspended)</option>
                                        </select>
                                    </div>

                                    <div class="space-y-2">
                                        <label
                                            class="text-xs font-black uppercase tracking-widest text-slate-500">Workspace
                                            Plan</label>
                                        <select name="plan"
                                            class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-sm font-bold">
                                            @foreach($plans as $plan)
                                                <option value="{{ $plan->name }}" {{ old('plan', $team->subscription_plan) === $plan->name ? 'selected' : '' }}>
                                                    {{ $plan->display_name }}
                                                    (${{ number_format($plan->monthly_price, 0) }}/mo)
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Infrastructure Capabilities -->
                            <div class="space-y-4 pt-4">
                                <h3
                                    class="text-xs font-black uppercase tracking-widest text-emerald-500 border-b border-emerald-50 dark:border-slate-800 pb-2 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                    Infrastructure & Add-ons
                                </h3>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Enable
                                    system-level capabilities beyond the standard plan.</p>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                    <label class="relative flex items-center cursor-pointer group">
                                        <input type="checkbox" name="features[]" value="backups" class="peer hidden" {{ $team->addOns->contains('type', 'backups') ? 'checked' : '' }}>
                                        <div
                                            class="flex items-center gap-4 w-full p-4 rounded-2xl bg-slate-50 dark:bg-slate-800 border-2 border-transparent peer-checked:border-emerald-500 peer-checked:bg-emerald-500/5 transition-all">
                                            <div class="flex-1">
                                                <div
                                                    class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-tight">
                                                    System Snapshots</div>
                                                <div
                                                    class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                                    Point-in-time recovery</div>
                                            </div>
                                            <div
                                                class="w-6 h-6 rounded-full border-2 border-slate-200 dark:border-slate-700 flex items-center justify-center peer-checked:bg-emerald-500 peer-checked:border-emerald-500 transition-all">
                                                <svg class="w-4 h-4 text-white hidden peer-checked:block" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="3" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>
                                        </div>
                                    </label>

                                    <label class="relative flex items-center cursor-pointer group">
                                        <input type="checkbox" name="features[]" value="cloud_backups"
                                            class="peer hidden" {{ $team->addOns->contains('type', 'cloud_backups') ? 'checked' : '' }}>
                                        <div
                                            class="flex items-center gap-4 w-full p-4 rounded-2xl bg-slate-50 dark:bg-slate-800 border-2 border-transparent peer-checked:border-blue-500 peer-checked:bg-blue-500/5 transition-all">
                                            <div class="flex-1">
                                                <div
                                                    class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-tight">
                                                    Cloud Off-site</div>
                                                <div
                                                    class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                                    Google Drive Integration</div>
                                            </div>
                                            <div
                                                class="w-6 h-6 rounded-full border-2 border-slate-200 dark:border-slate-700 flex items-center justify-center peer-checked:bg-blue-500 peer-checked:border-blue-500 transition-all">
                                                <svg class="w-4 h-4 text-white hidden peer-checked:block" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="3" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Info Section (Read Only) -->
                        <div class="pt-6 border-t border-slate-50 dark:border-slate-800">
                            <div class="bg-indigo-50/50 dark:bg-indigo-900/10 rounded-2xl p-6 flex gap-4">
                                <div class="flex-shrink-0 pt-1">
                                    <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h4
                                        class="text-sm font-black text-indigo-900 dark:text-indigo-100 uppercase tracking-wide">
                                        Owner Details</h4>
                                    <p class="text-xs font-medium text-indigo-700 dark:text-indigo-300 mt-1">
                                        Owner Name: {{ $team->owner->name }}<br>
                                        Owner Email: {{ $team->owner->email }}
                                    </p>
                                    <p class="text-[10px] text-indigo-500 mt-2">To change owner details, please manage
                                        the User directly in user management.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="p-8 md:p-10 bg-slate-50/50 dark:bg-slate-800/50 flex justify-end gap-4 border-t border-slate-50 dark:border-slate-800">
                        <a href="{{ route('admin.dashboard') }}"
                            class="px-8 py-4 bg-white dark:bg-slate-800 text-slate-500 font-black uppercase tracking-widest text-xs rounded-2xl hover:bg-slate-50 dark:hover:bg-slate-700 transition-all border border-slate-100 dark:border-slate-800">
                            Cancel
                        </a>
                        <button type="submit"
                            class="px-10 py-4 bg-indigo-600 text-white font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-indigo-600/20 hover:scale-[1.02] active:scale-95 transition-all">
                            Update Workspace
                        </button>
                    </div>
                </form>
            </div>
            <!-- Billing Overrides -->
            <div
                class="mt-8 bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
                <div class="p-8 md:p-10 space-y-8">
                    <h3
                        class="text-xs font-black uppercase tracking-widest text-slate-400 border-b border-slate-50 dark:border-slate-800 pb-2">
                        Billing Overrides (Staff Only)
                    </h3>

                    @if($team->billingOverrides->count() > 0)
                        <div class="space-y-4">
                            @foreach($team->billingOverrides as $override)
                                <div
                                    class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-800 rounded-2xl border {{ $override->trashed() || ($override->expires_at && $override->expires_at->isPast()) ? 'opacity-50 grayscale' : 'border-indigo-100 dark:border-indigo-900' }}">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="text-[10px] font-black uppercase tracking-widest px-2 py-0.5 rounded bg-indigo-500 text-white">
                                                {{ str_replace('_', ' ', $override->type) }}
                                            </span>
                                            <span class="text-sm font-black">{{ $override->key }}: {{ $override->value }}</span>
                                        </div>
                                        <p class="text-[10px] font-bold text-slate-400 mt-1 uppercase">
                                            By {{ $override->creator->name ?? 'System' }} •
                                            {{ $override->expires_at ? 'Expires ' . $override->expires_at->format('M d, Y') : 'Permanent' }}
                                        </p>
                                        <p class="text-xs italic text-slate-500 mt-1">"{{ $override->reason }}"</p>
                                    </div>
                                    <form action="{{ route('admin.tenants.overrides.destroy', [$team->id, $override->id]) }}"
                                        method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="p-2 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/10 rounded-xl transition-all">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs font-bold text-slate-400 text-center py-4 italic">No active overrides for this
                            workspace.</p>
                    @endif

                    <!-- Add New Override -->
                    <form action="{{ route('admin.tenants.overrides.store', $team->id) }}" method="POST"
                        class="pt-6 border-t border-slate-50 dark:border-slate-800">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label
                                    class="text-[10px] font-black uppercase tracking-widest text-slate-500">Type</label>
                                <select name="type"
                                    class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-bold">
                                    <option value="limit_increase">Limit Increase</option>
                                    <option value="feature_enable">Feature Enable</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">Metric
                                    Key</label>
                                <input type="text" name="key" placeholder="e.g., message_limit" required
                                    class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-bold">
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">New
                                    Value</label>
                                <input type="text" name="value" placeholder="e.g., 5000 or true" required
                                    class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-bold">
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">Duration
                                    (Days)</label>
                                <input type="number" name="duration" value="30"
                                    class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-bold">
                            </div>
                        </div>
                        <div class="mt-4 space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-slate-500">Internal
                                Reason</label>
                            <textarea name="reason" rows="2" required placeholder="Why is this override being granted?"
                                class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-bold"></textarea>
                        </div>
                        <button type="submit"
                            class="mt-4 w-full py-4 bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-black uppercase tracking-widest text-[10px] rounded-2xl hover:scale-[1.01] transition-all">
                            Apply Override
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>