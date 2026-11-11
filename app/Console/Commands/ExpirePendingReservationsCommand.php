<?php

namespace App\Console\Commands;

use App\Actions\Reservations\ExpirePendingReservations;
use Illuminate\Console\Command;

class ExpirePendingReservationsCommand extends Command
{
    protected $signature = 'reservations:expire-pending';

    protected $description = 'Expire les réservations temporaires dont le délai est dépassé';

    public function handle(ExpirePendingReservations $expirePendingReservations): int
    {
        $count = $expirePendingReservations();

        $this->info("{$count} réservation(s) expirée(s).");

        return self::SUCCESS;
    }
}
