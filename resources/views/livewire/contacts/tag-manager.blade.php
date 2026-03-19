<div x-data="{ open: @entangle('isOpen') }">
    <x-app-modal wire:model="isOpen" maxWidth="lg">
        <div class="p-8 pb-0">
            <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight">
                Manage <span class="text-wa-teal">Tags</span>
            </h2>
        </div>

        <div class="p-8 space-y-6">
            <!-- Create New Tag -->
            <div class="bg-slate-50 dark:bg-slate-800 rounded-2xl p-4 space-y-4">
                <h3 class="text-xs font-black uppercase tracking-widest text-slate-400">Create New Tag</h3>
                <div class="flex gap-4">
                    <div class="flex-1 space-y-1">
                        <input type="text" wire:model="newTagName" placeholder="Tag Name (e.g. VIP)"
                            class="w-full px-4 py-2 bg-white dark:bg-slate-700 border-none rounded-xl text-sm font-bold focus:ring-2 focus:ring-wa-teal/20 text-slate-900 dark:text-white">
                        @error('newTagName') <span class="text-rose-500 text-[10px] uppercase font-bold">{{ $message }}</span> @enderror
                    </div>
                    <div class="w-16">
                        <input type="color" wire:model="newTagColor"
                            class="w-full h-10 px-1 py-1 bg-white dark:bg-slate-700 border-none rounded-xl cursor-pointer">
                    </div>
                    <x-app-button variant="primary" wire:click="createTag">Add</x-app-button>
                </div>
                @if (session()->has('message'))
                    <div class="text-xs font-bold text-wa-teal">{{ session('message') }}</div>
                @endif
            </div>

            <!-- Existing Tags List -->
            <div class="space-y-4">
                <h3 class="text-xs font-black uppercase tracking-widest text-slate-400">Existing Tags</h3>
                <div class="space-y-2 max-h-60 overflow-y-auto pr-2">
                    @forelse($tags as $tag)
                        <div class="flex items-center justify-between p-3 bg-white dark:bg-slate-800 rounded-xl border border-slate-100 dark:border-slate-700">
                            <div class="flex items-center gap-3">
                                <div class="w-4 h-4 rounded-full" style="background-color: {{ $tag->color }}"></div>
                                <span class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ $tag->name }}</span>
                            </div>
                            <button wire:click="deleteTag({{ $tag->id }})" class="text-slate-400 hover:text-rose-500 transition-colors">
                                <x-icon name="trash" class="w-4 h-4" />
                            </button>
                        </div>
                    @empty
                        <div class="text-center py-6 text-slate-400 text-xs italic">No tags yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </x-app-modal>
</div>
