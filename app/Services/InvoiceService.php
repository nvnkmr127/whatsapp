<?php

namespace App\Services;

use App\Models\Team;
use App\Models\Plan;
use App\Models\TeamInvoice;
use App\Models\TeamInvoiceItem;
use App\Models\WhatsAppConversation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    /**
     * Generate an itemized, explainable invoice for a team.
     */
    public function generateMonthlyInvoice(Team $team, Carbon $billingStart)
    {
        $billingEnd = (clone $billingStart)->addMonth()->subSecond();

        return DB::transaction(function () use ($team, $billingStart, $billingEnd) {
            $invoice = TeamInvoice::create([
                'team_id' => $team->id,
                'invoice_number' => 'INV-' . strtoupper(bin2hex(random_bytes(4))),
                'status' => 'draft',
                'period_start' => $billingStart,
                'period_end' => $billingEnd,
                'due_at' => now()->addDays(7),
            ]);

            // 1. Subscription Charge
            $plan = Plan::where('name', $team->subscription_plan)->first();
            if ($plan && $plan->monthly_price > 0) {
                $this->addSubscriptionItem($invoice, $plan, $billingStart, $billingEnd);
            }

            // 2. Usage Charges (WhatsApp Conversations)
            $this->addWhatsAppUsageItems($invoice, $billingStart, $billingEnd);

            // 3. AI Usage (if metered beyond plan)
            $this->addAiUsageItems($invoice, $billingStart, $billingEnd);

            // Update Totals
            $subtotal = $invoice->items()->sum('amount');
            $tax = $subtotal * 0.00; // Placeholder for tax logic

            $invoice->update([
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'total_amount' => $subtotal + $tax,
            ]);

            return $invoice;
        });
    }

    /**
     * Add a subscription line item with period explanation.
     */
    protected function addSubscriptionItem(TeamInvoice $invoice, Plan $plan, $start, $end)
    {
        TeamInvoiceItem::create([
            'team_invoice_id' => $invoice->id,
            'type' => 'subscription',
            'feature_key' => 'plan',
            'label' => "{$plan->display_name} Plan",
            'description' => "Monthly subscription fee for the period {$start->format('M d')} to {$end->format('M d, Y')}.",
            'quantity' => 1,
            'unit_price' => $plan->monthly_price,
            'amount' => $plan->monthly_price,
            'period_start' => $start,
            'period_end' => $end,
        ]);
    }

    /**
     * Calculate and add pro-rated items for mid-cycle changes.
     */
    public function addProratedChangeItem(TeamInvoice $invoice, Plan $oldPlan, Plan $newPlan, Carbon $changeDate, Carbon $cycleEnd)
    {
        $totalDays = $changeDate->diffInDays($cycleEnd->copy()->startOfMonth()); // simplified
        $daysRemaining = $changeDate->diffInDays($cycleEnd);

        $refundAmount = ($oldPlan->monthly_price / 30) * $daysRemaining;
        $chargeAmount = ($newPlan->monthly_price / 30) * $daysRemaining;

        // Refund unused portion of old plan
        TeamInvoiceItem::create([
            'team_invoice_id' => $invoice->id,
            'type' => 'adjustment',
            'label' => "Pro-rated Refund: {$oldPlan->display_name}",
            'description' => "Unused time on {$oldPlan->display_name} ({$daysRemaining} days remaining).",
            'quantity' => 1,
            'unit_price' => -$refundAmount,
            'amount' => -$refundAmount,
            'is_prorated' => true,
            'metadata' => ['days_remaining' => $daysRemaining, 'base_price' => $oldPlan->monthly_price]
        ]);

        // Charge for new plan portion
        TeamInvoiceItem::create([
            'team_invoice_id' => $invoice->id,
            'type' => 'subscription',
            'label' => "Pro-rated Charge: {$newPlan->display_name}",
            'description' => "Remaining time on {$newPlan->display_name} ({$daysRemaining} days).",
            'quantity' => 1,
            'unit_price' => $chargeAmount,
            'amount' => $chargeAmount,
            'is_prorated' => true,
            'metadata' => ['days' => $daysRemaining, 'base_price' => $newPlan->monthly_price]
        ]);
    }

    /**
     * Aggregates WhatsApp conversation costs into explainable line items.
     */
    protected function addWhatsAppUsageItems(TeamInvoice $invoice, $start, $end)
    {
        $usage = WhatsAppConversation::where('team_id', $invoice->team_id)
            ->where('created_at', '>=', $start)
            ->where('created_at', '<=', $end)
            ->select('category', DB::raw('count(*) as qty'), DB::raw('sum(cost) as total'))
            ->groupBy('category')
            ->get();

        foreach ($usage as $item) {
            TeamInvoiceItem::create([
                'team_invoice_id' => $invoice->id,
                'type' => 'usage',
                'feature_key' => 'message',
                'label' => "WhatsApp: " . ucfirst($item->category) . " Conversations",
                'description' => "Aggregated charges for {$item->qty} {$item->category} conversation windows.",
                'quantity' => $item->qty,
                'unit_price' => $item->total / $item->qty,
                'amount' => $item->total,
                'period_start' => $start,
                'period_end' => $end,
            ]);
        }
    }

    protected function addAiUsageItems(TeamInvoice $invoice, $start, $end)
    {
        // Logic to charge for AI if exceeded plan allowance
    }
}
