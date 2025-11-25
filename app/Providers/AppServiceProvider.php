<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use App\Models\Role;

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
        // 🔒 Evita errores cuando la tabla aún no existe
        if (Schema::hasTable('roles')) {
            // ✅ Solo ejecuta si la tabla ya fue migrada
            if (Role::count() === 0) {
                Role::insert([
                    ['name' => 'admin', 'state' => true, 'permission' => json_encode(['all'])],
                    ['name' => 'empleado', 'state' => true, 'permission' => json_encode(['limited'])],
                    ['name' => 'invitado', 'state' => true, 'permission' => json_encode(['view-only'])],
                ]);
            }
        }
    }
}
