<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

/** @mixin Model */
trait EmpresaScope
{
    protected static function bootEmpresaScope()
    {
        if (! is_subclass_of(static::class, Model::class)) {
            return;
        }

        forward_static_call([static::class, 'addGlobalScope'], 'empresa', function (Builder $builder) {
            if (! Auth::check()) {
                return;
            }

            $model = $builder->getModel();
            $table = $model->getTable();

            $user = Auth::user();
            $empresaId = $user->sucursal->empresa_id ?? $user->empresa_id ?? null;

            if ($empresaId && Schema::hasColumn($table, 'empresa_id')) {
                $builder->where($table . '.empresa_id', $empresaId);
            }

            $userRole = strtolower($user->rol->nombre ?? '');
            if ($userRole === 'programador') {
                return;
            }

            if (Schema::hasColumn($table, 'sucursal_id')) {
                $sucursalId = $user->sucursal_id ?? $user->sucursal->id ?? null;
                if ($sucursalId) {
                    $builder->where($table . '.sucursal_id', $sucursalId);
                }
            }
        });
    }
}