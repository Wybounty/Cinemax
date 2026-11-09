<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\Dashboard\ArchiveManagedFilm;
use App\Actions\Dashboard\StoreManagedFilm;
use App\Actions\Dashboard\UpdateManagedFilm;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StoreFilmRequest;
use App\Http\Requests\Dashboard\UpdateFilmRequest;
use App\Models\Film;
use App\Models\Genre;
use App\Queries\ManagedFilmIndexQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FilmController extends Controller
{
    public function index(Request $request, ManagedFilmIndexQuery $query): Response
    {
        $this->authorize('viewAny', Film::class);

        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->trim()->toString();
        $genreId = $request->integer('genre') ?: null;

        $genres = Genre::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('dashboard/films/Index', [
            'films' => $query->paginate($search, $status, $genreId),
            'filters' => [
                'search' => $search,
                'status' => $query->normalizeStatus($status),
                'genre' => $genreId !== null && $genres->contains('id', $genreId)
                    ? (string) $genreId
                    : '',
            ],
            'filterGenres' => $genres->map(fn (Genre $genre): array => [
                'id' => $genre->id,
                'name' => $genre->name,
            ]),
        ]);
    }

    public function store(
        StoreFilmRequest $request,
        StoreManagedFilm $storeManagedFilm,
    ): RedirectResponse {
        $storeManagedFilm($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Film créé avec succès.'),
        ]);

        return redirect()->route('dashboard.films.index');
    }

    public function update(
        UpdateFilmRequest $request,
        Film $film,
        UpdateManagedFilm $updateManagedFilm,
    ): RedirectResponse {
        $updateManagedFilm($film, $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Film mis à jour avec succès.'),
        ]);

        return redirect()->route('dashboard.films.index');
    }

    public function destroy(
        Film $film,
        ArchiveManagedFilm $archiveManagedFilm,
    ): RedirectResponse {
        $this->authorize('delete', $film);

        $archiveManagedFilm($film);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Film archivé avec succès.'),
        ]);

        return redirect()->route('dashboard.films.index');
    }

    public function publish(Request $request, Film $film): RedirectResponse
    {
        $this->authorize('update', $film);

        $film->update(['published' => true]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Film publié.'),
        ]);

        return redirect()->route('dashboard.films.index');
    }

    public function unpublish(Request $request, Film $film): RedirectResponse
    {
        $this->authorize('update', $film);

        $film->update(['published' => false]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Film dépublié.'),
        ]);

        return redirect()->route('dashboard.films.index');
    }
}
