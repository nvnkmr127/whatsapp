<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class DynamicSystemMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectString,
        public string $htmlContent,
        public ?string $textContent = null,
        public array $headersArray = []
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectString,
        );
    }

    public function content(): Content
    {
        $content = new Content(
            htmlString: $this->htmlContent,
        );

        if ($this->textContent) {
            $content->text = 'emails.raw_text';
            $content->with['text'] = $this->textContent;
        }

        return $content;
    }

    public function headers(): Headers
    {
        return new Headers(
            text: $this->headersArray
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
