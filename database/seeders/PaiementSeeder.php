<?php

namespace Database\Seeders;

use App\Models\Paiement;
use App\Models\Reservation;
use Illuminate\Database\Seeder;

class PaiementSeeder extends Seeder
{
    public function run(): void
    {
        $reservations = Reservation::query()
            ->where('reference', 'like', 'DEMO-R-%')
            ->orderBy('reference')
            ->get();

        foreach ($reservations as $reservation) {
            $this->seedPrimaryPaiement($reservation);

            if (in_array($reservation->status, [Reservation::STATUS_CANCELLED, Reservation::STATUS_EXPIRED], true)) {
                $this->seedFailedAttempt($reservation);
            }
        }
    }

    private function seedPrimaryPaiement(Reservation $reservation): void
    {
        $reference = 'DEMO-P-'.$reservation->reference;

        [$status, $paidAt, $transactionRef] = match ($reservation->status) {
            Reservation::STATUS_CONFIRMED => [
                Paiement::STATUS_COMPLETED,
                $reservation->reserved_at->copy()->addMinutes(5),
                'SIM-'.$reservation->reference,
            ],
            Reservation::STATUS_PENDING => [
                Paiement::STATUS_PENDING,
                null,
                null,
            ],
            default => [
                Paiement::STATUS_FAILED,
                null,
                'SIM-FAIL-'.$reservation->reference,
            ],
        };

        Paiement::query()->updateOrCreate(
            ['transaction_reference' => $transactionRef ?? $reference],
            [
                'reservation_id' => $reservation->id,
                'amount' => $reservation->total_amount,
                'method' => Paiement::METHOD_SIMULATION,
                'status' => $status,
                'paid_at' => $paidAt,
            ],
        );
    }

    private function seedFailedAttempt(Reservation $reservation): void
    {
        $transactionRef = 'SIM-FAIL-RETRY-'.$reservation->reference;

        if (Paiement::query()->where('transaction_reference', $transactionRef)->exists()) {
            return;
        }

        Paiement::query()->create([
            'reservation_id' => $reservation->id,
            'amount' => $reservation->total_amount,
            'method' => Paiement::METHOD_SIMULATION,
            'status' => Paiement::STATUS_FAILED,
            'transaction_reference' => $transactionRef,
            'paid_at' => null,
        ]);
    }
}
