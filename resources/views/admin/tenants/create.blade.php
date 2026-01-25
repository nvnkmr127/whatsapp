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
                    New <span class="text-wa-green">Workspace</span>
                </h1>
                <p class="text-slate-500 font-medium">Create a new isolated environment for a client company.</p>
            </div>

            <!-- Form Card -->
            <div
                class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-xl border border-slate-50 dark:border-slate-800 overflow-hidden">
                <form action="{{ route('admin.tenants.store') }}" method="POST">
                    @csrf

                    <div class="p-8 md:p-10 space-y-8">
                        <!-- Company Details -->
                        <div class="space-y-6">
                            <h3
                                class="text-xs font-black uppercase tracking-widest text-slate-400 border-b border-slate-50 dark:border-slate-800 pb-2">
                                Company Information</h3>

                            <div class="space-y-2">
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500">Company Name
                                    <span class="text-rose-500">*</span></label>
                                <input type="text" name="company_name" value="{{ old('company_name') }}" required
                                    class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-900 dark:text-white font-bold placeholder:text-slate-400 focus:ring-2 focus:ring-wa-green/20 transition-all"
                                    placeholder="e.g. Acme Corp">
                                @error('company_name') <span
                                class="text-rose-500 text-xs font-bold uppercase">{{ $message }}</span> @enderror
                            </div>

                            <div class="space-y-2">
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500">Subscription
                                    Plan <span class="text-rose-500">*</span></label>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    @foreach($plans as $plan)
                                        <label class="cursor-pointer">
                                            <input type="radio" name="plan" value="{{ $plan->name }}" class="peer hidden" {{ old('plan') === $plan->name ? 'checked' : '' }}>
                                            <div
                                                class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800 border-2 border-transparent peer-checked:border-wa-green peer-checked:bg-wa-green/5 transition-all text-center relative">
                                                @if($plan->name === 'pro')
                                                    <div
                                                        class="absolute -top-2 right-4 px-2 py-0.5 bg-wa-green text-white text-[10px] font-black uppercase rounded-full">
                                                        Recommended</div>
                                                @endif
                                                <div class="font-black text-slate-900 dark:text-white">
                                                    {{ $plan->display_name }}</div>
                                                <div
                                                    class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">
                                                    ${{ number_format($plan->monthly_price, 0) }}/mo</div>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                                @error('plan') <span
                                class="text-rose-500 text-xs font-bold uppercase">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Owner Account -->
                        <div class="space-y-6 pt-4">
                            <h3
                                class="text-xs font-black uppercase tracking-widest text-slate-400 border-b border-slate-50 dark:border-slate-800 pb-2">
                                Administrator Account</h3>
                            <p class="text-xs font-medium text-slate-500">This user will be the Super Admin of the new
                                workspace.</p>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label class="text-xs font-black uppercase tracking-widest text-slate-500">Owner
                                        Name <span class="text-rose-500">*</span></label>
                                    <input type="text" name="owner_name" value="{{ old('owner_name') }}" required
                                        class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-900 dark:text-white font-bold placeholder:text-slate-400 focus:ring-2 focus:ring-wa-green/20 transition-all"
                                        placeholder="Full Name">
                                    @error('owner_name') <span
                                        class="text-rose-500 text-xs font-bold uppercase">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="space-y-2">
                                    <label class="text-xs font-black uppercase tracking-widest text-slate-500">Owner
                                        Email <span class="text-rose-500">*</span></label>
                                    <input type="email" name="owner_email" value="{{ old('owner_email') }}" required
                                        class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-900 dark:text-white font-bold placeholder:text-slate-400 focus:ring-2 focus:ring-wa-green/20 transition-all"
                                        placeholder="email@company.com">
                                    @error('owner_email') <span
                                        class="text-rose-500 text-xs font-bold uppercase">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="text-xs font-black uppercase tracking-widest text-slate-500">Temporary
                                    Password <span class="text-rose-500">*</span></label>
                                <input type="password" name="owner_password" required minlength="8"
                                    class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl text-slate-900 dark:text-white font-bold placeholder:text-slate-400 focus:ring-2 focus:ring-wa-green/20 transition-all"
                                    placeholder="Minimum 8 characters">
                                @error('owner_password') <span
                                class="text-rose-500 text-xs font-bold uppercase">{{ $message }}</span> @enderror
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
                            class="px-10 py-4 bg-wa-green text-white font-black uppercase tracking-widest text-xs rounded-2xl shadow-xl shadow-wa-green/20 hover:scale-[1.02] active:scale-95 transition-all">
                            Create Workspace
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>