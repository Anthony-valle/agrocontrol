<?php

namespace App\Models;

use App\Traits\TracksDeletionMetadata;
use App\Traits\EmpresaScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PreparacionSueloActividad extends Model
{
    use HasFactory, EmpresaScope, SoftDeletes, TracksDeletionMetadata;

    protected $table = 'preparacion_suelo_actividades';

    protected $fillable = [
        'empresa_id',
        'codigo',
        'nombre',
        'actividad_secundaria',
        'unidad_medida',
        'observaciones',
        'estado',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'estado' => 'boolean',
        'deleted_at' => 'datetime',
    ];
}