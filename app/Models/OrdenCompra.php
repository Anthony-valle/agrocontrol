<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrdenCompra extends Model
{
    use HasFactory;

    protected $table = 'ordenes_compra';

    protected $fillable = [
        'empresa_id',
        'solicitud_compra_id',
        'generado_por',
        'recibido_por',
        'diferencias_aprobadas_por',
        'codigo',
        'proveedor',
        'fecha_emision',
        'recibido_en',
        'diferencias_aprobadas_en',
        'estado',
        'recepcion_estado',
        'total_estimado',
        'observaciones',
        'recepcion_observaciones',
        'diferencias_observaciones',
        'detalle_items',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'recibido_en' => 'datetime',
        'diferencias_aprobadas_en' => 'datetime',
        'total_estimado' => 'decimal:3',
        'detalle_items' => 'array',
    ];

    public function solicitudCompra()
    {
        return $this->belongsTo(SolicitudCompra::class, 'solicitud_compra_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'generado_por');
    }

    public function receptor()
    {
        return $this->belongsTo(User::class, 'recibido_por');
    }

    public function aprobadorDiferencias()
    {
        return $this->belongsTo(User::class, 'diferencias_aprobadas_por');
    }

    public function getDetalleItemsResolvedAttribute(): array
    {
        return collect($this->detalle_items ?? [])
            ->filter(fn ($item) => is_array($item))
            ->map(function (array $item) {
                $cantidadSolicitada = (float) ($item['cantidad'] ?? 0);
                $cantidadRecibida = isset($item['cantidad_recibida']) && $item['cantidad_recibida'] !== ''
                    ? (float) $item['cantidad_recibida']
                    : null;

                $faltante = isset($item['cantidad_faltante'])
                    ? (float) $item['cantidad_faltante']
                    : ($cantidadRecibida !== null ? max($cantidadSolicitada - $cantidadRecibida, 0) : null);

                $excedente = isset($item['cantidad_excedente'])
                    ? (float) $item['cantidad_excedente']
                    : ($cantidadRecibida !== null ? max($cantidadRecibida - $cantidadSolicitada, 0) : null);

                return [
                    'descripcion' => trim((string) ($item['descripcion'] ?? '')),
                    'categoria' => trim((string) ($item['categoria'] ?? '')),
                    'unidad' => trim((string) ($item['unidad'] ?? '')),
                    'cantidad' => $cantidadSolicitada,
                    'precio_unitario' => isset($item['precio_unitario']) && $item['precio_unitario'] !== '' ? (float) $item['precio_unitario'] : null,
                    'subtotal' => isset($item['subtotal']) && $item['subtotal'] !== '' ? (float) $item['subtotal'] : null,
                    'cantidad_recibida' => $cantidadRecibida,
                    'cantidad_faltante' => $faltante,
                    'cantidad_excedente' => $excedente,
                    'estado_recepcion' => $item['estado_recepcion'] ?? null,
                ];
            })
            ->values()
            ->all();
    }

    public function getRecepcionResumenAttribute(): array
    {
        $items = collect($this->detalle_items_resolved);

        return [
            'items' => $items->count(),
            'completos' => $items->where('estado_recepcion', 'completa')->count(),
            'con_diferencias' => $items->filter(fn ($item) => in_array($item['estado_recepcion'], ['faltante', 'excedente'], true))->count(),
            'pendientes' => $items->filter(fn ($item) => empty($item['estado_recepcion']))->count(),
            'solicitado_total' => $items->sum('cantidad'),
            'recibido_total' => $items->sum(fn ($item) => (float) ($item['cantidad_recibida'] ?? 0)),
            'faltante_total' => $items->sum(fn ($item) => (float) ($item['cantidad_faltante'] ?? 0)),
            'excedente_total' => $items->sum(fn ($item) => (float) ($item['cantidad_excedente'] ?? 0)),
        ];
    }

    public function getRecepcionEstadoLabelAttribute(): string
    {
        return match ($this->recepcion_estado) {
            'completa' => 'Completa',
            'con_diferencias' => 'Con diferencias',
            'diferencias_aprobadas' => 'Diferencias aprobadas',
            default => 'Pendiente',
        };
    }
}