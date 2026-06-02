<?php

namespace App\Http\Controllers\Reporteria;

use App\Exports\LotesReportExport;
use App\Exports\LotesDetalleReportExport;
use App\Http\Controllers\Controller;
use App\Models\Cosecha;
use App\Models\Cultivo;
use App\Models\Lote;
use App\Models\Sucursale;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class LotesReportController extends Controller
{
    public function index(Request $request)
    {
        $sucursales = Sucursale::orderBy('nombre')->get(['id', 'nombre']);

        $filas = $this->filasLotes($request);

        $metricas = [
            'lotes' => $filas->count(),
            'area_total' => $filas->sum('area'),
            'cultivos' => $filas->sum('cultivos'),
            'cosecha_neta' => $filas->sum('cosecha_neta'),
        ];

        return view('modules.reporteria.lotes', compact('sucursales', 'filas', 'metricas'));
    }

    public function exportExcel(Request $request)
    {
        $filas = $this->filasLotes($request);
        $fileName = 'reporte_lotes_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new LotesReportExport($filas), $fileName);
    }

    public function exportPdf(Request $request)
    {
        $filas = $this->filasLotes($request);
        $metricas = [
            'lotes' => $filas->count(),
            'area_total' => $filas->sum('area'),
            'cultivos' => $filas->sum('cultivos'),
            'cosecha_neta' => $filas->sum('cosecha_neta'),
        ];

        $pdf = Pdf::loadView('modules.reporteria.lotes_pdf', compact('filas', 'metricas'));

        return $pdf->download('reporte_lotes_' . now()->format('Ymd_His') . '.pdf');
    }

    public function show(int $loteId)
    {
        $data = $this->detalleLoteData($loteId);

        return view('modules.reporteria.lotes_show', $data);
    }

    public function exportDetalleExcel(int $loteId)
    {
        $data = $this->detalleLoteData($loteId);
        $fileName = 'reporte_lote_detalle_' . $data['lote']->id . '_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new LotesDetalleReportExport($data['lote'], $data['cultivos'], $data['cosechas']), $fileName);
    }

    public function exportDetallePdf(int $loteId)
    {
        $data = $this->detalleLoteData($loteId);
        $pdf = Pdf::loadView('modules.reporteria.lotes_show_pdf', $data);

        return $pdf->download('reporte_lote_detalle_' . $data['lote']->id . '_' . now()->format('Ymd_His') . '.pdf');
    }

    private function filasLotes(Request $request)
    {
        $lotes = Lote::with('sucursal')
            ->when($request->filled('sucursal_id'), fn ($query) => $query->where('sucursal_id', $request->sucursal_id))
            ->when($request->filled('estado'), fn ($query) => $query->where('estado', $request->estado))
            ->orderBy('nombre')
            ->get(['id', 'codigo', 'nombre', 'sucursal_id', 'estado', 'area']);

        $cultivosPorLote = Cultivo::select(
            'lotes_id',
            DB::raw('COUNT(*) as total_cultivos'),
            DB::raw("SUM(CASE WHEN estado = 'Activo' THEN 1 ELSE 0 END) as cultivos_activos"),
            DB::raw('SUM(COALESCE(hectareas, 0)) as hectareas_cultivo')
        )
            ->whereNotNull('lotes_id')
            ->groupBy('lotes_id')
            ->get()
            ->keyBy('lotes_id');

        $cosechasPorLote = Cosecha::join('cultivos', 'cultivos.id', '=', 'cosechas.cultivo_id')
            ->select(
                'cultivos.lotes_id',
                DB::raw('SUM(COALESCE(cosechas.cantidad_neta, 0)) as cantidad_neta'),
                DB::raw('SUM(COALESCE(cosechas.cantidad_disponible, 0)) as cantidad_disponible')
            )
            ->whereNotNull('cultivos.lotes_id')
            ->groupBy('cultivos.lotes_id')
            ->get()
            ->keyBy('lotes_id');

        return $lotes->map(function ($lote) use ($cultivosPorLote, $cosechasPorLote) {
            $cultivos = $cultivosPorLote->get($lote->id);
            $cosechas = $cosechasPorLote->get($lote->id);

            return [
                'id' => $lote->id,
                'codigo' => $lote->codigo,
                'nombre' => $lote->nombre,
                'sucursal' => $lote->sucursal->nombre ?? '-',
                'estado' => $lote->estado,
                'area' => (float) ($lote->area ?? 0),
                'cultivos' => (int) ($cultivos->total_cultivos ?? 0),
                'cultivos_activos' => (int) ($cultivos->cultivos_activos ?? 0),
                'hectareas_cultivo' => (float) ($cultivos->hectareas_cultivo ?? 0),
                'cosecha_neta' => (float) ($cosechas->cantidad_neta ?? 0),
                'disponible' => (float) ($cosechas->cantidad_disponible ?? 0),
            ];
        });
    }

    private function detalleLoteData(int $loteId): array
    {
        $lote = Lote::with('sucursal')->findOrFail($loteId);

        $relations = ['cosechas'];
        if (Schema::hasTable('cosecha_facturas')) {
            $relations[] = 'cosechas.facturas';
        }

        $cultivos = Cultivo::with($relations)
            ->withSum('consumos', 'total')
            ->where('lotes_id', $loteId)
            ->orderByDesc('fecha_siembra')
            ->get()
            ->map(function ($cultivo) {
                $facturas = Schema::hasTable('cosecha_facturas')
                    ? $cultivo->cosechas->flatMap->facturas
                    : collect();

                return [
                    'id' => $cultivo->id,
                    'nombre' => $cultivo->nombre,
                    'codigo' => $cultivo->codigo,
                    'estado' => $cultivo->estado,
                    'fecha_siembra' => $cultivo->fecha_siembra,
                    'hectareas' => (float) ($cultivo->hectareas ?? 0),
                    'inversion' => (float) ($cultivo->consumos_sum_total ?? 0),
                    'cosecha_neta' => (float) $cultivo->cosechas->sum('cantidad_neta'),
                    'disponible' => (float) $cultivo->cosechas->sum('cantidad_disponible'),
                    'ventas' => (float) $facturas->sum('total'),
                ];
            });

        $cosechas = Cosecha::with('cultivo')
            ->whereHas('cultivo', fn ($query) => $query->where('lotes_id', $loteId))
            ->latest('fecha_cosecha')
            ->take(10)
            ->get();

        $metricas = [
            'cultivos' => $cultivos->count(),
            'activos' => $cultivos->where('estado', 'Activo')->count(),
            'inversion' => $cultivos->sum('inversion'),
            'ventas' => $cultivos->sum('ventas'),
        ];

        return compact('lote', 'cultivos', 'cosechas', 'metricas');
    }
}
