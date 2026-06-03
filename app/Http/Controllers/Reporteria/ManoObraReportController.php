<?php

namespace App\Http\Controllers\Reporteria;

use App\Http\Controllers\Controller;
use App\Models\Consumo_detalles;
use App\Models\Labore;
use App\Models\planes_detalles;
use Illuminate\Http\Request;

class ManoObraReportController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);
        if (! in_array($perPage, [5, 10, 20, 50], true)) {
            $perPage = 10;
        }

        $labores = Labore::orderBy('nombre')->get();
        $planificado = (float) planes_detalles::where('categoria', 'Mano de Obra')->sum('subtotal');

        $ejecucionesQuery = Consumo_detalles::query()
            ->join('consumos', 'consumos.id', '=', 'consumo_detalles.consumo_id')
            ->leftJoin('cultivos', 'cultivos.id', '=', 'consumos.cultivo_id')
            ->where('consumo_detalles.categoria', 'Mano de Obra')
            ->orderByDesc('consumos.fecha_consumo')
            ->select([
                'consumos.fecha_consumo as fecha',
                'cultivos.nombre as cultivo',
                'consumo_detalles.descripcion',
                'consumo_detalles.cantidad',
                'consumo_detalles.unidad_medida',
                'consumo_detalles.subtotal',
            ]);

        $resumenCultivos = (clone $ejecucionesQuery)
            ->get()
            ->groupBy(fn ($fila) => trim((string) ($fila->cultivo ?: 'Sin cultivo')))
            ->map(function ($items, $cultivo) {
                return [
                    'cultivo' => $cultivo,
                    'registros' => $items->count(),
                    'actividades' => $items->pluck('descripcion')->filter()->unique()->count(),
                    'cantidad_total' => (float) $items->sum('cantidad'),
                    'subtotal_total' => (float) $items->sum('subtotal'),
                ];
            })
            ->sortByDesc('subtotal_total')
            ->values();

        $costosPorCultivoActividad = Consumo_detalles::query()
            ->join('consumos', 'consumos.id', '=', 'consumo_detalles.consumo_id')
            ->leftJoin('cultivos', 'cultivos.id', '=', 'consumos.cultivo_id')
            ->where('consumo_detalles.categoria', 'Mano de Obra')
            ->groupBy('cultivos.nombre', 'consumo_detalles.descripcion', 'consumo_detalles.unidad_medida')
            ->orderByRaw('SUM(consumo_detalles.subtotal) DESC')
            ->selectRaw('COALESCE(cultivos.nombre, ?) as cultivo', ['Sin cultivo'])
            ->selectRaw('COALESCE(consumo_detalles.descripcion, ?) as actividad', ['Sin actividad'])
            ->selectRaw('COALESCE(consumo_detalles.unidad_medida, ?) as unidad_medida', [''])
            ->selectRaw('COUNT(*) as registros')
            ->selectRaw('SUM(consumo_detalles.cantidad) as cantidad_total')
            ->selectRaw('SUM(consumo_detalles.subtotal) as subtotal_total')
            ->paginate($perPage, ['*'], 'detalle_page')
            ->withQueryString();

        $ejecutado = (float) (clone $ejecucionesQuery)->sum('consumo_detalles.subtotal');

        $ejecuciones = $ejecucionesQuery
            ->paginate($perPage)
            ->withQueryString();

        $metricas = [
            'catalogo' => $labores->count(),
            'activas' => $labores->where('estado', 1)->count(),
            'costo_promedio' => (float) $labores->avg('costo_unitario'),
            'planificado' => $planificado,
            'ejecutado' => $ejecutado,
            'cultivos_con_ejecucion' => $resumenCultivos->count(),
        ];

        $resumenSecundaria = $labores
            ->groupBy(fn ($labor) => $labor->actividad_secundaria ?: 'Sin actividad secundaria')
            ->map(function ($items, $actividad) {
                return [
                    'actividad' => $actividad,
                    'registros' => $items->count(),
                    'costo_promedio' => (float) $items->avg('costo_unitario'),
                    'activas' => $items->where('estado', 1)->count(),
                ];
            })
            ->sortByDesc('registros')
            ->values();

        return view('modules.reporteria.mano_obra', compact(
            'labores',
            'metricas',
            'resumenSecundaria',
            'resumenCultivos',
            'costosPorCultivoActividad',
            'ejecuciones',
            'perPage'
        ));
    }
}
