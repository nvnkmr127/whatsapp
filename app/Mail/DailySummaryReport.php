<?php

namespace App\Mail;

use App\Models\Team;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailySummaryReport extends Mailable
{
    use Queueable, SerializesModels;

    public $team;
    public $stats;
    public $date;

    /**
     * Create a new message instance.
     */
    public function __construct(Team $team, array $stats, $date)
    {
        $this->team = $team;
        $this->stats = $stats;
        $this->date = $date;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Daily Activity Report: ' . $this->date,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.reports.daily-summary',
        );
    }
}
