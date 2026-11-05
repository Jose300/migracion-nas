<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LoginController::class, 'showLoginForm'])->name('login')->middleware('guest');

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
        Route::resource('usuarios', App\Http\Controllers\Config\UsersController::class)->names('usuarios');
        Route::resource('roles', App\Http\Controllers\Config\RolesController::class)->names('roles');
        Route::get('/permisos', [App\Http\Controllers\Config\PermissionsController::class, 'index'])->name('permisos.index');
        Route::post('/permisos/toggle', [App\Http\Controllers\Config\PermissionsController::class, 'toggle'])->name('permisos.toggle');
        // Auditoría
        Route::get('/auditoria', [App\Http\Controllers\Config\AuditController::class, 'index'])->name('auditorias.index');
        Route::get('/auditoria/exportar', [App\Http\Controllers\Config\AuditController::class, 'export'])->name('auditorias.export');
        // Redirección amigable por si se ingresa la URL anterior de auditoria/errores
        Route::redirect('/auditoria/errores', '/configuracion/log-errores');
        Route::get('/auditoria/{auditoria}', [App\Http\Controllers\Config\AuditController::class, 'show'])->name('auditorias.show');

        // Log de Errores (Módulo independiente fuera de Auditoría)
        Route::prefix('log-errores')->name('log-errores.')->group(function () {
            Route::get('/', [App\Http\Controllers\Config\ErrorLogController::class, 'index'])->name('index');
            Route::get('/exportar', [App\Http\Controllers\Config\ErrorLogController::class, 'export'])->name('export');
            Route::post('/simular', [App\Http\Controllers\Config\ErrorLogController::class, 'simulate'])->name('simulate');
            Route::post('/limpiar', [App\Http\Controllers\Config\ErrorLogController::class, 'clear'])->name('clear');
            Route::get('/{error}', [App\Http\Controllers\Config\ErrorLogController::class, 'show'])->name('show');
            Route::put('/{error}/estado', [App\Http\Controllers\Config\ErrorLogController::class, 'updateEstado'])->name('update-estado');
            Route::delete('/{error}', [App\Http\Controllers\Config\ErrorLogController::class, 'destroy'])->name('destroy');
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
});
