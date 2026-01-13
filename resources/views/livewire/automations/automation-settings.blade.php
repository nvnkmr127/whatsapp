<div class="max-w-4xl mx-auto py-10 sm:px-6 lg:px-8">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Automation Settings') }}
        </h2>
    </x-slot>

    <div class="bg-white shadow overflow-hidden sm:rounded-lg p-6">
        <div class="space-y-6">
            <!-- Away Message Section -->
            <div>
                <h3 class="text-lg font-medium text-gray-900">Away Message</h3>
                <p class="text-sm text-gray-500">Automatically reply when your business is closed.</p>

                <div class="mt-4 flex items-center">
                    <input type="checkbox" wire:model="away_message_enabled" id="away_message_enabled"
                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <label for="away_message_enabled" class="ml-2 text-sm text-gray-700">Enable Away Message</label>
                </div>

                <div class="mt-4">
                    <label class="block font-medium text-sm text-gray-700">Message Content</label>
                    <textarea wire:model="away_message" class="w-full mt-1 border-gray-300 rounded-md shadow-sm"
                        rows="3" placeholder="We are currently closed. We will get back to you..."></textarea>
                </div>
            </div>

            <hr>

            <!-- Timezone -->
            <div>
                <label class="block font-medium text-sm text-gray-700">Timezone</label>
                <input type="text" wire:model="timezone" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                    placeholder="e.g. UTC, Asia/Kolkata">
            </div>

            <!-- Advanced Config -->
            <div>
                <label class="block font-medium text-sm text-gray-700">Business Hours (JSON)</label>
                <p class="text-xs text-gray-500 mb-1">Format: {"mon": ["09:00", "17:00"], "tue": [...]}</p>
                <textarea wire:model="business_hours"
                    class="w-full mt-1 border-gray-300 rounded-md shadow-sm font-mono text-xs" rows="5"></textarea>
            </div>

            <!-- Save -->
            <div class="flex items-center justify-end">
                <button wire:click="save" class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700">
                    Save Changes
                </button>
            </div>

            @if(session()->has('message'))
                <div class="text-green-600 text-sm text-right mt-2">{{ session('message') }}</div>
            @endif
        </div>
    </div>
</div>