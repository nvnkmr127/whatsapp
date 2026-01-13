<div class="px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">WhatsApp Templates</h2>
        <div class="space-x-2">
            <button wire:click="syncTemplates"
                class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                Sync Status
            </button>
            <button wire:click="$set('showCreateModal', true)"
                class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition">
                + New Template
            </button>
        </div>
    </div>

    <!-- Feedback -->
    @if(session()->has('success'))
        <div class="mb-4 bg-green-100 text-green-800 p-3 rounded">{{ session('success') }}</div>
    @endif
    @if(session()->has('error'))
        <div class="mb-4 bg-red-100 text-red-800 p-3 rounded">{{ session('error') }}</div>
    @endif

    <!-- Template Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($templates as $tpl)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="font-bold text-lg dark:text-white">{{ $tpl->name }}</h3>
                        <span class="text-xs text-gray-500 uppercase tracking-wide">{{ $tpl->category }} •
                            {{ $tpl->language }}</span>
                    </div>
                    <span
                        class="px-2 py-1 text-xs rounded font-bold
                            {{ $tpl->status === 'APPROVED' ? 'bg-green-100 text-green-800' :
            ($tpl->status === 'REJECTED' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                        {{ $tpl->status }}
                    </span>
                </div>

                <div
                    class="mt-4 p-3 bg-gray-50 dark:bg-gray-900 rounded text-sm text-gray-700 dark:text-gray-300 h-32 overflow-y-auto">
                    <!-- Preview logic simplified for brevity -->
                    @foreach($tpl->components as $comp)
                        @if($comp['type'] === 'HEADER' && isset($comp['text']))
                            <div class="font-bold mb-1">{{ $comp['text'] }}</div>
                        @elseif($comp['type'] === 'BODY')
                            <p>{{ $comp['text'] }}</p>
                        @elseif($comp['type'] === 'FOOTER')
                            <div class="text-xs text-gray-400 mt-2">{{ $comp['text'] }}</div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <!-- Create Modal -->
    <x-dialog-modal wire:model="showCreateModal">
        <x-slot name="title">Create WhatsApp Template</x-slot>

        <x-slot name="content">
            <div class="space-y-4">
                <!-- Name & Category -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-label value="Template Name (lowercase, no spaces)" />
                        <x-input type="text" wire:model="name" class="w-full" placeholder="e.g. welcome_offer" />
                        @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <x-label value="Category" />
                        <select wire:model="category"
                            class="w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-900 dark:text-white">
                            <option value="UTILITY">Utility</option>
                            <option value="MARKETING">Marketing</option>
                            <option value="AUTHENTICATION">Authentication</option>
                        </select>
                    </div>
                </div>

                <!-- Body -->
                <div>
                    <x-label value="Message Body" />
                    <textarea wire:model="body" rows="4"
                        class="w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-900 dark:text-white"
                        placeholder="Hello {{1}}, thank you for..."></textarea>
                    <p class="text-xs text-gray-500 mt-1">Use {{1}}, {{2}} for variables.</p>
                </div>

                <!-- Footer -->
                <div>
                    <x-label value="Footer (Optional)" />
                    <x-input type="text" wire:model="footer" class="w-full" placeholder="Reply STOP to unsubscribe" />
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showCreateModal', false)">Cancel</x-secondary-button>
            <x-button class="ml-2" wire:click="createTemplate">Submit to Meta</x-button>
        </x-slot>
    </x-dialog-modal>
</div>