<?php

namespace App\Models;

use App\Traits\EmpresaScope;
use App\Traits\TracksDeletionMetadata;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lote extends Model
{
    use HasFactory, EmpresaScope, SoftDeletes, TracksDeletionMetadata;

    protected $table = 'lotes';

    protected $fillable = [
        'empresa_id',
        'codigo',
        'nombre',
        'area',
        'poligono',
        'estado',
        'sucursal_id',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

        public function sucursal()
    {
        return $this->belongsTo(Sucursale::class, 'sucursal_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getEstadoTextoAttribute()
    {
        return $this->estado ? 'Activo' : 'Inactivo';
    }
}


