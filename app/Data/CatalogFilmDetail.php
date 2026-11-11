<?php

namespace App\Data;

use App\Models\Film;
use App\Support\FilmPosterStorage;

class CatalogFilmDetail
{
    /**
     * @return array<string, mixed>
     */
    public static function fromModel(Film $film, FilmPosterStorage $posters): array
    {
        return [
            'id' => $film->id,
            'title' => $film->title,
            'synopsis' => $film->synopsis,
            'poster_url' => $posters->url($film->poster),
            'duration' => $film->duration,
            'duration_formatted' => __(':minutes min', ['minutes' => $film->duration]),
            'minimum_age' => $film->minimum_age,
            'minimum_age_label' => $film->minimum_age !== null
                ? __(':age+', ['age' => $film->minimum_age])
                : 'Tout public',
            'genres' => $film->genres
                ->map(fn ($genre): array => [
                    'id' => $genre->id,
                    'name' => $genre->name,
                ])
                ->values()
                ->all(),
        ];
    }
}
