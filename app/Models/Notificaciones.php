<?php

namespace App\Models;

use App\Traits\EmpresaScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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

    public function scopeNoLeidas($query)
    {
        return $query->where('leido', false);
    }

    public static function registrarParaSupervision(array $payload): void
    {
        $empresaId = isset($payload['empresa_id']) && $payload['empresa_id'] !== null
            ? (int) $payload['empresa_id']
            : null;

        $basePayload = [
            'empresa_id' => $empresaId,
            'cultivo_id' => isset($payload['cultivo_id']) && $payload['cultivo_id'] !== null ? (int) $payload['cultivo_id'] : null,
            'mensaje' => (string) ($payload['mensaje'] ?? ''),
            'tipo' => (string) ($payload['tipo'] ?? 'general'),
            'leido' => (bool) ($payload['leido'] ?? false),
        ];

        $destinatarios = static::destinatariosSupervision($empresaId);

        if ($destinatarios->isEmpty()) {
            if (!empty($payload['user_id'])) {
                static::create($basePayload + ['user_id' => (int) $payload['user_id']]);
            }

            return;
        }

        $destinatarios->each(function (int $userId) use ($basePayload) {
            static::create($basePayload + ['user_id' => $userId]);
        });
    }

    public static function destinatariosSupervision(?int $empresaId = null): Collection
    {
        return User::query()
            ->with(['rol', 'sucursal'])
            ->get()
            ->filter(function (User $user) use ($empresaId) {
                if (!$user->hasAnyRole(['admin', 'propietario', 'programador', 'superadmin'])) {
                    return false;
                }

                if ($empresaId === null) {
                    return true;
                }

                return (int) ($user->sucursal->empresa_id ?? 0) === $empresaId;
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

}
