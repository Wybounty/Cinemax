<?php

namespace App\Http\Controllers;

use App\Actions\Dashboard\MarkCompletedSeances;
use App\Actions\Reservations\ExpirePendingReservations;
use App\Data\CatalogFilmDetail;
use App\Data\CatalogFilmListItem;
use App\Data\CatalogSeanceListItem;
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

    public function show(
        Film $film,
        FilmPosterStorage $posters,
        MarkCompletedSeances $markCompletedSeances,
        ExpirePendingReservations $expirePendingReservations,
    ): Response {
        if (! $film->published || $film->trashed()) {
            abort(404);
        }

        $markCompletedSeances();
        $expirePendingReservations();

        $film->load(['genres' => fn ($query) => $query->orderBy('name')]);

        $seances = $film->seances()
            ->bookable()
            ->with(['salle' => fn ($query) => $query->withTrashed()])
            ->orderBy('start_at')
            ->get()
            ->map(fn ($seance): array => CatalogSeanceListItem::fromModel($seance));

        return Inertia::render('FilmShow', [
            'film' => CatalogFilmDetail::fromModel($film, $posters),
            'seances' => $seances,
        ]);
    }
}
