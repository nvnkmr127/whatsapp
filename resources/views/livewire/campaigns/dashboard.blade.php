<div class="p-6 space-y-8 bg-slate-900 min-h-screen text-white">
    <!-- Header Section -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-blue-400 to-emerald-400">
                {{ $campaign->name }}
            </h1>
            <p class="text-slate-400 mt-1">Campaign Analytics & Live Progress</p>
        </div>

        <div
            class="px-4 py-2 rounded-full border border-slate-700 bg-slate-800/50 backdrop-blur-md flex items-center space-x-2">
            <div @class([
                'w-3 h-3 rounded-full',
                'bg-blue-500 animate-pulse' => $campaign->status === 'processing',
                'bg-emerald-500' => $campaign->status === 'completed',
                'bg-red-500' => $campaign->status === 'failed',
                'bg-yellow-500' => $campaign->status === 'paused',
                'bg-slate-500' => $campaign->status === 'scheduled',
            ])></div>
            <span class="text-sm font-medium uppercase tracking-wider">{{ $campaign->status }}</span>
        </div>
    </div>

    <!-- Metrics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        @php
            $cards = [
                ['label' => 'Total Audience', 'value' => $metrics['total'], 'icon' => 'users', 'color' => 'blue'],
                ['label' => 'Messages Sent', 'value' => $metrics['sent'], 'icon' => 'paper-plane', 'color' => 'indigo'],
                ['label' => 'Delivered', 'value' => $metrics['delivered'], 'icon' => 'check-double', 'color' => 'emerald'],
                ['label' => 'Failed', 'value' => $metrics['failed'], 'icon' => 'exclamation-circle', 'color' => 'red'],
            ];
        @endphp

        @foreach($cards as $card)
            <div
                class="relative group overflow-hidden rounded-2xl border border-slate-800 bg-slate-800/30 p-6 backdrop-blur-xl transition-all hover:border-slate-700 hover:bg-slate-800/50">
                <div
                    class="absolute -right-4 -top-4 w-24 h-24 bg-{{ $card['color'] }}-500/10 rounded-full blur-2xl group-hover:bg-{{ $card['color'] }}-500/20 transition-all">
                </div>
                <div class="flex flex-col">
                    <span class="text-slate-400 text-sm font-medium">{{ $card['label'] }}</span>
                    <span class="text-3xl font-bold mt-2 tabular-nums">{{ number_format($card['value']) }}</span>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Progress Visualization -->
    <div class="rounded-3xl border border-slate-800 bg-slate-800/20 p-8 backdrop-blur-2xl">
        <div class="flex justify-between items-end mb-6">
            <div>
                <h2 class="text-xl font-semibold">Delivery Funnel</h2>
                <p class="text-slate-500 text-sm">Real-time breakdown of message states</p>
            </div>
            <div class="text-right">
                @php
                    $percent = $metrics['total'] > 0 ? round(($metrics['processed'] ?? ($metrics['sent'] + $metrics['failed']) / $metrics['total']) * 100) : 0;
                @endphp
                <span class="text-4xl font-black text-blue-400">{{ $percent }}%</span>
            </div>
        </div>

        <!-- Funnel Bar -->
        <div class="h-6 w-full bg-slate-700/30 rounded-full overflow-hidden flex">
            @if($metrics['total'] > 0)
                <div class="h-full bg-blue-500 transition-all duration-1000 ease-out"
                    style="width: {{ ($metrics['sent'] / $metrics['total']) * 100 }}%"></div>
                <div class="h-full bg-emerald-500 transition-all duration-1000 ease-out"
                    style="width: {{ ($metrics['delivered'] / $metrics['total']) * 100 }}%"></div>
                <div class="h-full bg-red-500 transition-all duration-1000 ease-out"
                    style="width: {{ ($metrics['failed'] / $metrics['total']) * 100 }}%"></div>
            @endif
        </div>

        <div class="mt-4 flex space-x-6 text-xs font-medium uppercase tracking-widest text-slate-500">
            <div class="flex items-center space-x-2">
                <div class="w-3 h-3 rounded-sm bg-blue-500"></div>
                <span>Sent</span>
            </div>
            <div class="flex items-center space-x-2">
                <div class="w-3 h-3 rounded-sm bg-emerald-500"></div>
                <span>Delivered</span>
            </div>
            <div class="flex items-center space-x-2">
                <div class="w-3 h-3 rounded-sm bg-red-500"></div>
                <span>Failed</span>
            </div>
        </div>
    </div>

    <!-- Recent Failures (Auto-updating log placeholder) -->
    @if($metrics['failed'] > 0)
        <div class="rounded-2xl border border-red-900/30 bg-red-950/10 p-6">
            <h3 class="text-lg font-semibold text-red-400 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                    </path>
                </svg>
                Recent Failures
            </h3>
            <div class="space-y-3">
                @foreach($campaign->messages()->where('status', 'failed')->latest()->take(3)->get() as $msg)
                    <div
                        class="flex justify-between items-center text-sm p-3 rounded-lg bg-red-900/10 border border-red-900/20">
                        <span class="font-mono text-slate-300">{{ $msg->contact->phone_number }}</span>
                        <span class="text-red-300 italic">{{ $msg->error_message ?? 'Unknown API Error' }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>