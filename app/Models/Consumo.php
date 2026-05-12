<?php

namespace App\Models;

use App\Traits\EmpresaScope;
use App\Traits\TracksDeletionMetadata;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Consumo extends Model
{
    use HasFactory, EmpresaScope, SoftDeletes, TracksDeletionMetadata;

    protected  $table = 'consumos';

    protected $fillable = [
        'empresa_id',
        'cultivo_id',
        'fecha_consumo',
        'total',
        'estado',
        'created_by',
        'updated_by',
        'validated_by',
        'anulado_by',
        'fecha_anulacion',
        'motivo_anulacion'
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];
    
    public function cultivo()
    {
        return $this->belongsTo(Cultivo::class, 'cultivo_id', 'id');
    }

    public function detalles()
    { 
        return $this->hasMany(Consumo_detalles::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validador()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function anulador()
    {
        return $this->belongsTo(User::class, 'anulado_by');
    }

    public function notificaciones()
    {
        return $this->hasMany(Notificaciones::class, 'cultivo_id', 'cultivo_id');
    }

    public function getEstadoNormalizadoAttribute(): string
    {
        $estado = strtoupper(trim((string) $this->estado));

        return match ($estado) {
            'FINALIZADO', 'ANULADO', 'PENDIENTE' => $estado,
            '1', '' => 'PENDIENTE',
            default => $estado,
        };
    }

}
