<?php

namespace App\Queries;

use App\Data\ManagedSeanceListItem;
use App\Models\Seance;
use App\Support\FilmPosterStorage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ManagedSeanceIndexQuery
{
    /**
     * @var list<string>
     */
    private const STATUS_FILTERS = [
        Seance::STATUS_SCHEDULED,
        Seance::STATUS_CANCELLED,
        Seance::STATUS_COMPLETED,
    ];

    /**
     * @var list<string>
     */
    private const PERIOD_FILTERS = [
        'today',
        'upcoming',
    ];

    public function __construct(
        private FilmPosterStorage $posters,
    ) {}

    public function normalizeStatus(string $status): string
    {
        return in_array($status, self::STATUS_FILTERS, true) ? $status : '';
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
        string $status,
        ?int $salleId,
        ?string $date,
        string $period,
    ): LengthAwarePaginator {
        $status = $this->normalizeStatus($status);
        $period = $this->normalizePeriod($period);

        $query = Seance::query()
            ->with([
                'film' => fn ($relation) => $relation->withTrashed(),
                'salle' => fn ($relation) => $relation->withTrashed(),
            ])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->whereHas('film', function (Builder $builder) use ($search): void {
                    $builder->where('title', 'like', "%{$search}%");
                });
            })
            ->when($salleId !== null, fn (Builder $builder) => $builder->where('salle_id', $salleId))
            ->when(
                $date !== null && $date !== '',
                fn (Builder $builder) => $builder->whereDate('start_at', $date),
            )
            ->when($status !== '', fn (Builder $builder) => $builder->where('status', $status))
            ->when($period === 'today', function (Builder $builder): void {
                $builder->whereDate('start_at', Carbon::today());
            })
            ->when($period === 'upcoming', function (Builder $builder): void {
                $builder
                    ->where('start_at', '>=', now())
                    ->where('status', Seance::STATUS_SCHEDULED);
            });

        if ($period === 'today' || $period === 'upcoming') {
            $query->orderBy('start_at');
        } else {
            $query->orderByDesc('start_at');
        }

        return $query
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Seance $seance): array => ManagedSeanceListItem::fromModel($seance, $this->posters));
    }
}
