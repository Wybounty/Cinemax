<?php

namespace App\Queries;

use App\Data\ManagedReservationListItem;
use App\Models\Paiement;
use App\Models\Reservation;
use App\Models\Salle;
use App\Support\FilmPosterStorage;
use App\Support\ReservationPaymentStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ManagedReservationIndexQuery
{
    /**
     * @var list<string>
     */
    private const RESERVATION_STATUS_FILTERS = [
        Reservation::STATUS_PENDING,
        Reservation::STATUS_CONFIRMED,
        Reservation::STATUS_CANCELLED,
        Reservation::STATUS_EXPIRED,
    ];

    /**
     * @var list<string>
     */
    private const PAYMENT_STATUS_FILTERS = [
        Paiement::STATUS_PENDING,
        Paiement::STATUS_COMPLETED,
        Paiement::STATUS_FAILED,
    ];

    /**
     * @var list<string>
     */
    private const PERIOD_FILTERS = [
        'today',
        'week',
        'month',
    ];

    public function __construct(
        private FilmPosterStorage $posters,
    ) {}

    public function normalizeReservationStatus(string $status): string
    {
        return in_array($status, self::RESERVATION_STATUS_FILTERS, true) ? $status : '';
    }

    public function normalizePaymentStatus(string $status): string
    {
        return in_array($status, self::PAYMENT_STATUS_FILTERS, true) ? $status : '';
    }

    public function normalizePeriod(string $period): string
    {
        return in_array($period, self::PERIOD_FILTERS, true) ? $period : '';
    }

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginate(
        string $search,
        string $reservationStatus,
        string $paymentStatus,
        ?int $salleId,
        ?string $reservedDate,
        ?string $seanceDate,
        string $period,
    ): LengthAwarePaginator {
        $reservationStatus = $this->normalizeReservationStatus($reservationStatus);
        $paymentStatus = $this->normalizePaymentStatus($paymentStatus);
        $period = $this->normalizePeriod($period);

        $query = Reservation::query()
            ->with([
                'user',
                'paiements',
                'seance.film' => fn ($relation) => $relation->withTrashed(),
                'seance.salle' => fn ($relation) => $relation->withTrashed(),
            ])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('reference', 'like', "%{$search}%")
                        ->orWhere('contact_name', 'like', "%{$search}%")
                        ->orWhere('contact_email', 'like', "%{$search}%")
                        ->orWhereHas('user', function (Builder $user) use ($search): void {
                            $user
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        })
                        ->orWhereHas('seance.film', function (Builder $film) use ($search): void {
                            $film->where('title', 'like', "%{$search}%");
                        });
                });
            })
            ->when(
                $reservationStatus !== '',
                fn (Builder $builder) => $builder->where('status', $reservationStatus),
            )
            ->when(
                $paymentStatus !== '',
                fn (Builder $builder) => ReservationPaymentStatus::applyFilter($builder, $paymentStatus),
            )
            ->when(
                $salleId !== null,
                fn (Builder $builder) => $builder->whereHas(
                    'seance',
                    fn (Builder $seance) => $seance->where('salle_id', $salleId),
                ),
            )
            ->when(
                $reservedDate !== null && $reservedDate !== '',
                fn (Builder $builder) => $builder->whereDate('reserved_at', $reservedDate),
            )
            ->when(
                $seanceDate !== null && $seanceDate !== '',
                fn (Builder $builder) => $builder->whereHas(
                    'seance',
                    fn (Builder $seance) => $seance->whereDate('start_at', $seanceDate),
                ),
            )
            ->when($period === 'today', fn (Builder $builder) => $builder->whereDate('reserved_at', Carbon::today()))
            ->when($period === 'week', fn (Builder $builder) => $builder->where('reserved_at', '>=', Carbon::now()->subDays(7)))
            ->when($period === 'month', fn (Builder $builder) => $builder->where('reserved_at', '>=', Carbon::now()->subDays(30)))
            ->orderByDesc('reserved_at');

        return $query
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Reservation $reservation): array => ManagedReservationListItem::fromModel($reservation, $this->posters));
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    public function salleOptions(): array
    {
        return Salle::query()
            ->notArchived()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Salle $salle): array => [
                'id' => $salle->id,
                'name' => $salle->name,
            ])
            ->all();
    }
}
