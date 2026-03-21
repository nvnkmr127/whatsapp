<?php

namespace App\Services\Email\Drivers;

use App\Enums\EmailUseCase;
use App\Mail\DynamicSystemMail;
use App\Models\EmailLog;
use App\Models\SmtpConfig;
use App\Services\Email\Contracts\EmailPayload;
use App\Services\Email\Contracts\EmailProviderContract;
use App\Services\Email\Contracts\EmailResult;
use App\Services\Email\SmtpHealthService;
use Exception;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SmtpDriver implements EmailProviderContract
{
    public function __construct(
        protected SmtpHealthService $healthService
    ) {}

    public function send(EmailPayload $payload): EmailResult
    {
        $mailable = new DynamicSystemMail(
            subjectString: $payload->subject,
            htmlContent: $payload->html,
            textContent: $payload->text
        );

        $to = $payload->to;
        $useCase = $payload->useCase;
        $templateId = $payload->metadata['template_id'] ?? null;
        $subject = $payload->subject;

        try {
            if (SmtpConfig::count() === 0) {
                $this->sendLegacy($to, $useCase, $mailable);
                return EmailResult::ok('smtp', 'legacy_' . uniqid());
            }

            $configs = SmtpConfig::where('is_active', true)
                ->whereJsonContains('use_case', $useCase->value)
                ->where('health_status', '!=', 'failing')
                ->orderBy('priority')
                ->get();

            if ($configs->isEmpty()) {
                $configs = SmtpConfig::where('is_active', true)
                    ->where('health_status', '!=', 'failing')
                    ->orderBy('priority')
                    ->get();

                if ($configs->isEmpty()) {
                    $this->sendLegacy($to, $useCase, $mailable);
                    return EmailResult::ok('smtp', 'legacy_fallback_' . uniqid());
                }
            }

            foreach ($configs as $config) {
                try {
                    $mailerName = 'dynamic_smtp_' . $config->id;
                    $this->configureDynamicMailer($mailerName, $config);

                    Mail::mailer($mailerName)->to($to)->send($mailable);

                    $this->healthService->reportSuccess($config);

                    return EmailResult::ok('smtp', 'smtp_cfg_' . $config->id . '_' . uniqid());

                } catch (Exception $e) {
                    $this->healthService->reportFailure($config);
                    Log::error("SmtpDriver: Failed to send via {$config->name}: " . $e->getMessage());
                }
            }

            return EmailResult::fail('smtp', 'All dynamic SMTP providers failed.');

        } catch (Exception $e) {
            return EmailResult::fail('smtp', $e->getMessage());
        }
    }

    public function supports(EmailUseCase $useCase): bool
    {
        return true;
    }

    public function getName(): string
    {
        return 'smtp';
    }

    public function isHealthy(): bool
    {
        return SmtpConfig::where('is_active', true)
            ->where('health_status', '!=', 'failing')
            ->exists();
    }

    protected function sendLegacy($to, EmailUseCase $useCase, Mailable $mailable): void
    {
        $mailer = $useCase->getMailer();
        Mail::mailer($mailer)->to($to)->send($mailable);
    }

    protected function configureDynamicMailer(string $name, SmtpConfig $config): void
    {
        config([
            "mail.mailers.{$name}" => [
                'transport' => 'smtp',
                'host' => $config->host,
                'port' => $config->port,
                'username' => $config->username,
                'password' => $config->password,
                'encryption' => $config->encryption,
                'timeout' => 30,
                'local_domain' => config('mail.mailers.smtp.local_domain'),
            ]
        ]);

        if (!empty($config->from_address)) {
            config(["mail.from.address" => $config->from_address]);
            config(["mail.from.name" => $config->from_name]);
        }
    }
}
