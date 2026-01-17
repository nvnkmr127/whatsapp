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
        $team = $cart->team;
        if (!$team) {
            Log::error("Cart {$cart->id} has no team.");
            return;
        }

        $contact = $cart->contact;
        if (!$contact) {
            Log::error("Cart {$cart->id} has no contact.");
            return;
        }

        // Initialize WhatsApp Service
        $whatsapp = new \App\Services\WhatsAppService($team);

        $config = $team->commerce_config ?? [];
        $templateName = $config['abandoned_cart_template'] ?? null;

        try {
            if ($templateName) {
                // Send Template Message
                // Assuming template has 1 variable for the cart link or checkout URL
                // We'll generate a checkout link (mock for now if not defined)
                $checkoutUrl = config('app.url') . "/checkout/" . $cart->uuid;

                $result = $whatsapp->sendTemplate(
                    $contact->phone_number,
                    $templateName,
                    'en_US', // Default language, could be dynamic
                    [$contact->first_name ?? 'there', $checkoutUrl] // Body params: Name, Link
                );
            } else {
                // Fallback: Send Text Message
                $checkoutUrl = config('app.url') . "/checkout/" . $cart->uuid;
                $message = "Hi " . ($contact->first_name ?? 'there') . ", you left items in your cart. Complete your purchase here: " . $checkoutUrl;

                $result = $whatsapp->sendText($contact->phone_number, $message);
            }

            if (isset($result['success']) && $result['success']) {
                Log::info("Sent Abandoned Cart Reminder to {$contact->phone_number} for Cart {$cart->uuid}");

                $cart->update([
                    'reminder_sent_at' => now(),
                    'status' => 'reminder_sent' // Optional: update status to reflect reminder sent
                ]);
            } else {
                Log::error("Failed to send reminder for Cart {$cart->uuid}: " . json_encode($result));
            }

        } catch (\Exception $e) {
            Log::error("Exception sending reminder for Cart {$cart->uuid}: " . $e->getMessage());
        }
    }
}
