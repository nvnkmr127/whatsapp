<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DynamicSystemMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectString,
        public string $htmlContent,
        public ?string $textContent = null
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectString,
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->htmlContent,
            textString: $this->textContent,
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
