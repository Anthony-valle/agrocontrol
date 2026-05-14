<?php

namespace App\Http\Controllers\Reporteria;

use App\Http\Controllers\Controller;
use App\Models\Cultivo;
use App\Models\Lote;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class RentabilidadReportController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'lote_id' => ['nullable', 'integer', 'exists:lotes,id'],
            'cultivo_id' => ['nullable', 'integer', 'exists:cultivos,id'],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
        ]);

        $relations = ['lote', 'consumos', 'cosechas'];
        $hayTablaFacturas = Schema::hasTable('cosecha_facturas');

        if ($hayTablaFacturas) {
            $relations[] = 'cosechas.facturas';
        }

        $hayFiltros = $request->filled('lote_id')
            || $request->filled('cultivo_id')
            || $request->filled('fecha_inicio')
            || $request->filled('fecha_fin');

        $fechaInicio = $this->normalizarFechaFiltro($request->input('fecha_inicio'))?->startOfDay();
        $fechaFin = $this->normalizarFechaFiltro($request->input('fecha_fin'))?->endOfDay();

        $lotes = Lote::orderBy('nombre')->get(['id', 'nombre']);
        $cultivos = Cultivo::orderBy('nombre')->get(['id', 'nombre', 'lotes_id']);

        $rentabilidad = Cultivo::with($relations)
            ->when($request->filled('lote_id'), fn ($query) => $query->where('lotes_id', $request->integer('lote_id')))
            ->when($request->filled('cultivo_id'), fn ($query) => $query->where('id', $request->integer('cultivo_id')))
            ->orderBy('nombre')
            ->get()
            ->map(function ($cultivo) use ($fechaInicio, $fechaFin, $hayTablaFacturas) {
                $consumos = $cultivo->consumos->filter(fn ($consumo) => $this->fechaDentroDeRango($consumo->fecha_consumo, $fechaInicio, $fechaFin));
                $cosechas = $cultivo->cosechas->filter(fn ($cosecha) => $this->fechaDentroDeRango($cosecha->fecha_cosecha, $fechaInicio, $fechaFin));
                $facturas = $hayTablaFacturas
                    ? $cultivo->cosechas->flatMap->facturas
                        ->filter(fn ($factura) => $this->fechaDentroDeRango($factura->fecha_factura, $fechaInicio, $fechaFin))
                    : collect();
                $ingresos = (float) $facturas->sum('total');
                $inversion = (float) $consumos->sum('total');
                $utilidad = $ingresos - $inversion;

                return [
                    'id' => $cultivo->id,
                    'nombre' => $cultivo->nombre,
                    'lote' => $cultivo->lote->nombre ?? '-',
                    'estado' => $cultivo->estado,
                    'produccion' => (float) $cosechas->sum('cantidad_neta'),
                    'disponible' => (float) $cosechas->sum('cantidad_disponible'),
                    'inversion' => $inversion,
                    'ingresos' => $ingresos,
                    'utilidad' => $utilidad,
                    'margen' => $ingresos > 0 ? ($utilidad / $ingresos) * 100 : null,
                    'tiene_movimientos_filtrados' => $consumos->isNotEmpty() || $cosechas->isNotEmpty() || $facturas->isNotEmpty(),
                ];
            })
            ->when(
                $request->filled('fecha_inicio') || $request->filled('fecha_fin'),
                fn ($collection) => $collection->filter(fn (array $fila) => $fila['tiene_movimientos_filtrados'])
            )
            ->sortByDesc('utilidad')
            ->values()
            ->map(function (array $fila) {
                unset($fila['tiene_movimientos_filtrados']);

                return $fila;
            });

        $metricas = [
            'cultivos' => $rentabilidad->count(),
            'inversion' => $rentabilidad->sum('inversion'),
            'ingresos' => $rentabilidad->sum('ingresos'),
            'utilidad' => $rentabilidad->sum('utilidad'),
        ];

        return view('modules.reporteria.rentabilidad', compact('rentabilidad', 'metricas', 'lotes', 'cultivos', 'hayFiltros'));
    }

    private function normalizarFechaFiltro(?string $fecha): ?Carbon
    {
        if ($fecha === null || trim($fecha) === '') {
            return null;
        }

        try {
            return Carbon::parse($fecha);
        } catch (\Throwable $error) {
            return null;
        }
    }

    private function fechaDentroDeRango(mixed $fecha, ?Carbon $fechaInicio, ?Carbon $fechaFin): bool
    {
        if ($fechaInicio === null && $fechaFin === null) {
            return true;
        }

        if ($fecha === null || $fecha === '') {
            return false;
        }

        try {
            $fechaEvaluada = Carbon::parse((string) $fecha);
        } catch (\Throwable $error) {
            return false;
        }

        if ($fechaInicio !== null && $fechaEvaluada->lt($fechaInicio)) {
            return false;
        }

        if ($fechaFin !== null && $fechaEvaluada->gt($fechaFin)) {
            return false;
        }

        return true;
    }
}
