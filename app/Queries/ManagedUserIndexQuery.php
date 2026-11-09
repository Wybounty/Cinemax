<?php

namespace App\Queries;

use App\Data\ManagedUserListItem;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ManagedUserIndexQuery
{
    /**
     * @var list<string>
     */
    private const SORTABLE_COLUMNS = [
        'id',
        'name',
        'email',
        'created_at',
        'role',
    ];

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function normalizeSort(string $sort): string
    {
        return in_array($sort, self::SORTABLE_COLUMNS, true) ? $sort : 'created_at';
    }

    public function normalizeDirection(string $direction): string
    {
        return $direction === 'asc' ? 'asc' : 'desc';
    }

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginate(
        string $search,
        string $role,
        string $sort,
        string $direction,
    ): LengthAwarePaginator {
        $sort = $this->normalizeSort($sort);
        $direction = $this->normalizeDirection($direction);

        $query = User::query()
            ->with('role')
            ->where('is_active', true)
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when(
                in_array($role, Role::assignableFilterSlugs(), true),
                fn (Builder $query) => $query->whereHas(
                    'role',
                    fn (Builder $roleQuery) => $roleQuery->where('slug', $role),
                ),
            );

        if ($sort === 'role') {
            $query
                ->leftJoin('roles', 'users.role_id', '=', 'roles.id')
                ->orderBy('roles.slug', $direction)
                ->select('users.*');
        } else {
            $query->orderBy($sort, $direction);
        }

        return $query
            ->paginate(10)
            ->withQueryString()
            ->through(fn (User $user): array => ManagedUserListItem::fromModel($user));
    }
}
