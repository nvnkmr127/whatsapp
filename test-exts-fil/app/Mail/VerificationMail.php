<?php

namespace App\Mail;

use App\Services\MergeFields;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $actionUrl;

    public $actionText;

    protected $userId;

    protected $contactId;

    public $template;

    public $subject;

    public $body;

    public $gretting;

    public function mount()
    {
        if (! checkPermission('email_template.view')) {
            return redirect()->route('admin.dashboard');
        }
    }

    /**
     * Create a new message instance.
     */
    public function __construct($gretting, $actionUrl, $actionText, $slug, $userId = null, $contactId = null)
    {
        $this->gretting   = $gretting;
        $this->actionUrl  = $actionUrl;
        $this->actionText = $actionText;
        $this->userId     = $userId;
        $this->contactId  = $contactId;
        $this->template   = mailTemplate($slug);

        $this->subject = $this->parseContent($this->template->merge_fields_groups, $this->template->subject, ['userId' => $userId, 'contactId' => $contactId]);

        $this->body = $this->parseContent($this->template->merge_fields_groups, $this->template->message, ['userId' => $userId, 'contactId' => $contactId]);

    }

    protected function parseContent($groups, $subject, $content)
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
                'title'      => $this->template->subject,
                'body'       => $this->body,
                'actionUrl'  => $this->actionUrl,
                'actionText' => $this->actionText,
                'greeting'   => $this->gretting,
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
