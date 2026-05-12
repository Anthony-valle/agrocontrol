<?php

namespace App\Http\Middleware;

use App\Models\DeletionAudit;
use App\Models\Notificaciones;
use App\Models\Sucursale;
use Illuminate\Support\Facades\DB;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuditUserAction
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!$this->shouldAudit($request, $response)) {
            return $response;
        }

        $user = Auth::user();
        if (!$user) {
            return $response;
        }

        Notificaciones::registrarParaSupervision([
            'empresa_id' => $this->resolveEmpresaId($user),
            'cultivo_id' => $this->resolveCultivoId($request, $user),
            'user_id' => $user->id,
            'mensaje' => $this->buildMessage($request, $user),
            'tipo' => 'auditoria',
            'leido' => false,
        ]);

        if ($request->method() === 'DELETE') {
            $this->storeDeletionAudit($request, $user);
        }

        return $response;
    }

    private function shouldAudit(Request $request, Response $response): bool
    {
        if (!$request->user()) {
            return false;
        }

        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return false;
        }

        if ($response->getStatusCode() >= 400) {
            return false;
        }

        $routeName = (string) optional($request->route())->getName();
        if (in_array($routeName, ['notificaciones.leer', 'notificaciones.marcar-leidas', 'alertas.leidas'], true)) {
            return false;
        }

        return true;
    }

    private function resolveEmpresaId(object $user): ?int
    {
        $empresaId = $user->sucursal->empresa_id ?? null;

        if (!$empresaId && !empty($user->sucursal_id)) {
            $empresaId = Sucursale::query()
                ->withoutGlobalScopes()
                ->whereKey($user->sucursal_id)
                ->value('empresa_id');
        }

        return $empresaId ? (int) $empresaId : null;
    }

    private function resolveCultivoId(Request $request, object $user): ?int
    {
        $routeCultivo = $request->route('cultivo');
        if (is_object($routeCultivo) && isset($routeCultivo->id)) {
            return (int) $routeCultivo->id;
        }

        $routeConsumo = $request->route('consumo');
        if (is_object($routeConsumo) && isset($routeConsumo->cultivo_id) && $routeConsumo->cultivo_id) {
            return (int) $routeConsumo->cultivo_id;
        }

        $cultivoId = $request->integer('cultivo_id');
        if ($cultivoId > 0) {
            return $cultivoId;
        }

        $empresaId = $this->resolveEmpresaId($user);
        if (!$empresaId) {
            return null;
        }

        $cultivoFallback = DB::table('cultivos')
            ->where('empresa_id', $empresaId)
            ->orderBy('id')
            ->value('id');

        return $cultivoFallback ? (int) $cultivoFallback : null;
    }

    private function buildMessage(Request $request, object $user): string
    {
        $route = $request->route();
        $routeName = (string) optional($route)->getName();
        $label = $this->resolveResourceLabel($routeName, $request->path());
        $verb = match ($request->method()) {
            'POST' => 'creó',
            'PUT', 'PATCH' => 'actualizó',
            'DELETE' => 'eliminó',
            default => strtolower($request->method()),
        };

        $usuario = $user->usuario ?? $user->name ?? ('Usuario #' . $user->id);
        $message = 'El usuario ' . $usuario . ' ' . $verb . ' ' . $label . '.';

        if ($request->method() === 'DELETE') {
            $reason = trim((string) ($request->input('delete_reason') ?: $request->header('X-Delete-Reason') ?: ''));
            if ($reason !== '') {
                $message .= ' Motivo: ' . $reason . '.';
            }
        }

        return $message;
    }

    private function storeDeletionAudit(Request $request, object $user): void
    {
        $target = $this->resolveDeletionTarget($request);
        $payload = $request->except(['_token', '_method', 'password', 'password_confirmation']);

        DeletionAudit::create([
            'empresa_id' => $this->resolveEmpresaId($user),
            'cultivo_id' => $this->resolveCultivoId($request, $user),
            'user_id' => $user->id,
            'user_name' => $user->usuario ?? $user->name ?? ('Usuario #' . $user->id),
            'reason' => trim((string) ($request->input('delete_reason') ?: $request->header('X-Delete-Reason') ?: 'Sin justificación proporcionada')),
            'route_name' => (string) optional($request->route())->getName(),
            'path' => $request->path(),
            'target_key' => $target['key'],
            'target_id' => $target['id'],
            'target_type' => $target['type'],
            'target_label' => $target['label'],
            'target_display' => $target['display'],
            'request_payload' => $payload,
        ]);
    }

    private function resolveDeletionTarget(Request $request): array
    {
        $route = $request->route();

        foreach ((array) optional($route)->parameters() as $key => $value) {
            if (is_object($value)) {
                $id = $value->id ?? null;
                $display = $value->nombre
                    ?? $value->usuario
                    ?? $value->name
                    ?? $value->codigo
                    ?? $value->descripcion
                    ?? null;

                return [
                    'key' => (string) $key,
                    'id' => $id ? (int) $id : null,
                    'type' => class_basename($value),
                    'label' => $this->resolveResourceLabel((string) optional($route)->getName(), $request->path()),
                    'display' => $display,
                ];
            }

            if (is_scalar($value) && !in_array((string) $key, ['_token', '_method'], true)) {
                return [
                    'key' => (string) $key,
                    'id' => is_numeric($value) ? (int) $value : null,
                    'type' => 'route-parameter',
                    'label' => $this->resolveResourceLabel((string) optional($route)->getName(), $request->path()),
                    'display' => (string) $value,
                ];
            }
        }

        return [
            'key' => null,
            'id' => null,
            'type' => null,
            'label' => $this->resolveResourceLabel((string) optional($route)->getName(), $request->path()),
            'display' => null,
        ];
    }

    private function resolveResourceLabel(string $routeName, string $path): string
    {
        $base = $routeName !== '' ? explode('.', $routeName)[0] : trim(explode('/', trim($path, '/'))[0] ?? 'acción');

        return match ($base) {
            'cultivo' => 'un cultivo',
            'lotes' => 'un lote',
            'labores' => 'una labor',
            'planes' => 'un plan de cultivo',
            'consumo' => 'un consumo',
            'cosecha' => 'una cosecha',
            'sucursal' => 'una sucursal',
            'empresas' => 'una empresa',
            'usuarios' => 'un usuario',
            'bodegas' => 'una bodega',
            'categorias' => 'una categoría',
            'preparacion-suelo' => 'una mecanización',
            'preparacion-suelo-actividades' => 'una actividad de preparación de suelo',
            'movimientos' => 'un movimiento de inventario',
            'insumos' => 'un insumo',
            'notificaciones' => 'notificaciones',
            default => 'una acción en ' . str_replace(['-', '_'], ' ', $base),
        };
    }
}