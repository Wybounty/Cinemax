<?php

namespace App\Actions\Reservations;

use App\Models\Film;
use App\Models\Reservation;
use App\Models\Salle;
use App\Models\Seance;
use App\Models\User;
use App\Support\SeanceSeatAvailability;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreatePendingReservation
{
    public const EXPIRY_MINUTES = 10;

    public const MAX_DEADLOCK_RETRIES = 3;

    public function __construct(
        private ExpirePendingReservations $expirePendingReservationsAction,
    ) {}

    public function __invoke(
        ?User $user,
        int $seanceId,
        int $quantity,
        ?string $contactName = null,
        ?string $contactEmail = null,
    ): Reservation {
        if ($user === null && ($contactEmail === null || trim($contactEmail) === '')) {
            throw ValidationException::withMessages([
                'contact_email' => __('Une adresse e-mail est requise pour la réservation invité.'),
            ]);
        }
        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => __('Sélectionnez au moins une place.'),
            ]);
        }

        ($this->expirePendingReservationsAction)();

        $attempt = 0;

        while (true) {
            try {
                return DB::transaction(function () use ($user, $seanceId, $quantity, $contactName, $contactEmail): Reservation {
                    /** @var Seance $seance */
                    $seance = Seance::query()
                        ->with([
                            'film' => fn ($query) => $query->withTrashed(),
                            'salle' => fn ($query) => $query->withTrashed(),
                        ])
                        ->whereKey($seanceId)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $this->assertSeanceIsReservable($seance);

                    $remaining = SeanceSeatAvailability::for($seance)->remainingSeats();

                    if ($quantity > $remaining) {
                        throw ValidationException::withMessages([
                            'quantity' => __('Il ne reste que :count place(s) pour cette séance.', [
                                'count' => $remaining,
                            ]),
                        ]);
                    }

                    $unitPrice = (float) $seance->ticket_price;
                    $totalAmount = round($unitPrice * $quantity, 2);
                    $reservedAt = now();

                    $email = trim((string) ($contactEmail ?: $user?->email));
                    $name = trim((string) ($contactName ?: $user?->name ?: __('Invité')));

                    return Reservation::query()->create([
                        'user_id' => $user?->id,
                        'seance_id' => $seance->id,
                        'reference' => $this->generateReference(),
                        'contact_name' => $name,
                        'contact_email' => $email,
                        'guest_email' => $user === null ? $email : null,
                        'quantity' => $quantity,
                        'total_amount' => number_format($totalAmount, 2, '.', ''),
                        'status' => Reservation::STATUS_PENDING,
                        'reserved_at' => $reservedAt,
                        'expires_at' => $reservedAt->copy()->addMinutes(self::EXPIRY_MINUTES),
                    ]);
                });
            } catch (QueryException $exception) {
                if ($this->isDeadlock($exception) && ++$attempt < self::MAX_DEADLOCK_RETRIES) {
                    usleep(100_000 * $attempt);

                    continue;
                }

                throw $exception;
            }
        }
    }

    private function assertSeanceIsReservable(Seance $seance): void
    {
        if ($seance->status !== Seance::STATUS_SCHEDULED) {
            throw ValidationException::withMessages([
                'seance_id' => __('Cette séance n’est pas disponible à la réservation.'),
            ]);
        }

        if ($seance->end_at->isPast()) {
            throw ValidationException::withMessages([
                'seance_id' => __('Cette séance est déjà terminée.'),
            ]);
        }

        /** @var Film|null $film */
        $film = $seance->film;

        if ($film === null || $film->trashed() || ! $film->published) {
            throw ValidationException::withMessages([
                'seance_id' => __('Ce film n’est plus disponible à la réservation.'),
            ]);
        }

        /** @var Salle|null $salle */
        $salle = $seance->salle;

        if ($salle === null || $salle->trashed() || ! $salle->isActive()) {
            throw ValidationException::withMessages([
                'seance_id' => __('Cette salle n’accepte pas de nouvelles réservations.'),
            ]);
        }
    }

    private function generateReference(): string
    {
        do {
            $reference = 'RES-'.strtoupper(Str::random(12));
        } while (Reservation::query()->where('reference', $reference)->exists());

        return $reference;
    }

    private function isDeadlock(QueryException $exception): bool
    {
        $message = $exception->getMessage();

        return str_contains($message, 'Deadlock')
            || str_contains($message, '1213')
            || str_contains($message, '40001');
    }
}
