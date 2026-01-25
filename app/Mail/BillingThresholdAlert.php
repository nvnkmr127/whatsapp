<?php

namespace App\Mail;

use App\Models\Team;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BillingThresholdAlert extends Mailable
{
    use Queueable, SerializesModels;

    public $team;
    public $metric;
    public $level;
    public $percent;
    public $alertMessage;

    /**
     * Create a new message instance.
     */
    public function __construct(Team $team, string $metric, string $level, float $percent, string $alertMessage)
    {
        $this->team = $team;
        $this->metric = $metric;
        $this->level = $level;
        $this->percent = $percent;
        $this->alertMessage = $alertMessage;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = "[Alert] " . ucfirst($this->level) . ": Usage threshold reached for " . config('app.name');
        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.billing.threshold-alert',
        );
    }
}
