<?php

use App\Http\Controllers\CatalogFilmController;
use App\Http\Controllers\Dashboard\FilmController;
use App\Http\Controllers\Dashboard\UserController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('film', [CatalogFilmController::class, 'index'])->name('film');

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
    });
});

require __DIR__.'/settings.php';
