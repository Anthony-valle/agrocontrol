<?php

namespace App\Http\Controllers\Reporteria;

use App\Exports\CosechasReportExport;
use App\Http\Controllers\Controller;
use App\Models\Cosecha;
use App\Models\Cultivo;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Maatwebsite\Excel\Facades\Excel;

class CosechasReportController extends Controller
{
    private const REGISTROS_POR_PAGINA = 10;

    public function index(Request $request)
    {
        $cultivos = Cultivo::orderBy('nombre')->get(['id', 'nombre', 'unidad_medida']);
        $hayFiltros = $request->filled('cultivo_id')
            || $request->filled('desde')
            || $request->filled('hasta');
        $cultivoSeleccionado = $request->filled('cultivo_id')
            ? $cultivos->firstWhere('id', (int) $request->cultivo_id)
            : null;

        if ($hayFiltros) {
            $query = $this->cosechasFiltradas($request);
            $cosechasMetricas = (clone $query)->get();
            $cosechas = $query->paginate(self::REGISTROS_POR_PAGINA)->withQueryString();
        } else {
            $cosechasMetricas = collect();
            $cosechas = new LengthAwarePaginator([], 0, self::REGISTROS_POR_PAGINA, 1, [
                'path' => route('reporteria.cosechas'),
                'pageName' => 'page',
            ]);
        }

        $filtrosAplicados = [
            'cultivo' => $cultivoSeleccionado?->nombre,
            'desde' => $request->filled('desde') ? Carbon::parse($request->desde)->format('d/m/Y') : null,
            'hasta' => $request->filled('hasta') ? Carbon::parse($request->hasta)->format('d/m/Y') : null,
        ];

        $totales = [
            'registros' => $cosechasMetricas->count(),
            'bruta' => $cosechasMetricas->sum('cantidad_bruta'),
            'descarte' => $cosechasMetricas->sum('descarte'),
            'neta' => $cosechasMetricas->sum('cantidad_neta'),
            'disponible' => $cosechasMetricas->sum('cantidad_disponible'),
            'rendimiento' => $cosechasMetricas->sum('cantidad_bruta') > 0
                ? ($cosechasMetricas->sum('cantidad_neta') / $cosechasMetricas->sum('cantidad_bruta')) * 100
                : 0,
        ];

        $resumenPorCultivo = $cosechasMetricas
            ->groupBy(fn ($cosecha) => $cosecha->cultivo->nombre ?? 'Sin cultivo')
            ->map(function ($items, $nombreCultivo) {
                $bruta = $items->sum('cantidad_bruta');
                $neta = $items->sum('cantidad_neta');

                return [
                    'cultivo' => $nombreCultivo,
                    'unidad_medida' => $items->first()?->unidad_medida,
                    'lote' => $items->first()?->cultivo?->lote?->nombre ?? '-',
                    'registros' => $items->count(),
                    'bruta' => $bruta,
                    'descarte' => $items->sum('descarte'),
                    'neta' => $neta,
                    'disponible' => $items->sum('cantidad_disponible'),
                    'rendimiento' => $bruta > 0 ? ($neta / $bruta) * 100 : 0,
                    'ultima_fecha' => optional($items->sortByDesc('fecha_cosecha')->first())->fecha_cosecha,
                ];
            })
            ->sortByDesc('neta')
            ->values();

        $resumenMensual = $cosechasMetricas
            ->groupBy(fn ($cosecha) => Carbon::parse($cosecha->fecha_cosecha)->format('Y-m'))
            ->map(function ($items, $periodo) {
                $bruta = $items->sum('cantidad_bruta');
                $neta = $items->sum('cantidad_neta');

                return [
                    'periodo' => Carbon::createFromFormat('Y-m', $periodo)->translatedFormat('F Y'),
                    'bruta' => $bruta,
                    'descarte' => $items->sum('descarte'),
                    'neta' => $neta,
                    'rendimiento' => $bruta > 0 ? ($neta / $bruta) * 100 : 0,
                ];
            })
            ->values();

        return view('modules.reporteria.cosechas', compact(
            'cultivos',
            'cosechas',
            'totales',
            'resumenPorCultivo',
            'resumenMensual',
            'filtrosAplicados',
            'hayFiltros'
        ));
    }

    public function exportExcel(Request $request)
    {
        $cosechas = $this->cosechasFiltradas($request)->get();
        $fileName = 'reporte_cosechas_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new CosechasReportExport($cosechas), $fileName);
    }

    public function exportPdf(Request $request)
    {
        $cosechas = $this->cosechasFiltradas($request)->get();
        $filtros = $request->only(['cultivo_id', 'desde', 'hasta']);

        $totales = [
            'registros' => $cosechas->count(),
            'bruta' => $cosechas->sum('cantidad_bruta'),
            'descarte' => $cosechas->sum('descarte'),
            'neta' => $cosechas->sum('cantidad_neta'),
            'disponible' => $cosechas->sum('cantidad_disponible'),
            'rendimiento' => $cosechas->sum('cantidad_bruta') > 0
                ? ($cosechas->sum('cantidad_neta') / $cosechas->sum('cantidad_bruta')) * 100
                : 0,
        ];

        $pdf = Pdf::loadView('modules.reporteria.cosechas_pdf', compact('cosechas', 'filtros', 'totales'));

        return $pdf->download('reporte_cosechas_' . now()->format('Ymd_His') . '.pdf');
    }

    private function cosechasFiltradas(Request $request)
    {
        return Cosecha::with(['cultivo.lote', 'usuario'])
            ->when($request->filled('cultivo_id'), fn ($query) => $query->where('cultivo_id', $request->cultivo_id))
            ->when($request->filled('desde'), fn ($query) => $query->whereDate('fecha_cosecha', '>=', $request->desde))
            ->when($request->filled('hasta'), fn ($query) => $query->whereDate('fecha_cosecha', '<=', $request->hasta))
            ->orderByDesc('fecha_cosecha');
    }
}
