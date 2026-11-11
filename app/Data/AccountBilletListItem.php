<?php

namespace App\Data;

use App\Models\Billet;
use App\Support\FilmPosterStorage;

class AccountBilletListItem
{
    /**
     * @return array<string, mixed>
     */
    public static function fromModel(Billet $billet, FilmPosterStorage $posters): array
    {
        $reservation = $billet->reservation;
        $seance = $reservation?->seance;
        $film = $seance?->film;

        return [
            'id' => $billet->id,
            'ticket_code' => $billet->ticket_code,
            'status' => $billet->status,
            'status_label' => self::statusLabel($billet->status),
            'issued_at_formatted' => $billet->issued_at->locale('fr')->translatedFormat('d M Y H:i'),
            'reservation_reference' => $reservation?->reference ?? '—',
            'film_title' => $film?->title ?? '—',
            'film_poster_url' => $film ? $posters->url($film->poster) : null,
            'seance_date_formatted' => $seance?->start_at->locale('fr')->translatedFormat('l j F Y') ?? '—',
            'seance_time_range' => $seance
                ? $seance->start_at->format('H:i').' – '.$seance->end_at->format('H:i')
                : '—',
        ];
    }

    private static function statusLabel(string $status): string
    {
        return match ($status) {
            'valid' => 'Valide',
            'used' => 'Utilisé',
            'cancelled' => 'Annulé',
            default => $status,
        };
    }
}
