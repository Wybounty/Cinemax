<?php

namespace App\Data;

use App\Models\Billet;
use App\Models\Paiement;
use App\Models\Reservation;
use App\Support\FilmPosterStorage;
use App\Support\ReservationPaymentStatus;

class ManagedReservationDetail
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

        $paymentStatus = ReservationPaymentStatus::resolve($reservation);
        $primaryPaiement = ReservationPaymentStatus::primaryPaiement($reservation);

        return [
            'id' => $reservation->id,
            'reference' => $reservation->reference,
            'status' => $reservation->status,
            'status_label' => self::reservationStatusLabel($reservation->status),
            'quantity' => $reservation->quantity,
            'total_amount_formatted' => number_format((float) $reservation->total_amount, 2, ',', ' ').' €',
            'reserved_at_formatted' => $reservation->reserved_at->locale('fr')->translatedFormat('d M Y H:i'),
            'expires_at' => $reservation->expires_at?->toIso8601String(),
            'expires_at_formatted' => $reservation->expires_at?->locale('fr')->translatedFormat('d M Y H:i'),
            'can_cancel' => in_array(
                $reservation->status,
                [Reservation::STATUS_PENDING, Reservation::STATUS_CONFIRMED],
                true,
            ),
            'client' => [
                'name' => $reservation->contact_name ?: $user?->name ?? '—',
                'email' => $reservation->contact_email ?: $user?->email ?? '—',
                'user_id' => $user?->id,
                'account_email' => $user?->email,
            ],
            'film' => [
                'title' => $film?->title ?? '—',
                'poster_url' => $film ? $posters->url($film->poster) : null,
            ],
            'seance' => [
                'date_formatted' => $seance?->start_at->locale('fr')->translatedFormat('l j F Y') ?? '—',
                'time_range' => $seance
                    ? $seance->start_at->format('H:i').' – '.$seance->end_at->format('H:i')
                    : '—',
                'salle_name' => $salle?->name ?? '—',
                'ticket_price_formatted' => $seance
                    ? number_format((float) $seance->ticket_price, 2, ',', ' ').' €'
                    : '—',
            ],
            'payment' => [
                'status' => $paymentStatus,
                'status_label' => ReservationPaymentStatus::label($paymentStatus),
                'amount_formatted' => $primaryPaiement
                    ? number_format((float) $primaryPaiement->amount, 2, ',', ' ').' €'
                    : number_format((float) $reservation->total_amount, 2, ',', ' ').' €',
                'method' => $primaryPaiement?->method,
                'method_label' => self::methodLabel($primaryPaiement?->method),
                'transaction_reference' => $primaryPaiement?->transaction_reference,
                'paid_at_formatted' => $primaryPaiement?->paid_at?->locale('fr')->translatedFormat('d M Y H:i'),
            ],
            'paiements' => $reservation->paiements
                ->sortByDesc('id')
                ->values()
                ->map(fn (Paiement $paiement): array => [
                    'id' => $paiement->id,
                    'amount_formatted' => number_format((float) $paiement->amount, 2, ',', ' ').' €',
                    'method' => $paiement->method,
                    'method_label' => self::methodLabel($paiement->method),
                    'status' => $paiement->status,
                    'status_label' => ReservationPaymentStatus::label($paiement->status),
                    'transaction_reference' => $paiement->transaction_reference,
                    'paid_at_formatted' => $paiement->paid_at?->locale('fr')->translatedFormat('d M Y H:i'),
                ])
                ->all(),
            'billets' => $reservation->billets
                ->sortBy('id')
                ->values()
                ->map(fn (Billet $billet): array => [
                    'id' => $billet->id,
                    'ticket_code' => $billet->ticket_code,
                    'status' => $billet->status,
                    'status_label' => self::billetStatusLabel($billet->status),
                    'issued_at_formatted' => $billet->issued_at->locale('fr')->translatedFormat('d M Y H:i'),
                ])
                ->all(),
        ];
    }

    private static function methodLabel(?string $method): string
    {
        return match ($method) {
            Paiement::METHOD_SIMULATION => 'Simulation',
            null => '—',
            default => $method,
        };
    }

    private static function billetStatusLabel(string $status): string
    {
        return match ($status) {
            Billet::STATUS_VALID => 'Valide',
            Billet::STATUS_USED => 'Utilisé',
            Billet::STATUS_CANCELLED => 'Annulé',
            default => $status,
        };
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
