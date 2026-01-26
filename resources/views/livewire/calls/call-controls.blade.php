<div>
    @if($activeCall)
        {{-- Active Call UI --}}
        <div class="bg-gradient-to-br from-wa-teal to-emerald-600 text-white rounded-2xl p-4 shadow-lg animate-pulse-slow">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="bg-white/20 rounded-full p-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
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
                                <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                            </svg>
                        </button>
                        
                        {{-- Reject button --}}
                        <button wire:click="rejectCall" 
                                class="bg-red-500 hover:bg-red-600 text-white rounded-full p-3 transition-all transform hover:scale-110">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                            </svg>
                        </button>
                    @else
                        {{-- End call button --}}
                        <button wire:click="endCall" 
                                class="bg-red-500 hover:bg-red-600 text-white rounded-full p-3 transition-all transform hover:scale-110">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
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
        {{-- Eligibility Status & Call Button --}}
        @if($eligibility && !$eligibility['eligible'])
            {{-- Blocked State --}}
            <div class="bg-red-50 dark:bg-red-900/20 border-2 border-red-200 dark:border-red-800 rounded-2xl p-4">
                <div class="flex items-start space-x-3">
                    <div class="bg-red-500 text-white rounded-full p-2 flex-shrink-0">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <div class="font-bold text-sm text-red-900 dark:text-red-100">Calling Unavailable</div>
                        <div class="text-xs text-red-700 dark:text-red-300 mt-1">{{ $eligibility['user_message'] }}</div>
                        
                        @if($eligibility['can_retry_at'])
                            <div class="text-xs text-red-600 dark:text-red-400 mt-2">
                                Retry after: {{ \Carbon\Carbon::parse($eligibility['can_retry_at'])->format('M d, h:i A') }}
                            </div>
                        @endif

                        <button wire:click="$toggle('showEligibilityDetails')" 
                                class="text-xs text-red-600 dark:text-red-400 hover:underline mt-2">
                            {{ $showEligibilityDetails ? 'Hide' : 'Show' }} Details
                        </button>

                        @if($showEligibilityDetails && $eligibility['checks'])
                            <div class="mt-3 space-y-2 text-xs">
                                @foreach($eligibility['checks'] as $checkName => $check)
                                    <div class="flex items-center space-x-2">
                                        @if($check['passed'])
                                            <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                        @else
                                            <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                            </svg>
                                        @endif
                                        <span class="font-semibold text-red-800 dark:text-red-200">{{ ucwords(str_replace('_', ' ', $checkName)) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @elseif($eligibility && $eligibility['eligible'])
            {{-- Ready to Call --}}
            <button wire:click="initiateCall" 
                    wire:loading.attr="disabled"
                    wire:target="initiateCall"
                    class="w-full bg-gradient-to-r from-wa-teal to-emerald-600 hover:from-wa-teal-dark hover:to-emerald-700 text-white font-bold py-3 px-6 rounded-2xl shadow-lg hover:shadow-xl transition-all transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center space-x-2">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                </svg>
                <span wire:loading.remove wire:target="initiateCall">Call Contact</span>
                <span wire:loading wire:target="initiateCall">Initiating...</span>
            </button>

            {{-- Eligibility Status Indicators --}}
            @if($eligibility['checks'])
                <div class="mt-3 flex items-center justify-center space-x-4 text-xs text-slate-500 dark:text-slate-400">
                    @if($eligibility['checks']['quality_rating']['warning'] ?? false)
                        <div class="flex items-center space-x-1">
                            <svg class="w-4 h-4 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <span>Quality: {{ $eligibility['checks']['quality_rating']['details']['rating'] }}</span>
                        </div>
                    @endif
                    @if(isset($eligibility['checks']['agent_availability']['details']['available_agents']))
                        <div class="flex items-center space-x-1">
                            <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                            </svg>
                            <span>{{ $eligibility['checks']['agent_availability']['details']['available_agents'] }} agents available</span>
                        </div>
                    @endif
                </div>
            @endif
        @else
            {{-- Loading State --}}
            <div class="w-full bg-slate-100 dark:bg-slate-800 text-slate-400 font-bold py-3 px-6 rounded-2xl flex items-center justify-center">
                <svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Checking eligibility...
            </div>
        @endif
    @endif
</div>

@push('scripts')
<script>
    // Listen for call events
    window.addEventListener('call-initiated', event => {
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