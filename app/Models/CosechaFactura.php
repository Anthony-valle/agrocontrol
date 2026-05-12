<?php

namespace App\Models;

use App\Traits\EmpresaScope;
use App\Traits\TracksDeletionMetadata;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CosechaFactura extends Model
{
    use HasFactory, EmpresaScope, SoftDeletes, TracksDeletionMetadata;

    protected $table = 'cosecha_facturas';

    protected $fillable = [
        'empresa_id',
        'cosecha_id',
        'numero_factura',
        'cliente',
        'fecha_factura',
        'cantidad_vendida',
        'precio_unitario',
        'total',
        'archivo',
        'observaciones',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function cosecha()
    {
        return $this->belongsTo(Cosecha::class, 'cosecha_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}