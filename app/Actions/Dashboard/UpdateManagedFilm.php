<?php

namespace App\Actions\Dashboard;

use App\Models\Film;
use App\Support\FilmPosterStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class UpdateManagedFilm
{
    public function __construct(
        private FilmPosterStorage $posters,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public function __invoke(Film $film, array $validated): Film
    {
        return DB::transaction(function () use ($film, $validated): Film {
            /** @var UploadedFile|null $poster */
            $poster = $validated['poster'] ?? null;

            if ($poster instanceof UploadedFile) {
                $this->posters->delete($film->poster);
                $film->poster = $this->posters->store($poster);
            }

            /** @var list<int> $genreIds */
            $genreIds = array_values(array_map('intval', $validated['genre_ids']));

            $film->fill([
                'title' => $validated['title'],
                'synopsis' => $validated['synopsis'] ?? null,
                'duration' => (int) $validated['duration'],
                'minimum_age' => isset($validated['minimum_age'])
                    ? (int) $validated['minimum_age']
                    : null,
            ]);

            if (array_key_exists('published', $validated)) {
                $film->published = (bool) $validated['published'];
            }

            $film->save();
            $film->genres()->sync($genreIds);

            return $film;
        });
    }
}
