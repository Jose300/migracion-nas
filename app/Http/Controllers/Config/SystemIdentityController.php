<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SystemIdentityController extends Controller
{
    /**
     * Display the system identity and branding management view.
     */
    public function index()
    {
        $appName = setting('app_name', config('app.name', 'Sistema Base'));
        $logoNavbar = setting('logo_navbar');
        $logoLogin = setting('logo_login');
        $favicon = setting('favicon');

        return view('config.identidad.index', compact(
            'appName',
            'logoNavbar',
            'logoLogin',
            'favicon'
        ));
    }

    /**
     * Update system identity settings (name, logos, favicon).
     */
    public function update(Request $request)
    {
        $request->validate([
            'app_name' => ['required', 'string', 'max:60'],
            'logo_navbar' => ['nullable', 'file', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
            'logo_login' => ['nullable', 'file', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
            'favicon' => ['nullable', 'file', 'mimes:ico,png,svg', 'max:1024'],
        ], [
            'app_name.required' => 'El nombre del sistema es obligatorio.',
            'app_name.max' => 'El nombre del sistema no debe exceder 60 caracteres.',
            'logo_navbar.mimes' => 'El logotipo de la barra lateral debe ser formato PNG, JPG, JPEG, SVG o WEBP.',
            'logo_navbar.max' => 'El logotipo de la barra lateral no debe superar 2 MB.',
            'logo_login.mimes' => 'El logotipo de inicio de sesión debe ser formato PNG, JPG, JPEG, SVG o WEBP.',
            'logo_login.max' => 'El logotipo de inicio de sesión no debe superar 2 MB.',
            'favicon.mimes' => 'El favicon debe ser formato ICO, PNG o SVG.',
            'favicon.max' => 'El favicon no debe superar 1 MB.',
        ]);

        // Validación estricta de dimensiones mínimas (prohibir imágenes muy pequeñas)
        if ($request->hasFile('logo_navbar')) {
            $file = $request->file('logo_navbar');
            if (strtolower($file->getClientOriginalExtension()) !== 'svg') {
                $info = @getimagesize($file->getRealPath());
                if ($info && ($info[0] < 80 || $info[1] < 25)) {
                    return back()->withErrors([
                        'logo_navbar' => "La imagen cargada es muy pequeña ({$info[0]}×{$info[1]} px). El tamaño mínimo requerido para la barra lateral es de 80 × 25 px."
                    ])->withInput();
                }
            }
        }

        if ($request->hasFile('logo_login')) {
            $file = $request->file('logo_login');
            if (strtolower($file->getClientOriginalExtension()) !== 'svg') {
                $info = @getimagesize($file->getRealPath());
                if ($info && ($info[0] < 120 || $info[1] < 35)) {
                    return back()->withErrors([
                        'logo_login' => "La imagen cargada es muy pequeña ({$info[0]}×{$info[1]} px). El tamaño mínimo requerido para el inicio de sesión es de 120 × 35 px."
                    ])->withInput();
                }
            }
        }

        if ($request->hasFile('favicon')) {
            $file = $request->file('favicon');
            if (strtolower($file->getClientOriginalExtension()) !== 'svg') {
                $info = @getimagesize($file->getRealPath());
                if ($info && ($info[0] < 16 || $info[1] < 16)) {
                    return back()->withErrors([
                        'favicon' => "El favicon cargado es muy pequeño ({$info[0]}×{$info[1]} px). El tamaño mínimo requerido es de 16 × 16 px."
                    ])->withInput();
                }
            }
        }

        // 1. Guardar Nombre del Sistema
        Setting::set('app_name', trim($request->app_name), 'identity');

        // 2. Procesar y Redimensionar Logotipo de Barra Lateral (Navbar: Máx. óptimo 370 × 84 px @2x)
        if ($request->boolean('remove_logo_navbar')) {
            $this->deleteStoredFile('logo_navbar');
        } elseif ($request->hasFile('logo_navbar')) {
            $this->deleteStoredFile('logo_navbar');
            $path = $this->storeAndOptimizeImage($request->file('logo_navbar'), 370, 84);
            Setting::set('logo_navbar', $path, 'identity');
        }

        // 3. Procesar y Redimensionar Logotipo de Inicio de Sesión (Login: Máx. óptimo 560 × 130 px @2x)
        if ($request->boolean('remove_logo_login')) {
            $this->deleteStoredFile('logo_login');
        } elseif ($request->hasFile('logo_login')) {
            $this->deleteStoredFile('logo_login');
            $path = $this->storeAndOptimizeImage($request->file('logo_login'), 560, 130);
            Setting::set('logo_login', $path, 'identity');
        }

        // 4. Procesar y Optimizar Favicon (Máx. óptimo 64 × 64 px)
        if ($request->boolean('remove_favicon')) {
            $this->deleteStoredFile('favicon');
        } elseif ($request->hasFile('favicon')) {
            $this->deleteStoredFile('favicon');
            $path = $this->storeAndOptimizeImage($request->file('favicon'), 64, 64);
            Setting::set('favicon', $path, 'identity');
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Identidad del sistema actualizada exitosamente.',
                'app_name' => setting('app_name'),
                'logo_navbar_url' => setting('logo_navbar') ? asset('storage/' . setting('logo_navbar')) : null,
                'logo_login_url' => setting('logo_login') ? asset('storage/' . setting('logo_login')) : null,
                'favicon_url' => setting('favicon') ? asset('storage/' . setting('favicon')) : null,
            ]);
        }

        return redirect()->route('identidad.index')->with('success', 'Identidad del sistema actualizada exitosamente con logotipos optimizados.');
    }

    /**
     * Delete a specific identity asset (logo or favicon).
     */
    public function destroyAsset(Request $request, string $type)
    {
        $validKeys = [
            'navbar' => 'logo_navbar',
            'login'  => 'logo_login',
            'favicon'=> 'favicon',
        ];

        if (!array_key_exists($type, $validKeys)) {
            return response()->json([
                'success' => false,
                'message' => 'Tipo de recurso no válido.'
            ], 400);
        }

        $key = $validKeys[$type];
        $this->deleteStoredFile($key);

        return response()->json([
            'success' => true,
            'message' => 'El elemento ha sido restablecido a su valor por defecto.',
        ]);
    }

    /**
     * Delete a stored file from public disk and clear setting.
     */
    protected function deleteStoredFile(string $key): void
    {
        $oldPath = Setting::get($key);
        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }
        Setting::remove($key);
    }

    /**
     * Store and optionally resize/optimize image keeping aspect ratio and alpha transparency.
     */
    protected function storeAndOptimizeImage($file, int $maxTargetWidth, int $maxTargetHeight): string
    {
        $ext = strtolower($file->getClientOriginalExtension());

        // Archivos SVG o ICO se almacenan directamente sin alterar vector o cabeceras
        if ($ext === 'svg' || $ext === 'ico') {
            return $file->store('identity', 'public');
        }

        $imageInfo = @getimagesize($file->getRealPath());
        if (!$imageInfo) {
            return $file->store('identity', 'public');
        }

        [$origWidth, $origHeight, $imageType] = $imageInfo;

        // Si ya está dentro de los límites óptimos recomendados, guardar directamente
        if ($origWidth <= $maxTargetWidth && $origHeight <= $maxTargetHeight) {
            return $file->store('identity', 'public');
        }

        // Calcular factor de escala proporcional
        $ratio = min($maxTargetWidth / $origWidth, $maxTargetHeight / $origHeight);
        $newWidth = (int) max(1, round($origWidth * $ratio));
        $newHeight = (int) max(1, round($origHeight * $ratio));

        // Cargar imagen según formato
        $srcImage = match ($imageType) {
            IMAGETYPE_PNG => @imagecreatefrompng($file->getRealPath()),
            IMAGETYPE_JPEG => @imagecreatefromjpeg($file->getRealPath()),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($file->getRealPath()) : null,
            default => null,
        };

        if (!$srcImage) {
            return $file->store('identity', 'public');
        }

        // Crear nuevo lienzo optimizado con canal alfa preservado
        $dstImage = imagecreatetruecolor($newWidth, $newHeight);
        if ($imageType === IMAGETYPE_PNG || $imageType === IMAGETYPE_WEBP) {
            imagealphablending($dstImage, false);
            imagesavealpha($dstImage, true);
            $transparent = imagecolorallocatealpha($dstImage, 255, 255, 255, 127);
            imagefilledrectangle($dstImage, 0, 0, $newWidth, $newHeight, $transparent);
        }

        imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

        // Guardar archivo redimensionado
        $fileName = 'identity/' . \Illuminate\Support\Str::random(40) . '.' . $ext;
        $tempPath = tempnam(sys_get_temp_dir(), 'opt_img_');

        match ($imageType) {
            IMAGETYPE_PNG => imagepng($dstImage, $tempPath, 8),
            IMAGETYPE_JPEG => imagejpeg($dstImage, $tempPath, 92),
            IMAGETYPE_WEBP => function_exists('imagewebp') ? imagewebp($dstImage, $tempPath, 92) : imagepng($dstImage, $tempPath),
            default => imagepng($dstImage, $tempPath),
        };

        imagedestroy($srcImage);
        imagedestroy($dstImage);

        Storage::disk('public')->put($fileName, file_get_contents($tempPath));
        @unlink($tempPath);

        return $fileName;
    }
}
