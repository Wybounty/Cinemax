<?php

namespace App\Data;

use App\Models\Seance;

class CatalogSeanceListItem
{
    /**
     * @return array<string, mixed>
     */
    public static function fromModel(Seance $seance): array
    {
        return [
            'id' => $seance->id,
            'date' => $seance->start_at->format('Y-m-d'),
            'date_formatted' => $seance->start_at->locale('fr')->translatedFormat('l j F Y'),
            'date_short' => $seance->start_at->locale('fr')->translatedFormat('D j M'),
            'start_time' => $seance->start_at->format('H:i'),
            'end_time' => $seance->end_at->format('H:i'),
            'time_range' => $seance->start_at->format('H:i').' – '.$seance->end_at->format('H:i'),
            'salle_name' => $seance->salle?->name ?? '—',
            ...$seance->seatAvailability()->toSummary(),
            'ticket_price' => $seance->ticket_price,
            'ticket_price_formatted' => number_format((float) $seance->ticket_price, 2, ',', ' ').' €',
            'status' => $seance->status,
            'status_label' => __('Programmée'),
        ];
    }
}
