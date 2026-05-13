<?php

namespace App\Http\Controllers\Reporteria;

use App\Exports\ConsumosFiltradosExport;
use App\Http\Controllers\Controller;
use App\Models\Consumo;
use App\Models\Cultivo;
use App\Models\Lote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Maatwebsite\Excel\Facades\Excel;

class ConsumosReportController extends Controller
{
    private const REGISTROS_POR_PAGINA = 10;

    public function index(Request $request)
    {
        $lotes = Lote::orderBy('nombre')->get(['id', 'nombre']);
        $cultivos = Cultivo::orderBy('nombre')->get(['id', 'nombre', 'lotes_id']);
        $hayFiltros = $request->filled('lote_id')
            || $request->filled('cultivo_id')
            || $request->filled('fecha_inicio')
            || $request->filled('fecha_fin');

        if ($hayFiltros) {
            $query = $this->baseQuery($request);
            $consumosMetricas = (clone $query)->get();
            $consumos = $query->paginate(self::REGISTROS_POR_PAGINA)->withQueryString();
        } else {
            $consumosMetricas = collect();
            $consumos = new LengthAwarePaginator([], 0, self::REGISTROS_POR_PAGINA, 1, [
                'path' => route('reporteria.consumos'),
                'pageName' => 'page',
            ]);
        }

        $metricas = [
            'registros' => $consumosMetricas->count(),
            'lineas' => $consumosMetricas->sum(fn ($consumo) => $consumo->detalles->count()),
            'total' => (float) $consumosMetricas->sum('total'),
            'promedio' => $consumosMetricas->count() > 0 ? (float) $consumosMetricas->avg('total') : 0,
        ];

        return view('modules.reporteria.consumos', compact('lotes', 'cultivos', 'consumos', 'metricas', 'hayFiltros'));
    }

    public function exportExcel(Request $request)
    {
        $consumos = $this->baseQuery($request)->get();
        $fileName = 'reporte_consumos_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new ConsumosFiltradosExport($consumos), $fileName);
    }

    public function exportPdf(Request $request)
    {
        $consumos = $this->baseQuery($request)->get();
        $filtros = $request->only(['lote_id', 'cultivo_id', 'fecha_inicio', 'fecha_fin']);

        $pdf = Pdf::loadView('modules.reporteria.consumos_pdf', compact('consumos', 'filtros'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('reporte_consumos_' . now()->format('Ymd_His') . '.pdf');
    }

    private function baseQuery(Request $request)
    {
        return Consumo::with(['cultivo.lote', 'detalles.insumo'])
            ->when($request->filled('lote_id'), function ($query) use ($request) {
                $query->whereHas('cultivo', function ($cultivoQuery) use ($request) {
                    $cultivoQuery->where('lotes_id', $request->lote_id);
                });
            })
            ->when($request->filled('cultivo_id'), fn ($query) => $query->where('cultivo_id', $request->cultivo_id))
            ->when($request->filled('fecha_inicio'), fn ($query) => $query->whereDate('fecha_consumo', '>=', $request->fecha_inicio))
            ->when($request->filled('fecha_fin'), fn ($query) => $query->whereDate('fecha_consumo', '<=', $request->fecha_fin))
            ->orderByDesc('fecha_consumo');
    }
}
