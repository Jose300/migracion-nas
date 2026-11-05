<?php

namespace Database\Seeders;

use App\Models\LogError;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class LogErrorSeeder extends Seeder
{
    public function run(): void
    {
        if (LogError::count() > 0) {
            return;
        }

        $admin = User::where('email', 'jose.perera74@gmail.com')->first();
        $adminId = $admin?->id ?? 1;
        $adminName = $admin?->name ?? 'Jose Perera';
        $adminEmail = $admin?->email ?? 'jose.perera74@gmail.com';

        // Error 1: QueryException (CRITICAL / Pendiente)
        LogError::create([
            'nivel' => 'CRITICAL',
            'clase_error' => 'Illuminate\\Database\\QueryException',
            'mensaje' => "SQLSTATE[42S22]: Column not found: 1054 Unknown column 'departamento_id' in 'where clause' (Connection: mysql, SQL: select * from users where departamento_id = 4 and status = 'Activo' limit 25)",
            'codigo' => '42S22',
            'archivo' => base_path('app/Http/Controllers/Config/UsersController.php'),
            'linea' => 42,
            'trace' => "#0 " . base_path('vendor/laravel/framework/src/Illuminate/Database/Connection.php') . "(841): Illuminate\\Database\\Connection->runQueryCallback(...)\n#1 " . base_path('app/Http/Controllers/Config/UsersController.php') . "(42): App\\Http\\Controllers\\Config\\UsersController->index(...)",
            'url' => 'http://sistema-base.test/configuracion/usuarios?departamento=4',
            'metodo_http' => 'GET',
            'ip_address' => '154.27.138.144',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36',
            'request_data' => [
                'departamento' => '4',
                'filtro_avanzado' => 'true',
            ],
            'headers' => [
                'host' => 'sistema-base.test',
                'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'accept' => 'text/html,application/xhtml+xml',
            ],
            'user_id' => $adminId,
            'user_name' => $adminName,
            'user_email' => $adminEmail,
            'estado' => 'pendiente',
            'hash' => md5('UsersController.php:42:QueryException'),
            'created_at' => Carbon::now()->subHours(2),
            'updated_at' => Carbon::now()->subHours(2),
        ]);

        // Error 2: DivisionByZeroError (ERROR / En Revisión)
        LogError::create([
            'nivel' => 'ERROR',
            'clase_error' => 'DivisionByZeroError',
            'mensaje' => 'Division by zero in metric percentage calculation formula',
            'codigo' => '0',
            'archivo' => base_path('app/Http/Controllers/DashboardController.php'),
            'linea' => 68,
            'trace' => "#0 " . base_path('app/Http/Controllers/DashboardController.php') . "(68): calculateMetrics(...)\n#1 " . base_path('routes/web.php') . "(15): Closure(...)",
            'url' => 'http://sistema-base.test/dashboard',
            'metodo_http' => 'GET',
            'ip_address' => '154.27.138.144',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36',
            'request_data' => [],
            'headers' => [
                'host' => 'sistema-base.test',
                'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            ],
            'user_id' => $adminId,
            'user_name' => $adminName,
            'user_email' => $adminEmail,
            'estado' => 'en_revision',
            'solucion_notas' => 'Revisando manejo de denominadores en cero cuando aún no hay transacciones en el periodo seleccionado.',
            'hash' => md5('DashboardController.php:68:DivisionByZeroError'),
            'created_at' => Carbon::now()->subHours(5),
            'updated_at' => Carbon::now()->subHours(1),
        ]);

        // Error 3: ModelNotFoundException (WARNING / Resuelto)
        LogError::create([
            'nivel' => 'WARNING',
            'clase_error' => 'Illuminate\\Database\\Eloquent\\ModelNotFoundException',
            'mensaje' => 'No query results for model [App\\Models\\User] with key 9999',
            'codigo' => '404',
            'archivo' => base_path('app/Http/Controllers/Config/UsersController.php'),
            'linea' => 47,
            'trace' => "#0 " . base_path('vendor/laravel/framework/src/Illuminate/Database/Eloquent/Builder.php') . "(614): firstOrFail(...)",
            'url' => 'http://sistema-base.test/configuracion/usuarios/9999/edit',
            'metodo_http' => 'GET',
            'ip_address' => '154.27.138.144',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36',
            'request_data' => [],
            'headers' => [
                'host' => 'sistema-base.test',
            ],
            'user_id' => $adminId,
            'user_name' => $adminName,
            'user_email' => $adminEmail,
            'estado' => 'resuelto',
            'solucion_notas' => 'Se implementó respuesta JSON 404 controlada en lugar de permitir que la excepción se eleve sin capturar.',
            'resuelto_por_user_id' => $adminId,
            'resuelto_at' => Carbon::now()->subHours(1),
            'hash' => md5('UsersController.php:47:ModelNotFoundException'),
            'created_at' => Carbon::now()->subDay(),
            'updated_at' => Carbon::now()->subHours(1),
        ]);
    }
}
