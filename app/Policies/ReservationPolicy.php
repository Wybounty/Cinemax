<?php

namespace App\Policies;

use App\Models\Billet;
use App\Models\Paiement;
use App\Models\Reservation;
use App\Models\User;
use App\Support\GuestReservationSession;
use App\Support\ReservationPaymentStatus;

class ReservationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(?User $user, Reservation $reservation): bool
    {
        if ($reservation->user_id !== null) {
            return $user !== null && $user->id === $reservation->user_id;
        }

        return GuestReservationSession::canAccess($reservation);
    }

    public function create(?User $user): bool
    {
        return $user === null || $user->is_active;
    }

    public function checkout(?User $user, Reservation $reservation): bool
    {
        if ($reservation->user_id !== null) {
            return $user !== null && $user->id === $reservation->user_id;
        }

        return GuestReservationSession::canAccess($reservation);
    }

    public function pay(?User $user, Reservation $reservation): bool
    {
        return $this->checkout($user, $reservation);
    }

    public function cancel(?User $user, Reservation $reservation): bool
    {
        return $this->checkout($user, $reservation);
    }

    public function resendTickets(?User $user, Reservation $reservation): bool
    {
        return $this->downloadTickets($user, $reservation);
    }

    public function manageAny(User $user): bool
    {
        return $user->canAccessUserManagement();
    }

    public function manage(User $user, Reservation $reservation): bool
    {
        return $user->canAccessUserManagement();
    }

    public function cancelAsAdmin(User $user, Reservation $reservation): bool
    {
        return $user->canAccessUserManagement();
    }

    public function downloadTickets(?User $user, Reservation $reservation): bool
    {
        if ($reservation->status !== Reservation::STATUS_CONFIRMED) {
            return false;
        }

        if (ReservationPaymentStatus::resolve($reservation) !== Paiement::STATUS_COMPLETED) {
            return false;
        }

        if (! $reservation->billets()->where('status', Billet::STATUS_VALID)->exists()) {
            return false;
        }

        if ($reservation->user_id !== null) {
            return $user !== null && $user->id === $reservation->user_id;
        }

        return GuestReservationSession::canAccess($reservation);
    }
}
