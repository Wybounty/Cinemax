<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\Dashboard\ArchiveManagedSalle;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\StoreSalleRequest;
use App\Http\Requests\Dashboard\UpdateSalleRequest;
use App\Models\Salle;
use App\Queries\ManagedSalleIndexQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SalleController extends Controller
{
    public function index(Request $request, ManagedSalleIndexQuery $query): Response
    {
        $this->authorize('viewAny', Salle::class);

        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->trim()->toString();

        return Inertia::render('dashboard/salles/Index', [
            'salles' => $query->paginate($search, $status),
            'filters' => [
                'search' => $search,
                'status' => $query->normalizeStatus($status),
            ],
            'statusOptions' => [
                ['value' => Salle::STATUS_ACTIVE, 'label' => 'Active'],
                ['value' => Salle::STATUS_INACTIVE, 'label' => 'Inactive'],
            ],
        ]);
    }

    public function store(StoreSalleRequest $request): RedirectResponse
    {
        Salle::query()->create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Salle créée avec succès.'),
        ]);

        return redirect()->route('dashboard.salles.index');
    }

    public function update(UpdateSalleRequest $request, Salle $salle): RedirectResponse
    {
        $salle->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Salle mise à jour avec succès.'),
        ]);

        return redirect()->route('dashboard.salles.index');
    }

    public function destroy(
        Request $request,
        Salle $salle,
        ArchiveManagedSalle $archiveManagedSalle,
    ): RedirectResponse {
        $this->authorize('delete', $salle);

        $archiveManagedSalle($salle, $request->boolean('force'));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Salle archivée avec succès.'),
        ]);

        return redirect()->route('dashboard.salles.index');
    }

    public function activate(Salle $salle): RedirectResponse
    {
        $this->authorize('update', $salle);

        $salle->update(['status' => Salle::STATUS_ACTIVE]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Salle activée.'),
        ]);

        return redirect()->back();
    }

    public function deactivate(Salle $salle): RedirectResponse
    {
        $this->authorize('update', $salle);

        $salle->update(['status' => Salle::STATUS_INACTIVE]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Salle désactivée.'),
        ]);

        return redirect()->back();
    }
}
