<div class="p-6" wire:poll.30s>
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">SLA Management</h1>
            <p class="text-sm text-gray-500">Monitor and enforce response time targets across your team.</p>
        </div>
        <div class="flex items-center space-x-3">
            <button wire:click="runCheck" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                Sync SLA Status
            </button>
            <button wire:click="$set('showPolicyForm', true)" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 shadow-sm transition-colors">
                + New Policy
            </button>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <p class="text-sm font-medium text-gray-500 mb-1">Breach Rate</p>
            <div class="flex items-end space-x-2">
                <span class="text-3xl font-bold {{ $this->stats['breach_rate'] > 10 ? 'text-red-600' : 'text-green-600' }}">{{ $this->stats['breach_rate'] }}%</span>
            </div>
            <p class="text-xs text-gray-400 mt-2">Target: < 5%</p>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <p class="text-sm font-medium text-gray-500 mb-1">On Track</p>
            <span class="text-3xl font-bold text-gray-900">{{ $this->stats['on_track'] }}</span>
            <p class="text-xs text-green-500 mt-2">Satisfied customers</p>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <p class="text-sm font-medium text-gray-500 mb-1">Breached</p>
            <span class="text-3xl font-bold text-red-600">{{ $this->stats['breached'] }}</span>
            <p class="text-xs text-gray-400 mt-2">Requires attention</p>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <p class="text-sm font-medium text-gray-500 mb-1">Avg 1st Response</p>
            <span class="text-3xl font-bold text-gray-900">{{ $this->stats['avg_first_response'] ?: '--' }}m</span>
            <p class="text-xs text-gray-400 mt-2">Last {{ $days }} days</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Policies List -->
        <div class="lg:col-span-1">
            <h3 class="text-lg font-bold text-gray-800 mb-4">SLA Policies</h3>
            <div class="space-y-4">
                @foreach($this->policies as $policy)
                    <div class="bg-white p-4 rounded-xl shadow-sm border {{ $policy->is_default ? 'border-indigo-200 bg-indigo-50/30' : 'border-gray-100' }} group">
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="font-bold text-gray-900 flex items-center">
                                    {{ $policy->name }}
                                    @if($policy->is_default)
                                        <span class="ml-2 px-1.5 py-0.5 bg-indigo-100 text-indigo-700 text-tiny uppercase font-black rounded">Default</span>
                                    @endif
                                </h4>
                                <div class="mt-2 space-y-1">
                                    <div class="flex items-center text-xs text-gray-500">
                                        <span class="w-24">1st Response:</span>
                                        <span class="font-semibold text-gray-700">{{ $policy->first_response_label }}</span>
                                    </div>
                                    <div class="flex items-center text-xs text-gray-500">
                                        <span class="w-24">Resolution:</span>
                                        <span class="font-semibold text-gray-700">{{ $policy->resolution_label }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex space-x-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button wire:click="editPolicy({{ $policy->id }})" class="p-1 text-gray-400 hover:text-indigo-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg></button>
                                <button wire:confirm="Are you sure you want to delete this policy?" wire:click="deletePolicy({{ $policy->id }})" class="p-1 text-gray-400 hover:text-red-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Active Breaches & Warnings -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Breached Conversations -->
            <div>
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-gray-800 flex items-center">
                        <span class="w-2 h-2 bg-red-600 rounded-full mr-2"></span>
                        Active Breaches
                    </h3>
                    <span class="text-xs font-bold text-red-600 bg-red-50 px-2 py-0.5 rounded-full">{{ count($this->breachedConversations) }} critical</span>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned To</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Policy</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($this->breachedConversations as $conv)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $conv->contact?->name ?? 'Unknown' }}</div>
                                        <div class="text-xs text-gray-500">{{ $conv->contact?->phone_number }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $conv->assignee?->name ?? 'Unassigned' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500 font-medium">
                                        {{ $conv->slaPolicy?->name ?? '--' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-tiny font-black uppercase bg-red-100 text-red-700 rounded-full">Breached</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-gray-500 text-sm">No active breaches. Great job! 🚀</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Warning List -->
            @if(count($this->warningConversations) > 0)
                <div>
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                        <span class="w-2 h-2 bg-yellow-400 rounded-full mr-2"></span>
                        Expiring Soon
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($this->warningConversations as $conv)
                            <div class="bg-white p-4 rounded-xl border border-yellow-200 shadow-sm flex justify-between items-center">
                                <div>
                                    <p class="text-sm font-bold text-gray-900">{{ $conv->contact?->name }}</p>
                                    <p class="text-xs text-gray-500">Assigned: {{ $conv->assignee?->name ?? 'Unassigned' }}</p>
                                </div>
                                <span class="text-tiny font-black text-yellow-700 uppercase bg-yellow-100 px-2 py-0.5 rounded-full">Warn</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Policy Form Modal -->
    @if($showPolicyForm)
        <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm flex items-center justify-center z-50">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                    <h2 class="text-xl font-bold text-gray-800">{{ $editingPolicyId ? 'Edit' : 'New' }} SLA Policy</h2>
                    <button wire:click="resetPolicyForm" class="text-gray-400 hover:text-gray-600"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Policy Name</label>
                        <input type="text" wire:model="policyName" class="w-full rounded-lg border-gray-300 focus:ring-indigo-500" placeholder="e.g. VIP Support">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">First Response (min)</label>
                            <input type="number" wire:model="firstResponseMin" class="w-full rounded-lg border-gray-300 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Resolution (min)</label>
                            <input type="number" wire:model="resolutionMin" class="w-full rounded-lg border-gray-300 focus:ring-indigo-500">
                        </div>
                    </div>
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" wire:model="isDefault" class="rounded text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm font-medium text-gray-700">Set as default policy</span>
                    </label>
                </div>
                <div class="p-6 bg-gray-50 flex justify-end space-x-3">
                    <button wire:click="resetPolicyForm" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">Cancel</button>
                    <button wire:click="savePolicy" class="px-6 py-2 bg-indigo-600 text-white rounded-lg text-sm font-bold hover:bg-indigo-700 shadow-md">
                        Save Policy
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
