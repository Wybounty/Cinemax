<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdminRole = Role::query()->where('slug', Role::SLUG_SUPER_ADMIN)->firstOrFail();
        $adminRole = Role::query()->where('slug', Role::SLUG_ADMIN)->firstOrFail();
        $spectateurRole = Role::query()->where('slug', Role::SLUG_SPECTATEUR)->firstOrFail();

        User::query()->updateOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role_id' => $superAdminRole->id,
                'is_active' => true,
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin Grand Cinéma',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role_id' => $adminRole->id,
                'is_active' => true,
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'spectateur@example.com'],
            [
                'name' => 'Jean Spectateur',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role_id' => $spectateurRole->id,
                'is_active' => true,
            ],
        );
    }
}
