<?php

namespace App\Data;

use App\Models\Film;
use App\Support\FilmPosterStorage;

class CatalogFilmListItem
{
    /**
     * @return array<string, mixed>
     */
    public static function fromModel(Film $film, FilmPosterStorage $posters): array
    {
        return [
            'id' => $film->id,
            'title' => $film->title,
            'poster_url' => $posters->url($film->poster),
        ];
    }
}
