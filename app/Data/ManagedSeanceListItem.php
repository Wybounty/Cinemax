<?php

namespace App\Data;

use App\Models\Seance;
use App\Support\FilmPosterStorage;

class ManagedSeanceListItem
{
    /**
     * @return array<string, mixed>
     */
    public static function fromModel(Seance $seance, FilmPosterStorage $posters): array
    {
        $film = $seance->film;
        $salle = $seance->salle;

        return [
            'id' => $seance->id,
            'film_id' => $seance->film_id,
            'film_title' => $film?->title ?? '—',
            'film_poster_url' => $film ? $posters->url($film->poster) : null,
            'film_duration' => $film?->duration ?? 0,
            'salle_id' => $seance->salle_id,
            'salle_name' => $salle?->name ?? '—',
            'salle_capacity' => $seance->totalSeats(),
            'reserved_seats' => $seance->reservedSeatsCount(),
            'remaining_seats' => $seance->remainingSeatsCount(),
            'date' => $seance->start_at->format('Y-m-d'),
            'date_formatted' => $seance->start_at->translatedFormat('d M Y'),
            'start_time' => $seance->start_at->format('H:i'),
            'end_time' => $seance->end_at->format('H:i'),
            'time_range' => $seance->start_at->format('H:i').' – '.$seance->end_at->format('H:i'),
            'ticket_price' => $seance->ticket_price,
            'ticket_price_formatted' => number_format((float) $seance->ticket_price, 2, ',', ' ').' €',
            'status' => $seance->status,
            'has_reservations' => $seance->hasReservations(),
        ];
    }
}
