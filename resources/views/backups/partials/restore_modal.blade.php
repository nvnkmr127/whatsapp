<div x-show="showRestoreModal" class="fixed z-[60] inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog"
    aria-modal="true" x-cloak>
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:p-0">
        <!-- Background overlay -->
        <div x-show="showRestoreModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showRestoreModal = false">
        </div>

        <!-- Modal panel -->
        <div x-show="showRestoreModal" x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="inline-block align-middle bg-white dark:bg-slate-900 rounded-[2.5rem] text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full border border-slate-100 dark:border-slate-800">

            <div class="p-8 sm:p-10" x-data="{ confirming: false, restoreKey: '' }">
                <div class="flex flex-col items-center text-center mb-8">
                    <div
                        class="w-20 h-20 bg-rose-50 dark:bg-rose-950/30 rounded-3xl flex items-center justify-center text-rose-600 mb-6">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tight"
                        id="modal-title">
                        Critical Restoration
                    </h3>
                    <p class="mt-2 text-slate-500 dark:text-slate-400 font-medium leading-relaxed">
                        This action is irreversible. All current data for your workspace will be replaced by the
                        snapshot from <span class="text-slate-900 dark:text-white font-black"
                            x-text="selectedBackupDate || 'this backup'"></span>.
                    </p>
                </div>

                <div class="space-y-6">
                    <div
                        class="p-6 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-800">
                        <label for="restore-confirmation"
                            class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 text-center">
                            Type <span class="text-rose-500 font-black">RESTORE</span> to authorize
                        </label>
                        <input type="text" id="restore-confirmation" x-model="restoreKey"
                            class="block w-full px-4 py-4 text-center text-lg font-black bg-white dark:bg-slate-900 border-2 border-slate-200 dark:border-slate-700 rounded-xl focus:ring-rose-500 focus:border-rose-500 dark:text-white placeholder:text-slate-300 dark:placeholder:text-slate-700 transition-all uppercase"
                            placeholder="RESTORE">
                    </div>

                    <div class="flex flex-col sm:flex-row gap-4">
                        <button @click="showRestoreModal = false" type="button"
                            class="flex-1 px-8 py-4 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-black text-xs uppercase tracking-widest rounded-2xl hover:bg-slate-200 dark:hover:bg-slate-700 transition-all active:scale-95">
                            Cancel
                        </button>

                        <form :action="'{{ url('/backups') }}/' + selectedBackup + '/restore'" method="POST"
                            class="flex-1">
                            @csrf
                            <input type="hidden" name="confirmation" x-model="restoreKey">
                            <button type="submit"
                                class="w-full px-8 py-4 bg-rose-600 text-white font-black text-xs uppercase tracking-widest rounded-2xl shadow-xl shadow-rose-500/20 hover:bg-rose-700 transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed disabled:active:scale-100"
                                :disabled="restoreKey !== 'RESTORE' || confirming" @click="confirming = true">
                                <span x-show="!confirming">Initialize Restore</span>
                                <span x-show="confirming" class="flex items-center justify-center gap-2">
                                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    Processing...
                                </span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>