<?php

namespace App\Http\Controllers\Reporteria;

use App\Http\Controllers\Controller;
use App\Models\Consumo;
use App\Models\Labore;
use App\Models\planes_detalles;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ManoObraReportController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);
        if (! in_array($perPage, [5, 10, 20, 50], true)) {
            $perPage = 10;
        }

        $labores = Labore::orderBy('nombre')->get();
        $planificado = planes_detalles::where('categoria', 'Mano de Obra')->get();

        $ejecuciones = Consumo::with(['cultivo', 'detalles'])
            ->orderByDesc('fecha_consumo')
            ->get()
            ->flatMap(function ($consumo) {
                return $consumo->detalles
                    ->where('categoria', 'Mano de Obra')
                    ->map(function ($detalle) use ($consumo) {
                        return [
                            'fecha' => $consumo->fecha_consumo,
                            'cultivo' => $consumo->cultivo->nombre ?? '-',
                            'descripcion' => $detalle->descripcion,
                            'cantidad' => (float) $detalle->cantidad,
                            'unidad_medida' => $detalle->unidad_medida,
                            'subtotal' => (float) $detalle->subtotal,
                        ];
                    });
            })
            ->values();

        $metricas = [
            'catalogo' => $labores->count(),
            'activas' => $labores->where('estado', 1)->count(),
            'costo_promedio' => (float) $labores->avg('costo_unitario'),
            'planificado' => (float) $planificado->sum('subtotal'),
            'ejecutado' => (float) $ejecuciones->sum('subtotal'),
        ];

        $ejecuciones = $this->paginarColeccion($ejecuciones, $perPage, $request);

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

        return view('modules.reporteria.mano_obra', compact('labores', 'metricas', 'resumenSecundaria', 'ejecuciones', 'perPage'));
    }

    private function paginarColeccion(mixed $items, int $perPage, Request $request): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage();
        $total = $items->count();
        $resultados = $items->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $resultados,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }
}
