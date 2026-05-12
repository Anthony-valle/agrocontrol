<?php

namespace App\Models;

use App\Traits\EmpresaScope;
use App\Traits\TracksDeletionMetadata;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User;

class Bodega extends Model
{
    use HasFactory, EmpresaScope, SoftDeletes, TracksDeletionMetadata;

    protected $table = 'bodegas';

    protected $fillable = [
        'empresa_id',
        'codigo',
        'nombre',
        'responsable',
        'ubicacion',
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

    public function inventarios()
    {
        return $this->hasMany(inventario::class);
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getEstadoTextoAttribute()
    {
        return $this->estado ? 'Activo' : 'Inactivo';
    }
}
