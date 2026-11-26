<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permission = Permission::updateOrCreate(
            ['name' => 'reubicar-orbitas-pendientes-nas'],
            [
                'module' => 'Migración NAS',
                'description' => 'Ejecutar traslado masivo de órbitas pendientes (rescate local y compartida intermedia) hacia Inspur RAWDATA final',
                'guard_name' => 'web',
            ]
        );

        foreach (['Develop', 'Admin'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo($permission);
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        Permission::where('name', 'reubicar-orbitas-pendientes-nas')->delete();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
