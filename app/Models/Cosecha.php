<?php

namespace App\Models;

use App\Traits\EmpresaScope;
use App\Traits\TracksDeletionMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class Cosecha extends Model
{
    use EmpresaScope, SoftDeletes, TracksDeletionMetadata;

    private static ?array $schemaColumns = null;

    protected $table = 'cosechas';

    protected $fillable = [
        'empresa_id',
        'cultivo_id',
        'cantidad_bruta',
        'descarte',
        'cantidad_descarte',
        'cantidad_neta',
        'cantidad_disponible',
        'precio_venta_unitario',
        'unidad_medida',
        'motivo_descarte',
        'fecha_cosecha',
        'observaciones',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function cultivo()
    {
        return $this->belongsTo(Cultivo::class, 'cultivo_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function facturas()
    {
        return $this->hasMany(CosechaFactura::class, 'cosecha_id');
    }

    public function getDescarteAttribute(mixed $value)
    {
        if ($value !== null) {
            return $value;
        }

        return $this->attributes['cantidad_descarte'] ?? 0;
    }

    public function getCantidadDisponibleAttribute(mixed $value)
    {
        if ($value !== null) {
            return $value;
        }

        return $this->attributes['cantidad_neta'] ?? 0;
    }

    public function getPrecioVentaUnitarioAttribute(mixed $value)
    {
        return $value;
    }
}
