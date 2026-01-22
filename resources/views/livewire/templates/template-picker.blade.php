<div class="space-y-4">
    <!-- Filters -->
    <div
        class="flex gap-4 items-center bg-slate-50 dark:bg-slate-900 p-4 rounded-xl border border-slate-100 dark:border-slate-800">
        <select wire:model.live="categoryFilter"
            class="bg-white dark:bg-slate-950 border-none rounded-lg text-xs font-bold shadow-sm focus:ring-2 focus:ring-wa-teal">
            <option value="">All Categories</option>
            <option value="MARKETING">Marketing</option>
            <option value="UTILITY">Utility</option>
            <option value="AUTHENTICATION">Authentication</option>
        </select>

        <select wire:model.live="languageFilter"
            class="bg-white dark:bg-slate-950 border-none rounded-lg text-xs font-bold shadow-sm focus:ring-2 focus:ring-wa-teal">
            <option value="">All Languages</option>
            <option value="en_US">English (US)</option>
            <!-- Add more dynamically if needed -->
        </select>

        <div class="ml-auto flex items-center gap-2">
            <label class="text-[10px] font-black uppercase text-slate-400">Show Inactive</label>
            <input type="checkbox" wire:model.live="showInactive"
                class="rounded border-slate-300 text-wa-teal focus:ring-wa-teal">
        </div>
    </div>

    <!-- Warnings -->
    @if($selectionWarning)
        <div
            class="p-4 rounded-xl bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 text-xs font-bold border border-amber-200 dark:border-amber-800 flex items-center gap-3">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            {{ $selectionWarning }}
        </div>
    @endif

    <!-- Template Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 max-h-[500px] overflow-y-auto p-1">
        @forelse($templates as $tpl)
            @php
                $isDisabled = $tpl->readiness_score < $minReadiness && !$showInactive;
                $isSelected = $selectedTemplateId == $tpl->id;

                // Health Color
                $healthColor = 'bg-rose-500';
                if ($tpl->readiness_score >= 90)
                    $healthColor = 'bg-emerald-500';
                elseif ($tpl->readiness_score >= 70)
                    $healthColor = 'bg-amber-500';
            @endphp

            <div @if(!$isDisabled) wire:click="$set('selectedTemplateId', {{ $tpl->id }})" @endif class="relative p-4 rounded-2xl border transition-all cursor-pointer group
                    {{ $isSelected ? 'border-wa-teal ring-2 ring-wa-teal/20 bg-wa-teal/5' : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:border-wa-teal/50' }}
                    {{ $isDisabled ? 'opacity-50 grayscale cursor-not-allowed' : '' }}
                    ">
                @if($isDisabled)
                    <div
                        class="absolute inset-0 z-10 bg-white/10 dark:bg-black/10 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                        <span class="bg-black text-white text-[10px] py-1 px-2 rounded-lg shadow-lg font-bold">Unsafe for
                            Sending</span>
                    </div>
                @endif

                <div class="flex justify-between items-start mb-3">
                    <div>
                        <div class="text-sm font-bold text-slate-900 dark:text-white truncate max-w-[140px]">
                            {{ $tpl->name }}</div>
                        <div class="text-[10px] text-slate-500 uppercase tracking-wider">{{ $tpl->language }}</div>
                    </div>
                    <div class="flex flex-col items-end gap-1">
                        <span class="text-[8px] font-black px-1.5 py-0.5 rounded uppercase
                                {{ $tpl->category === 'MARKETING' ? 'bg-purple-100 text-purple-600' : '' }}
                                {{ $tpl->category === 'UTILITY' ? 'bg-blue-100 text-blue-600' : '' }}
                                {{ $tpl->category === 'AUTHENTICATION' ? 'bg-rose-100 text-rose-600' : '' }}
                            ">
                            {{ $tpl->category }}
                        </span>
                    </div>
                </div>

                <!-- Health Bar -->
                <div class="w-full h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden flex items-center">
                    <div class="h-full {{ $healthColor }} transition-all duration-500"
                        style="width: {{ $tpl->readiness_score }}%"></div>
                </div>
                <div class="flex justify-between mt-1">
                    <span class="text-[9px] font-bold text-slate-400">Health</span>
                    <span
                        class="text-[9px] font-bold {{ str_replace('bg-', 'text-', $healthColor) }}">{{ $tpl->readiness_score }}%</span>
                </div>

                <!-- Preview Snippet -->
                <div
                    class="mt-3 text-[10px] text-slate-500 line-clamp-2 leading-relaxed bg-slate-50 dark:bg-slate-950 p-2 rounded-lg border border-slate-100 dark:border-slate-800">
                    {{ Str::limit($tpl->content, 80) }}
                </div>
            </div>
        @empty
            <div class="col-span-full py-12 text-center">
                <div
                    class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-400 mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
                <p class="text-xs font-bold text-slate-500">No safe templates found</p>
                <p class="text-[10px] text-slate-400 mt-1">Try changing filters or show inactive</p>
            </div>
        @endforelse
    </div>
</div>