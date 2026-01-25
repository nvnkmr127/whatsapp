<?php

namespace App\Services\Email;

use App\Models\SmtpConfig;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

class SmtpHealthService
{
    /**
     * Test the connection for a given SMTP config.
     *
     * @param SmtpConfig $config
     * @return bool
     */
    public function testConnection(SmtpConfig $config): bool
    {
        try {
            $transport = new EsmtpTransport(
                $config->host,
                (int) $config->port,
                $config->encryption === 'tls'
            );

            $transport->setUsername($config->username);
            $transport->setPassword($config->password);

            $transport->start();
            $transport->stop();

            $this->reportSuccess($config);
            return true;
        } catch (\Exception $e) {
            Log::error("SMTP Connection Test Failed for {$config->name}: " . $e->getMessage());
            $this->reportFailure($config);
            return false;
        }
    }

    public function reportSuccess(SmtpConfig $config): void
    {
        $config->update([
            'health_status' => 'healthy',
            'failure_count' => 0,
            'last_checked_at' => now(),
        ]);
    }

    public function reportFailure(SmtpConfig $config): void
    {
        $config->increment('failure_count');
        $config->update(['last_checked_at' => now()]);

        if ($config->failure_count >= 3 && $config->health_status !== 'failing') {
            $config->update(['health_status' => 'failing']);
            Log::alert("SMTP Provider {$config->name} marked as FAILING after repeated errors.");
        }
    }
}
