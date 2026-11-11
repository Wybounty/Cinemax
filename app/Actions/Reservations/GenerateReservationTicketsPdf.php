<?php

namespace App\Actions\Reservations;

use App\Models\Reservation;
use App\Support\ReservationTicketsPdfBuilder;
use Illuminate\Http\Response;

class GenerateReservationTicketsPdf
{
    public function __construct(
        private ReservationTicketsPdfBuilder $pdfBuilder,
    ) {}

    public function __invoke(Reservation $reservation): Response
    {
        return $this->pdfBuilder
            ->pdf($reservation)
            ->download($this->pdfBuilder->filename($reservation));
    }
}
