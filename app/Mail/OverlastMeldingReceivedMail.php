<?php

namespace App\Mail;

use App\Models\OverlastMelding;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OverlastMeldingReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public OverlastMelding $melding;
    public string $type;

    public function __construct(OverlastMelding $melding, string $type = 'admin')
    {
        $this->melding = $melding->load('verwerktDoor');
        $this->type = $type;
    }

    public function envelope(): Envelope
    {
        $subject = $this->type === 'melder'
            ? 'Bevestiging overlastmelding #' . $this->melding->id
            : 'Nieuwe overlastmelding #' . $this->melding->id . ' ontvangen';

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.overlast_melding_received',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
