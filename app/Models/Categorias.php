<?php

namespace App\Models;

use App\Traits\EmpresaScope;
use App\Traits\TracksDeletionMetadata;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Categorias extends Model
{
    use HasFactory, EmpresaScope, SoftDeletes, TracksDeletionMetadata;

    protected $table = 'categorias';

    protected $fillable = [
        'empresa_id',
        'usuarios_id',
        'nombre',
        'estado',
        'sucursal_id',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    //Relacion: una categoria tiene muchos insumos
     public function insumos()
    {
        return $this->hasMany(Insumo::class, 'categoria_nombre', 'nombre');
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursale::class);
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
