<?php

namespace App\Http\Controllers\Reporteria;

use App\Exports\CultivoCategoriaConsumosExport;
use App\Http\Controllers\Controller;
use App\Exports\CultivosReportExport;
use App\Models\Consumo;
use App\Models\Cosecha;
use App\Models\CosechaFactura;
use App\Models\Cultivo;
use App\Models\Lote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class CultivosReportController extends Controller
{
    public function index(Request $request)
    {
        $lotes = Lote::orderBy('nombre')->get(['id', 'nombre']);
        $perPage = $this->resolverPerPage($request);

        $cultivos = $this->buildRowsPaginadas($request, $perPage);

        $metricas = $this->buildMetricas($request);

        return view('modules.reporteria.cultivos', compact('lotes', 'cultivos', 'metricas', 'perPage'));
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

        $pdf = app('dompdf.wrapper')->loadView('modules.reporteria.cultivos_pdf', compact('cultivos', 'metricas'));

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
        [$cultivo, $categoriaNormalizada, $detallesCategoria, $fecha, $actividad] = $this->resolverDetalleCategoria($request, $cultivoId);

        return view('modules.reporteria.partials.cultivo_consumos_categoria', [
            'cultivo' => $cultivo,
            'categoria' => $categoriaNormalizada,
            'detallesCategoria' => $detallesCategoria,
            'totalCategoria' => (float) $detallesCategoria->sum('subtotal'),
            'cantidadCategoria' => (float) $detallesCategoria->sum('cantidad'),
            'fechasCategoria' => $detallesCategoria->pluck('fecha')->filter()->unique()->sortDesc()->values(),
            'selectedFecha' => $fecha,
            'selectedActividad' => $actividad,
        ]);
    }

    public function exportConsumosCategoriaExcel(Request $request, int $cultivoId)
    {
        [$cultivo, $categoriaNormalizada, $detallesCategoria] = $this->resolverDetalleCategoria($request, $cultivoId);

        $fileName = 'cultivo_' . $cultivo->id . '_categoria_' . Str::slug($categoriaNormalizada, '_') . '_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new CultivoCategoriaConsumosExport($cultivo, $categoriaNormalizada, $detallesCategoria), $fileName);
    }

    public function exportConsumosCategoriaPdf(Request $request, int $cultivoId)
    {
        [$cultivo, $categoriaNormalizada, $detallesCategoria, $fecha, $actividad] = $this->resolverDetalleCategoria($request, $cultivoId);

        $pdf = app('dompdf.wrapper')->loadView('modules.reporteria.cultivo_consumos_categoria_pdf', [
            'cultivo' => $cultivo,
            'categoria' => $categoriaNormalizada,
            'detallesCategoria' => $detallesCategoria,
            'totalCategoria' => (float) $detallesCategoria->sum('subtotal'),
            'cantidadCategoria' => (float) $detallesCategoria->sum('cantidad'),
            'selectedFecha' => $fecha,
            'selectedActividad' => $actividad,
        ]);

        return $pdf->download('cultivo_' . $cultivo->id . '_categoria_' . Str::slug($categoriaNormalizada, '_') . '_' . now()->format('Ymd_His') . '.pdf');
    }

    private function buildRows(Request $request)
    {
        return $this->buildRowsQuery($request)
            ->get()
            ->map(fn ($cultivo) => $this->formatCultivoRow($cultivo));
    }

    private function buildRowsPaginadas(Request $request, int $perPage)
    {
        return $this->buildRowsQuery($request)
            ->paginate($perPage)
            ->through(fn ($cultivo) => $this->formatCultivoRow($cultivo))
            ->withQueryString();
    }

    private function buildRowsQuery(Request $request)
    {
        $facturasDisponibles = $this->cosechaFacturasDisponible();

        $consumosSubquery = Consumo::query()
            ->selectRaw('cultivo_id, COALESCE(SUM(total), 0) as inversion')
            ->groupBy('cultivo_id');

        $cosechasSubquery = Cosecha::query()
            ->selectRaw('cultivo_id, COALESCE(SUM(cantidad_neta), 0) as produccion, COALESCE(SUM(cantidad_disponible), 0) as disponible')
            ->groupBy('cultivo_id');

        $query = Cultivo::query()
            ->leftJoin('lotes', 'cultivos.lotes_id', '=', 'lotes.id')
            ->leftJoinSub($consumosSubquery, 'consumos_totales', function ($join) {
                $join->on('consumos_totales.cultivo_id', '=', 'cultivos.id');
            })
            ->leftJoinSub($cosechasSubquery, 'cosechas_totales', function ($join) {
                $join->on('cosechas_totales.cultivo_id', '=', 'cultivos.id');
            })
            ->when($facturasDisponibles, function ($baseQuery) {
                $facturasSubquery = CosechaFactura::query()
                    ->join('cosechas', 'cosechas.id', '=', 'cosecha_facturas.cosecha_id')
                    ->selectRaw('cosechas.cultivo_id, COALESCE(SUM(cosecha_facturas.total), 0) as ingresos')
                    ->groupBy('cosechas.cultivo_id');

                $baseQuery->leftJoinSub($facturasSubquery, 'facturas_totales', function ($join) {
                    $join->on('facturas_totales.cultivo_id', '=', 'cultivos.id');
                });
            })
            ->when($request->filled('lote_id'), fn ($query) => $query->where('cultivos.lotes_id', $request->lote_id))
            ->when($request->filled('estado'), fn ($query) => $query->where('cultivos.estado', $request->estado))
            ->select([
                'cultivos.id',
                'cultivos.nombre',
                'cultivos.codigo',
                'cultivos.estado',
                'cultivos.unidad_medida',
                'cultivos.fecha_siembra',
                'cultivos.hectareas',
                DB::raw("COALESCE(lotes.nombre, '-') as lote_nombre"),
                DB::raw('COALESCE(consumos_totales.inversion, 0) as inversion'),
                DB::raw('COALESCE(cosechas_totales.produccion, 0) as produccion'),
                DB::raw('COALESCE(cosechas_totales.disponible, 0) as disponible'),
                DB::raw($facturasDisponibles ? 'COALESCE(facturas_totales.ingresos, 0) as ingresos' : '0 as ingresos'),
            ])
            ->orderByDesc('cultivos.fecha_siembra');

        return $query;
    }

    private function formatCultivoRow(object $cultivo): array
    {
        $ingresos = (float) ($cultivo->ingresos ?? 0);
        $inversion = (float) ($cultivo->inversion ?? 0);

        return [
            'id' => (int) $cultivo->id,
            'nombre' => $cultivo->nombre,
            'codigo' => $cultivo->codigo,
            'lote' => $cultivo->lote_nombre ?? '-',
            'estado' => $cultivo->estado,
            'unidad_medida' => $cultivo->unidad_medida,
            'fecha_siembra' => $cultivo->fecha_siembra,
            'hectareas' => (float) ($cultivo->hectareas ?? 0),
            'inversion' => $inversion,
            'produccion' => (float) ($cultivo->produccion ?? 0),
            'disponible' => (float) ($cultivo->disponible ?? 0),
            'ingresos' => $ingresos,
            'utilidad' => $ingresos - $inversion,
        ];
    }

    private function buildMetricas(Request $request): array
    {
        $metricas = DB::query()
            ->fromSub($this->buildRowsQuery($request), 'cultivos_reporte')
            ->selectRaw('COUNT(*) as registros')
            ->selectRaw("COALESCE(SUM(CASE WHEN estado = 'Activo' THEN 1 ELSE 0 END), 0) as activos")
            ->selectRaw('COALESCE(SUM(inversion), 0) as inversion')
            ->selectRaw('COALESCE(SUM(ingresos), 0) as ingresos')
            ->selectRaw('COALESCE(SUM(disponible), 0) as disponible')
            ->first();

        return [
            'registros' => (int) ($metricas->registros ?? 0),
            'activos' => (int) ($metricas->activos ?? 0),
            'inversion' => (float) ($metricas->inversion ?? 0),
            'ingresos' => (float) ($metricas->ingresos ?? 0),
            'disponible' => (float) ($metricas->disponible ?? 0),
        ];
    }

    private function resolverPerPage(Request $request): int
    {
        $perPage = (int) $request->input('per_page', 50);

        return in_array($perPage, [25, 50, 100], true) ? $perPage : 50;
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

    private function resolverDetalleCategoria(Request $request, int $cultivoId): array
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

        $fecha = trim((string) $request->query('fecha', ''));
        $actividad = trim((string) $request->query('actividad', ''));
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
            ->filter(function ($detalle) use ($fecha, $actividad) {
                $matchesFecha = $fecha === '' || $detalle->fecha === $fecha;
                $matchesActividad = $actividad === '' || (string) $detalle->descripcion === $actividad;

                return $matchesFecha && $matchesActividad;
            })
            ->values();

        return [$cultivo, $categoriaNormalizada, $detallesCategoria, $fecha, $actividad];
    }
}
