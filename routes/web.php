<?php

use App\Http\Controllers\Dashboard\UserController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::middleware(['admin'])->prefix('dashboard')->group(function () {

        // Users
        Route::get('utilisateurs', [UserController::class, 'index'])->name('dashboard.users.index');
        Route::post('utilisateurs', [UserController::class, 'store'])->name('dashboard.users.store');
        Route::put('utilisateurs/{user}', [UserController::class, 'update'])->name('dashboard.users.update');
        Route::delete('utilisateurs/{user}', [UserController::class, 'destroy'])->name('dashboard.users.destroy');
    });
});

require __DIR__.'/settings.php';
