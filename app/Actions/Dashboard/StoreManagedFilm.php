<?php

namespace App\Actions\Dashboard;

use App\Models\Film;
use App\Support\FilmPosterStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class StoreManagedFilm
{
    public function __construct(
        private FilmPosterStorage $posters,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public function __invoke(array $validated): Film
    {
        return DB::transaction(function () use ($validated): Film {
            /** @var UploadedFile|null $poster */
            $poster = $validated['poster'] ?? null;
            $posterPath = $poster instanceof UploadedFile
                ? $this->posters->store($poster)
                : null;

            /** @var list<int> $genreIds */
            $genreIds = array_values(array_map('intval', $validated['genre_ids']));

            $film = Film::query()->create([
                'title' => $validated['title'],
                'synopsis' => $validated['synopsis'] ?? null,
                'poster' => $posterPath,
                'duration' => (int) $validated['duration'],
                'minimum_age' => isset($validated['minimum_age'])
                    ? (int) $validated['minimum_age']
                    : null,
                'published' => (bool) ($validated['published'] ?? false),
            ]);

            $film->genres()->sync($genreIds);

            return $film;
        });
    }
}
