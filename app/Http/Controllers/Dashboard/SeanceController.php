<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\Dashboard\MarkCompletedSeances;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StoreSeanceRequest;
use App\Http\Requests\Dashboard\UpdateSeanceRequest;
use App\Models\Film;
use App\Models\Salle;
use App\Models\Seance;
use App\Queries\ManagedSeanceIndexQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SeanceController extends Controller
{
    public function index(
        Request $request,
        ManagedSeanceIndexQuery $query,
        MarkCompletedSeances $markCompletedSeances,
    ): Response {
        $this->authorize('viewAny', Seance::class);

        $markCompletedSeances();

        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->trim()->toString();
        $period = $request->string('period')->trim()->toString();
        $date = $request->string('date')->trim()->toString();
        $salleId = $request->integer('salle') ?: null;

        $salles = Salle::query()
            ->notArchived()
            ->orderBy('name')
            ->get(['id', 'name', 'status']);

        $films = Film::query()
            ->where('published', true)
            ->orderBy('title')
            ->get(['id', 'title', 'duration']);

        return Inertia::render('dashboard/seances/Index', [
            'seances' => $query->paginate($search, $status, $salleId, $date !== '' ? $date : null, $period),
            'filters' => [
                'search' => $search,
                'status' => $query->normalizeStatus($status),
                'salle' => $salleId !== null && $salles->contains('id', $salleId)
                    ? (string) $salleId
                    : '',
                'date' => $date,
                'period' => $query->normalizePeriod($period),
            ],
            'salleOptions' => $salles->map(fn (Salle $salle): array => [
                'id' => $salle->id,
                'name' => $salle->name,
                'status' => $salle->status,
            ]),
            'filmOptions' => $films->map(fn (Film $film): array => [
                'id' => $film->id,
                'title' => $film->title,
                'duration' => $film->duration,
            ]),
            'statusOptions' => [
                ['value' => Seance::STATUS_SCHEDULED, 'label' => 'Programmée'],
                ['value' => Seance::STATUS_CANCELLED, 'label' => 'Annulée'],
                ['value' => Seance::STATUS_COMPLETED, 'label' => 'Terminée'],
            ],
            'cleaningMinutes' => Seance::CLEANING_MINUTES,
        ]);
    }

    public function store(StoreSeanceRequest $request): RedirectResponse
    {
        Seance::query()->create($request->seancePayload());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Séance programmée avec succès.'),
        ]);

        return redirect()->route('dashboard.seances.index');
    }

    public function update(UpdateSeanceRequest $request, Seance $seance): RedirectResponse
    {
        $seance->update($request->seancePayload());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Séance mise à jour avec succès.'),
        ]);

        return redirect()->route('dashboard.seances.index');
    }

    public function destroy(Seance $seance): RedirectResponse
    {
        $this->authorize('delete', $seance);

        if ($seance->hasReservations()) {
            throw ValidationException::withMessages([
                'seance' => __('Impossible de supprimer cette séance : des réservations sont associées. Annulez-la pour la conserver.'),
            ]);
        }

        $seance->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Séance supprimée.'),
        ]);

        return redirect()->route('dashboard.seances.index');
    }
}
