<?php

namespace App\Http\Controllers;

use App\Models\TeamInvoice;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    public function download($invoiceId)
    {
        $invoice = TeamInvoice::with(['team.owner', 'items'])->findOrFail($invoiceId);

        // Ensure user belongs to the team
        if (auth()->user()->currentTeam->id !== $invoice->team_id) {
            abort(403);
        }

        $pdf = Pdf::loadView('pdf.invoice', compact('invoice'));

        return $pdf->download("Invoice-{$invoice->invoice_number}.pdf");
    }
}
