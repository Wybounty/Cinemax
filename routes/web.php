<?php

use App\Http\Controllers\AccountBilletController;
use App\Http\Controllers\CatalogFilmController;
use App\Http\Controllers\Dashboard\FilmController;
use App\Http\Controllers\Dashboard\MyReservationController;
use App\Http\Controllers\Dashboard\ReservationController as DashboardReservationController;
use App\Http\Controllers\Dashboard\SalleController;
use App\Http\Controllers\Dashboard\SeanceController;
use App\Http\Controllers\Dashboard\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\SeanceAvailabilityController;
use App\Models\Reservation;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('film', [CatalogFilmController::class, 'index'])->name('film');
Route::get('film/{film}', [CatalogFilmController::class, 'show'])->name('film.show');
Route::get('seances/{seance}/disponibilite', [SeanceAvailabilityController::class, 'show'])
    ->name('seances.availability');

Route::post('reservations', [ReservationController::class, 'store'])->name('reservations.store');
Route::get('reservations/{reservation}/paiement', [ReservationController::class, 'checkout'])
    ->name('reservations.checkout');
Route::post('reservations/{reservation}/paiement', [ReservationController::class, 'pay'])
    ->name('reservations.payment');
Route::get('reservations/{reservation}/confirmation', [ReservationController::class, 'confirmed'])
    ->name('reservations.confirmed');
Route::post('reservations/{reservation}/annuler', [ReservationController::class, 'cancel'])
    ->name('reservations.cancel');
Route::get('reservations/{reservation}/billets.pdf', [ReservationController::class, 'downloadTickets'])
    ->name('reservations.download-tickets');
Route::post('reservations/{reservation}/renvoyer-billets', [ReservationController::class, 'resendTickets'])
    ->name('reservations.resend-tickets');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('mes-reservations', '/dashboard/mes-reservations')->name('account.reservations.index');
    Route::get('mes-reservations/{reservation}', function (Reservation $reservation) {
        return redirect()->route('dashboard.my-reservations.show', $reservation);
    })->name('account.reservations.show');

    Route::prefix('dashboard')->group(function () {
        Route::get('mes-reservations', [MyReservationController::class, 'index'])
            ->name('dashboard.my-reservations.index');
        Route::get('mes-reservations/{reservation}', [MyReservationController::class, 'show'])
            ->name('dashboard.my-reservations.show');
        Route::get('mes-reservations/{reservation}/billets.pdf', [MyReservationController::class, 'downloadTickets'])
            ->name('dashboard.my-reservations.download-tickets');
    });

    Route::get('mes-billets', [AccountBilletController::class, 'index'])->name('account.billets.index');
    Route::get('mes-billets/{billet}', [AccountBilletController::class, 'show'])->name('account.billets.show');

    Route::get('dashboard', DashboardController::class)->name('dashboard');

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

        Route::get('reservations', [DashboardReservationController::class, 'index'])->name('dashboard.reservations.index');
        Route::get('reservations/{reservation}', [DashboardReservationController::class, 'show'])->name('dashboard.reservations.show');
        Route::post('reservations/{reservation}/annuler', [DashboardReservationController::class, 'cancel'])->name('dashboard.reservations.cancel');

        Route::get('salles', [SalleController::class, 'index'])->name('dashboard.salles.index');
        Route::post('salles', [SalleController::class, 'store'])->name('dashboard.salles.store');
        Route::put('salles/{salle}', [SalleController::class, 'update'])->name('dashboard.salles.update');
        Route::delete('salles/{salle}', [SalleController::class, 'destroy'])->name('dashboard.salles.destroy');
        Route::patch('salles/{salle}/activate', [SalleController::class, 'activate'])->name('dashboard.salles.activate');
        Route::patch('salles/{salle}/deactivate', [SalleController::class, 'deactivate'])->name('dashboard.salles.deactivate');
    });
});

require __DIR__.'/settings.php';
