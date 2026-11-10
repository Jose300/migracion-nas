<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LoginController::class, 'showLoginForm'])->name('login')->middleware('guest');

// Ruta fallback pública para servir archivos de storage directamente por PHP
// garantizando visualización si Apache/Nginx no tiene habilitado FollowSymLinks o Junctions en Windows/Linux
Route::get('/storage/{path}', function (string $path) {
    $fullPath = storage_path('app/public/' . $path);
    if (!file_exists($fullPath) || is_dir($fullPath)) {
        abort(404);
    }
    return response()->file($fullPath);
})->where('path', '.*')->name('storage.serve');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm']);
    Route::post('/', [LoginController::class, 'login']);
    Route::post('/login', [LoginController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Perfil de usuario
    Route::get('/perfil', [App\Http\Controllers\ProfileController::class, 'index'])->name('perfil.index');
    Route::put('/perfil', [App\Http\Controllers\ProfileController::class, 'update'])->name('perfil.update');

    // Configuración
    Route::prefix('configuracion')->group(function () {
        // Usuarios
        Route::resource('usuarios', App\Http\Controllers\Config\UsersController::class)
            ->names('usuarios')
            ->middleware([
                'index'   => 'permission:ver-usuarios',
                'show'    => 'permission:ver-usuarios',
                'create'  => 'permission:crear-usuarios',
                'store'   => 'permission:crear-usuarios',
                'edit'    => 'permission:editar-usuarios',
                'update'  => 'permission:editar-usuarios',
                'destroy' => 'permission:eliminar-usuarios',
            ]);

        // Roles
        Route::resource('roles', App\Http\Controllers\Config\RolesController::class)
            ->names('roles')
            ->middleware([
                'index'   => 'permission:ver-roles',
                'show'    => 'permission:ver-roles',
                'create'  => 'permission:crear-roles',
                'store'   => 'permission:crear-roles',
                'edit'    => 'permission:editar-roles',
                'update'  => 'permission:editar-roles',
                'destroy' => 'permission:eliminar-roles',
            ]);

        // Permisos
        Route::get('/permisos', [App\Http\Controllers\Config\PermissionsController::class, 'index'])
            ->name('permisos.index')
            ->middleware('permission:ver-permisos');
        Route::post('/permisos/toggle', [App\Http\Controllers\Config\PermissionsController::class, 'toggle'])
            ->name('permisos.toggle')
            ->middleware('permission:asignar-permisos');

        // Auditoría
        Route::get('/auditoria', [App\Http\Controllers\Config\AuditController::class, 'index'])
            ->name('auditorias.index')
            ->middleware('permission:ver-auditoria');
        Route::get('/auditoria/exportar', [App\Http\Controllers\Config\AuditController::class, 'export'])
            ->name('auditorias.export')
            ->middleware('permission:exportar-auditoria');
        // Redirección amigable por si se ingresa la URL anterior de auditoria/errores
        Route::redirect('/auditoria/errores', '/configuracion/log-errores');
        Route::get('/auditoria/{auditoria}', [App\Http\Controllers\Config\AuditController::class, 'show'])
            ->name('auditorias.show')
            ->middleware('permission:ver-auditoria');

        // Log de Errores (Módulo independiente fuera de Auditoría)
        Route::prefix('log-errores')->name('log-errores.')->group(function () {
            Route::get('/', [App\Http\Controllers\Config\ErrorLogController::class, 'index'])
                ->name('index')
                ->middleware('permission:ver-logs');
            Route::get('/exportar', [App\Http\Controllers\Config\ErrorLogController::class, 'export'])
                ->name('export')
                ->middleware('permission:exportar-logs');
            Route::post('/simular', [App\Http\Controllers\Config\ErrorLogController::class, 'simulate'])
                ->name('simulate')
                ->middleware('permission:gestionar-logs');
            Route::post('/limpiar', [App\Http\Controllers\Config\ErrorLogController::class, 'clear'])
                ->name('clear')
                ->middleware('permission:gestionar-logs');
            Route::get('/{error}', [App\Http\Controllers\Config\ErrorLogController::class, 'show'])
                ->name('show')
                ->middleware('permission:ver-logs');
            Route::put('/{error}/estado', [App\Http\Controllers\Config\ErrorLogController::class, 'updateEstado'])
                ->name('update-estado')
                ->middleware('permission:gestionar-logs');
            Route::delete('/{error}', [App\Http\Controllers\Config\ErrorLogController::class, 'destroy'])
                ->name('destroy')
                ->middleware('permission:gestionar-logs');
        });

        // Información del Sistema
        Route::get('/sistema', [App\Http\Controllers\Config\SystemInfoController::class, 'index'])
            ->name('sistema.index')
            ->middleware('can:ver-sistema');

        // Identidad del Sistema (Logos, Favicon y Nombre)
        Route::prefix('identidad')->name('identidad.')->group(function () {
            Route::get('/', [App\Http\Controllers\Config\SystemIdentityController::class, 'index'])
                ->name('index')
                ->middleware('can:ver-identidad');
            Route::post('/', [App\Http\Controllers\Config\SystemIdentityController::class, 'update'])
                ->name('update')
                ->middleware('can:editar-identidad');
            Route::delete('/asset/{type}', [App\Http\Controllers\Config\SystemIdentityController::class, 'destroyAsset'])
                ->name('destroy-asset')
                ->middleware('can:editar-identidad');
        });

        // Módulo de Conexiones y Sesiones al Sistema
        Route::prefix('conexiones')->name('conexiones.')->group(function () {
            Route::middleware('permission:ver-conexiones')->group(function () {
                Route::get('/', [App\Http\Controllers\Config\ConexionController::class, 'index'])->name('index');
                Route::get('/{conexion}', [App\Http\Controllers\Config\ConexionController::class, 'show'])->whereNumber('conexion')->name('show');
            });

            Route::get('/export-excel', [App\Http\Controllers\Config\ConexionController::class, 'exportExcel'])
                ->middleware('permission:descargar-excel-conexiones')->name('export-excel');

            Route::post('/{conexion}/cerrar', [App\Http\Controllers\Config\ConexionController::class, 'cerrarSesionRemota'])
                ->middleware('permission:gestionar-conexiones')->whereNumber('conexion')->name('cerrar');

            Route::middleware('permission:eliminar-conexiones')->group(function () {
                Route::delete('/{conexion}', [App\Http\Controllers\Config\ConexionController::class, 'destroy'])->whereNumber('conexion')->name('destroy');
                Route::post('/limpiar', [App\Http\Controllers\Config\ConexionController::class, 'limpiar'])->name('limpiar');
            });
        });
    });

    // Módulo de Migración y Sincronización NAS
    Route::prefix('nas-migracion')->name('nas-migracion.')->group(function () {
        Route::get('/', [App\Http\Controllers\NasMigrationController::class, 'index'])
            ->middleware('permission:ver-migracion-nas')->name('index');

        Route::post('/tareas', [App\Http\Controllers\NasMigrationController::class, 'store'])
            ->middleware('permission:crear-tarea-nas')->name('tasks.store');

        Route::put('/tareas/{task}', [App\Http\Controllers\NasMigrationController::class, 'update'])
            ->middleware('permission:editar-tarea-nas')->name('tasks.update');

        Route::delete('/tareas/{task}', [App\Http\Controllers\NasMigrationController::class, 'destroy'])
            ->middleware('permission:eliminar-tarea-nas')->name('tasks.destroy');

        Route::post('/tareas/{task}/test-connection', [App\Http\Controllers\NasMigrationController::class, 'testConnection'])
            ->middleware('permission:probar-conexion-nas')->name('tasks.test');

        Route::post('/tareas/{task}/sync', [App\Http\Controllers\NasMigrationController::class, 'startSync'])
            ->middleware('permission:ejecutar-migracion-nas')->name('tasks.sync');

        Route::post('/tareas/{task}/stop', [App\Http\Controllers\NasMigrationController::class, 'stopSync'])
            ->middleware('permission:ejecutar-migracion-nas')->name('tasks.stop');

        Route::get('/tareas/{task}/status', [App\Http\Controllers\NasMigrationController::class, 'checkStatus'])
            ->middleware('permission:ver-migracion-nas')->name('tasks.status');

        Route::get('/logs/{log}', [App\Http\Controllers\NasMigrationController::class, 'showLog'])
            ->middleware('permission:ver-consola-log-nas')->name('logs.show');

        // Submódulo de Confirmación y Cotejo de Archivos (Synology vs Inspur)
        Route::prefix('confirmacion')->name('confirmacion.')->group(function () {
            Route::get('/', [App\Http\Controllers\NasVerificationController::class, 'index'])
                ->middleware('permission:ver-confirmacion-nas')->name('index');

            Route::post('/iniciar/{task}', [App\Http\Controllers\NasVerificationController::class, 'startVerification'])
                ->middleware('permission:ejecutar-confirmacion-nas')->name('start');

            Route::get('/{verification}/status', [App\Http\Controllers\NasVerificationController::class, 'checkStatus'])
                ->middleware('permission:ver-confirmacion-nas')->name('status');

            Route::post('/simular/{task}', [App\Http\Controllers\NasVerificationController::class, 'simulate'])
                ->middleware('permission:ejecutar-confirmacion-nas')->name('simulate');

            Route::get('/{verification}/exportar', [App\Http\Controllers\NasVerificationController::class, 'export'])
                ->middleware('permission:exportar-confirmacion-nas')->name('export');
        });

        // Submódulo de Estadísticas de Migración (Resumen por Responsable, Satélite y Nuevo Ejecutor)
        Route::prefix('estadisticas')->name('estadisticas.')->group(function () {
            Route::get('/', [App\Http\Controllers\NasStatsController::class, 'index'])
                ->middleware('permission:ver-estadisticas-nas')->name('index');

            Route::post('/', [App\Http\Controllers\NasStatsController::class, 'store'])
                ->middleware('permission:crear-estadisticas-nas')->name('store');

            Route::delete('/{stat}', [App\Http\Controllers\NasStatsController::class, 'destroy'])
                ->middleware('permission:crear-estadisticas-nas')->name('destroy');

            Route::get('/exportar', [App\Http\Controllers\NasStatsController::class, 'export'])
                ->middleware('permission:exportar-estadisticas-nas')->name('export');
        });
    });
});
