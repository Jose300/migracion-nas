<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $helperPath = app_path('Helpers/helpers.php');
        if (file_exists($helperPath)) {
            require_once $helperPath;
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                $customName = \App\Models\Setting::get('app_name');
                if (!empty($customName)) {
                    config(['app.name' => $customName]);
                }
            }
        } catch (\Throwable $e) {
            // Ignorar excepciones al arrancar si no hay conexión a base de datos
        }
    }
}
