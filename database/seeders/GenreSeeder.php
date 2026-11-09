<?php

namespace Database\Seeders;

use App\Models\Genre;
use Illuminate\Database\Seeder;

class GenreSeeder extends Seeder
{
    /**
     * @var list<string>
     */
    private const GENRES = [
        'Action',
        'Aventure',
        'Animation',
        'Comédie',
        'Documentaire',
        'Drame',
        'Fantastique',
        'Horreur',
        'Policier',
        'Romance',
        'Science-fiction',
        'Thriller',
    ];

    public function run(): void
    {
        foreach (self::GENRES as $name) {
            Genre::query()->firstOrCreate(['name' => $name]);
        }
    }
}
