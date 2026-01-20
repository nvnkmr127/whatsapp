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

                            <div class="space-y-2">
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500">Subscription
                                    Status <span class="text-rose-500">*</span></label>
                                <div class="relative">
                                    <select name="subscription_status"
                                        class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-900 dark:text-white font-bold cursor-pointer focus:ring-2 focus:ring-indigo-500/20 transition-all">
                                        <option value="active" {{ old('subscription_status', $team->subscription_status) === 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="inactive" {{ old('subscription_status', $team->subscription_status) === 'inactive' ? 'selected' : '' }}>Inactive
                                        </option>
                                        <option value="cancelled" {{ old('subscription_status', $team->subscription_status) === 'cancelled' ? 'selected' : '' }}>Cancelled
                                        </option>
                                    </select>
                                    <div
                                        class="absolute right-5 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </div>
                                @error('subscription_status') <span
                                class="text-rose-500 text-xs font-bold uppercase">{{ $message }}</span> @enderror
                            </div>

                            <div class="space-y-2">
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500">Subscription
                                    Plan <span class="text-rose-500">*</span></label>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <label class="cursor-pointer">
                                        <input type="radio" name="plan" value="basic" class="peer hidden" {{ old('plan', $team->subscription_plan) === 'basic' ? 'checked' : '' }}>
                                        <div
                                            class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800 border-2 border-transparent peer-checked:border-indigo-500 peer-checked:bg-indigo-500/5 transition-all text-center">
                                            <div class="font-black text-slate-900 dark:text-white">Basic</div>
                                            <div class="text-xs font-bold text-slate-400">Starter features</div>
                                        </div>
                                    </label>
                                    <label class="cursor-pointer">
                                        <input type="radio" name="plan" value="pro" class="peer hidden" {{ old('plan', $team->subscription_plan) === 'pro' ? 'checked' : '' }}>
                                        <div
                                            class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800 border-2 border-transparent peer-checked:border-indigo-500 peer-checked:bg-indigo-500/5 transition-all text-center">
                                            <div class="font-black text-slate-900 dark:text-white">Pro</div>
                                            <div class="text-xs font-bold text-slate-400">Most popular</div>
                                        </div>
                                    </label>
                                    <label class="cursor-pointer">
                                        <input type="radio" name="plan" value="enterprise" class="peer hidden" {{ old('plan', $team->subscription_plan) === 'enterprise' ? 'checked' : '' }}>
                                        <div
                                            class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800 border-2 border-transparent peer-checked:border-indigo-500 peer-checked:bg-indigo-500/5 transition-all text-center">
                                            <div class="font-black text-slate-900 dark:text-white">Enterprise</div>
                                            <div class="text-xs font-bold text-slate-400">Full power</div>
                                        </div>
                                    </label>
                                </div>
                                @error('plan') <span
                                class="text-rose-500 text-xs font-bold uppercase">{{ $message }}</span> @enderror
                            </div>

                            <!-- Feature Add-ons -->
                            <div class="space-y-4 pt-4">
                                <h3
                                    class="text-xs font-black uppercase tracking-widest text-slate-400 border-b border-slate-50 dark:border-slate-800 pb-2">
                                    Feature Add-ons</h3>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <label class="relative flex items-center cursor-pointer group">
                                        <input type="checkbox" name="features[]" value="backups" class="peer hidden" {{ $team->addOns->contains('type', 'backups') ? 'checked' : '' }}>
                                        <div
                                            class="flex items-center gap-4 w-full p-4 rounded-2xl bg-slate-50 dark:bg-slate-800 border-2 border-transparent peer-checked:border-wa-green peer-checked:bg-wa-green/5 transition-all">
                                            <div
                                                class="w-10 h-10 bg-white dark:bg-slate-900 rounded-xl flex items-center justify-center text-slate-400 group-hover:text-wa-green transition-colors shadow-sm">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                                                </svg>
                                            </div>
                                            <div class="flex-1">
                                                <div
                                                    class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-tight">
                                                    Manual Backups</div>
                                                <div
                                                    class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                                    Enable Snapshot Recovery</div>
                                            </div>
                                            <div
                                                class="w-6 h-6 rounded-full border-2 border-slate-200 dark:border-slate-700 flex items-center justify-center peer-checked:bg-wa-green peer-checked:border-wa-green transition-all">
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
                                            <div
                                                class="w-10 h-10 bg-white dark:bg-slate-900 rounded-xl flex items-center justify-center text-slate-400 group-hover:text-blue-500 transition-colors shadow-sm">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                                </svg>
                                            </div>
                                            <div class="flex-1">
                                                <div
                                                    class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-tight">
                                                    Cloud Sync</div>
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
        </div>
    </div>
</x-app-layout>