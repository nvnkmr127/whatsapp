<div>
    @if($activeCall)
        {{-- Active Call UI --}}
        <div class="bg-gradient-to-br from-wa-teal to-emerald-600 text-white rounded-2xl p-4 shadow-lg animate-pulse-slow">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="bg-white/20 rounded-full p-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                        </svg>
                    </div>
                    <div>
                        <div class="font-bold text-sm">
                            @if($activeCall->status === 'ringing')
                                {{ $activeCall->direction === 'inbound' ? 'Incoming Call' : 'Calling...' }}
                            @elseif($activeCall->status === 'in_progress')
                                Call in Progress
                            @else
                                {{ ucfirst($activeCall->status) }}
                            @endif
                        </div>
                        <div class="text-xs opacity-90">{{ $contact->name ?? $contact->phone_number }}</div>
                    </div>
                </div>

                <div class="flex items-center space-x-2">
                    @if($activeCall->status === 'ringing' && $activeCall->direction === 'inbound')
                        {{-- Answer button --}}
                        <button wire:click="answerCall"
                            class="bg-green-500 hover:bg-green-600 text-white rounded-full p-3 transition-all transform hover:scale-110">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                            </svg>
                        </button>

                        {{-- Reject button --}}
                        <button wire:click="rejectCall"
                            class="bg-red-500 hover:bg-red-600 text-white rounded-full p-3 transition-all transform hover:scale-110">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                            </svg>
                        </button>
                    @else
                        {{-- End call button --}}
                        <button wire:click="endCall"
                            class="bg-red-500 hover:bg-red-600 text-white rounded-full p-3 transition-all transform hover:scale-110">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                            </svg>
                        </button>
                    @endif
                </div>
            </div>

            @if($activeCall->status === 'in_progress' && $activeCall->answered_at)
                <div class="mt-3 text-center text-sm font-semibold">
                    {{ $activeCall->answered_at->diffForHumans(null, true) }}
                </div>
            @endif
        </div>
    @else
        {{-- Call Button --}}
        <button wire:click="initiateCall" wire:loading.attr="disabled" wire:target="initiateCall"
            class="w-full bg-gradient-to-r from-wa-teal to-emerald-600 hover:from-wa-teal-dark hover:to-emerald-700 text-white font-bold py-3 px-6 rounded-2xl shadow-lg hover:shadow-xl transition-all transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center space-x-2">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path
                    d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
            </svg>
            <span wire:loading.remove wire:target="initiateCall">Call Contact</span>
            <span wire:loading wire:target="initiateCall">Initiating...</span>
        </button>
    @endif
</div>

@push('scripts')
    <script>
        // Listen for call events
        window.addEventListener('call-initiated', event => {
            // Show success notification
            console.log('Call initiated:', event.detail.message);
        });

        window.addEventListener('call-answered', event => {
            console.log('Call answered:', event.detail.message);
        });

        window.addEventListener('call-ended', event => {
            console.log('Call ended:', event.detail.message);
        });

        window.addEventListener('call-rejected', event => {
            console.log('Call rejected:', event.detail.message);
        });

        window.addEventListener('call-error', event => {
            // Show error notification
            alert('Call Error: ' + event.detail.message);
        });
    </script>
@endpush