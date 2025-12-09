<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\Gate; // Importeer de Gate Facade
use App\Models\User; // <-- ESSENTIEEL: Importeer het User model

/**
 * Service Provider: AppServiceProvider
 *
 * Wordt automatisch geladen door Laravel. Hier kunnen globale services of gates
 * worden geregistreerd en bootstrap-logic worden geplaatst (zoals Vite prefetch).
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
        
        // Defineer de Gate voor het management dashboard
        Gate::define('view-management-dashboard', function (User $user) {
            return $user->role === 'Beheerder' || $user->role === 'Coördinator';
        });
    }
}