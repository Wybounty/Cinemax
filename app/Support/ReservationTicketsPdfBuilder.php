<?php

namespace App\Support;

use App\Models\Billet;
use App\Models\Reservation;
use Barryvdh\DomPDF\Facade\Pdf as PdfFacade;
use Barryvdh\DomPDF\PDF;
use Illuminate\Support\Facades\Storage;

class ReservationTicketsPdfBuilder
{
    public function __construct(
        private FilmPosterStorage $posters,
        private BilletQrCodeGenerator $qrCodeGenerator,
    ) {}

    /**
     * @return array{tickets: list<array<string, mixed>>, brand: string, logo_data_uri: string|null}
     */
    public function viewData(Reservation $reservation): array
    {
        $reservation->loadMissing([
            'billets' => fn ($query) => $query
                ->where('status', Billet::STATUS_VALID)
                ->orderBy('id'),
            'seance.film',
            'seance.salle',
        ]);

        $film = $reservation->seance?->film;
        $seance = $reservation->seance;
        $salle = $seance?->salle;
        $posterDataUri = $this->fileDataUri($film?->poster);

        $tickets = $reservation->billets->map(function (Billet $billet) use ($reservation, $film, $seance, $salle, $posterDataUri): array {
            $svg = $this->qrCodeGenerator->svg($billet, 180);

            return [
                'ticket_code' => $billet->ticket_code,
                'qr_data_uri' => 'data:image/svg+xml;base64,'.base64_encode($svg),
                'film_title' => $film?->title ?? '—',
                'poster_data_uri' => $posterDataUri,
                'seance_date' => $seance?->start_at->locale('fr')->translatedFormat('l j F Y') ?? '—',
                'seance_start' => $seance?->start_at->format('H:i') ?? '—',
                'seance_end' => $seance?->end_at->format('H:i') ?? '—',
                'salle_name' => $salle?->name ?? '—',
                'reference' => $reservation->reference,
            ];
        })->all();

        return [
            'brand' => config('app.name', 'Cinemax'),
            'logo_data_uri' => $this->logoDataUri(),
            'tickets' => $tickets,
        ];
    }

    public function pdf(Reservation $reservation): PDF
    {
        return PdfFacade::loadView('pdf.reservation-tickets', $this->viewData($reservation))
            ->setPaper('a4', 'portrait');
    }

    public function output(Reservation $reservation): string
    {
        return $this->pdf($reservation)->output();
    }

    public function filename(Reservation $reservation): string
    {
        return 'billets-'.$reservation->reference.'.pdf';
    }

    private function fileDataUri(?string $path): ?string
    {
        if ($path === null || $path === '' || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $contents = Storage::disk('public')->get($path);
        $mime = Storage::disk('public')->mimeType($path) ?: 'image/jpeg';

        return 'data:'.$mime.';base64,'.base64_encode($contents);
    }

    private function logoDataUri(): ?string
    {
        $path = public_path('favicon.svg');

        if (! is_readable($path)) {
            return null;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        return 'data:image/svg+xml;base64,'.base64_encode($contents);
    }
}
