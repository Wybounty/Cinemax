<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\Dashboard\CancelManagedReservation;
use App\Actions\Reservations\ExpirePendingReservations;
use App\Data\ManagedReservationDetail;
use App\Data\ManagedReservationStats;
use App\Http\Controllers\Controller;
use App\Models\Paiement;
use App\Models\Reservation;
use App\Queries\ManagedReservationIndexQuery;
use App\Support\FilmPosterStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReservationController extends Controller
{
    public function index(
        Request $request,
        ManagedReservationIndexQuery $query,
        ExpirePendingReservations $expirePendingReservations,
    ): Response {
        $this->authorize('manageAny', Reservation::class);

        $expirePendingReservations();

        $search = $request->string('search')->trim()->toString();
        $reservationStatus = $request->string('reservation_status')->trim()->toString();
        $paymentStatus = $request->string('payment_status')->trim()->toString();
        $salleId = $request->integer('salle') ?: null;
        $reservedDate = $request->string('reserved_date')->trim()->toString();
        $seanceDate = $request->string('seance_date')->trim()->toString();
        $period = $request->string('period')->trim()->toString();

        $salleOptions = $query->salleOptions();

        return Inertia::render('dashboard/reservations/Index', [
            'stats' => ManagedReservationStats::compute(),
            'reservations' => $query->paginate(
                $search,
                $reservationStatus,
                $paymentStatus,
                $salleId !== null && collect($salleOptions)->contains('id', $salleId)
                    ? $salleId
                    : null,
                $reservedDate !== '' ? $reservedDate : null,
                $seanceDate !== '' ? $seanceDate : null,
                $period,
            ),
            'filters' => [
                'search' => $search,
                'reservation_status' => $query->normalizeReservationStatus($reservationStatus),
                'payment_status' => $query->normalizePaymentStatus($paymentStatus),
                'salle' => $salleId !== null && collect($salleOptions)->contains('id', $salleId)
                    ? (string) $salleId
                    : '',
                'reserved_date' => $reservedDate,
                'seance_date' => $seanceDate,
                'period' => $query->normalizePeriod($period),
            ],
            'salleOptions' => $salleOptions,
            'reservationStatusOptions' => [
                ['value' => Reservation::STATUS_PENDING, 'label' => 'En attente'],
                ['value' => Reservation::STATUS_CONFIRMED, 'label' => 'Confirmée'],
                ['value' => Reservation::STATUS_CANCELLED, 'label' => 'Annulée'],
                ['value' => Reservation::STATUS_EXPIRED, 'label' => 'Expirée'],
            ],
            'paymentStatusOptions' => [
                ['value' => Paiement::STATUS_PENDING, 'label' => 'Paiement en attente'],
                ['value' => Paiement::STATUS_COMPLETED, 'label' => 'Payé'],
                ['value' => Paiement::STATUS_FAILED, 'label' => 'Échec du paiement'],
            ],
            'periodOptions' => [
                ['value' => 'today', 'label' => 'Réservées aujourd’hui'],
                ['value' => 'week', 'label' => '7 derniers jours'],
                ['value' => 'month', 'label' => '30 derniers jours'],
            ],
        ]);
    }

    public function show(
        Request $request,
        Reservation $reservation,
        ExpirePendingReservations $expirePendingReservations,
        FilmPosterStorage $posters,
    ): Response {
        $this->authorize('manage', $reservation);

        $expirePendingReservations();
        $reservation->refresh();

        $reservation->load([
            'user',
            'paiements',
            'billets',
            'seance.film' => fn ($relation) => $relation->withTrashed(),
            'seance.salle' => fn ($relation) => $relation->withTrashed(),
        ]);

        return Inertia::render('dashboard/reservations/Show', [
            'reservation' => ManagedReservationDetail::fromModel($reservation, $posters),
        ]);
    }

    public function cancel(
        Reservation $reservation,
        CancelManagedReservation $cancelManagedReservation,
    ): RedirectResponse {
        $this->authorize('cancelAsAdmin', $reservation);

        $cancelManagedReservation($reservation);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Réservation annulée. Les places ont été libérées.'),
        ]);

        return redirect()->route('dashboard.reservations.show', $reservation);
    }
}
