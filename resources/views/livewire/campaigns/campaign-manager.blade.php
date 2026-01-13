<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold bg-white p-4 rounded shadow w-full">Campaigns & Broadcasts</h2>
        <button wire:click="$set('showCreateModal', true)"
            class="bg-blue-600 text-white px-4 py-2 rounded shadow ml-4 whitespace-nowrap">
            New Campaign
        </button>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
            {{ session('message') }}
        </div>
    @endif

    <!-- Campaign List -->
    <div class="bg-white rounded shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sent</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rate</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($campaigns as $campaign)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $campaign->name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    {{ $campaign->status === 'completed' ? 'bg-green-100 text-green-800' :
                    ($campaign->status === 'processing' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                                        {{ ucfirst($campaign->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    {{ $campaign->sent_count }} / {{ $campaign->total_contacts }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($campaign->sent_count > 0)
                                        {{ number_format(($campaign->read_count / $campaign->sent_count) * 100, 1) }}% Read
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                @endforeach
            </tbody>
        </table>
        {{ $campaigns->links() }}
    </div>

    <!-- Create Modal Overlap (Simple for MVP) -->
    @if($showCreateModal)
        <div
            class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center z-50">
            <div class="relative p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
                <h3 class="text-lg font-bold mb-4">Create Campaign</h3>

                <div class="mb-4">
                    <label class="block text-sm font-bold mb-2">Campaign Name</label>
                    <input type="text" wire:model="name" class="w-full border rounded p-2">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-bold mb-2">Select Template</label>
                    <select wire:model="templateName" class="w-full border rounded p-2">
                        <option value="">-- Choose Template --</option>
                        @foreach($availableTemplates as $tpl)
                            <option value="{{ $tpl->name }}">{{ $tpl->name }} ({{ $tpl->language }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-bold mb-2">Audience (Tags)</label>
                    <div class="border rounded p-2 max-h-32 overflow-y-auto">
                        @foreach($availableTags as $tag)
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" wire:model="selectedTags" value="{{ $tag->id }}">
                                <span style="color: {{ $tag->color }}">{{ $tag->name }}</span>
                            </label>
                        @endforeach
                        @if(count($availableTags) === 0)
                            <p class="text-gray-500 text-sm">No tags found. Will send to ALL.</p>
                        @endif
                    </div>
                </div>

                <div class="mb-4">
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" wire:model.live="sendNow"
                            class="rounded border-gray-300 text-blue-600 shadow-sm">
                        <span class="text-sm font-bold">Send Immediately</span>
                    </label>
                </div>

                @if(!$sendNow)
                    <div class="mb-4">
                        <label class="block text-sm font-bold mb-2">Schedule For</label>
                        <input type="datetime-local" wire:model="scheduledAt" class="w-full border rounded p-2">
                    </div>
                @endif

                <div class="flex justify-end space-x-2 mt-4">
                    <button wire:click="$set('showCreateModal', false)"
                        class="px-4 py-2 bg-gray-300 rounded">Cancel</button>
                    <button wire:click="create" class="px-4 py-2 bg-blue-600 text-white rounded">Launch</button>
                </div>
            </div>
        </div>
    @endif
</div>