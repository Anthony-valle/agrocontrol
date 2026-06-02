<?php

namespace App\Models;

use App\Traits\EmpresaScope;
use App\Traits\TracksDeletionMetadata;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Labore extends Model
{
    use HasFactory, EmpresaScope, SoftDeletes, TracksDeletionMetadata;

    protected $table = 'labores';

    protected $fillable = [
        'empresa_id',
        'codigo', 
        'nombre', 
        'actividad_secundaria', 
        'unidad_medida', 
        'costo_unitario', 
        'observaciones',
        'estado',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'estado' => 'boolean',
        'costo_unitario' => 'decimal:3',
        'deleted_at' => 'datetime',
    ];
}
