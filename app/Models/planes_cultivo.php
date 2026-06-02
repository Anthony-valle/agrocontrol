<?php

namespace App\Models;

use App\Traits\EmpresaScope;
use App\Traits\TracksDeletionMetadata;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class planes_cultivo extends Model
{
    use HasFactory, EmpresaScope, SoftDeletes, TracksDeletionMetadata;

    protected $table ='planes_cultivos';

    protected $fillable = [
        'empresa_id',
        'cultivo_id',
        'semana',
        'fecha_plan',
        'cosecha_estimada',
        'estado',
        'total_presupuesto',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'cosecha_estimada' => 'decimal:3',
        'total_presupuesto' => 'decimal:3',
        'deleted_at' => 'datetime',
    ];

    public function detalles()
    {
        return $this->hasMany(planes_detalles::class,'plan_cultivo_id');
    }

    public function cultivo()
    {
        return $this->belongsTo(Cultivo::class,'cultivo_id');
    }

    public function insumo()
    {
        return $this->belongsTo(Insumo::class);
    }

    public function labores()
    {
        return $this->belongsTo(labore::class);
    }
}


