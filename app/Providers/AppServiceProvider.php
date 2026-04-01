<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $featuresPath = app_path('Features');

        if (File::exists($featuresPath)) {
            $directories = File::directories($featuresPath);

            foreach ($directories as $directory) {
                $featureName = basename($directory);

                // 1. Auto-load Migrations
                $this->loadMigrationsFrom($directory . '/Migrations');

                // 2. Auto-load Routes (api.php)
                $routeFile = $directory . '/routes.php';
                if (File::exists($routeFile)) {
                    Route::middleware(['api', 'auth:sanctum'])
                        ->prefix('api/' . strtolower($featureName))
                        ->group($routeFile);
                }
            }
        }
    }
}