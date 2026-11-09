<?php

namespace App\Http\Controllers;

use App\Data\CatalogFilmListItem;
use App\Models\Film;
use App\Support\FilmPosterStorage;
use Inertia\Inertia;
use Inertia\Response;

class CatalogFilmController extends Controller
{
    public function index(FilmPosterStorage $posters): Response
    {
        $films = Film::query()
            ->forCatalog()
            ->orderBy('title')
            ->get()
            ->map(fn (Film $film): array => CatalogFilmListItem::fromModel($film, $posters));

        return Inertia::render('Films', [
            'films' => $films,
        ]);
    }
}
