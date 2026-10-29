<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SapTimeoutAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public $summary;

    public $recentLogs;

    public $isTest;

    /**
     * Create a new message instance.
     */
    public function __construct(array $summary, $recentLogs, bool $isTest = false)
    {
        $this->summary = $summary;
        $this->recentLogs = $recentLogs;
        $this->isTest = $isTest;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $prefix = $this->isTest ? '[TEST] ' : '⚠️ [ALERT] ';
        $count = $this->summary['total_timeouts'] ?? 1;

        return new Envelope(
            subject: "{$prefix}SAP API Operation Timed Out ({$count}x terdeteksi) - Daijo MES",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.sap_timeout_alert',
            with: [
                'summary' => $this->summary,
                'recentLogs' => $this->recentLogs,
                'isTest' => $this->isTest,
                'dashboardUrl' => url('/api-log-dashboard'),
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
