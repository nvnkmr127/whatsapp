<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <x-validation-errors class="mb-4" />

        @session('status')
            <div class="mb-4 font-medium text-sm text-green-600 dark:text-green-400">
                {{ $value }}
            </div>
        @endsession

        <livewire:auth.passwordless-login />

        @if(app()->environment('local', 'testing'))
            <div class="mt-8 border-t pt-6">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-4">Dev Auto-Login (Full Permissions)
                </p>
                <div class="grid grid-cols-1 gap-3">
                    <a href="{{ route('dev.login', 'admin@example.com') }}"
                        class="flex items-center justify-between px-4 py-2 bg-red-50 text-red-700 border border-red-200 rounded-lg hover:bg-red-100 transition group">
                        <span class="font-bold">Super Admin</span>
                        <span
                            class="text-[10px] bg-red-200 px-2 py-0.5 rounded uppercase tracking-tighter group-hover:bg-red-300">All
                            Modules</span>
                    </a>

                    <a href="{{ route('dev.login', 'manager@example.com') }}"
                        class="flex items-center justify-between px-4 py-2 bg-blue-50 text-blue-700 border border-blue-200 rounded-lg hover:bg-blue-100 transition group">
                        <span class="font-bold">Team Manager</span>
                        <span
                            class="text-[10px] bg-blue-200 px-2 py-0.5 rounded uppercase tracking-tighter group-hover:bg-blue-300">CRM
                            + Campaigns</span>
                    </a>

                    <a href="{{ route('dev.login', 'agent@example.com') }}"
                        class="flex items-center justify-between px-4 py-2 bg-green-50 text-green-700 border border-green-200 rounded-lg hover:bg-green-100 transition group">
                        <span class="font-bold">Support Agent</span>
                        <span
                            class="text-[10px] bg-green-200 px-2 py-0.5 rounded uppercase tracking-tighter group-hover:bg-green-300">Chat
                            Only</span>
                    </a>
                </div>
            </div>
        @endif
    </x-authentication-card>
</x-guest-layout>