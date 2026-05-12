<?php

namespace App\Http\Controllers\Reporteria;

use App\Http\Controllers\Controller;
use App\Models\Consumo;
use App\Models\Cosecha;
use App\Models\CosechaFactura;
use App\Models\Cultivo;
use App\Models\InventarioBodega;
use App\Models\Lote;
use App\Models\Notificaciones;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DashboardReportController extends Controller
{
    public function index()
    {
        $cosechaFacturasDisponible = Schema::hasTable('cosecha_facturas');

        $metricas = [
            'lotes' => Lote::count(),
            'cultivos_activos' => Cultivo::where('estado', 'Activo')->count(),
            'cosecha_neta' => Cosecha::sum('cantidad_neta'),
            'ventas' => $cosechaFacturasDisponible ? CosechaFactura::sum('total') : 0,
            'consumos' => Consumo::sum('total'),
            'alertas' => Notificaciones::where('leido', false)->count(),
        ];

        $cultivos = Cultivo::with(['lote', 'cosechas'])
            ->withSum('consumos', 'total')
            ->orderBy('nombre')
            ->get();

        $topCultivos = $cultivos->map(function ($cultivo) {
            $facturas = Schema::hasTable('cosecha_facturas')
                ? $cultivo->cosechas->flatMap->facturas
                : collect();
            $ingresos = $facturas->sum('total');
            $produccion = $cultivo->cosechas->sum('cantidad_neta');

            return [
                'id' => $cultivo->id,
                'nombre' => $cultivo->nombre,
                'lote' => $cultivo->lote->nombre ?? '-',
                'estado' => $cultivo->estado,
                'produccion' => $produccion,
                'inversion' => (float) ($cultivo->consumos_sum_total ?? 0),
                'ingresos' => $ingresos,
                'utilidad' => $ingresos - (float) ($cultivo->consumos_sum_total ?? 0),
            ];
        })->sortByDesc('ingresos')->take(8)->values();

        $stockCritico = InventarioBodega::with(['insumo', 'bodega.sucursal'])
            ->get()
            ->filter(function ($item) {
                return $item->insumo
                    && $item->insumo->stock_minimo !== null
                    && $item->stock_actual <= $item->insumo->stock_minimo;
            })
            ->sortBy(fn ($item) => $item->stock_actual - ($item->insumo->stock_minimo ?? 0))
            ->take(8)
            ->values();

        $actividadReciente = collect()
            ->merge(
                Consumo::with('cultivo')
                    ->latest('fecha_consumo')
                    ->take(8)
                    ->get()
                    ->map(fn ($consumo) => [
                        'tipo' => 'Consumo',
                        'fecha' => $consumo->fecha_consumo,
                        'titulo' => $consumo->cultivo->nombre ?? 'Cultivo sin nombre',
                        'detalle' => 'Consumo registrado por ' . agro_number((float) $consumo->total, 2) . ' Lps',
                    ])
            )
            ->merge(
                Cosecha::with('cultivo')
                    ->latest('fecha_cosecha')
                    ->take(8)
                    ->get()
                    ->map(fn ($cosecha) => [
                        'tipo' => 'Cosecha',
                        'fecha' => $cosecha->fecha_cosecha,
                        'titulo' => $cosecha->cultivo->nombre ?? 'Cultivo sin nombre',
                        'detalle' => 'Producción neta: ' . agro_number((float) $cosecha->cantidad_neta, 2) . ' ' . ($cosecha->unidad_medida ?? ''),
                    ])
            )
            ->sortByDesc('fecha')
            ->take(12)
            ->values();

        return view('modules.reporteria.dashboard', compact('metricas', 'topCultivos', 'stockCritico', 'actividadReciente'));
    }
}
