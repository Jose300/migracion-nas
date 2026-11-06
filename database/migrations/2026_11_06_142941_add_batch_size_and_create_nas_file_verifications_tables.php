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
        // 1. Agregar batch_size a nas_migration_tasks si no existe
        if (!Schema::hasColumn('nas_migration_tasks', 'batch_size')) {
            Schema::table('nas_migration_tasks', function (Blueprint $table) {
                $table->unsignedInteger('batch_size')->default(500)->after('delete_after_sync');
            });
        }

        // 2. Tabla principal de sesiones de verificación y confirmación
        if (!Schema::hasTable('nas_file_verifications')) {
            Schema::create('nas_file_verifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('nas_migration_task_id')->constrained('nas_migration_tasks')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->enum('status', ['running', 'completed', 'failed', 'cancelled'])->default('running');
                $table->timestamp('started_at');
                $table->timestamp('finished_at')->nullable();
                $table->integer('duration_seconds')->nullable()->default(0);

                // Contadores globales
                $table->unsignedInteger('total_files_synology')->default(0);
                $table->unsignedInteger('total_files_inspur')->default(0);
                $table->unsignedInteger('total_confirmed')->default(0);
                $table->unsignedInteger('total_mismatched_size')->default(0);
                $table->unsignedInteger('total_mismatched_date')->default(0);
                $table->unsignedInteger('total_missing_in_target')->default(0);

                // Volúmenes de datos
                $table->unsignedBigInteger('total_bytes_synology')->default(0);
                $table->unsignedBigInteger('total_bytes_inspur')->default(0);

                $table->text('summary')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamps();
            });
        }

        // 3. Tabla de detalle de cada archivo comparado
        if (!Schema::hasTable('nas_file_verification_items')) {
            Schema::create('nas_file_verification_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('nas_file_verification_id')->constrained('nas_file_verifications')->cascadeOnDelete();
                $table->string('relative_path', 1000);
                $table->string('file_name', 255)->index();
                
                // Synology (Origen)
                $table->unsignedBigInteger('synology_size')->nullable();
                $table->timestamp('synology_mtime')->nullable();

                // Inspur (Destino)
                $table->unsignedBigInteger('inspur_size')->nullable();
                $table->timestamp('inspur_mtime')->nullable();

                // Estado de corroboración
                $table->enum('status', ['confirmed', 'size_mismatch', 'date_mismatch', 'missing_target', 'missing_source'])
                    ->default('confirmed')
                    ->index();
                
                $table->string('notes', 500)->nullable();
                $table->timestamps();

                // Índices compuestos para búsquedas rápidas con miles de archivos
                $table->index(['nas_file_verification_id', 'status'], 'idx_verify_status');
            });
        }

        // 4. Crear permisos para el módulo de Confirmación NAS
        try {
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            $newPermissions = [
                [
                    'name' => 'ver-confirmacion-nas',
                    'module' => 'Migración NAS',
                    'description' => 'Consultar el módulo de confirmación y cotejo de archivos entre NAS Synology e Inspur',
                ],
                [
                    'name' => 'ejecutar-confirmacion-nas',
                    'module' => 'Migración NAS',
                    'description' => 'Ejecutar procesos de verificación, comparación y simulación de archivos copiados',
                ],
            ];

            foreach ($newPermissions as $p) {
                Permission::firstOrCreate(
                    ['name' => $p['name']],
                    [
                        'module' => $p['module'],
                        'description' => $p['description'],
                        'guard_name' => 'web',
                    ]
                );
            }

            foreach (['Develop', 'Admin'] as $roleName) {
                $role = Role::where('name', $roleName)->first();
                if ($role) {
                    $role->givePermissionTo(array_column($newPermissions, 'name'));
                }
            }
        } catch (\Throwable $e) {
            // Silencioso si ocurre contingencia en ambiente de migración inicial
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nas_file_verification_items');
        Schema::dropIfExists('nas_file_verifications');

        if (Schema::hasColumn('nas_migration_tasks', 'batch_size')) {
            Schema::table('nas_migration_tasks', function (Blueprint $table) {
                $table->dropColumn('batch_size');
            });
        }

        try {
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            Permission::whereIn('name', ['ver-confirmacion-nas', 'ejecutar-confirmacion-nas'])->delete();
        } catch (\Throwable $e) {
            //
        }
    }
};
