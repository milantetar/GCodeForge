<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'gcode.use',
            'profile.update',
            'users.view',
            'users.update',
            'users.suspend',
            'users.delete',
            'roles.view',
            'roles.manage',
            'activity.view',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::findOrCreate('user', 'web')->syncPermissions([
            'gcode.use',
            'profile.update',
        ]);

        Role::findOrCreate('admin', 'web')->syncPermissions([
            'gcode.use',
            'profile.update',
            'users.view',
            'users.update',
            'users.suspend',
            'roles.view',
            'activity.view',
        ]);

        Role::findOrCreate('super-admin', 'web');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
