<?php

namespace App\Services\Email;

use App\Enums\EmailUseCase;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailDispatcher
{
    protected $healthService;

    public function __construct(SmtpHealthService $healthService)
    {
        $this->healthService = $healthService;
    }

    /**
     * Send an email using the appropriate mailer based on use case.
     */
    public function send($to, EmailUseCase $useCase, Mailable $mailable, ?int $templateId = null): void
    {
        $subject = 'System Email';
        try {
            $reflection = new \ReflectionClass($mailable);
            if ($reflection->hasProperty('subject')) {
                $prop = $reflection->getProperty('subject');
                $prop->setAccessible(true);
                $subject = $prop->getValue($mailable) ?: 'System Email';
            }
        } catch (\Exception $e) {
            // Fallback
        }

        // 1. Get ordered list of active providers
        try {
            if (\App\Models\SmtpConfig::count() === 0) {
                $this->sendLegacy($to, $useCase, $mailable);
                $this->logSuccess($to, $useCase, 'legacy', null, $subject, $templateId);
                return;
            }
        } catch (\Exception $e) {
            $this->sendLegacy($to, $useCase, $mailable);
            $this->logSuccess($to, $useCase, 'legacy_error_recovery', null, $subject, $templateId);
            return;
        }

        $configs = \App\Models\SmtpConfig::where('is_active', true)
            ->whereJsonContains('use_case', $useCase->value)
            ->where('health_status', '!=', 'failing')
            ->orderBy('priority')
            ->get();

        if ($configs->isEmpty()) {
            $configs = \App\Models\SmtpConfig::where('is_active', true)
                ->where('health_status', '!=', 'failing')
                ->orderBy('priority')
                ->get();

            if ($configs->isEmpty()) {
                $this->sendLegacy($to, $useCase, $mailable);
                $this->logSuccess($to, $useCase, 'legacy_fallback', null, $subject, $templateId);
                return;
            }
        }

        $lastException = null;
        $sent = false;

        foreach ($configs as $config) {
            try {
                $mailerName = 'dynamic_smtp_' . $config->id;
                $this->configureDynamicMailer($mailerName, $config);

                Mail::mailer($mailerName)->to($to)->send($mailable);

                $this->healthService->reportSuccess($config);
                $this->logSuccess($to, $useCase, $config->name, $config->id, $subject, $templateId);
                $sent = true;
                break;

            } catch (\Exception $e) {
                $lastException = $e;
                $this->healthService->reportFailure($config);
                $this->logFailure($to, $useCase, $config->name, $config->id, $subject, $e, $templateId);
            }
        }

        if (!$sent) {
            throw $lastException ?? new \Exception("All SMTP providers failed.");
        }
    }

    protected function logSuccess($to, $useCase, $provider, $configId, $subject, $templateId): void
    {
        try {
            \App\Models\EmailLog::create([
                'recipient' => is_array($to) ? json_encode($to) : $to,
                'use_case' => $useCase,
                'template_id' => $templateId,
                'subject' => $subject,
                'status' => 'sent',
                'smtp_config_id' => $configId,
                'provider_name' => $provider,
                'sent_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to write EmailLog: " . $e->getMessage());
        }
    }

    protected function logFailure($to, $useCase, $provider, $configId, $subject, \Exception $e, $templateId): void
    {
        try {
            $error = $e->getMessage();
            $type = 'smtp_error';
            if (str_contains($error, 'Connection could not be established'))
                $type = 'network';
            if (str_contains($error, 'Authentication failed'))
                $type = 'authentication';

            \App\Models\EmailLog::create([
                'recipient' => is_array($to) ? json_encode($to) : $to,
                'use_case' => $useCase,
                'template_id' => $templateId,
                'subject' => $subject,
                'status' => 'failed',
                'failure_reason' => $error,
                'failure_type' => $type,
                'smtp_config_id' => $configId,
                'provider_name' => $provider,
                'failed_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to write EmailLog: " . $e->getMessage());
        }
    }

    protected function sendLegacy($to, EmailUseCase $useCase, Mailable $mailable): void
    {
        $mailer = $useCase->getMailer();
        Mail::mailer($mailer)->to($to)->send($mailable);
    }

    protected function configureDynamicMailer(string $name, \App\Models\SmtpConfig $config): void
    {
        config([
            "mail.mailers.{$name}" => [
                'transport' => 'smtp',
                'host' => $config->host,
                'port' => $config->port,
                'username' => $config->username,
                'password' => $config->password,
                'encryption' => $config->encryption,
                'timeout' => null,
                'local_domain' => env('MAIL_EHLO_DOMAIN'),
            ]
        ]);

        if (!empty($config->from_address)) {
            config(["mail.from.address" => $config->from_address]);
            config(["mail.from.name" => $config->from_name]);
        }
    }
}
