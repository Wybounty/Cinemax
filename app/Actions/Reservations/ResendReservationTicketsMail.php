<?php

namespace App\Actions\Reservations;

use App\Models\Paiement;
use App\Models\Reservation;
use App\Support\ReservationPaymentStatus;
use Illuminate\Validation\ValidationException;

class ResendReservationTicketsMail
{
    public function __construct(
        private SendReservationTicketsMail $sendReservationTicketsMail,
    ) {}

    public function __invoke(Reservation $reservation): bool
    {
        if ($reservation->status !== Reservation::STATUS_CONFIRMED) {
            throw ValidationException::withMessages([
                'reservation' => __('Les billets ne peuvent être renvoyés que pour une réservation confirmée.'),
            ]);
        }

        if (ReservationPaymentStatus::resolve($reservation) !== Paiement::STATUS_COMPLETED) {
            throw ValidationException::withMessages([
                'reservation' => __('Le paiement doit être confirmé avant l’envoi des billets.'),
            ]);
        }

        $reservation->loadMissing(['seance.film', 'seance.salle', 'billets', 'paiements', 'user']);

        return ($this->sendReservationTicketsMail)($reservation, forceResend: true);
    }
}
