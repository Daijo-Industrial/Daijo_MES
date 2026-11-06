<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SpkPlannedQuantityChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $changes;

    public string $batchId;

    public bool $isTest;

    /**
     * Create a new message instance.
     *
     * @param array $changes List of SPK changes with planned_qty modified
     * @param string $batchId Sesi Sync Batch ID
     * @param bool $isTest Mode pengujian
     */
    public function __construct(array $changes, string $batchId, bool $isTest = false)
    {
        $this->changes = $changes;
        $this->batchId = $batchId;
        $this->isTest = $isTest;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $prefix = $this->isTest ? '🧪 [TEST] ' : '📋 [SPK ALERT] ';
        $count = count($this->changes);

        return new Envelope(
            subject: "{$prefix}Perubahan Planned Quantity SPK ({$count} SPK Direvisi) - Daijo MES",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.spk_planned_quantity_changed',
            with: [
                'changes' => $this->changes,
                'batchId' => $this->batchId,
                'isTest' => $this->isTest,
                'auditUrl' => url('/spk-changes?change_type=PLANNED_QTY_CHANGE&batch_id=' . urlencode($this->batchId)),
            ]
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}

