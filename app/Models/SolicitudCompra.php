<?php

namespace App\Models;

use App\Traits\EmpresaScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SolicitudCompra extends Model
{
    use HasFactory, EmpresaScope;

    protected $table = 'solicitudes_compra';

    public const ESTADO_PENDIENTE_APROBACION = 'pendiente_aprobacion';
    public const ESTADO_APROBADA = 'aprobada';
    public const ESTADO_EN_PROCESO = 'en_proceso';
    public const ESTADO_RECHAZADA = 'rechazada';
    public const ESTADO_RECIBIDA = 'recibida';

    protected $fillable = [
        'empresa_id',
        'solicitante_id',
        'aprobado_por',
        'gestionado_por',
        'recibido_por',
        'insumo_id',
        'bodega_destino_id',
        'movimiento_inventario_id',
        'factura_inventario_id',
        'codigo',
        'departamento',
        'asunto',
        'unidad',
        'cantidad',
        'precio_estimado',
        'prioridad',
        'estado',
        'descripcion',
        'detalle_items',
        'observaciones_compra',
        'motivo_rechazo',
        'fecha_requerida',
        'aprobado_en',
        'gestionado_en',
        'rechazado_en',
        'recibido_en',
    ];

    protected $casts = [
        'cantidad' => 'decimal:3',
        'precio_estimado' => 'decimal:3',
        'detalle_items' => 'array',
        'fecha_requerida' => 'date',
        'aprobado_en' => 'datetime',
        'gestionado_en' => 'datetime',
        'rechazado_en' => 'datetime',
        'recibido_en' => 'datetime',
    ];

    public function solicitante()
    {
        return $this->belongsTo(User::class, 'solicitante_id');
    }

    public function aprobador()
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }

    public function gestorCompra()
    {
        return $this->belongsTo(User::class, 'gestionado_por');
    }

    public function receptor()
    {
        return $this->belongsTo(User::class, 'recibido_por');
    }

    public function insumo()
    {
        return $this->belongsTo(Insumo::class, 'insumo_id');
    }

    public function bodegaDestino()
    {
        return $this->belongsTo(Bodega::class, 'bodega_destino_id');
    }

    public function movimientoInventario()
    {
        return $this->belongsTo(MovimientoInventario::class, 'movimiento_inventario_id');
    }

    public function facturaInventario()
    {
        return $this->belongsTo(FacturaInventario::class, 'factura_inventario_id');
    }

    public function ordenCompra()
    {
        return $this->hasOne(OrdenCompra::class, 'solicitud_compra_id');
    }

    public function getEstadoLabelAttribute(): string
    {
        return str_replace('_', ' ', $this->estado);
    }

    public function getDetalleItemsResolvedAttribute(): array
    {
        $items = $this->detalle_items;

        if (is_array($items) && $items !== []) {
            return array_values(array_filter(array_map(function ($item) {
                if (! is_array($item)) {
                    return null;
                }

                return [
                    'insumo_id' => $item['insumo_id'] ?? null,
                    'descripcion' => trim((string) ($item['descripcion'] ?? '')),
                    'categoria' => trim((string) ($item['categoria'] ?? '')),
                    'unidad' => trim((string) ($item['unidad'] ?? '')),
                    'cantidad' => (float) ($item['cantidad'] ?? 0),
                    'precio_estimado' => isset($item['precio_estimado']) && $item['precio_estimado'] !== ''
                        ? (float) $item['precio_estimado']
                        : null,
                ];
            }, $items)));
        }

        return [[
            'insumo_id' => $this->insumo_id,
            'descripcion' => $this->asunto,
            'categoria' => '',
            'unidad' => $this->unidad,
            'cantidad' => (float) ($this->cantidad ?? 0),
            'precio_estimado' => $this->precio_estimado !== null ? (float) $this->precio_estimado : null,
        ]];
    }

    public function getDetalleItemsCountAttribute(): int
    {
        return count($this->detalle_items_resolved);
    }
}