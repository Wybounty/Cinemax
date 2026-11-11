<?php

namespace App\Queries;

use App\Models\Billet;
use App\Models\Film;
use App\Models\Paiement;
use App\Models\Reservation;
use App\Models\Salle;
use App\Models\Seance;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class DashboardStatisticsQuery
{
    /**
     * @var list<string>
     */
    private const PERIODS = ['today', '7d', '30d', 'month', 'year'];

    public function normalizePeriod(string $period): string
    {
        return in_array($period, self::PERIODS, true) ? $period : '30d';
    }

    /**
     * @return array{start: CarbonInterface, end: CarbonInterface, label: string}
     */
    public function dateRange(string $period): array
    {
        $period = $this->normalizePeriod($period);
        $end = now()->endOfDay();

        $start = match ($period) {
            'today' => now()->startOfDay(),
            '7d' => now()->subDays(6)->startOfDay(),
            '30d' => now()->subDays(29)->startOfDay(),
            'month' => now()->startOfMonth(),
            'year' => now()->startOfYear(),
            default => now()->subDays(29)->startOfDay(),
        };

        $label = match ($period) {
            'today' => 'Aujourd’hui',
            '7d' => '7 derniers jours',
            '30d' => '30 derniers jours',
            'month' => 'Ce mois-ci',
            'year' => 'Cette année',
            default => '30 derniers jours',
        };

        return ['start' => $start, 'end' => $end, 'label' => $label];
    }

    /**
     * @return array<string, mixed>
     */
    public function build(string $period): array
    {
        $period = $this->normalizePeriod($period);
        $range = $this->dateRange($period);
        $start = $range['start'];
        $end = $range['end'];

        $cards = [
            'films_total' => Film::withTrashed()->count(),
            'films_active' => Film::query()->where('published', true)->count(),
            'seances_total' => Seance::query()->where('status', Seance::STATUS_SCHEDULED)->count(),
            'seances_upcoming' => Seance::query()
                ->where('status', Seance::STATUS_SCHEDULED)
                ->where('start_at', '>=', now())
                ->count(),
            'salles_available' => Salle::query()->notArchived()->where('status', Salle::STATUS_ACTIVE)->count(),
            'reservations_total' => Reservation::query()
                ->whereBetween('reserved_at', [$start, $end])
                ->count(),
            'reservations_confirmed' => Reservation::query()
                ->where('status', Reservation::STATUS_CONFIRMED)
                ->whereBetween('reserved_at', [$start, $end])
                ->count(),
            'tickets_sold' => Billet::query()
                ->where('status', '!=', Billet::STATUS_CANCELLED)
                ->whereHas(
                    'reservation',
                    fn ($query) => $query
                        ->where('status', Reservation::STATUS_CONFIRMED)
                        ->whereBetween('reserved_at', [$start, $end]),
                )
                ->count(),
            'revenue' => (float) Paiement::query()
                ->where('status', Paiement::STATUS_COMPLETED)
                ->whereBetween('paid_at', [$start, $end])
                ->sum('amount'),
            'revenue_formatted' => number_format(
                (float) Paiement::query()
                    ->where('status', Paiement::STATUS_COMPLETED)
                    ->whereBetween('paid_at', [$start, $end])
                    ->sum('amount'),
                2,
                ',',
                ' ',
            ).' €',
        ];

        $series = $this->dailySeries($start, $end);

        return [
            'period' => $period,
            'period_label' => $range['label'],
            'cards' => $cards,
            'charts' => [
                'reservations_per_day' => $series['reservations'],
                'tickets_per_day' => $series['tickets'],
                'revenue_per_day' => $series['revenue'],
                'average_fill_rate_percent' => $this->averageFillRatePercent($start, $end),
            ],
            'recent_reservations' => $this->recentReservations(),
        ];
    }

    /**
     * @return array{reservations: list<array{date: string, label: string, value: int}>, tickets: list<array{date: string, label: string, value: int}>, revenue: list<array{date: string, label: string, value: float, value_formatted: string}>}
     */
    private function dailySeries(CarbonInterface $start, CarbonInterface $end): array
    {
        $days = $this->dayKeys($start, $end);

        $reservationRows = Reservation::query()
            ->selectRaw('DATE(reserved_at) as day, COUNT(*) as total')
            ->where('status', Reservation::STATUS_CONFIRMED)
            ->whereBetween('reserved_at', [$start, $end])
            ->groupBy('day')
            ->pluck('total', 'day');

        $ticketRows = Billet::query()
            ->selectRaw('DATE(billets.issued_at) as day, COUNT(*) as total')
            ->join('reservations', 'reservations.id', '=', 'billets.reservation_id')
            ->where('reservations.status', Reservation::STATUS_CONFIRMED)
            ->where('billets.status', '!=', Billet::STATUS_CANCELLED)
            ->whereBetween('billets.issued_at', [$start, $end])
            ->groupBy('day')
            ->pluck('total', 'day');

        $revenueRows = Paiement::query()
            ->selectRaw('DATE(paid_at) as day, SUM(amount) as total')
            ->where('status', Paiement::STATUS_COMPLETED)
            ->whereBetween('paid_at', [$start, $end])
            ->groupBy('day')
            ->pluck('total', 'day');

        $reservations = [];
        $tickets = [];
        $revenue = [];

        foreach ($days as $day) {
            $label = Carbon::parse($day)->locale('fr')->isoFormat('DD MMM');
            $reservations[] = [
                'date' => $day,
                'label' => $label,
                'value' => (int) ($reservationRows[$day] ?? 0),
            ];
            $tickets[] = [
                'date' => $day,
                'label' => $label,
                'value' => (int) ($ticketRows[$day] ?? 0),
            ];
            $amount = (float) ($revenueRows[$day] ?? 0);
            $revenue[] = [
                'date' => $day,
                'label' => $label,
                'value' => $amount,
                'value_formatted' => number_format($amount, 2, ',', ' ').' €',
            ];
        }

        return compact('reservations', 'tickets', 'revenue');
    }

    /**
     * @return list<string>
     */
    private function dayKeys(CarbonInterface $start, CarbonInterface $end): array
    {
        $days = [];
        $cursor = $start->copy()->startOfDay();

        while ($cursor->lte($end)) {
            $days[] = $cursor->toDateString();
            $cursor = $cursor->addDay();
        }

        return $days;
    }

    private function averageFillRatePercent(CarbonInterface $start, CarbonInterface $end): float
    {
        $seanceIds = Seance::query()
            ->whereBetween('start_at', [$start, $end])
            ->where('status', '!=', Seance::STATUS_CANCELLED)
            ->pluck('id');

        if ($seanceIds->isEmpty()) {
            return 0.0;
        }

        $capacity = (int) Seance::query()
            ->whereIn('seances.id', $seanceIds)
            ->join('salles', 'salles.id', '=', 'seances.salle_id')
            ->sum('salles.capacity');

        if ($capacity === 0) {
            return 0.0;
        }

        $soldSeats = (int) Billet::query()
            ->join('reservations', 'reservations.id', '=', 'billets.reservation_id')
            ->whereIn('reservations.seance_id', $seanceIds)
            ->where('reservations.status', Reservation::STATUS_CONFIRMED)
            ->where('billets.status', '!=', Billet::STATUS_CANCELLED)
            ->count();

        return round(($soldSeats / $capacity) * 100, 1);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recentReservations(): array
    {
        return Reservation::query()
            ->with(['user', 'seance.film'])
            ->orderByDesc('reserved_at')
            ->limit(10)
            ->get()
            ->map(function (Reservation $reservation): array {
                $film = $reservation->seance?->film;
                $seance = $reservation->seance;

                return [
                    'id' => $reservation->id,
                    'reference' => $reservation->reference,
                    'client' => $reservation->contact_name
                        ?: $reservation->user?->name
                        ?: '—',
                    'email' => $reservation->contact_email
                        ?: $reservation->guest_email
                        ?: $reservation->user?->email
                        ?: '—',
                    'film_title' => $film?->title ?? '—',
                    'seance_label' => $seance
                        ? $seance->start_at->locale('fr')->translatedFormat('d M Y H:i')
                        : '—',
                    'tickets' => $reservation->quantity,
                    'total_formatted' => number_format((float) $reservation->total_amount, 2, ',', ' ').' €',
                    'status' => $reservation->status,
                    'status_label' => match ($reservation->status) {
                        Reservation::STATUS_PENDING => 'En attente',
                        Reservation::STATUS_CONFIRMED => 'Confirmée',
                        Reservation::STATUS_CANCELLED => 'Annulée',
                        Reservation::STATUS_EXPIRED => 'Expirée',
                        default => $reservation->status,
                    },
                ];
            })
            ->all();
    }
}
