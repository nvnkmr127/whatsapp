<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Email\CentralEmailService;
use App\Models\User;

class TestEmailDelivery extends Command
{
    protected $signature = 'email:test-otp {email}';
    protected $description = 'Test the decentralized email OTP delivery';

    public function handle(CentralEmailService $service)
    {
        $email = $this->argument('email');
        $this->info("Attempting to send test OTP to {$email}...");

        try {
            $service->sendOtp($email, [
                'name' => 'Tester',
                'code' => '999999',
                'expiry' => '5 minutes'
            ]);
            $this->info("Success! OTP queued for delivery.");
        } catch (\Exception $e) {
            $this->error("Failed: " . $e->getMessage());
        }
    }
}
