<?php

namespace App\Data;

use App\Models\Billet;
use App\Models\Paiement;
use App\Models\Reservation;
use App\Support\FilmPosterStorage;
use App\Support\ReservationPaymentStatus;

class AccountReservationDetail
{
    /**
     * @return array<string, mixed>
     */
    public static function fromModel(Reservation $reservation, FilmPosterStorage $posters): array
    {
        $base = AccountReservationListItem::fromModel($reservation, $posters);
        $seance = $reservation->seance;
        $film = $seance?->film;

        $paymentStatus = ReservationPaymentStatus::resolve($reservation);

        return [
            ...$base,
            'film_synopsis' => $film?->synopsis,
            'seance_start_time' => $seance?->start_at->format('H:i') ?? '—',
            'seance_end_time' => $seance?->end_at->format('H:i') ?? '—',
            'unit_price_formatted' => $seance
                ? number_format((float) $seance->ticket_price, 2, ',', ' ').' €'
                : '—',
            'payment_status' => $paymentStatus,
            'payment_status_label' => ReservationPaymentStatus::label($paymentStatus),
            'expires_at' => $reservation->expires_at?->toIso8601String(),
            'expires_at_formatted' => $reservation->expires_at?->locale('fr')->translatedFormat('d M Y H:i'),
            'billets_count' => $reservation->billets->count(),
            'can_pay' => $reservation->status === Reservation::STATUS_PENDING
                && ($reservation->expires_at === null || $reservation->expires_at->isFuture()),
            'can_cancel' => in_array(
                $reservation->status,
                [Reservation::STATUS_PENDING, Reservation::STATUS_CONFIRMED],
                true,
            ),
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

        return $reservation->billets
            ->contains(fn (Billet $billet): bool => $billet->status === Billet::STATUS_VALID);
    }
}
