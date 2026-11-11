<?php

namespace App\Http\Controllers;

use App\Queries\DashboardStatisticsQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardStatisticsQuery $statistics): Response
    {
        $user = $request->user();
        $isAdmin = $user->canAccessUserManagement();
        $period = $statistics->normalizePeriod($request->string('period')->toString());

        return Inertia::render('Dashboard', [
            'is_admin' => $isAdmin,
            'stats' => $isAdmin ? $statistics->build($period) : null,
            'period' => $period,
            'period_options' => [
                ['value' => 'today', 'label' => 'Aujourd’hui'],
                ['value' => '7d', 'label' => '7 derniers jours'],
                ['value' => '30d', 'label' => '30 derniers jours'],
                ['value' => 'month', 'label' => 'Ce mois-ci'],
                ['value' => 'year', 'label' => 'Cette année'],
            ],
        ]);
    }
}
