<?php

namespace App\Support;

use App\Models\Paiement;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Builder;

class ReservationPaymentStatus
{
    public static function resolve(Reservation $reservation): string
    {
        $paiements = $reservation->relationLoaded('paiements')
            ? $reservation->paiements
            : $reservation->paiements()->get();

        if ($paiements->contains(fn (Paiement $paiement): bool => $paiement->status === Paiement::STATUS_COMPLETED)) {
            return Paiement::STATUS_COMPLETED;
        }

        if ($paiements->contains(fn (Paiement $paiement): bool => $paiement->status === Paiement::STATUS_FAILED)) {
            return Paiement::STATUS_FAILED;
        }

        return Paiement::STATUS_PENDING;
    }

    public static function label(string $status): string
    {
        return match ($status) {
            Paiement::STATUS_PENDING => 'Paiement en attente',
            Paiement::STATUS_COMPLETED => 'Payé',
            Paiement::STATUS_FAILED => 'Échec du paiement',
            default => $status,
        };
    }

    /**
     * @param  Builder<Reservation>  $query
     */
    public static function applyFilter(Builder $query, string $paymentStatus): void
    {
        if ($paymentStatus === Paiement::STATUS_COMPLETED) {
            $query->whereHas(
                'paiements',
                fn (Builder $builder) => $builder->where('status', Paiement::STATUS_COMPLETED),
            );

            return;
        }

        if ($paymentStatus === Paiement::STATUS_FAILED) {
            $query
                ->whereHas(
                    'paiements',
                    fn (Builder $builder) => $builder->where('status', Paiement::STATUS_FAILED),
                )
                ->whereDoesntHave(
                    'paiements',
                    fn (Builder $builder) => $builder->where('status', Paiement::STATUS_COMPLETED),
                );

            return;
        }

        if ($paymentStatus === Paiement::STATUS_PENDING) {
            $query->whereDoesntHave(
                'paiements',
                fn (Builder $builder) => $builder->where('status', Paiement::STATUS_COMPLETED),
            )->where(function (Builder $builder): void {
                $builder
                    ->whereDoesntHave('paiements')
                    ->orWhereHas(
                        'paiements',
                        fn (Builder $paiement) => $paiement->where('status', Paiement::STATUS_PENDING),
                    );
            });
        }
    }

    public static function primaryPaiement(Reservation $reservation): ?Paiement
    {
        $paiements = $reservation->relationLoaded('paiements')
            ? $reservation->paiements
            : $reservation->paiements()->get();

        $completed = $paiements->firstWhere('status', Paiement::STATUS_COMPLETED);

        if ($completed !== null) {
            return $completed;
        }

        return $paiements->sortByDesc('id')->first();
    }
}
