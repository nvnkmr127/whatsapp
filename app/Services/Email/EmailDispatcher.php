<?php

namespace App\Services\Email;

use App\Enums\EmailUseCase;
use App\Services\Email\Contracts\EmailPayload;
use App\Services\Email\Contracts\EmailResult;
use Exception;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\Log;

class EmailDispatcher
{
    /**
     * @param EmailProviderManager $manager
     */
    public function __construct(
        protected EmailProviderManager $manager
    ) {}

    /**
     * Send an email using the multi-driver architecture.
     */
    public function send($to, EmailUseCase $useCase, Mailable $mailable, ?int $templateId = null): void
    {
        // 1. Extract content and subject from Mailable
        $subject = 'System Email';
        $html = '';
        $text = null;

        try {
            $html = (string) $mailable->render();

            $reflection = new \ReflectionClass($mailable);
            if ($reflection->hasProperty('subject')) {
                $prop = $reflection->getProperty('subject');
                $prop->setAccessible(true);
                $subject = $prop->getValue($mailable) ?: 'System Email';
            }
            
            // Text content handling if available
            if (property_exists($mailable, 'textContent') && !empty($mailable->textContent)) {
                $text = $mailable->textContent;
            }

            // Extract headers if available (Laravel 10+ headers() method or custom property)
            $headers = [];
            if (method_exists($mailable, 'headers')) {
                $mailableHeaders = $mailable->headers();
                if (property_exists($mailableHeaders, 'text')) {
                    $headers = $mailableHeaders->text;
                }
            } elseif (property_exists($mailable, 'headersArray')) {
                $headers = $mailable->headersArray;
            }
        } catch (\Exception $e) {
            Log::warning("EmailDispatcher Content Extraction: " . $e->getMessage());
        }

        // 2. Build Payload
        $payload = new EmailPayload(
            to: is_array($to) ? (implode(',', array_keys($to)) ?: implode(',', $to)) : $to,
            subject: $subject,
            html: $html,
            text: $text,
            useCase: $useCase,
            metadata: ['template_id' => $templateId],
            headers: $headers
        );

        // 3. Delegate to Manager (Resolved Driver Architecture)
        $result = $this->manager->send($payload);

        // 4. Log Result
        $this->logResult($to, $useCase, $result, $subject, $templateId);

        // 5. Fail loud if the final driver (including fallback) failed
        if (!$result->success) {
            throw new Exception("Final Email Delivery Failure ({$result->providerName}): " . $result->error);
        }
    }

    /**
     * Send an email using a registered EmailTemplate slug.
     */
    public function dispatchByTemplate(string $to, string $templateSlug, array $data = []): void
    {
        $template = \App\Models\EmailTemplate::where('slug', $templateSlug)->first();
        if (!$template) {
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
        if ($text) $text = strtr($text, $replace);

        $mailable = new \App\Mail\DynamicSystemMail($subject, $html, $text);
        
        $this->send($to, $template->type, $mailable, $template->id);
    }

    /**
     * Store result in EmailLog.
     */
    protected function logResult($to, EmailUseCase $useCase, EmailResult $result, string $subject, ?int $templateId): void
    {
        try {
            $data = [
                'recipient' => is_array($to) ? json_encode($to) : $to,
                'use_case' => $useCase,
                'template_id' => $templateId,
                'subject' => $subject,
                'status' => $result->success ? 'sent' : 'failed',
                'provider_name' => $result->providerName,
                'message_id' => $result->messageId,
                'metadata' => [
                    'message_id' => $result->messageId,
                    'error' => $result->error
                ],
            ];

            if ($result->success) {
                $data['sent_at'] = now();
            } else {
                $data['failed_at'] = now();
                $data['failure_reason'] = $result->error;
                $data['failure_type'] = 'provider_error';
            }

            \App\Models\EmailLog::create($data);
        } catch (\Exception $e) {
            Log::error("Failed to write EmailLog via EmailDispatcher: " . $e->getMessage());
        }
    }
}
