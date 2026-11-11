<?php

namespace App\Data;

use App\Models\Reservation;
use App\Support\FilmPosterStorage;

class ReservationCheckoutData
{
    /**
     * @return array<string, mixed>
     */
    public static function fromModel(Reservation $reservation, FilmPosterStorage $posters): array
    {
        $seance = $reservation->seance;
        $film = $seance?->film;
        $salle = $seance?->salle;
        $unitPrice = $seance ? (float) $seance->ticket_price : 0;

        return [
            'id' => $reservation->id,
            'reference' => $reservation->reference,
            'status' => $reservation->status,
            'quantity' => $reservation->quantity,
            'total_amount' => $reservation->total_amount,
            'total_amount_formatted' => number_format((float) $reservation->total_amount, 2, ',', ' ').' €',
            'unit_price' => number_format($unitPrice, 2, '.', ''),
            'unit_price_formatted' => number_format($unitPrice, 2, ',', ' ').' €',
            'expires_at' => $reservation->expires_at?->toIso8601String(),
            'film' => [
                'title' => $film?->title ?? '—',
                'poster_url' => $film ? $posters->url($film->poster) : null,
            ],
            'seance' => [
                'date_formatted' => $seance?->start_at->locale('fr')->translatedFormat('l j F Y') ?? '—',
                'time_range' => $seance
                    ? $seance->start_at->format('H:i').' – '.$seance->end_at->format('H:i')
                    : '—',
            ],
            'salle_name' => $salle?->name ?? '—',
        ];
    }
}
