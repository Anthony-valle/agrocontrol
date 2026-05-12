<?php

namespace App\Http\Controllers\Reporteria;

use App\Http\Controllers\Controller;
use App\Exports\CultivosReportExport;
use App\Models\Cultivo;
use App\Models\Lote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class CultivosReportController extends Controller
{
    public function index(Request $request)
    {
        $lotes = Lote::orderBy('nombre')->get(['id', 'nombre']);

        $cultivos = $this->buildRows($request);

        $metricas = [
            'registros' => $cultivos->count(),
            'activos' => $cultivos->where('estado', 'Activo')->count(),
            'inversion' => $cultivos->sum('inversion'),
            'ingresos' => $cultivos->sum('ingresos'),
            'disponible' => $cultivos->sum('disponible'),
        ];

        return view('modules.reporteria.cultivos', compact('lotes', 'cultivos', 'metricas'));
    }

    public function exportExcel(Request $request)
    {
        $cultivos = $this->buildRows($request);
        $fileName = 'reporte_cultivos_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new CultivosReportExport($cultivos), $fileName);
    }

    public function exportPdf(Request $request)
    {
        $cultivos = $this->buildRows($request);
        $metricas = [
            'registros' => $cultivos->count(),
            'activos' => $cultivos->where('estado', 'Activo')->count(),
            'inversion' => $cultivos->sum('inversion'),
            'ingresos' => $cultivos->sum('ingresos'),
            'disponible' => $cultivos->sum('disponible'),
        ];

        $pdf = Pdf::loadView('modules.reporteria.cultivos_pdf', compact('cultivos', 'metricas'));

        return $pdf->download('reporte_cultivos_' . now()->format('Ymd_His') . '.pdf');
    }

    public function show(int $cultivoId)
    {
        $cultivo = $this->baseCultivoQuery(['consumos.detalles', 'planes.detalles'])
            ->findOrFail($cultivoId);

        $facturas = $this->facturasDeCosechas($cultivo->cosechas);

        $metricas = [
            'planes' => $cultivo->planes->count(),
            'consumos' => $cultivo->consumos->count(),
            'inversion' => (float) $cultivo->consumos->sum('total'),
            'cosecha_neta' => (float) $cultivo->cosechas->sum('cantidad_neta'),
            'disponible' => (float) $cultivo->cosechas->sum('cantidad_disponible'),
            'ingresos' => (float) $facturas->sum('total'),
        ];

        $consumosRecientes = $cultivo->consumos->sortByDesc('fecha_consumo')->take(8);
        $cosechasRecientes = $cultivo->cosechas->sortByDesc('fecha_cosecha')->take(8);
        $fechasConsumo = $this->buildFechasConsumo($cultivo);
        $categoriasConsumoReporte = $this->buildCategoriasConsumoReporte($cultivo);

        return view('modules.reporteria.cultivos_show', compact('cultivo', 'metricas', 'consumosRecientes', 'cosechasRecientes', 'fechasConsumo', 'categoriasConsumoReporte'));
    }

    public function consumosPorFecha(Request $request, int $cultivoId)
    {
        $cultivo = $this->baseCultivoQuery([
            'consumos.detalles',
            'consumos.validador',
            'consumos.anulador',
        ])->findOrFail($cultivoId);

        $fecha = trim((string) $request->query('fecha', ''));

        if ($fecha === '') {
            abort(422, 'La fecha es requerida.');
        }

        $consumos = $cultivo->consumos
            ->filter(fn ($consumo) => (string) $consumo->fecha_consumo === $fecha)
            ->sortByDesc('created_at')
            ->values();

        $totalFecha = (float) $consumos->sum('total');

        return view('modules.reporteria.partials.cultivo_consumos_fecha', [
            'cultivo' => $cultivo,
            'fecha' => $fecha,
            'consumos' => $consumos,
            'totalFecha' => $totalFecha,
        ]);
    }

    public function consumosPorCategoria(Request $request, int $cultivoId)
    {
        $cultivo = $this->baseCultivoQuery([
            'consumos.detalles.insumo',
            'consumos.detalles.bodega',
            'consumos.validador',
            'consumos.anulador',
        ])->findOrFail($cultivoId);

        $categoria = trim((string) $request->query('categoria', ''));

        if ($categoria === '') {
            abort(422, 'La categoria es requerida.');
        }

        $categoriaNormalizada = $this->normalizarCategoriaConsumo($categoria);

        $detallesCategoria = $cultivo->consumos
            ->sortByDesc('fecha_consumo')
            ->flatMap(function ($consumo) use ($categoriaNormalizada) {
                return $consumo->detalles
                    ->filter(fn ($detalle) => $this->normalizarCategoriaConsumo($detalle->categoria) === $categoriaNormalizada)
                    ->map(function ($detalle) use ($consumo, $categoriaNormalizada) {
                        $esManoObra = $categoriaNormalizada === 'Mano De Obra';

                        return (object) [
                            'consumo_id' => $consumo->id,
                            'fecha' => (string) $consumo->fecha_consumo,
                            'estado' => $consumo->estado_normalizado,
                            'categoria' => $categoriaNormalizada,
                            'codigo' => $esManoObra ? null : ($detalle->insumo->codigo ?? null),
                            'descripcion' => $detalle->descripcion,
                            'insumo' => $esManoObra ? null : ($detalle->insumo->nombre ?? null),
                            'bodega' => $detalle->bodega->nombre ?? '-',
                            'lote' => $detalle->lote ?: '-',
                            'cantidad' => (float) $detalle->cantidad,
                            'unidad_medida' => $detalle->unidad_medida ?: '-',
                            'costo_unitario' => (float) $detalle->costo_unitario,
                            'subtotal' => (float) $detalle->subtotal,
                        ];
                    });
            })
            ->values();

        return view('modules.reporteria.partials.cultivo_consumos_categoria', [
            'cultivo' => $cultivo,
            'categoria' => $categoriaNormalizada,
            'detallesCategoria' => $detallesCategoria,
            'totalCategoria' => (float) $detallesCategoria->sum('subtotal'),
            'cantidadCategoria' => (float) $detallesCategoria->sum('cantidad'),
            'fechasCategoria' => $detallesCategoria->pluck('fecha')->filter()->unique()->sortDesc()->values(),
        ]);
    }

    private function buildRows(Request $request)
    {
        return $this->baseCultivoQuery()
            ->withSum('consumos', 'total')
            ->when($request->filled('lote_id'), fn ($query) => $query->where('lotes_id', $request->lote_id))
            ->when($request->filled('estado'), fn ($query) => $query->where('estado', $request->estado))
            ->orderByDesc('fecha_siembra')
            ->get()
            ->map(function ($cultivo) {
                $facturas = $this->facturasDeCosechas($cultivo->cosechas);
                $ingresos = (float) $facturas->sum('total');
                $inversion = (float) ($cultivo->consumos_sum_total ?? 0);

                return [
                    'id' => $cultivo->id,
                    'nombre' => $cultivo->nombre,
                    'codigo' => $cultivo->codigo,
                    'lote' => $cultivo->lote->nombre ?? '-',
                    'estado' => $cultivo->estado,
                    'unidad_medida' => $cultivo->unidad_medida,
                    'fecha_siembra' => $cultivo->fecha_siembra,
                    'hectareas' => (float) ($cultivo->hectareas ?? 0),
                    'inversion' => $inversion,
                    'produccion' => (float) $cultivo->cosechas->sum('cantidad_neta'),
                    'disponible' => (float) $cultivo->cosechas->sum('cantidad_disponible'),
                    'ingresos' => $ingresos,
                    'utilidad' => $ingresos - $inversion,
                ];
            });
    }

    private function baseCultivoQuery(array $extraRelations = [])
    {
        $relations = array_merge(['lote', 'cosechas'], $extraRelations);

        if ($this->cosechaFacturasDisponible()) {
            $relations[] = 'cosechas.facturas';
        }

        return Cultivo::with($relations);
    }

    private function facturasDeCosechas(mixed $cosechas)
    {
        if (! $this->cosechaFacturasDisponible()) {
            return collect();
        }

        return $cosechas->flatMap->facturas;
    }

    private function cosechaFacturasDisponible(): bool
    {
        return Schema::hasTable('cosecha_facturas');
    }

    private function buildFechasConsumo(Cultivo $cultivo)
    {
        return $cultivo->consumos
            ->filter(fn ($consumo) => ! empty($consumo->fecha_consumo))
            ->groupBy(fn ($consumo) => (string) $consumo->fecha_consumo)
            ->map(function ($consumos, $fecha) {
                return (object) [
                    'fecha' => $fecha,
                    'registros' => $consumos->count(),
                    'total' => (float) $consumos->sum('total'),
                ];
            })
            ->sortByDesc('fecha')
            ->values();
    }

    private function buildCategoriasConsumoReporte(Cultivo $cultivo)
    {
        return $cultivo->consumos
            ->flatMap(function ($consumo) {
                return $consumo->detalles->map(function ($detalle) use ($consumo) {
                    return (object) [
                        'categoria' => $this->normalizarCategoriaConsumo($detalle->categoria),
                        'fecha' => (string) $consumo->fecha_consumo,
                        'cantidad' => (float) $detalle->cantidad,
                        'subtotal' => (float) $detalle->subtotal,
                    ];
                });
            })
            ->groupBy('categoria')
            ->map(function ($items, $categoria) {
                $fechas = $items->pluck('fecha')
                    ->filter()
                    ->unique()
                    ->sortDesc()
                    ->values();

                return (object) [
                    'categoria' => $categoria,
                    'registros' => $items->count(),
                    'cantidad_total' => (float) $items->sum('cantidad'),
                    'total' => (float) $items->sum('subtotal'),
                    'fechas' => $fechas,
                    'primera_fecha' => $fechas->last(),
                    'ultima_fecha' => $fechas->first(),
                ];
            })
            ->sortByDesc('total')
            ->values();
    }

    private function normalizarCategoriaConsumo(?string $categoria): string
    {
        $texto = trim((string) $categoria);

        if ($texto === '') {
            return 'Otros Insumos';
        }

        return mb_convert_case($texto, MB_CASE_TITLE, 'UTF-8');
    }
}
