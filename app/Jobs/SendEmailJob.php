<?php

namespace App\Jobs;

use App\Enums\EmailUseCase;
use App\Mail\DynamicSystemMail;
use App\Models\SmtpConfig;
use App\Services\Email\Contracts\EmailPayload;
use App\Services\Email\Contracts\EmailResult;
use App\Services\Email\EmailDispatcher;
use App\Services\Email\SmtpHealthService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = [10, 60, 300];

    public function __construct(
        public EmailPayload $payload
    ) {
        $this->onQueue('notifications');
    }

    public function handle(EmailDispatcher $dispatcher, SmtpHealthService $healthService): void
    {
        $result = $this->deliver($healthService);

        $dispatcher->logResult(
            $this->payload->to,
            $this->payload->useCase,
            $result,
            $this->payload->subject,
            $this->payload->metadata['template_id'] ?? null
        );

        if (! $result->success) {
            throw new Exception("Email delivery failure ({$result->providerName}): {$result->error}");
        }
    }

    /**
     * Route by use case: Resend for marketing/alert/notification when configured,
     * otherwise the tenant SMTP failover chain. OTP and invoices never go via Resend.
     */
    protected function deliver(SmtpHealthService $healthService): EmailResult
    {
        $useCase = $this->payload->useCase;
        $provider = config('mail.routing.'.$useCase->value) ?: config('mail.provider', 'smtp');

        $resendUseCases = [EmailUseCase::MARKETING, EmailUseCase::ALERT, EmailUseCase::NOTIFICATION];
        if ($provider === 'resend' && config('services.resend.api_key') && in_array($useCase, $resendUseCases)) {
            return $this->sendViaResend();
        }

        return $this->sendViaSmtp($healthService);
    }

    protected function sendViaResend(): EmailResult
    {
        try {
            $mailable = $this->mailable();

            if ($from = config('services.resend.from_address')) {
                $mailable->from($from, config('services.resend.from_name'));
            }

            $sent = Mail::mailer('resend')->to($this->payload->to)->send($mailable);

            return EmailResult::ok('resend', $sent?->getMessageId() ?? (string) Str::ulid());
        } catch (Exception $e) {
            return EmailResult::fail('resend', $e->getMessage());
        }
    }

    protected function sendViaSmtp(SmtpHealthService $healthService): EmailResult
    {
        $to = $this->payload->to;
        $useCase = $this->payload->useCase;

        try {
            // If global mailer is set to 'log', respect it immediately (local/dev)
            if (config('mail.default') === 'log') {
                $this->sendLegacy($to, $useCase);

                return EmailResult::ok('log', 'logged_via_default_override');
            }

            if (SmtpConfig::count() === 0) {
                $this->sendLegacy($to, $useCase);

                return EmailResult::ok('smtp', 'legacy_'.Str::ulid());
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
                    $this->sendLegacy($to, $useCase);

                    return EmailResult::ok('smtp', 'legacy_fallback_'.Str::ulid());
                }
            }

            foreach ($configs as $config) {
                try {
                    $mailable = $this->mailable();
                    if (! empty($config->from_address)) {
                        $mailable->from($config->from_address, $config->from_name);
                    }

                    Mail::build([
                        'transport' => 'smtp',
                        'host' => $config->host,
                        'port' => (int) $config->port,
                        'username' => $config->username,
                        'password' => $config->password,
                        'encryption' => $config->encryption,
                        'timeout' => 30,
                        'local_domain' => config('mail.mailers.smtp.local_domain'),
                    ])->to($to)->send($mailable);

                    $healthService->reportSuccess($config);

                    return EmailResult::ok('smtp', 'smtp_cfg_'.$config->id.'_'.Str::ulid());
                } catch (Exception $e) {
                    $healthService->reportFailure($config);
                    Log::error("SendEmailJob: Failed to send via {$config->name}: ".$e->getMessage());
                }
            }

            return EmailResult::fail('smtp', 'All dynamic SMTP providers failed.');
        } catch (Exception $e) {
            return EmailResult::fail('smtp', $e->getMessage());
        }
    }

    protected function sendLegacy(string $to, EmailUseCase $useCase): void
    {
        Mail::mailer($useCase->getMailer())->to($to)->send($this->mailable());
    }

    protected function mailable(): DynamicSystemMail
    {
        return new DynamicSystemMail(
            subjectString: $this->payload->subject,
            htmlContent: $this->payload->html,
            textContent: $this->payload->text,
            headersArray: $this->payload->headers,
        );
    }
}
