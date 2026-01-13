<?php

namespace App\Mail;

use App\Services\MergeFields;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $template;

    public $subject;

    public $body;

    public function mount()
    {
        if (! checkPermission('email_template.view')) {
            return redirect()->route('admin.dashboard');
        }
    }

    /**
     * Create a new message instance.
     */
    public function __construct($slug, $userId = null)
    {
        $this->template = mailTemplate($slug);
        $this->subject  = $this->parseContent($this->template->merge_fields_groups, $this->template->subject, ['userId' => $userId]);
        $this->body     = $this->parseContent($this->template->merge_fields_groups, $this->template->message, ['userId' => $userId]);
    }

    protected function parseContent($groups, $subject, $content = [])
    {
        return app(MergeFields::class)->parseTemplates(
            $groups,
            $subject,
            $content
        );
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'components.mail',
            with: [
                'title' => $this->template->subject,
                'body'  => $this->body,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
