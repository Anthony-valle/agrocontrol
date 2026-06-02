<?php

namespace App\Models;

use App\Traits\EmpresaScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class Notificaciones extends Model
{
    use HasFactory, EmpresaScope;

    protected $table = 'notificaciones';
    
    protected $fillable = [
        'empresa_id',
        'cultivo_id',
        'user_id',
        'mensaje',
        'tipo',
        'leido'
    ];

    public function cultivo(){
        return $this->belongsTo(Cultivo::class);
    }

    public function usuario(){
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeNoLeidas(Builder $query): Builder
    {
        return $query->where('leido', false);
    }

    public static function registrarParaSupervision(array $payload): void
    {
        if (DB::transactionLevel() > 0) {
            DB::afterCommit(function () use ($payload): void {
                static::persistirParaSupervision($payload);
            });

            return;
        }

        static::persistirParaSupervision($payload);
    }

    public static function registrarParaCompras(array $payload): void
    {
        if (DB::transactionLevel() > 0) {
            DB::afterCommit(function () use ($payload): void {
                static::persistirParaCompras($payload);
            });

            return;
        }

        static::persistirParaCompras($payload);
    }

    public static function persistirParaSupervision(array $payload): void
    {
        $empresaId = isset($payload['empresa_id']) && $payload['empresa_id'] !== null
            ? (int) $payload['empresa_id']
            : null;

        $destinatarios = static::destinatariosSupervision($empresaId);

        static::persistirParaDestinatarios(
            $destinatarios,
            static::buildBasePayload($payload),
            !empty($payload['user_id']) ? (int) $payload['user_id'] : null,
        );
    }

    public static function persistirParaCompras(array $payload): void
    {
        $empresaId = isset($payload['empresa_id']) && $payload['empresa_id'] !== null
            ? (int) $payload['empresa_id']
            : null;

        $destinatarios = static::destinatariosCompra($empresaId);

        static::persistirParaDestinatarios(
            $destinatarios,
            static::buildBasePayload($payload),
            !empty($payload['user_id']) ? (int) $payload['user_id'] : null,
        );
    }

    public static function destinatariosCompra(?int $empresaId = null): Collection
    {
        return User::query()
            ->whereHas('rol', function ($query) {
                $query->whereIn('nombre', ['compra', 'compras', 'encargado_compra', 'encargado compras']);
            })
            ->when($empresaId !== null, function ($query) use ($empresaId) {
                $query->whereHas('sucursal', function ($sucursalQuery) use ($empresaId) {
                    $sucursalQuery->withoutGlobalScopes()->where('empresa_id', $empresaId);
                });
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    private static function buildBasePayload(array $payload): array
    {
        $empresaId = isset($payload['empresa_id']) && $payload['empresa_id'] !== null
            ? (int) $payload['empresa_id']
            : null;

        return [
            'empresa_id' => $empresaId,
            'cultivo_id' => isset($payload['cultivo_id']) && $payload['cultivo_id'] !== null ? (int) $payload['cultivo_id'] : null,
            'mensaje' => (string) ($payload['mensaje'] ?? ''),
            'tipo' => (string) ($payload['tipo'] ?? 'general'),
            'leido' => (bool) ($payload['leido'] ?? false),
        ];
    }

    private static function persistirParaDestinatarios(Collection $destinatarios, array $basePayload, ?int $fallbackUserId = null): void
    {
        if ($destinatarios->isEmpty()) {
            if ($fallbackUserId !== null) {
                static::create($basePayload + ['user_id' => $fallbackUserId]);
            }

            return;
        }

        $timestamp = now();
        $registros = $destinatarios
            ->map(fn (int $userId) => $basePayload + [
                'user_id' => $userId,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ])
            ->all();

        DB::table((new static())->getTable())->insert($registros);
    }

    public static function destinatariosSupervision(?int $empresaId = null): Collection
    {
        return User::query()
            ->whereHas('rol', function ($query) {
                $query->whereIn('nombre', ['admin', 'propietario', 'programador', 'superadmin']);
            })
            ->when($empresaId !== null, function ($query) use ($empresaId) {
                $query->whereHas('sucursal', function ($sucursalQuery) use ($empresaId) {
                    $sucursalQuery->withoutGlobalScopes()->where('empresa_id', $empresaId);
                });
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

}
