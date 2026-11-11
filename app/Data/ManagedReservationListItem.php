<?php

namespace App\Data;

use App\Models\Reservation;
use App\Support\FilmPosterStorage;
use App\Support\ReservationPaymentStatus;

class ManagedReservationListItem
{
    /**
     * @return array<string, mixed>
     */
    public static function fromModel(Reservation $reservation, FilmPosterStorage $posters): array
    {
        $seance = $reservation->seance;
        $film = $seance?->film;
        $salle = $seance?->salle;
        $user = $reservation->user;

        $spectatorName = $reservation->contact_name ?: $user?->name ?? '—';
        $spectatorEmail = $reservation->contact_email ?: $user?->email ?? '—';

        $paymentStatus = ReservationPaymentStatus::resolve($reservation);

        return [
            'id' => $reservation->id,
            'reference' => $reservation->reference,
            'spectator_name' => $spectatorName,
            'spectator_email' => $spectatorEmail,
            'film_title' => $film?->title ?? '—',
            'film_poster_url' => $film ? $posters->url($film->poster) : null,
            'seance_date_formatted' => $seance?->start_at->locale('fr')->translatedFormat('l j F Y') ?? '—',
            'seance_time_range' => $seance
                ? $seance->start_at->format('H:i').' – '.$seance->end_at->format('H:i')
                : '—',
            'salle_name' => $salle?->name ?? '—',
            'quantity' => $reservation->quantity,
            'total_amount_formatted' => number_format((float) $reservation->total_amount, 2, ',', ' ').' €',
            'status' => $reservation->status,
            'status_label' => self::reservationStatusLabel($reservation->status),
            'payment_status' => $paymentStatus,
            'payment_status_label' => ReservationPaymentStatus::label($paymentStatus),
            'reserved_at_formatted' => $reservation->reserved_at->locale('fr')->translatedFormat('d M Y H:i'),
        ];
    }

    private static function reservationStatusLabel(string $status): string
    {
        return match ($status) {
            Reservation::STATUS_PENDING => 'En attente',
            Reservation::STATUS_CONFIRMED => 'Confirmée',
            Reservation::STATUS_CANCELLED => 'Annulée',
            Reservation::STATUS_EXPIRED => 'Expirée',
            default => $status,
        };
    }
}
