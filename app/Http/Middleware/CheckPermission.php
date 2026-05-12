<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    public function handle(Request $request, Closure $next, $permiso)
    {
        $usuario = Auth::user();

        // Si es admin, deja pasar
        if ($usuario->hasRole('Admin')) {
            return $next($request);
        }

        // Si tiene permiso, deja pasar
        if ($usuario->hasPermiso($permiso)) {
            return $next($request);
        }

        // Si no tiene permiso, redirige o muestra error
        return redirect()->route('dashboard')->with('error', 'No tienes permiso para acceder a esta sección');
    }
}

