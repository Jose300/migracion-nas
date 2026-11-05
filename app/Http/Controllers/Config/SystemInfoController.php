<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class SystemInfoController extends Controller
{
    public function index()
    {
        // 1. Información General de la Aplicación
        $appName = config('app.name', 'Sistema Base');
        $appVersion = config('app.version', '1.0.0');
        $appEnv = app()->environment();
        $isLocal = app()->isLocal();
        $appDebug = config('app.debug');

        // 2. Framework y Lenguaje
        $laravelVersion = app()->version();
        $phpVersion = PHP_VERSION;
        $phpSapi = php_sapi_name();

        // 3. Base de Datos
        $dbDriver = config('database.default');
        $dbName = 'Desconocida';
        $dbVersion = 'N/A';
        $dbStatus = false;

        try {
            $pdo = DB::connection()->getPdo();
            $dbName = DB::connection()->getDatabaseName();
            $dbVersion = $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);
            $dbStatus = true;
        } catch (\Throwable $e) {
            $dbVersion = 'Error de conexión: ' . $e->getMessage();
        }

        $appUrl = config('app.url');
        $appTimezone = config('app.timezone', 'UTC');

        return view('config.sistema.index', compact(
            'appName',
            'appVersion',
            'appEnv',
            'isLocal',
            'appDebug',
            'appUrl',
            'appTimezone',
            'laravelVersion',
            'phpVersion',
            'phpSapi',
            'dbDriver',
            'dbName',
            'dbVersion',
            'dbStatus'
        ));
    }
}
