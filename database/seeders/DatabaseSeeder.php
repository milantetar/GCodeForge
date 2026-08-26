<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);
        $this->call(CmsPageSeeder::class);

        if (app()->isLocal()) {
            $user = User::query()->firstOrCreate(
                ['email' => 'test@example.com'],
                [
                    'name' => 'Test User',
                    'password' => 'password',
                    'email_verified_at' => now(),
                ],
            );

            $user->syncRoles(['user']);

            $admin = User::query()->firstOrCreate(
                ['email' => 'admin@example.com'],
                [
                    'name' => 'Local Administrator',
                    'password' => 'password',
                    'email_verified_at' => now(),
                ],
            );

            $admin->syncRoles(['admin']);
        }
    }
}
