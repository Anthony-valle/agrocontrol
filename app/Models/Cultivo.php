<?php

namespace App\Models;

use App\Traits\EmpresaScope;
use App\Traits\TracksDeletionMetadata;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cultivo extends Model
{
    use HasFactory, EmpresaScope, SoftDeletes, TracksDeletionMetadata;

    // Tabla asociada (opcional si sigue la convención)
    protected $table = 'cultivos';

    // Campos que se pueden asignar masivamente
    protected $fillable = [
        'empresa_id',
        'nombre',
        'codigo',
        'variedad',
        'ciclo',
        'unidad_medida',
        'fecha_siembra',
        'duracion_ciclo',
        'fecha_cosecha',
        'hectareas',
        'cosecha_estimada',
        'estado',
        'observaciones',
        'lotes_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];


    /**
     * RELACIONES
     */

    // Relación con Lote
    public function lote()
    {
        return $this->belongsTo(Lote::class, 'lotes_id');
    }

    // Usuario que creó el cultivo
    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Usuario que actualizó el cultivo
    public function actualizador()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    
    public function consumos()
    {
        return $this->hasMany(Consumo::class);
    }

    // Alias por compatibilidad con código que pueda usar el nombre singular
    public function consumo()
    {
        return $this->consumos();
    }

    public function cosechas()
    {
        return $this->hasMany(Cosecha::class);
    }

    public function planes()
    {
        return $this->hasMany(planes_cultivo::class, 'cultivo_id');
    }


    /**
     * ACCESORS / MUTATORS
     */

    // Texto legible para el estado
    public function getEstadoTextoAttribute()
    {
        return $this->estado === 'Activo' ? 'Activo' : 'Inactivo';
    }

    // Calcular fecha de cosecha automáticamente (opcional)
    public function calcularFechaCosecha()
    {
        if($this->fecha_siembra && $this->duracion_ciclo) {
            return $this->fecha_siembra->addDays($this->duracion_ciclo);
        }
        return null;
    }

    public function calcularSemanaCultivoParaFecha(mixed $fecha): ?int
    {
        $fechaObjetivo = $this->normalizarFechaCarbon($fecha);
        $fechaSiembra = $this->normalizarFechaCarbon($this->fecha_siembra);

        if (! $fechaObjetivo || ! $fechaSiembra) {
            return null;
        }

        if ($fechaObjetivo->lt($fechaSiembra)) {
            return 1;
        }

        return (int) floor($fechaSiembra->diffInDays($fechaObjetivo) / 7) + 1;
    }

    public function calcularSemanaAnioParaFecha(mixed $fecha): ?int
    {
        $fechaObjetivo = $this->normalizarFechaCarbon($fecha);

        return $fechaObjetivo ? (int) $fechaObjetivo->isoWeek() : null;
    }

    protected function normalizarFechaCarbon(mixed $fecha): ?Carbon
    {
        if ($fecha instanceof Carbon) {
            return $fecha->copy()->startOfDay();
        }

        if ($fecha instanceof \DateTimeInterface) {
            return Carbon::instance($fecha)->startOfDay();
        }

        if ($fecha === null || $fecha === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $fecha)->startOfDay();
        } catch (\Throwable $error) {
            return null;
        }
    }
}