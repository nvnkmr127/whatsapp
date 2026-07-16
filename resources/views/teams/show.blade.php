<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Team Settings') }}
        </h2>
    </x-slot>

    <div>
        <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
            @livewire('teams.update-team-name-form', ['team' => $team])

            <x-section-border />

            <div class="mt-10 sm:mt-0 flex items-center justify-between gap-4 rounded-xl border border-slate-200 dark:border-slate-700 p-6">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('Team Members') }}</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('Add, remove, and manage the roles of your team members.') }}</p>
                </div>
                <a href="{{ route('teams.members') }}"
                   class="inline-flex items-center px-4 py-2 bg-wa-teal text-white text-xs font-bold uppercase tracking-widest rounded-lg hover:opacity-90">
                    {{ __('Manage Members') }}
                </a>
            </div>

            @if (Gate::check('delete', $team) && ! $team->personal_team)
                <x-section-border />

                <div class="mt-10 sm:mt-0">
                    @livewire('teams.delete-team-form', ['team' => $team])
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
