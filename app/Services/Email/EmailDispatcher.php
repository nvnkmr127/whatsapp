<?php

namespace App\Services\Email;

use App\Enums\EmailUseCase;
use App\Jobs\SendEmailJob;
use App\Mail\DynamicSystemMail;
use App\Models\EmailLog;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Services\Email\Contracts\EmailPayload;
use App\Services\Email\Contracts\EmailResult;
use Exception;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\Log;

class EmailDispatcher
{
    /**
     * Queue an email for delivery via SendEmailJob (Resend or tenant SMTP failover).
     */
    public function send($to, EmailUseCase $useCase, Mailable $mailable, ?int $templateId = null): void
    {
        // 1. Extract content and subject from Mailable
        $subject = 'System Email';
        $html = '';
        $text = null;
        $headers = [];

        try {
            $html = (string) $mailable->render();

            if ($mailable instanceof DynamicSystemMail) {
                $subject = $mailable->subjectString ?: 'System Email';
                $text = $mailable->textContent ?: null;
                $headers = $mailable->headersArray;
            } else {
                $subject = $mailable->subject
                    ?: (method_exists($mailable, 'envelope') ? $mailable->envelope()->subject : null)
                    ?: 'System Email';

                if (method_exists($mailable, 'headers')) {
                    $headers = $mailable->headers()->text ?? [];
                }
            }
        } catch (Exception $e) {
            Log::warning('EmailDispatcher Content Extraction: '.$e->getMessage());
        }

        // 1.5 Normalize recipient
        $normalizedTo = $this->normalizeRecipient($to);

        // 2. Build Payload
        $payload = new EmailPayload(
            to: $normalizedTo,
            subject: $subject,
            html: $html,
            text: $text,
            useCase: $useCase,
            metadata: ['template_id' => $templateId],
            headers: $headers
        );

        // 3. Dispatch Job (Asynchronous delivery for production stability)
        SendEmailJob::dispatch($payload);

        // 4. Initial Log (Log that it's queued)
        $this->logInitialEntry($normalizedTo, $useCase, $subject, $templateId);
    }

    /**
     * Initial log entry to track that an email has been queued.
     */
    protected function logInitialEntry($to, $useCase, $subject, ?int $templateId): void
    {
        try {
            EmailLog::create([
                'recipient' => is_array($to) ? json_encode($to) : (string) $to,
                'use_case' => $useCase,
                'template_id' => $templateId,
                'subject' => substr($subject, 0, 255),
                'status' => 'queued',
            ]);
        } catch (Exception $e) {
            Log::error('Failed to write initial EmailLog: '.$e->getMessage());
        }
    }

    /**
     * Send an email using a registered EmailTemplate slug.
     */
    public function dispatchByTemplate(string $to, string $templateSlug, array $data = []): void
    {
        $template = EmailTemplate::where('slug', $templateSlug)->first();
        if (! $template) {
            throw new Exception("Email template with slug '$templateSlug' not found.");
        }

        $subject = $template->subject;
        $html = $template->content_html;
        $text = $template->content_text ?: null;

        // Simple variable replacement logic
        // We'll use a standardized approach for all dynamic content later,
        // but for now, we'll do basic replacement.
        $replace = [];
        foreach ($data as $key => $value) {
            if (is_scalar($value)) {
                $replace["{{$key}}"] = $value;
            }
        }

        // Add common objects if present
        if (isset($data['contact'])) {
            $contact = $data['contact'];
            $replace['{name}'] = $contact->name ?? 'there';
            $replace['{first_name}'] = explode(' ', $contact->name ?? '')[0];
            $replace['{phone_number}'] = $contact->phone_number;
            $replace['{email}'] = $contact->email ?? '';
        }

        // Add extra variables from data['variables'] if present
        if (isset($data['variables']) && is_array($data['variables'])) {
            foreach ($data['variables'] as $k => $v) {
                if (is_scalar($v)) {
                    $replace["{{$k}}"] = $v;
                }
            }
        }

        $subject = strtr($subject, $replace);
        $html = strtr($html, $replace);
        if ($text) {
            $text = strtr($text, $replace);
        }

        $mailable = new DynamicSystemMail($subject, $html, $text);

        $this->send($to, $template->type, $mailable, $template->id);
    }

    /**
     * Store result in EmailLog.
     */
    public function logResult($to, $useCase, EmailResult $result, string $subject, ?int $templateId): void
    {
        try {
            // Ensure recipient is a string and not too long for the DB
            $recipient = is_array($to) ? json_encode($to) : (string) $to;
            if (strlen($recipient) > 255) {
                $recipient = substr($recipient, 0, 252).'...';
            }

            $data = [
                'recipient' => $recipient,
                'use_case' => $useCase,
                'template_id' => $templateId,
                'subject' => substr($subject, 0, 255),
                'status' => $result->success ? 'sent' : 'failed',
                'provider_name' => $result->providerName,
                'message_id' => $result->messageId,
                'metadata' => [
                    'message_id' => $result->messageId,
                    'error' => $result->error,
                ],
            ];

            if ($result->success) {
                $data['sent_at'] = now();
            } else {
                $data['failed_at'] = now();
                $data['failure_reason'] = $result->error;
                $data['failure_type'] = 'provider_error';
            }

            EmailLog::create($data);
        } catch (Exception $e) {
            Log::error('Failed to write EmailLog via EmailDispatcher: '.$e->getMessage());
        }
    }

    /**
     * Normalize various recipient formats (User model, array, string) into a comma-separated string.
     */
    protected function normalizeRecipient($to): string
    {
        if (is_string($to)) {
            return $to;
        }

        if ($to instanceof User) {
            return $to->email;
        }

        if (is_array($to)) {
            // Handle ['email@example.com' => 'Name'] or ['email@example.com'] or [UserModel]
            return collect($to)->map(function ($value, $key) {
                if ($value instanceof User) {
                    return $value->email;
                }
                // If key is an email-like string and value is name, return key
                if (is_string($key) && str_contains($key, '@')) {
                    return $key;
                }

                return $value;
            })->unique()->implode(',');
        }

        return (string) $to;
    }
}
