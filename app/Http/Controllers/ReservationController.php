<?php

namespace App\Http\Controllers;

use App\Actions\Reservations\CancelReservation;
use App\Actions\Reservations\ConfirmSimulatedPayment;
use App\Actions\Reservations\CreatePendingReservation;
use App\Actions\Reservations\ExpirePendingReservations;
use App\Actions\Reservations\GenerateReservationTicketsPdf;
use App\Actions\Reservations\ResendReservationTicketsMail;
use App\Data\ReservationCheckoutData;
use App\Http\Requests\PayReservationRequest;
use App\Http\Requests\StoreReservationRequest;
use App\Models\Billet;
use App\Models\Reservation;
use App\Support\FilmPosterStorage;
use App\Support\GuestReservationSession;
use App\Support\ReservationPaymentStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ReservationController extends Controller
{
    public function store(
        StoreReservationRequest $request,
        CreatePendingReservation $createPendingReservation,
    ): RedirectResponse {
        $user = $request->user();

        $reservation = $createPendingReservation(
            $user,
            (int) $request->input('seance_id'),
            (int) $request->input('quantity'),
            $request->string('contact_name')->trim()->toString() ?: null,
            $request->string('contact_email')->trim()->toString() ?: null,
        );

        if ($reservation->isGuest()) {
            GuestReservationSession::remember($reservation);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Réservation temporaire créée. Vous avez 10 minutes pour régler.'),
        ]);

        return redirect()->route('reservations.checkout', $reservation);
    }

    public function checkout(
        Request $request,
        Reservation $reservation,
        ExpirePendingReservations $expirePendingReservations,
        FilmPosterStorage $posters,
    ): InertiaResponse|RedirectResponse {
        $this->authorize('checkout', $reservation);

        $expirePendingReservations();
        $reservation->refresh();

        if ($reservation->status === Reservation::STATUS_CONFIRMED) {
            return redirect()->route('reservations.confirmed', $reservation);
        }

        if (in_array($reservation->status, [Reservation::STATUS_EXPIRED, Reservation::STATUS_CANCELLED], true)) {
            $redirect = $reservation->isGuest()
                ? redirect()->route('film.show', $reservation->seance?->film)
                : redirect()->route('dashboard.my-reservations.show', $reservation);

            return $redirect->withErrors(['reservation' => __('Cette réservation n’est plus payable.')]);
        }

        $reservation->load(['seance.film', 'seance.salle']);

        $user = $request->user();

        return Inertia::render('reservations/Checkout', [
            'reservation' => ReservationCheckoutData::fromModel($reservation, $posters),
            'contact' => [
                'name' => $reservation->contact_name ?? $user?->name ?? '',
                'email' => $reservation->contact_email ?? $user?->email ?? $reservation->guest_email ?? '',
            ],
            'user_is_logged_in' => $user !== null,
            'is_guest' => $reservation->isGuest(),
        ]);
    }

    public function pay(
        PayReservationRequest $request,
        Reservation $reservation,
        ConfirmSimulatedPayment $confirmSimulatedPayment,
    ): RedirectResponse {
        $confirmSimulatedPayment(
            $request->user(),
            $reservation,
            $request->string('contact_name')->toString(),
            $request->string('contact_email')->toString(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Paiement simulé confirmé. Vos billets ont été envoyés par e-mail (simulation).'),
        ]);

        return redirect()->route('reservations.confirmed', $reservation);
    }

    public function confirmed(Request $request, Reservation $reservation, FilmPosterStorage $posters): InertiaResponse
    {
        $this->authorize('view', $reservation);

        $reservation->load(['seance.film', 'seance.salle', 'billets', 'paiements']);

        $canDownload = $request->user()?->can('downloadTickets', $reservation)
            ?? Gate::forUser(null)->allows('downloadTickets', $reservation);

        return Inertia::render('reservations/Confirmed', [
            'reservation' => ReservationCheckoutData::fromModel($reservation, $posters),
            'billets' => $reservation->billets->map(fn (Billet $billet): array => [
                'id' => $billet->id,
                'ticket_code' => $billet->ticket_code,
            ]),
            'is_guest' => $reservation->isGuest(),
            'can_download_tickets' => $canDownload,
            'can_resend_email' => $canDownload,
            'tickets_email_sent_at' => $reservation->tickets_email_sent_at?->toIso8601String(),
            'payment_status_label' => ReservationPaymentStatus::label(
                ReservationPaymentStatus::resolve($reservation),
            ),
        ]);
    }

    public function downloadTickets(
        Reservation $reservation,
        GenerateReservationTicketsPdf $generateReservationTicketsPdf,
    ): Response {
        $this->authorize('downloadTickets', $reservation);

        return $generateReservationTicketsPdf($reservation);
    }

    public function resendTickets(
        Reservation $reservation,
        ResendReservationTicketsMail $resendReservationTicketsMail,
    ): RedirectResponse {
        $this->authorize('resendTickets', $reservation);

        $sent = $resendReservationTicketsMail($reservation);

        Inertia::flash('toast', [
            'type' => $sent ? 'success' : 'error',
            'message' => $sent
                ? __('E-mail avec billets PDF renvoyé.')
                : __('L’envoi a échoué. Réessayez plus tard.'),
        ]);

        return redirect()->back();
    }

    public function cancel(
        Request $request,
        Reservation $reservation,
        CancelReservation $cancelReservation,
    ): RedirectResponse {
        $this->authorize('cancel', $reservation);

        $cancelReservation($request->user(), $reservation);

        $reservation->load('seance.film');

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Réservation annulée.'),
        ]);

        if ($reservation->isGuest()) {
            return redirect()->route('film.show', $reservation->seance?->film);
        }

        return redirect()->route('dashboard.my-reservations.index');
    }
}
