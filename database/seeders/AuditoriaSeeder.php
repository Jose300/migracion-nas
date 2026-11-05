<?php

namespace Database\Seeders;

use App\Models\Auditoria;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AuditoriaSeeder extends Seeder
{
    public function run(): void
    {
        if (Auditoria::count() > 0) {
            return;
        }

        $admin = User::where('email', 'jose.perera74@gmail.com')->first();
        $adminId = $admin?->id ?? 1;
        $adminName = $admin?->name ?? 'Jose Perera';
        $adminEmail = $admin?->email ?? 'jose.perera74@gmail.com';

        // Event 1: Creación de usuario Yefferson Quintero
        Auditoria::create([
            'user_id' => $adminId,
            'user_name' => 'Jose Perera',
            'user_email' => $adminEmail,
            'user_role' => 'Develop',
            'modulo' => 'Usuarios',
            'tabla' => 'users',
            'accion' => 'CREAR',
            'registro_id' => 2,
            'registro_codigo' => '2',
            'registro_nombre' => 'Yefferson Quintero',
            'data_anterior' => null,
            'data_nueva' => [
                'first_name' => 'Yefferson',
                'last_name' => 'Quintero',
                'name' => 'Yefferson Quintero',
                'email' => 'yefferson.q@sistema.com',
                'country_code' => '+58',
                'area_code' => '414',
                'phone' => '1234567',
                'status' => 'Activo',
            ],
            'campos_modificados' => ['first_name', 'last_name', 'name', 'email', 'country_code', 'area_code', 'phone', 'status'],
            'ip_address' => '154.27.138.144',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36',
            'url' => 'http://sistema-base.test/configuracion/usuarios',
            'metodo_http' => 'POST',
            'created_at' => Carbon::parse('2026-10-21 23:22:58'),
            'updated_at' => Carbon::parse('2026-10-21 23:22:58'),
        ]);

        // Event 2: Modificación de usuario Jose Perera
        Auditoria::create([
            'user_id' => $adminId,
            'user_name' => 'Jose Perera',
            'user_email' => $adminEmail,
            'user_role' => 'Develop',
            'modulo' => 'Usuarios',
            'tabla' => 'users',
            'accion' => 'ACTUALIZAR',
            'registro_id' => 1,
            'registro_codigo' => '1',
            'registro_nombre' => 'Jose Perera',
            'data_anterior' => [
                'area_code' => '412',
                'phone' => '8889900',
            ],
            'data_nueva' => [
                'area_code' => '416',
                'phone' => '9105581',
            ],
            'campos_modificados' => ['area_code', 'phone'],
            'ip_address' => '154.27.138.144',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36',
            'url' => 'http://sistema-base.test/perfil',
            'metodo_http' => 'PUT',
            'created_at' => Carbon::parse('2026-10-21 23:21:14'),
            'updated_at' => Carbon::parse('2026-10-21 23:21:14'),
        ]);
    }
}
