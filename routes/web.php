<?php

use App\Http\Controllers\CatalogFilmController;
use App\Http\Controllers\Dashboard\FilmController;
use App\Http\Controllers\Dashboard\SalleController;
use App\Http\Controllers\Dashboard\SeanceController;
use App\Http\Controllers\Dashboard\UserController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('film', [CatalogFilmController::class, 'index'])->name('film');
Route::get('film/{film}', [CatalogFilmController::class, 'show'])->name('film.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::middleware(['admin'])->prefix('dashboard')->group(function () {

        // Users
        Route::get('utilisateurs', [UserController::class, 'index'])->name('dashboard.users.index');
        Route::post('utilisateurs', [UserController::class, 'store'])->name('dashboard.users.store');
        Route::put('utilisateurs/{user}', [UserController::class, 'update'])->name('dashboard.users.update');
        Route::delete('utilisateurs/{user}', [UserController::class, 'destroy'])->name('dashboard.users.destroy');

        // Films
        Route::get('films', [FilmController::class, 'index'])->name('dashboard.films.index');
        Route::post('films', [FilmController::class, 'store'])->name('dashboard.films.store');
        Route::put('films/{film}', [FilmController::class, 'update'])->name('dashboard.films.update');
        Route::delete('films/{film}', [FilmController::class, 'destroy'])->name('dashboard.films.destroy');
        Route::patch('films/{film}/publish', [FilmController::class, 'publish'])->name('dashboard.films.publish');
        Route::patch('films/{film}/unpublish', [FilmController::class, 'unpublish'])->name('dashboard.films.unpublish');

        Route::get('seances', [SeanceController::class, 'index'])->name('dashboard.seances.index');
        Route::post('seances', [SeanceController::class, 'store'])->name('dashboard.seances.store');
        Route::put('seances/{seance}', [SeanceController::class, 'update'])->name('dashboard.seances.update');
        Route::delete('seances/{seance}', [SeanceController::class, 'destroy'])->name('dashboard.seances.destroy');

        Route::get('salles', [SalleController::class, 'index'])->name('dashboard.salles.index');
        Route::post('salles', [SalleController::class, 'store'])->name('dashboard.salles.store');
        Route::put('salles/{salle}', [SalleController::class, 'update'])->name('dashboard.salles.update');
        Route::delete('salles/{salle}', [SalleController::class, 'destroy'])->name('dashboard.salles.destroy');
        Route::patch('salles/{salle}/activate', [SalleController::class, 'activate'])->name('dashboard.salles.activate');
        Route::patch('salles/{salle}/deactivate', [SalleController::class, 'deactivate'])->name('dashboard.salles.deactivate');
    });
});

require __DIR__.'/settings.php';
