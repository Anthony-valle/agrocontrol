<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSensitiveActionRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && method_exists($user, 'canManageSensitiveActions') && $user->canManageSensitiveActions()) {
            return $next($request);
        }

        $message = 'No tienes permiso para ejecutar esta accion.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => $message], 403);
        }

        abort(403, $message);
    }
}