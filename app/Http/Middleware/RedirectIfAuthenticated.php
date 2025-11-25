<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, ...$guards)
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                // Si estás usando API + Sanctum, devuelve JSON en vez de redirect
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Ya estás autenticado'
                    ], 403);
                }

                // Para rutas web, puedes redirigir a una ruta que tenga tu dashboard
                return redirect('/dashboard');
                // O la ruta que tú hayas definido como "home"
            }
        }

        return $next($request);
    }
}
