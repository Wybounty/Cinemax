<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['slug' => Role::SLUG_SUPER_ADMIN, 'name' => 'Super administrateur'],
            ['slug' => Role::SLUG_ADMIN, 'name' => 'Administrateur'],
            ['slug' => Role::SLUG_SPECTATEUR, 'name' => 'Spectateur'],
        ];

        foreach ($roles as $role) {
            Role::query()->updateOrCreate(
                ['slug' => $role['slug']],
                ['name' => $role['name']],
            );
        }
    }
}
