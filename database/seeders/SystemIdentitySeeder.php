<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class SystemIdentitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Establecer nombre oficial del sistema
        Setting::set('app_name', 'ABAE', 'identity');

        // 2. Asegurar que el directorio identity existe en storage/app/public
        $identityDir = storage_path('app/public/identity');
        if (!File::exists($identityDir)) {
            File::makeDirectory($identityDir, 0755, true);
        }

        // 3. Si existen los logos oficiales en public/images, sincronizarlos a storage/app/public/identity
        $sourceLogo = public_path('images/logo-abae.png');
        if (File::exists($sourceLogo)) {
            $destLogoNavbar = 'identity/logo_navbar_abae.png';
            $destLogoLogin = 'identity/logo_login_abae.png';

            File::copy($sourceLogo, storage_path('app/public/' . $destLogoNavbar));
            File::copy($sourceLogo, storage_path('app/public/' . $destLogoLogin));

            Setting::set('logo_navbar', $destLogoNavbar, 'identity');
            Setting::set('logo_login', $destLogoLogin, 'identity');
        }

        // 4. Limpiar caché de configuración e identidad
        Setting::allKeyed();
    }
}
