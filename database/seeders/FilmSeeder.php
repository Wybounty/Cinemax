<?php

namespace Database\Seeders;

use App\Models\Film;
use App\Models\Genre;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FilmSeeder extends Seeder
{
    /**
     * @var list<array{title: string, synopsis: string, duration: int, minimum_age: int|null, published: bool, genres: list<string>}>
     */
    private const FILMS = [
        [
            'title' => 'Les Lumières du port',
            'synopsis' => 'Une famille se retrouve au bord de la Méditerranée pour un été qui change tout.',
            'duration' => 118,
            'minimum_age' => 12,
            'published' => true,
            'genres' => ['Drame', 'Romance'],
        ],
        [
            'title' => 'Nova Express',
            'synopsis' => 'Un pilote de course doit empêcher une catastrophe sur les rails magnétiques de demain.',
            'duration' => 124,
            'minimum_age' => 10,
            'published' => true,
            'genres' => ['Action', 'Science-fiction'],
        ],
        [
            'title' => 'Le Manoir des murmures',
            'synopsis' => 'Des étudiants passent une nuit dans un manoir abandonné. Ils n\'y sont pas seuls.',
            'duration' => 97,
            'minimum_age' => 16,
            'published' => true,
            'genres' => ['Horreur', 'Thriller'],
        ],
        [
            'title' => 'Rire sous les étoiles',
            'synopsis' => 'Un humoriste en difficulté retrouve l\'inspiration grâce à une tournée de campagne inattendue.',
            'duration' => 105,
            'minimum_age' => null,
            'published' => true,
            'genres' => ['Comédie'],
        ],
        [
            'title' => 'Couronne d\'automne',
            'synopsis' => 'Dans un royaume en guerre, une princesse doit choisir entre le devoir et la paix.',
            'duration' => 132,
            'minimum_age' => 12,
            'published' => true,
            'genres' => ['Aventure', 'Fantastique'],
        ],
        [
            'title' => 'Sur la piste',
            'synopsis' => 'Un documentaire sensible sur les musiciens de rue qui font vibrer les villes européennes.',
            'duration' => 88,
            'minimum_age' => null,
            'published' => true,
            'genres' => ['Documentaire'],
        ],
        [
            'title' => 'Minuit à Belleville',
            'synopsis' => 'Un détective privé enquête sur la disparition d\'une chanteuse de jazz dans le Paris des années 50.',
            'duration' => 111,
            'minimum_age' => 12,
            'published' => true,
            'genres' => ['Policier', 'Drame'],
        ],
        [
            'title' => 'Le Petit Cosmos',
            'synopsis' => 'Une petite fille rêve d\'explorer l\'espace avec son grand-père inventeur.',
            'duration' => 92,
            'minimum_age' => 6,
            'published' => true,
            'genres' => ['Animation', 'Aventure'],
        ],
    ];

    public function run(): void
    {
        foreach (self::FILMS as $data) {
            $slug = Str::slug($data['title']);
            $existing = Film::withTrashed()->where('title', $data['title'])->first();
            $poster = $existing?->poster ?? $this->downloadPoster($slug);

            $film = Film::query()->updateOrCreate(
                ['title' => $data['title']],
                [
                    'synopsis' => $data['synopsis'],
                    'poster' => $poster,
                    'duration' => $data['duration'],
                    'minimum_age' => $data['minimum_age'],
                    'published' => $data['published'],
                    'deleted_at' => null,
                ],
            );

            $genreIds = Genre::query()
                ->whereIn('name', $data['genres'])
                ->pluck('id')
                ->all();

            $film->genres()->sync($genreIds);
        }
    }

    private function downloadPoster(string $slug): ?string
    {
        $path = "posters/seed-{$slug}.jpg";

        if (Storage::disk('public')->exists($path)) {
            return $path;
        }

        try {
            $response = Http::timeout(20)->get("https://picsum.photos/seed/cinemax-{$slug}/400/600.jpg");

            if ($response->successful()) {
                Storage::disk('public')->put($path, $response->body());

                return $path;
            }
        } catch (\Throwable) {
            //
        }

        return null;
    }
}
