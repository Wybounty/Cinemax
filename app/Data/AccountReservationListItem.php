<?php

namespace App\Data;

use App\Models\Billet;
use App\Models\Paiement;
use App\Models\Reservation;
use App\Support\FilmPosterStorage;
use App\Support\ReservationPaymentStatus;

class AccountReservationListItem
{
    /**
     * @return array<string, mixed>
     */
    public static function fromModel(Reservation $reservation, FilmPosterStorage $posters): array
    {
        $seance = $reservation->seance;
        $film = $seance?->film;
        $salle = $seance?->salle;

        $paymentStatus = ReservationPaymentStatus::resolve($reservation);

        return [
            'id' => $reservation->id,
            'reference' => $reservation->reference,
            'status' => $reservation->status,
            'status_label' => self::statusLabel($reservation->status),
            'quantity' => $reservation->quantity,
            'total_amount' => $reservation->total_amount,
            'total_amount_formatted' => number_format((float) $reservation->total_amount, 2, ',', ' ').' €',
            'reserved_at' => $reservation->reserved_at->toIso8601String(),
            'reserved_at_formatted' => $reservation->reserved_at->locale('fr')->translatedFormat('d M Y H:i'),
            'film_title' => $film?->title ?? '—',
            'film_poster_url' => $film ? $posters->url($film->poster) : null,
            'seance_date_formatted' => $seance?->start_at->locale('fr')->translatedFormat('l j F Y') ?? '—',
            'seance_time_range' => $seance
                ? $seance->start_at->format('H:i').' – '.$seance->end_at->format('H:i')
                : '—',
            'salle_name' => $salle?->name ?? '—',
            'payment_status' => $paymentStatus,
            'payment_status_label' => ReservationPaymentStatus::label($paymentStatus),
            'expires_at' => $reservation->expires_at?->toIso8601String(),
            'can_download_tickets' => self::canDownloadTickets($reservation, $paymentStatus),
        ];
    }

    private static function canDownloadTickets(Reservation $reservation, string $paymentStatus): bool
    {
        if ($reservation->status !== Reservation::STATUS_CONFIRMED) {
            return false;
        }

        if ($paymentStatus !== Paiement::STATUS_COMPLETED) {
            return false;
        }

        $billets = $reservation->relationLoaded('billets')
            ? $reservation->billets
            : $reservation->billets()->where('status', Billet::STATUS_VALID)->get();

        return $billets->contains(fn (Billet $billet): bool => $billet->status === Billet::STATUS_VALID);
    }

    private static function statusLabel(string $status): string
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
