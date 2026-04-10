<div class="max-w-4xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Mobile App Configuration</h1>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            Connect your mobile device to your Watxio account to manage chats on the go.
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- QR Code Section -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="p-6 text-center">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Quick Sync</h3>
                <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-xl inline-block mb-4 border border-gray-100 dark:border-gray-700">
                    {!! $this->qrCode !!}
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 px-4">
                    Scan this QR code within the Watxio Mobile App to automatically configure your server and account details.
                </p>
            </div>
        </div>

        <!-- Manual Details Section -->
        <div class="space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Manual Configuration</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Server URL</label>
                        <div class="flex items-center">
                            <input type="text" readonly value="{{ config('app.url') }}" class="block w-full bg-gray-50 dark:bg-gray-900 border-gray-200 dark:border-gray-700 rounded-lg text-sm dark:text-gray-300">
                            <button x-on:click="navigator.clipboard.writeText('{{ config('app.url') }}')" class="ml-2 p-2 text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Team ID</label>
                        <div class="flex items-center">
                            <input type="text" readonly value="{{ auth()->user()->current_team_id }}" class="block w-full bg-gray-50 dark:bg-gray-900 border-gray-200 dark:border-gray-700 rounded-lg text-sm dark:text-gray-300">
                            <button x-on:click="navigator.clipboard.writeText('{{ auth()->user()->current_team_id }}')" class="ml-2 p-2 text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                            </button>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
                        @if(!$showToken)
                            <button wire:click="generateSetupToken" class="w-full flex justify-center items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all">
                                Generate Setup Token
                            </button>
                            <p class="mt-2 text-xs text-gray-500 text-center">
                                Use a token if you want to bypass password login on the app.
                            </p>
                        @else
                            <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1 text-red-500">Security: Setup Token (Copy now)</label>
                                <div class="flex items-center">
                                    <input type="text" readonly value="{{ $setupToken }}" class="block w-full bg-red-50 dark:bg-red-900/10 border-red-200 dark:border-red-900/30 rounded-lg text-sm font-mono dark:text-red-400">
                                    <button x-on:click="navigator.clipboard.writeText('{{ $setupToken }}')" class="ml-2 p-2 text-red-500 hover:text-red-600 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                    </button>
                                </div>
                                <p class="mt-2 text-xs text-red-500">
                                    This token will only be shown once. Do not share it.
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- App Store Links -->
            <div class="flex gap-4">
                <a href="#" class="flex-1 bg-black text-white px-4 py-2 rounded-xl flex items-center justify-center gap-2 hover:bg-gray-900 transition-colors border border-gray-800">
                   <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.1 2.48-1.34.03-1.77-.79-3.29-.79-1.53 0-1.99.76-3.27.82-1.35.05-2.33-1.32-3.19-2.55-1.74-2.5-3.07-7.04-1.29-10.14 1.1-1.92 3.06-3.14 5.2-3.17 1.63-.03 3.16 1.1 4.14 1.1 1 0 2.87-1.33 4.77-1.13 1.8.07 3.25.72 4.08 1.95a6.47 6.47 0 00-3.04 5.4c.04 4.3 3.48 5.76 3.53 5.79-.03.08-.55 1.88-1.84 3.73zM15.95 2.5a5.9 5.9 0 00-1.34 4.3c1.54.12 3.06-.7 3.8-2.1.84-1.5-.74-3.56-2.46-3.64z"/></svg>
                   <span>iOS</span>
                </a>
                <a href="#" class="flex-1 bg-black text-white px-4 py-2 rounded-xl flex items-center justify-center gap-2 hover:bg-gray-900 transition-colors border border-gray-800">
                   <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M3.609 1.814L13.792 12 3.61 22.186c-.18.18-.31.253-.474.156-.2-.119-.246-.431-.246-.665V2.327c0-.234.045-.546.246-.665.164-.097.294-.024.473.152zm11.238 9.141l3.056-1.737c.884-.502.884-1.323 0-1.825l-3.056-1.737-2.023 2.023 2.023 3.276zM13.06 13.06l-2.023-2.023-9.141 9.141c.159.049.332.033.504-.064l10.66-6.054zM2.396 3.887l9.141 9.141L3.901 2.368c-.172-.097-.345-.113-.504-.064l10.66 6.054-1.601-.91z"/></svg>
                   <span>Android</span>
                </a>
            </div>
        </div>
    </div>
</div>
