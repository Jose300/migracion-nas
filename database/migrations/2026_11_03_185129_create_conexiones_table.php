<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('conexiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('session_id', 255)->nullable()->index();
            $table->string('ip_address', 45)->index();
            $table->string('user_agent', 500)->nullable();
            $table->string('dispositivo', 150)->nullable();
            $table->string('pais', 100)->default('Venezuela');
            $table->string('ciudad', 150)->default('Caracas');
            $table->string('codigo_pais', 10)->default('VE');
            $table->timestamp('login_at')->index();
            $table->timestamp('last_activity_at')->nullable()->index();
            $table->timestamp('logout_at')->nullable();
            $table->unsignedInteger('duracion_segundos')->default(0);
            $table->enum('status', ['Activa', 'Cerrada', 'Expirada', 'Forzada'])->default('Activa')->index();
            $table->string('origen_cierre', 150)->nullable();
            $table->timestamps();
        });

        // Register and assign permissions
        try {
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            $permissions = [
                [
                    'name' => 'ver-conexiones',
                    'module' => 'Conexiones',
                    'description' => 'Consultar registro de inicios de sesión y conexiones de usuarios',
                ],
                [
                    'name' => 'gestionar-conexiones',
                    'module' => 'Conexiones',
                    'description' => 'Desconectar y cerrar sesiones activas de usuarios remotamente',
                ],
                [
                    'name' => 'eliminar-conexiones',
                    'module' => 'Conexiones',
                    'description' => 'Eliminar registros y depurar historial de conexiones del sistema',
                ],
                [
                    'name' => 'descargar-excel-conexiones',
                    'module' => 'Conexiones',
                    'description' => 'Exportar reporte de conexiones y sesiones a Excel o CSV',
                ],
            ];

            foreach ($permissions as $permData) {
                Permission::firstOrCreate(
                    ['name' => $permData['name']],
                    [
                        'module' => $permData['module'],
                        'description' => $permData['description'],
                        'guard_name' => 'web',
                    ]
                );
            }

            $permNames = array_column($permissions, 'name');

            foreach (['Develop', 'Admin'] as $roleName) {
                $role = Role::where('name', $roleName)->first();
                if ($role) {
                    $role->givePermissionTo($permNames);
                }
            }
        } catch (\Throwable $e) {
            // Silencioso si ocurre algún inconveniente en la asignación
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conexiones');

        try {
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            Permission::whereIn('name', [
                'ver-conexiones',
                'gestionar-conexiones',
                'eliminar-conexiones',
                'descargar-excel-conexiones',
            ])->delete();
        } catch (\Throwable $e) {}
    }
};

