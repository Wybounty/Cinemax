<?php

namespace App\Queries;

use App\Data\ManagedSalleListItem;
use App\Models\Salle;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ManagedSalleIndexQuery
{
    /**
     * @var list<string>
     */
    private const STATUS_FILTERS = [
        'active',
        'inactive',
        'archived',
    ];

    public function normalizeStatus(string $status): string
    {
        return in_array($status, self::STATUS_FILTERS, true) ? $status : '';
    }

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginate(string $search, string $status): LengthAwarePaginator
    {
        $status = $this->normalizeStatus($status);

        $query = Salle::query()
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            });

        if ($status === 'archived') {
            $query->onlyTrashed();
        } else {
            $query->when(
                $status === 'active',
                fn (Builder $builder) => $builder->where('status', Salle::STATUS_ACTIVE),
            )->when(
                $status === 'inactive',
                fn (Builder $builder) => $builder->where('status', Salle::STATUS_INACTIVE),
            );
        }

        return $query
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Salle $salle): array => ManagedSalleListItem::fromModel($salle));
    }
}
