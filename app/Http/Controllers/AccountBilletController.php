<?php

namespace App\Http\Controllers;

use App\Data\AccountBilletListItem;
use App\Models\Billet;
use App\Support\BilletQrCodeGenerator;
use App\Support\FilmPosterStorage;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountBilletController extends Controller
{
    public function index(Request $request, FilmPosterStorage $posters): Response
    {
        $this->authorize('viewAny', Billet::class);

        $billets = Billet::query()
            ->whereHas('reservation', fn ($query) => $query->where('user_id', $request->user()->id))
            ->with(['reservation.seance.film'])
            ->orderByDesc('issued_at')
            ->get()
            ->map(fn (Billet $billet): array => AccountBilletListItem::fromModel($billet, $posters));

        return Inertia::render('account/Billets/Index', [
            'billets' => $billets,
        ]);
    }

    public function show(
        Request $request,
        Billet $billet,
        FilmPosterStorage $posters,
        BilletQrCodeGenerator $qrCodeGenerator,
    ): Response {
        $this->authorize('view', $billet);

        $billet->load(['reservation.seance.film', 'reservation.seance.salle']);

        $reservation = $billet->reservation;
        $seance = $reservation?->seance;
        $film = $seance?->film;
        $salle = $seance?->salle;

        return Inertia::render('account/Billets/Show', [
            'billet' => [
                ...AccountBilletListItem::fromModel($billet, $posters),
                'salle_name' => $salle?->name ?? '—',
                'qr_svg' => $qrCodeGenerator->svg($billet),
                'qr_payload' => $qrCodeGenerator->payload($billet),
            ],
            'film_title' => $film?->title ?? '—',
            'reservation_reference' => $reservation?->reference ?? '—',
        ]);
    }
}
