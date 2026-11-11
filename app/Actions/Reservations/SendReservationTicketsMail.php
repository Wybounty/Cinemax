<?php

namespace App\Actions\Reservations;

use App\Mail\ReservationTicketsMail;
use App\Models\Reservation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendReservationTicketsMail
{
    public function __invoke(Reservation $reservation, bool $forceResend = false): bool
    {
        if (
            ! $forceResend
            && $reservation->tickets_email_sent_at !== null
            && $reservation->status === Reservation::STATUS_CONFIRMED
        ) {
            return true;
        }

        $email = $reservation->guest_email
            ?: $reservation->contact_email
            ?: $reservation->user?->email;

        if ($email === null || $email === '') {
            return false;
        }

        try {
            Mail::to($email)->send(new ReservationTicketsMail($reservation));

            $reservation->forceFill(['tickets_email_sent_at' => now()])->save();

            return true;
        } catch (\Throwable $exception) {
            Log::warning('Envoi e-mail billet (simulation) échoué.', [
                'reservation_id' => $reservation->id,
                'email' => $email,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
