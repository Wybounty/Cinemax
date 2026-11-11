<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\Reservations\ExpirePendingReservations;
use App\Actions\Reservations\GenerateReservationTicketsPdf;
use App\Data\AccountReservationDetail;
use App\Data\AccountReservationListItem;
use App\Http\Controllers\Controller;
use App\Models\Billet;
use App\Models\Reservation;
use App\Support\FilmPosterStorage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class MyReservationController extends Controller
{
    public function index(
        Request $request,
        ExpirePendingReservations $expirePendingReservations,
        FilmPosterStorage $posters,
    ): InertiaResponse {
        $this->authorize('viewAny', Reservation::class);

        $expirePendingReservations();

        $reservations = Reservation::query()
            ->where('user_id', $request->user()->id)
            ->with(['seance.film', 'seance.salle', 'paiements', 'billets'])
            ->orderByDesc('reserved_at')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Reservation $reservation): array => AccountReservationListItem::fromModel($reservation, $posters));

        return Inertia::render('dashboard/my-reservations/Index', [
            'reservations' => $reservations,
        ]);
    }

    public function show(
        Request $request,
        Reservation $reservation,
        ExpirePendingReservations $expirePendingReservations,
        FilmPosterStorage $posters,
    ): InertiaResponse {
        $this->authorize('view', $reservation);

        $expirePendingReservations();
        $reservation->refresh();
        $reservation->load(['seance.film', 'seance.salle', 'billets', 'paiements']);

        return Inertia::render('dashboard/my-reservations/Show', [
            'reservation' => AccountReservationDetail::fromModel($reservation, $posters),
            'billets' => $reservation->billets->map(fn (Billet $billet): array => [
                'id' => $billet->id,
                'ticket_code' => $billet->ticket_code,
                'status' => $billet->status,
                'status_label' => match ($billet->status) {
                    Billet::STATUS_VALID => 'Valide',
                    Billet::STATUS_USED => 'Utilisé',
                    Billet::STATUS_CANCELLED => 'Annulé',
                    default => $billet->status,
                },
                'issued_at_formatted' => $billet->issued_at->locale('fr')->translatedFormat('d M Y H:i'),
            ]),
        ]);
    }

    public function downloadTickets(
        Reservation $reservation,
        GenerateReservationTicketsPdf $generateReservationTicketsPdf,
    ): Response {
        $this->authorize('downloadTickets', $reservation);

        return $generateReservationTicketsPdf($reservation);
    }
}
