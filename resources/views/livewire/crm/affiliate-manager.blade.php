<div class="space-y-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h3 class="text-2xl font-black text-slate-900 tracking-tight">Affiliate Program</h3>
            <p class="text-sm text-slate-500 font-bold uppercase tracking-widest mt-1">Manage partners and commissions</p>
        </div>
        <button wire:click="$set('showCreateModal', true)" class="px-6 py-3 bg-slate-900 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-800 transition-colors">
            Add Affiliate
        </button>
    </div>

    <div class="bg-white border border-slate-200 rounded-[2.5rem] overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-50/50 text-slate-400 border-b border-slate-100">
                        <th class="px-8 py-4 text-[10px] font-black uppercase tracking-[0.2em]">Partner</th>
                        <th class="px-8 py-4 text-[10px] font-black uppercase tracking-[0.2em]">Code</th>
                        <th class="px-8 py-4 text-[10px] font-black uppercase tracking-[0.2em]">Rate</th>
                        <th class="px-8 py-4 text-[10px] font-black uppercase tracking-[0.2em]">Referrals</th>
                        <th class="px-8 py-4 text-[10px] font-black uppercase tracking-[0.2em]">Earnings</th>
                        <th class="px-8 py-4 text-right text-[10px] font-black uppercase tracking-[0.2em]">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($affiliates as $affiliate)
                        <tr class="group hover:bg-slate-50/50 transition-colors">
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-4">
                                    <div class="h-10 w-10 rounded-xl bg-slate-100 border border-slate-200 overflow-hidden">
                                        <img src="{{ $affiliate->user->profile_photo_url }}" class="h-full w-full object-cover grayscale group-hover:grayscale-0 transition-all">
                                    </div>
                                    <div>
                                        <div class="text-sm font-black text-slate-900">{{ $affiliate->user->name }}</div>
                                        <div class="text-xs text-slate-500 font-medium">{{ $affiliate->user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <span class="px-3 py-1 bg-indigo-50 text-indigo-600 rounded-lg text-[10px] font-black uppercase tracking-widest border border-indigo-200 font-mono">
                                    {{ $affiliate->code }}
                                </span>
                            </td>
                            <td class="px-8 py-6">
                                <span class="text-sm font-bold text-slate-700">{{ $affiliate->commission_rate }}%</span>
                            </td>
                            <td class="px-8 py-6">
                                <span class="text-sm font-bold text-slate-900">{{ $affiliate->referrals_count }}</span>
                            </td>
                            <td class="px-8 py-6">
                                <span class="text-sm font-bold text-emerald-600">{{ money($affiliate->referrals()->sum('earnings')) }}</span>
                            </td>
                            <td class="px-8 py-6 text-right">
                                <button class="text-slate-400 hover:text-indigo-600 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-8 py-12 text-center text-slate-400 font-medium">No affiliates found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($affiliates->hasPages())
            <div class="px-8 py-6 border-t border-slate-100 bg-slate-50">
                {{ $affiliates->links() }}
            </div>
        @endif
    </div>

    <!-- Create Modal -->
    <x-dialog-modal wire:model="showCreateModal">
        <x-slot name="title">Add New Affiliate</x-slot>
        <x-slot name="content">
            <div class="space-y-4">
                <div>
                    <x-label for="user_id" value="User" />
                    <select id="user_id" wire:model="newAffiliate.user_id" class="block w-full mt-1 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                        <option value="">Select User</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                        @endforeach
                    </select>
                    <x-input-error for="newAffiliate.user_id" class="mt-2" />
                </div>
                <div>
                    <x-label for="code" value="Affiliate Code" />
                    <x-input id="code" type="text" class="block w-full mt-1" wire:model="newAffiliate.code" placeholder="e.g. SUMMER2024" />
                    <x-input-error for="newAffiliate.code" class="mt-2" />
                </div>
                <div>
                    <x-label for="commission_rate" value="Commission Rate (%)" />
                    <x-input id="commission_rate" type="number" class="block w-full mt-1" wire:model="newAffiliate.commission_rate" />
                    <x-input-error for="newAffiliate.commission_rate" class="mt-2" />
                </div>
            </div>
        </x-slot>
        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showCreateModal', false)" wire:loading.attr="disabled">Cancel</x-secondary-button>
            <x-button class="ml-2" wire:click="save" wire:loading.attr="disabled">Create</x-button>
        </x-slot>
    </x-dialog-modal>
</div>
