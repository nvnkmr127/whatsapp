<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">Analytics & Billing</h2>
        <div class="space-x-2">
            <button wire:click="exportTransactions" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">
                Download CSV
            </button>
            <button wire:click="toggleSchedule"
                class="px-4 py-2 {{ $isScheduled ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' }} rounded">
                {{ $isScheduled ? '✓ Reports Scheduled (Weekly)' : 'Email Weekly Report' }}
            </button>
        </div>
    </div>

    <!-- Top Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <!-- Balance -->
        <div class="bg-white rounded shadow p-5">
            <h3 class="text-gray-500 text-sm">Wallet Balance</h3>
            <p class="text-3xl font-bold text-green-600">${{ number_format($wallet->balance, 2) }}</p>
            <button class="text-sm text-blue-500 mt-2 hover:underline">Add Funds</button>
        </div>

        <!-- Sent -->
        <div class="bg-white rounded shadow p-5">
            <h3 class="text-gray-500 text-sm">Messages Sent (30d)</h3>
            <p class="text-3xl font-bold">{{ number_format($msgSent) }}</p>
        </div>

        <!-- Received -->
        <div class="bg-white rounded shadow p-5">
            <h3 class="text-gray-500 text-sm">Messages Received (30d)</h3>
            <p class="text-3xl font-bold">{{ number_format($msgReceived) }}</p>
        </div>

        <!-- Tickets -->
        <div class="bg-white rounded shadow p-5">
            <h3 class="text-gray-500 text-sm">Tickets Resolved</h3>
            <p class="text-3xl font-bold">{{ number_format($ticketsResolved) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Billing History -->
        <div class="bg-white rounded shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="font-bold text-gray-700">Billing History / Invoices</h3>
            </div>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Invoice</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($transactions as $txn)
                        <tr>
                            <td class="px-6 py-4 text-sm">{{ $txn->created_at->format('Y-m-d') }}</td>
                            <td class="px-6 py-4 text-sm">{{ ucfirst(str_replace('_', ' ', $txn->type)) }}</td>
                            <td
                                class="px-6 py-4 text-sm font-bold {{ $txn->amount < 0 ? 'text-red-500' : 'text-green-500' }}">
                                {{ $txn->amount < 0 ? '-' : '+' }}${{ number_format(abs($txn->amount), 2) }}
                            </td>
                            <td class="px-6 py-4 text-sm text-right">
                                @if($txn->invoice_number)
                                    <a href="#" class="text-blue-500 hover:underline">{{ $txn->invoice_number }}</a>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Agent Performance (Placeholder for future breakdown) -->
        <div class="bg-white rounded shadow p-6">
            <h3 class="font-bold text-gray-700 mb-4">Performance Insights</h3>
            <p class="text-gray-500">Breakdown of agent response times and resolution rates coming soon.</p>
        </div>
    </div>
</div>