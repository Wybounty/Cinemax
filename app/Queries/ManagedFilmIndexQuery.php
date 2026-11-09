<?php

namespace App\Queries;

use App\Data\ManagedFilmListItem;
use App\Models\Film;
use App\Models\Genre;
use App\Support\FilmPosterStorage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ManagedFilmIndexQuery
{
    /**
     * @var list<string>
     */
    private const STATUS_FILTERS = [
        'published',
        'unpublished',
        'archived',
    ];

    public function normalizeStatus(string $status): string
    {
        return in_array($status, self::STATUS_FILTERS, true) ? $status : '';
    }

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginate(
        string $search,
        string $status,
        ?int $genreId,
    ): LengthAwarePaginator {
        $status = $this->normalizeStatus($status);
        $posters = app(FilmPosterStorage::class);

        $query = Film::query()
            ->with('genres')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where('title', 'like', "%{$search}%");
            })
            ->when(
                $genreId !== null && Genre::query()->whereKey($genreId)->exists(),
                fn (Builder $query) => $query->whereHas(
                    'genres',
                    fn (Builder $genreQuery) => $genreQuery->where('genres.id', $genreId),
                ),
            );

        if ($status === 'archived') {
            $query->onlyTrashed();
        } else {
            $query->when(
                $status === 'published',
                fn (Builder $builder) => $builder->where('published', true),
            )->when(
                $status === 'unpublished',
                fn (Builder $builder) => $builder->where('published', false),
            );
        }

        return $query
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Film $film): array => ManagedFilmListItem::fromModel($film, $posters));
    }
}
