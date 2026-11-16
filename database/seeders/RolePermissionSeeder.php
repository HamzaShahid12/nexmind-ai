<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public const PERMISSIONS = [
        'manage users', 'manage ai tools', 'manage plans', 'manage credits',
        'manage content', 'view analytics', 'manage settings',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $name) {
            Permission::findOrCreate($name, 'web');
        }

        Role::findOrCreate('user', 'web');
        Role::findOrCreate('admin', 'web')->syncPermissions(array_diff(self::PERMISSIONS, ['manage settings']));
        Role::findOrCreate('super_admin', 'web')->syncPermissions(self::PERMISSIONS);
    }
}
