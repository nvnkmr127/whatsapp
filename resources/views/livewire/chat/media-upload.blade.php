<div x-data="{ uploadError: $wire.entangle('uploadError') }" 
     @open-file-input.window="$refs.fileInput.click()">
    
    <input type="file" wire:model.live="newAttachment" class="hidden" x-ref="fileInput"
           x-on:livewire-upload-error="uploadError = 'File upload failed.'; showUploadErrorModal = true;"
           accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx">

    <!-- Attachment Preview Area (When file is picked) -->
    @if($newAttachment)
        <div class="px-6 py-4 bg-slate-50 dark:bg-slate-950 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between group/preview sticky bottom-0 z-30">
            <div class="flex items-center gap-4">
                <div class="h-14 w-14 rounded-2xl bg-white dark:bg-slate-800 p-1 shadow-lg shadow-wa-teal/5 border border-slate-100 dark:border-slate-800">
                    @if(Str::startsWith($newAttachment->getMimeType(), 'image'))
                        <img src="{{ $newAttachment->temporaryUrl() }}" class="h-full w-full rounded-xl object-cover">
                    @elseif(Str::startsWith($newAttachment->getMimeType(), 'video'))
                         <div class="h-full w-full rounded-xl bg-indigo-500/20 flex items-center justify-center">
                            <svg class="h-6 w-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                        </div>
                    @else
                        <div class="h-full w-full rounded-xl bg-wa-teal/20 flex items-center justify-center">
                            <svg class="h-6 w-6 text-wa-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </div>
                    @endif
                </div>
                <div>
                    <h4 class="text-xs font-black text-slate-800 dark:text-white uppercase tracking-tight truncate max-w-[200px]">
                        {{ $newAttachment->getClientOriginalName() }}
                    </h4>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                        {{ round($newAttachment->getSize() / 1024 / 1024, 2) }} MB • READY TO SEND
                    </span>
                </div>
            </div>
            
            <button wire:click="deleteAttachment"
                class="p-2.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-500 transition-all hover:scale-110 active:scale-95 border border-rose-100/50">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif

    <!-- Error Modal for Uploads -->
    <x-app-modal wire:model="uploadError" maxWidth="sm">
        <div class="p-6 text-center">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-rose-100 mb-6">
                <svg class="h-8 w-8 text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
            </div>
            <h3 class="text-lg font-black text-slate-900 mb-2 uppercase tracking-tight">{{ __('Upload Error') }}</h3>
            <p class="text-sm text-slate-500 mb-6 font-bold">{{ $uploadError }}</p>
            <x-app-button variant="primary" @click="$wire.uploadError = null" class="w-full">
                {{ __('Dismiss') }}
            </x-app-button>
        </div>
    </x-app-modal>
</div>
