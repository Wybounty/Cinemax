<?php

namespace Database\Seeders;

use App\Models\Salle;
use Illuminate\Database\Seeder;

class SalleSeeder extends Seeder
{
    /**
     * @var list<array{name: string, description: string|null, capacity: int, status: string}>
     */
    private const SALLES = [
        [
            'name' => 'Salle Grand Écran',
            'description' => 'Notre plus grande salle, équipée pour le cinéma numérique et le son immersif.',
            'capacity' => 176,
            'status' => Salle::STATUS_ACTIVE,
        ],
        [
            'name' => 'Salle Lumière',
            'description' => 'Salle polyvalente au cœur du complexe.',
            'capacity' => 112,
            'status' => Salle::STATUS_ACTIVE,
        ],
        [
            'name' => 'Salle Prestige',
            'description' => 'Fauteuils inclinables et espacement généreux.',
            'capacity' => 72,
            'status' => Salle::STATUS_ACTIVE,
        ],
        [
            'name' => 'Salon VIP',
            'description' => 'Petite salle privative pour événements.',
            'capacity' => 32,
            'status' => Salle::STATUS_ACTIVE,
        ],
        [
            'name' => 'Salle Atelier',
            'description' => 'Projections scolaires — actuellement hors service.',
            'capacity' => 50,
            'status' => Salle::STATUS_INACTIVE,
        ],
    ];

    public function run(): void
    {
        foreach (self::SALLES as $data) {
            Salle::withTrashed()->updateOrCreate(
                ['name' => $data['name']],
                [
                    'description' => $data['description'],
                    'capacity' => $data['capacity'],
                    'status' => $data['status'],
                    'deleted_at' => null,
                ],
            );
        }
    }
}
