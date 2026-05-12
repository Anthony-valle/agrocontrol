<?php

namespace App\Models;

use App\Traits\EmpresaScope;
use App\Traits\TracksDeletionMetadata;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sucursale extends Model
{
    use HasFactory, EmpresaScope, SoftDeletes, TracksDeletionMetadata;

    protected $table = 'sucursales';

    protected $fillable = [
        'nombre',
        'direccion',
        'telefono',
        'email',
        'responsable',
        'estado',
        'empresa_id',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    //relacion
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function bodega()
    {
        return $this->hasMany(Bodega::class, 'sucursal_id');
    }

   // Usuario que creó la empresa
    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Usuario que actualizó la empresa
    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getEstadoTextoAttribute()
    {
        return $this->estado ? 'Activo' : 'Inactivo';
    }
}
