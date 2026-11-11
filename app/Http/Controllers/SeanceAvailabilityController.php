<?php

namespace App\Http\Controllers;

use App\Actions\Reservations\ExpirePendingReservations;
use App\Models\Seance;
use App\Support\SeanceSeatAvailability;
use Illuminate\Http\JsonResponse;

class SeanceAvailabilityController extends Controller
{
    public function show(Seance $seance, ExpirePendingReservations $expirePendingReservations): JsonResponse
    {
        $expirePendingReservations();

        $seance->loadMissing('salle');

        return response()->json([
            'seance_id' => $seance->id,
            'ticket_price' => $seance->ticket_price,
            ...SeanceSeatAvailability::for($seance)->toSummary(),
        ]);
    }
}
