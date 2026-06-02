<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    public function handle(Request $request, Closure $next, $permiso)
    {
        $usuario = Auth::user();

        if (! $usuario instanceof User) {
            abort(403);
        }

        if ($usuario->isSuperUser()) {
            return $next($request);
        }

        if ($usuario->hasAccess((string) $permiso)) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => 'No tienes permiso para acceder a esta sección.',
            ], 403);
        }

        return redirect()->route('home')->with('error', 'No tienes permiso para acceder a esta sección.');
    }
}

