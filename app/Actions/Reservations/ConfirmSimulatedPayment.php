<?php

namespace App\Actions\Reservations;

use App\Models\Paiement;
use App\Models\Reservation;
use App\Models\User;
use App\Support\GuestReservationSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmSimulatedPayment
{
    public function __construct(
        private IssueReservationBillets $issueReservationBilletsAction,
        private ExpirePendingReservations $expirePendingReservationsAction,
        private SendReservationTicketsMail $sendReservationTicketsMail,
    ) {}

    public function __invoke(
        ?User $user,
        Reservation $reservation,
        string $contactName,
        string $contactEmail,
    ): Reservation {
        $this->assertCanPay($user, $reservation);

        ($this->expirePendingReservationsAction)();

        return DB::transaction(function () use ($reservation, $contactName, $contactEmail): Reservation {
            /** @var Reservation $locked */
            $locked = Reservation::query()
                ->whereKey($reservation->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status === Reservation::STATUS_CONFIRMED) {
                return $locked->load(['seance.film', 'seance.salle', 'billets', 'user']);
            }

            if ($locked->status === Reservation::STATUS_CANCELLED) {
                throw ValidationException::withMessages([
                    'reservation' => __('Cette réservation a été annulée.'),
                ]);
            }

            if ($locked->status === Reservation::STATUS_EXPIRED) {
                throw ValidationException::withMessages([
                    'reservation' => __('Cette réservation a expiré. Veuillez en créer une nouvelle.'),
                ]);
            }

            if ($locked->status !== Reservation::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'reservation' => __('Cette réservation ne peut pas être payée.'),
                ]);
            }

            if ($locked->expires_at !== null && $locked->expires_at->isPast()) {
                $locked->update(['status' => Reservation::STATUS_EXPIRED]);

                throw ValidationException::withMessages([
                    'reservation' => __('Le délai de paiement est dépassé. Les places ont été libérées.'),
                ]);
            }

            $completedPayment = $locked->paiements()
                ->where('status', Paiement::STATUS_COMPLETED)
                ->first();

            if ($completedPayment === null) {
                $transactionReference = 'SIM-'.$locked->reference;
                $paidAt = now();

                Paiement::query()->updateOrCreate(
                    ['transaction_reference' => $transactionReference],
                    [
                        'reservation_id' => $locked->id,
                        'amount' => $locked->total_amount,
                        'method' => Paiement::METHOD_SIMULATION,
                        'status' => Paiement::STATUS_COMPLETED,
                        'paid_at' => $paidAt,
                    ],
                );
            }

            $email = trim($contactEmail);

            $locked->update([
                'status' => Reservation::STATUS_CONFIRMED,
                'expires_at' => null,
                'contact_name' => trim($contactName),
                'contact_email' => $email,
                'guest_email' => $locked->user_id === null ? $email : $locked->guest_email,
            ]);

            ($this->issueReservationBilletsAction)($locked);

            $locked = $locked->fresh(['seance.film', 'seance.salle', 'billets', 'paiements', 'user']);

            ($this->sendReservationTicketsMail)($locked);

            return $locked;
        });
    }

    private function assertCanPay(?User $user, Reservation $reservation): void
    {
        if ($reservation->user_id !== null) {
            if ($user === null || $user->id !== $reservation->user_id) {
                abort(403);
            }

            return;
        }

        if (! GuestReservationSession::canAccess($reservation)) {
            abort(403);
        }
    }
}
