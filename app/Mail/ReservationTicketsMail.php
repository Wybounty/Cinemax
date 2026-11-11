<?php

namespace App\Mail;

use App\Models\Reservation;
use App\Support\ReservationTicketsPdfBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReservationTicketsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Reservation $reservation,
    ) {}

    public function envelope(): Envelope
    {
        $filmTitle = $this->reservation->seance?->film?->title ?? __('Votre séance');

        return new Envelope(
            subject: __('Cinemax — Billets confirmés : :film (:reference)', [
                'film' => $filmTitle,
                'reference' => $this->reservation->reference,
            ]),
        );
    }

    public function content(): Content
    {
        $this->reservation->loadMissing(['seance.film', 'seance.salle', 'billets', 'paiements']);

        return new Content(
            markdown: 'mail.reservations.tickets',
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $builder = app(ReservationTicketsPdfBuilder::class);

        return [
            Attachment::fromData(
                fn (): string => $builder->output($this->reservation),
                $builder->filename($this->reservation),
            )->withMime('application/pdf'),
        ];
    }
}
