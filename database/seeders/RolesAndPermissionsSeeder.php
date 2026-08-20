<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * @var list<string>
     */
    private const array PERMISSIONS = [
        'users.view',
        'users.manage',
        'settings.manage',
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission);
        }

        Role::findOrCreate('admin')->syncPermissions(self::PERMISSIONS);
        Role::findOrCreate('member');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
