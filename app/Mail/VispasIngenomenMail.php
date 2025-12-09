<?php

namespace App\Mail;

use App\Models\Overtreding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable: VispasIngenomenMail
 *
 * Wordt verzonden wanneer een vispas is ingenomen bij het registreren van een overtreding.
 * Bevat de overtreding en relevante relaties voor weergave in de e-mailtemplate.
 */
class VispasIngenomenMail extends Mailable
{
    use Queueable, SerializesModels;

    public Overtreding $overtreding;

    /**
     * Create a new message instance.
     */
    public function __construct(Overtreding $overtreding)
    {
        $this->overtreding = $overtreding->load('overtredingType', 'controleRonde.user');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Vispas Ingenomen',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.vispas_ingenomen',
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
