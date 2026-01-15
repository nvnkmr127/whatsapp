<?php

namespace App\Console\Commands;

use App\Models\Cart;
use App\Models\Team;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessCartEngine extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'commerce:process-carts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process expired carts and trigger abandonment workflows';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Cart Engine Processing...');

        // 1. Mark Expired Carts as Abandoned
        // We find ACTIVE carts where expires_at < now
        $expiredCarts = Cart::active()
            ->where('expires_at', '<', now())
            ->get();

        foreach ($expiredCarts as $cart) {
            $cart->update(['status' => 'abandoned']);
            Log::info("Cart {$cart->id} marked as abandoned.");
        }

        $this->info("Marked " . $expiredCarts->count() . " carts as abandoned.");

        // 2. Process Reminders for Abandoned Carts
        // We iterate through teams to get their specific delay settings
        $teams = Team::all(); // Optimization: Chunk if many teams

        foreach ($teams as $team) {
            $config = $team->commerce_config ?? [];
            $reminderDelay = $config['cart_reminder_minutes'] ?? 30;

            // Find abandoned carts for this team that haven't had a reminder yet
            // AND match the time criteria (abandoned_at + delay <= now)
            // Note: 'updated_at' is used as proxy for 'abandoned_at' time since we just updated it above
            // Better: We should probably trust 'expires_at' as the abandonment time.

            $abandonedCarts = Cart::where('team_id', $team->id)
                ->where('status', 'abandoned')
                ->whereNull('reminder_sent_at')
                ->where('expires_at', '<=', now()->subMinutes((int) $reminderDelay))
                ->get();

            foreach ($abandonedCarts as $cart) {
                $this->sendReminder($cart);
            }
        }

        $this->info('Cart Engine Processing Complete.');
    }

    protected function sendReminder(Cart $cart)
    {
        // TODO: Integrate with WhatsApp Message Sender
        // For now, valid logic is to just log it and mark sentinel

        Log::info("Sending Abandoned Cart Reminder for Cart {$cart->uuid}");

        $cart->update([
            'reminder_sent_at' => now(),
            // In future, maybe trigger an Automation/Flow here
        ]);
    }
}
