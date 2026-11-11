<?php

namespace App\Data;

use App\Models\Paiement;
use App\Models\Reservation;

class ManagedReservationStats
{
    /**
     * @return array<string, mixed>
     */
    public static function compute(): array
    {
        $total = Reservation::query()->count();
        $confirmed = Reservation::query()->where('status', Reservation::STATUS_CONFIRMED)->count();
        $pending = Reservation::query()->where('status', Reservation::STATUS_PENDING)->count();
        $cancelledOrExpired = Reservation::query()->whereIn('status', [
            Reservation::STATUS_CANCELLED,
            Reservation::STATUS_EXPIRED,
        ])->count();

        $paymentsCompleted = Reservation::query()->whereHas(
            'paiements',
            fn ($query) => $query->where('status', Paiement::STATUS_COMPLETED),
        )->count();

        $paymentsPending = Reservation::query()
            ->whereDoesntHave(
                'paiements',
                fn ($query) => $query->where('status', Paiement::STATUS_COMPLETED),
            )
            ->where(function ($query): void {
                $query
                    ->whereDoesntHave('paiements')
                    ->orWhereHas(
                        'paiements',
                        fn ($paiement) => $paiement->where('status', Paiement::STATUS_PENDING),
                    );
            })
            ->count();

        $revenue = (float) Paiement::query()
            ->where('status', Paiement::STATUS_COMPLETED)
            ->sum('amount');

        $revenueFormatted = number_format($revenue, 2, ',', ' ').' €';

        return [
            'total' => $total,
            'confirmed' => $confirmed,
            'pending' => $pending,
            'cancelled_or_expired' => $cancelledOrExpired,
            'payments_completed' => $paymentsCompleted,
            'payments_pending' => $paymentsPending,
            'revenue' => $revenue,
            'revenue_formatted' => $revenueFormatted,
        ];
    }
}
