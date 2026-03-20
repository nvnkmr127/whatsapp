<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333; line-height: 1.5; }
        .invoice-box { max-width: 800px; margin: auto; padding: 30px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 40px; }
        .logo { font-size: 24px; font-weight: bold; color: #128C7E; text-transform: uppercase; }
        .invoice-details { text-align: right; }
        .invoice-details h2 { margin: 0; color: #999; text-transform: uppercase; font-size: 14px; }
        .invoice-details p { margin: 0; font-weight: bold; }
        
        .addresses { display: table; width: 100%; margin-bottom: 40px; }
        .address-col { display: table-cell; width: 50%; }
        .address-col h3 { font-size: 12px; text-transform: uppercase; color: #999; margin-bottom: 5px; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
        th { background: #f9fafb; text-align: left; padding: 12px; font-size: 12px; text-transform: uppercase; color: #6b7280; border-bottom: 2px solid #e5e7eb; }
        td { padding: 12px; border-bottom: 1px solid #e5e7eb; font-size: 14px; }
        
        .totals { width: 100%; text-align: right; }
        .totals table { width: 250px; margin-left: auto; }
        .totals tr td:first-child { font-weight: bold; color: #6b7280; }
        .totals tr.grand-total td { font-size: 18px; font-weight: 900; color: #111827; border-top: 2px solid #128C7E; padding-top: 15px; }

        .footer { margin-top: 60px; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #eee; padding-top: 20px; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; text-transform: uppercase; }
        .badge-paid { background: #dcfce7; color: #166534; }
        .badge-pending { background: #fef9c3; color: #854d0e; }
    </style>
</head>
<body>
    <div class="invoice-box">
        <table style="width: 100%; border: none; margin-bottom: 40px;">
            <tr>
                <td style="border: none; padding: 0;">
                    <div class="logo">WhatsApp <span style="color: #333;">Business</span></div>
                </td>
                <td style="border: none; text-align: right; padding: 0;">
                    <div class="invoice-details">
                        <h2>Invoice Number</h2>
                        <p>{{ $invoice->invoice_number }}</p>
                        <h2>Date</h2>
                        <p>{{ $invoice->created_at->format('M d, Y') }}</p>
                        <div style="margin-top: 10px;">
                            <span class="badge {{ $invoice->status === 'paid' ? 'badge-paid' : 'badge-pending' }}">
                                {{ strtoupper($invoice->status) }}
                            </span>
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="addresses">
            <div class="address-col">
                <h3>Billed To</h3>
                <p><strong>{{ $invoice->team->name }}</strong><br>
                {{ $invoice->team->owner->name }}<br>
                {{ $invoice->team->owner->email }}</p>
            </div>
            <div class="address-col">
                <h3>From</h3>
                <p><strong>WhatsApp Solutions Inc.</strong><br>
                123 AI Avenue, Silicon Valley<br>
                California, USA</p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th style="text-align: center;">Qty</th>
                    <th style="text-align: right;">Price</th>
                    <th style="text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                    <tr>
                        <td>
                            <strong>{{ $item->label }}</strong><br>
                            <span style="font-size: 12px; color: #6b7280;">{{ $item->description }}</span>
                        </td>
                        <td style="text-align: center;">{{ number_format($item->quantity, 0) }}</td>
                        <td style="text-align: right;">{{ get_setting('currency_symbol', '$') }}{{ number_format($item->unit_price, 2) }}</td>
                        <td style="text-align: right;">{{ get_setting('currency_symbol', '$') }}{{ number_format($item->amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <table>
                <tr>
                    <td>Subtotal</td>
                    <td>{{ get_setting('currency_symbol', '$') }}{{ number_format($invoice->subtotal, 2) }}</td>
                </tr>
                <tr>
                    <td>Tax (0%)</td>
                    <td>{{ get_setting('currency_symbol', '$') }}{{ number_format($invoice->tax_amount, 2) }}</td>
                </tr>
                <tr class="grand-total">
                    <td>Total Amount</td>
                    <td>{{ get_setting('currency_symbol', '$') }}{{ number_format($invoice->total_amount, 2) }}</td>
                </tr>
            </table>
        </div>

        <div class="footer">
            <p>Thank you for choosing our WhatsApp Business solution. If you have any questions regarding this invoice, please contact support.</p>
            <p>© {{ date('Y') }} WhatsApp Solutions Inc. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
