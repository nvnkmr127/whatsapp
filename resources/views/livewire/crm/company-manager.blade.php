<div class="p-6">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h2 class="text-2xl font-black text-slate-900 tracking-tight">Companies</h2>
            <p class="text-slate-500 font-bold uppercase tracking-widest text-[10px]">B2B Relationships</p>
        </div>
        <button wire:click="create" class="px-6 py-3 bg-slate-900 text-white rounded-[1.2rem] text-[10px] font-black uppercase tracking-widest hover:bg-wa-teal transition-all shadow-lg shadow-slate-900/10">
            Add Company
        </button>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-50 border border-green-100 text-green-700 rounded-2xl flex items-center gap-2 text-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            {{ session('message') }}
        </div>
    @endif

    <div class="mb-8 relative group">
        <input type="text" wire:model.live="search" placeholder="Search by name, domain or industry..." 
            class="w-full pl-12 pr-6 py-4 bg-white border border-slate-200 rounded-2.5xl text-sm focus:ring-2 focus:ring-wa-teal focus:border-transparent transition-all shadow-sm">
        <div class="absolute left-4 top-4 text-slate-400 group-focus-within:text-wa-teal transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        @forelse($companies as $company)
            <div class="group relative bg-white border border-slate-200 rounded-[2rem] p-6 hover:shadow-2xl hover:shadow-slate-200/50 transition-all duration-500 hover:-translate-y-1">
                <div class="flex items-start justify-between mb-4">
                    <div class="h-14 w-14 rounded-[1.2rem] bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 font-black text-xl">
                        {{ substr($company->name, 0, 1) }}
                    </div>
                    <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity translate-x-2 group-hover:translate-x-0 transition-transform duration-300">
                        <button wire:click="edit({{ $company->id }})" class="p-2 bg-slate-50 text-slate-400 hover:text-slate-900 hover:bg-white border border-transparent hover:border-slate-100 rounded-xl transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-5M16.5 3.5a2.121 2.121 0 113 3L11.707 14.707a1 1 0 01-.414.263L8 15l.03-.327a1 1 0 01.263-.414L16.5 3.5z"></path></svg>
                        </button>
                        <button wire:click="confirmDelete({{ $company->id }})" class="p-2 bg-slate-50 text-slate-400 hover:text-red-600 hover:bg-white border border-transparent hover:border-slate-100 rounded-xl transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m4-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </div>
                </div>

                <h3 class="text-lg font-black text-slate-900 tracking-tight line-clamp-1 mb-1">{{ $company->name }}</h3>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-4">
                    {{ $company->domain ?? 'No domain set' }} • {{ $company->industry ?? 'Other' }}
                </p>

                <div class="space-y-4 pt-4 border-t border-slate-50">
                    <div class="flex justify-between items-center text-[10px] font-black uppercase tracking-widest">
                        <span class="text-slate-400">Contacts</span>
                        <span class="text-slate-900 bg-slate-50 px-2 py-1 rounded-lg border border-slate-100">{{ $company->contacts_count ?? 0 }} Assets</span>
                    </div>
                    <div class="flex justify-between items-center text-[10px] font-black uppercase tracking-widest">
                        <span class="text-slate-400">Pipeline Value</span>
                        <span class="text-wa-teal font-black">${{ number_format($company->deals_sum_value ?? 0, 2) }}</span>
                    </div>
                </div>

                <div class="mt-6 flex gap-2">
                    @if($company->website)
                        <a href="{{ $company->website }}" target="_blank" class="flex-1 py-3 bg-slate-50 text-slate-600 rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-slate-100 text-center transition-all">
                            Website
                        </a>
                    @endif
                    <button class="flex-1 py-3 bg-slate-900 text-white rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-wa-teal text-center transition-all shadow-lg shadow-slate-900/10 hover:shadow-wa-teal/20">
                        View Deals
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-full py-20 text-center">
                <div class="inline-flex items-center justify-center h-20 w-20 rounded-[2rem] bg-slate-50 border border-slate-100 mb-6">
                    <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900">No companies found</h3>
                <p class="text-slate-500 text-sm mt-2">Start by adding your first B2B relationship.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-8">
        {{ $companies->links() }}
    </div>

    {{-- Company Modal --}}
    @if($isModalOpen)
        <div class="fixed inset-0 z-[100] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" aria-hidden="true" wire:click="closeModal"></div>
                
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                
                <div class="inline-block align-bottom bg-white rounded-[2.5rem] text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full border border-slate-200">
                    <div class="px-8 pt-8 pb-6 border-b border-slate-50">
                        <h3 class="text-2xl font-black text-slate-900 tracking-tight">{{ $companyId ? 'Update Company' : 'New Company' }}</h3>
                        <p class="text-slate-500 font-bold uppercase tracking-widest text-[10px]">Relationship Details</p>
                    </div>

                    <form wire:submit.prevent="store" class="p-8 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="col-span-full">
                                <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Company Name</label>
                                <input type="text" wire:model="name" class="w-full px-5 py-4 bg-slate-50 border-none rounded-2xl text-sm focus:ring-2 focus:ring-wa-teal transition-all" placeholder="Acme Inc.">
                                @error('name') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Domain</label>
                                <input type="text" wire:model="domain" class="w-full px-5 py-4 bg-slate-50 border-none rounded-2xl text-sm focus:ring-2 focus:ring-wa-teal transition-all" placeholder="acme.com">
                            </div>

                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Industry</label>
                                <input type="text" wire:model="industry" class="w-full px-5 py-4 bg-slate-50 border-none rounded-2xl text-sm focus:ring-2 focus:ring-wa-teal transition-all" placeholder="Software">
                            </div>

                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Size</label>
                                <select wire:model="size" class="w-full px-5 py-4 bg-slate-50 border-none rounded-2xl text-sm focus:ring-2 focus:ring-wa-teal transition-all">
                                    <option value="">Select Size</option>
                                    <option value="1-10">1-10 employees</option>
                                    <option value="11-50">11-50 employees</option>
                                    <option value="51-200">51-200 employees</option>
                                    <option value="201-500">201-500 employees</option>
                                    <option value="500+">500+ employees</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Website</label>
                                <input type="text" wire:model="website" class="w-full px-5 py-4 bg-slate-50 border-none rounded-2xl text-sm focus:ring-2 focus:ring-wa-teal transition-all" placeholder="https://...">
                            </div>

                            <div class="col-span-full">
                                <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500 mb-2">Description</label>
                                <textarea wire:model="description" rows="3" class="w-full px-5 py-4 bg-slate-50 border-none rounded-2xl text-sm focus:ring-2 focus:ring-wa-teal transition-all" placeholder="Notes about this company..."></textarea>
                            </div>
                        </div>

                        <div class="flex gap-4 pt-4">
                            <button type="button" wire:click="$set('isModalOpen', false)" class="flex-1 py-4 bg-slate-100 text-slate-600 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-200 transition-all">
                                Cancel
                            </button>
                            <button type="submit" class="flex-1 py-4 bg-slate-900 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-wa-teal transition-all shadow-xl shadow-slate-900/10">
                                {{ $companyId ? 'Save Changes' : 'Create Company' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Delete Modal --}}
    @if($isDeleteModalOpen)
        <div class="fixed inset-0 z-[100] overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" wire:click="$set('isDeleteModalOpen', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-[2.5rem] text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-200">
                    <div class="p-8 text-center">
                        <div class="inline-flex items-center justify-center h-20 w-20 rounded-[2rem] bg-red-50 border border-red-100 mb-6 text-red-600">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        </div>
                        <h3 class="text-2xl font-black text-slate-900 tracking-tight mb-2">Delete Company?</h3>
                        <p class="text-slate-500 text-sm mb-8 font-medium">Are you sure you want to delete this company? This action will remove all associations but won't delete contacts or deals.</p>
                        
                        <div class="flex gap-4">
                            <button wire:click="$set('isDeleteModalOpen', false)" class="flex-1 py-4 bg-slate-100 text-slate-600 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-200 transition-all">
                                Cancel
                            </button>
                            <button wire:click="delete" class="flex-1 py-4 bg-red-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-red-700 transition-all shadow-xl shadow-red-600/20">
                                Confirm Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
