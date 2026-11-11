<?php

namespace App\Actions\Dashboard;

use App\Models\Seance;

class MarkCompletedSeances
{
    public function __invoke(): void
    {
        Seance::query()
            ->where('status', Seance::STATUS_SCHEDULED)
            ->where('end_at', '<', now())
            ->update(['status' => Seance::STATUS_COMPLETED]);
    }
}
