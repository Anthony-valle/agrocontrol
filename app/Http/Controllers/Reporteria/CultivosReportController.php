<?php

namespace App\Http\Controllers\Reporteria;

use Carbon\Carbon;
use App\Exports\CultivosConsumosGeneralExport;
use App\Exports\CultivoCategoriaConsumosExport;
use App\Http\Controllers\Controller;
use App\Exports\CultivosReportExport;
use App\Models\Consumo;
use App\Models\Cosecha;
use App\Models\CosechaFactura;
use App\Models\Categorias;
use App\Models\Cultivo;
use App\Models\Lote;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class CultivosReportController extends Controller
{
    private array $categoriasPorId = [];

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

    public function consumosGeneral(Request $request)
    {
        $lotes = Lote::orderBy('nombre')->get(['id', 'nombre']);
        $cultivosResumen = $this->buildGeneralMonthlyBaseQuery($request, false)->get();
        $cultivosActivos = $this->buildConsumosGeneralResumenFromCultivos($cultivosResumen);
        $resumenGeneral = $this->buildConsumosGeneralMatrix($cultivosResumen);
        $reporte = $this->buildGeneralMonthlyReport($request);

        return view('modules.reporteria.cultivos_consumos_general', [
            'lotes' => $lotes,
            'cultivosActivos' => $cultivosActivos,
            'resumenGeneral' => $resumenGeneral,
            'cultivoSeleccionado' => $reporte['cultivoSeleccionado'],
            'meses' => $reporte['meses'],
            'filas' => $reporte['filas'],
            'totales' => $reporte['totales'],
            'metricas' => $reporte['metricas'],
        ]);
    }

    public function consumosGeneralDetalle(Request $request, int $cultivo)
    {
        $cultivos = $this->buildGeneralMonthlyBaseQuery($request, false)
            ->whereKey($cultivo)
            ->get();

        abort_if($cultivos->isEmpty(), 404);

        $reporte = $this->buildGeneralMonthlyReportFromCultivos($cultivos);
        $cultivoSeleccionado = $reporte['cultivoSeleccionado'];

        abort_if(! $cultivoSeleccionado, 404);

        return view('modules.reporteria.cultivos_consumos_general_detalle', [
            'cultivoSeleccionado' => $cultivoSeleccionado,
            'meses' => $reporte['meses'],
            'filas' => $reporte['filas'],
            'totales' => $reporte['totales'],
            'metricas' => $reporte['metricas'],
            'backUrl' => route('reporteria.cultivos.consumos-general', $request->except('cultivo_id')),
            'filterQuery' => $request->except('cultivo_id'),
        ]);
    }

    public function exportConsumosGeneralExcel(Request $request)
    {
        $cultivos = $this->buildGeneralMonthlyBaseQuery($request, false)->get();
        $cultivosActivos = $this->buildConsumosGeneralResumenFromCultivos($cultivos);
        $resumenGeneral = $this->buildConsumosGeneralMatrix($cultivos);
        $detallesCultivos = $cultivos->map(function (Cultivo $cultivo) {
            $reporte = $this->buildGeneralMonthlyReportFromCultivos(collect([$cultivo]));

            return [
                'cultivo_id' => (int) $cultivo->id,
                'sheet_name' => $this->buildConsumosGeneralSheetName($cultivo),
                'general_detail_sheet_name' => $this->buildConsumosGeneralDetailSheetName($cultivo),
                'cultivo' => $cultivo,
                'meses' => $reporte['meses'],
                'filas' => $reporte['filas'],
                'totales' => $reporte['totales'],
                'detalle_general_rows' => $this->buildConsumosGeneralDetalleRows(collect([$cultivo])),
            ];
        })->values()->all();
        $fileName = 'reporte_general_consumos_cultivos_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new CultivosConsumosGeneralExport(
                $cultivosActivos,
                $resumenGeneral,
                $detallesCultivos,
            ),
            $fileName
        );
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
            'categoriasDisponibles' => $this->buildCategoriasDisponibles($cultivo),
            'showCategoriaFilter' => false,
        ]);
    }

    public function consumosPorCategoriaPagina(Request $request, int $cultivoId)
    {
        [$cultivo, $categoriaNormalizada, $detallesCategoria, $fecha, $actividad] = $this->resolverDetalleCategoria($request, $cultivoId);

        return view('modules.reporteria.cultivo_consumos_categoria_page', [
            'cultivo' => $cultivo,
            'categoria' => $categoriaNormalizada,
            'detallesCategoria' => $detallesCategoria,
            'totalCategoria' => (float) $detallesCategoria->sum('subtotal'),
            'cantidadCategoria' => (float) $detallesCategoria->sum('cantidad'),
            'fechasCategoria' => $detallesCategoria->pluck('fecha')->filter()->unique()->sortDesc()->values(),
            'selectedFecha' => $fecha,
            'selectedActividad' => $actividad,
            'categoriasDisponibles' => $this->buildCategoriasDisponibles($cultivo),
            'categoriaPageBaseUrl' => route('reporteria.cultivos.consumos-categoria.pagina', ['cultivo' => $cultivo->id]),
            'showCategoriaFilter' => true,
            'backUrl' => route('reporteria.cultivos.consumos-general.detalle', array_merge([
                'cultivo' => $cultivo->id,
            ], $request->except(['categoria', 'fecha', 'actividad']))),
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
        ])->setPaper('legal', 'landscape');

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

    private function buildGeneralRows(Request $request, array $categorias): Collection
    {
        return $this->buildGeneralBaseQuery($request)
            ->get()
            ->map(fn (Cultivo $cultivo) => $this->formatGeneralCultivoRow($cultivo, $categorias));
    }

    private function buildGeneralRowsPaginadas(Request $request, int $perPage, array $categorias)
    {
        return $this->buildGeneralBaseQuery($request)
            ->paginate($perPage)
            ->through(fn (Cultivo $cultivo) => $this->formatGeneralCultivoRow($cultivo, $categorias))
            ->withQueryString();
    }

    private function buildGeneralBaseQuery(Request $request)
    {
        $fechaInicio = trim((string) $request->input('fecha_inicio', ''));
        $fechaFin = trim((string) $request->input('fecha_fin', ''));

        return Cultivo::query()
            ->with([
                'lote:id,nombre',
                'planes.detalles',
                'consumos' => function ($query) use ($fechaInicio, $fechaFin) {
                    $query->with('detalles')
                        ->when($fechaInicio !== '', fn ($consumos) => $consumos->whereDate('fecha_consumo', '>=', $fechaInicio))
                        ->when($fechaFin !== '', fn ($consumos) => $consumos->whereDate('fecha_consumo', '<=', $fechaFin))
                        ->orderByDesc('fecha_consumo');
                },
            ])
            ->when($request->filled('lote_id'), fn ($query) => $query->where('lotes_id', $request->lote_id))
            ->when($request->filled('estado'), fn ($query) => $query->where('estado', $request->estado))
            ->orderByDesc('fecha_siembra')
            ->orderBy('nombre');
    }

    private function formatGeneralCultivoRow(Cultivo $cultivo, array $categoriasConfig): array
    {
        $plan = $cultivo->planes->sortByDesc('fecha_plan')->first();
        $planDetalles = $plan?->detalles ?? collect();
        $consumoDetalles = $cultivo->consumos->flatMap(fn ($consumo) => $consumo->detalles);
        $planTotals = $this->aggregateCategoryTotals($planDetalles, 'cantidad_estimada');
        $realTotals = $this->aggregateCategoryTotals($consumoDetalles, 'cantidad');
        $categorias = [];
        $totalPlanCosto = 0.0;
        $totalRealCosto = 0.0;

        foreach ($categoriasConfig as $categoria) {
            $key = $categoria['key'];
            $planData = $planTotals[$key] ?? ['cantidad' => 0.0, 'subtotal' => 0.0];
            $realData = $realTotals[$key] ?? ['cantidad' => 0.0, 'subtotal' => 0.0];
            $planCosto = (float) $planData['subtotal'];
            $realCosto = (float) $realData['subtotal'];

            $categorias[$key] = [
                'label' => $categoria['label'],
                'plan' => (float) $planData['cantidad'],
                'real' => (float) $realData['cantidad'],
                'costo_plan' => $planCosto,
                'costo_real' => $realCosto,
                'indice' => $planCosto > 0 ? (($realCosto / $planCosto) * 100) : null,
            ];

            $totalPlanCosto += $planCosto;
            $totalRealCosto += $realCosto;
        }

        return [
            'id' => (int) $cultivo->id,
            'codigo' => $cultivo->codigo,
            'nombre' => $cultivo->nombre,
            'lote' => $cultivo->lote->nombre ?? '-',
            'estado' => $cultivo->estado,
            'fecha_siembra' => $cultivo->fecha_siembra,
            'tiene_plan' => $plan !== null,
            'categorias' => $categorias,
            'total_costo_plan' => $totalPlanCosto,
            'total_costo_real' => $totalRealCosto,
            'desviacion_total' => $totalRealCosto - $totalPlanCosto,
        ];
    }

    private function buildGeneralMetricas(Request $request): array
    {
        $filas = $this->buildGeneralRows($request, $this->categoriasConsumoGeneralConfig());

        return [
            'registros' => $filas->count(),
            'con_plan' => $filas->where('tiene_plan', true)->count(),
            'costo_plan' => (float) $filas->sum('total_costo_plan'),
            'costo_real' => (float) $filas->sum('total_costo_real'),
            'desviacion' => (float) $filas->sum('desviacion_total'),
        ];
    }

    private function categoriasConsumoGeneralConfig(): array
    {
        return [
            ['key' => 'Fertilizante', 'label' => 'Fertilizante'],
            ['key' => 'Fitosanitario', 'label' => 'Fitosanitario'],
            ['key' => 'Otros Insumos', 'label' => 'Otros Insumos'],
            ['key' => 'Mano de Obra', 'label' => 'Mano de Obra'],
            ['key' => 'Preparacion de Suelo', 'label' => 'Preparacion de Suelo'],
            ['key' => 'Indirectos', 'label' => 'Indirectos'],
        ];
    }

    private function aggregateCategoryTotals(Collection $detalles, string $quantityField): array
    {
        return $detalles->groupBy(function ($detalle) use ($quantityField) {
            return $quantityField === 'cantidad'
                ? $this->resolverCategoriaDetalleConsumo($detalle)
                : $this->normalizarCategoriaReporte($detalle->categoria ?? null);
        })->map(function (Collection $items) use ($quantityField) {
            return [
                'cantidad' => (float) $items->sum($quantityField),
                'subtotal' => (float) $items->sum('subtotal'),
            ];
        })->toArray();
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
                'cultivos.codigo',
                'cultivos.nombre',
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

    private function buildGeneralMonthlyReport(Request $request): array
    {
        $cultivos = $this->buildGeneralMonthlyBaseQuery($request)->get();

        return $this->buildGeneralMonthlyReportFromCultivos($cultivos);
    }

    private function buildGeneralMonthlyReportFromCultivos(Collection $cultivos): array
    {
        $cultivoSeleccionado = $cultivos->count() === 1 ? $cultivos->first() : null;
        $meses = $this->buildGeneralMonthlyBuckets($cultivos);
        $categorias = $this->buildGeneralMonthlyCategories($cultivos);
        $filas = collect();

        foreach ($categorias as $categoria) {
            $mesesFila = collect($meses)->mapWithKeys(fn (array $mes) => [
                $mes['key'] => [
                    'plan' => 0.0,
                    'real' => 0.0,
                    'desviacion' => 0.0,
                ],
            ])->all();

            foreach ($cultivos as $cultivo) {
                foreach ($cultivo->planes as $plan) {
                    foreach ($plan->detalles as $detalle) {
                        if ($this->normalizarCategoriaReporte($detalle->categoria ?? null) !== $categoria['key']) {
                            continue;
                        }

                        $monthKey = $this->monthKeyForPlanDetalle($cultivo, $detalle->semana ?? null, $plan->fecha_plan ?? null);

                        if (! $monthKey || ! array_key_exists($monthKey, $mesesFila)) {
                            continue;
                        }

                        $mesesFila[$monthKey]['plan'] += (float) ($detalle->subtotal ?? 0);
                    }
                }

                foreach ($cultivo->consumos as $consumo) {
                    $monthKey = $this->monthKey($consumo->fecha_consumo ?? null);

                    if (! $monthKey || ! array_key_exists($monthKey, $mesesFila)) {
                        continue;
                    }

                    foreach ($consumo->detalles as $detalle) {
                        if ($this->normalizarCategoriaReporte($detalle->categoria ?? null) !== $categoria['key']) {
                            continue;
                        }

                        $mesesFila[$monthKey]['real'] += (float) ($detalle->subtotal ?? 0);
                    }
                }
            }

            $totalPlan = 0.0;
            $totalReal = 0.0;

            foreach ($mesesFila as $monthKey => $valores) {
                $mesesFila[$monthKey]['desviacion'] = $valores['real'] - $valores['plan'];
                $totalPlan += $mesesFila[$monthKey]['plan'];
                $totalReal += $mesesFila[$monthKey]['real'];
            }

            $filas->push([
                'categoria' => $categoria['label'],
                'meses' => $mesesFila,
                'total_plan' => $totalPlan,
                'total_real' => $totalReal,
                'total_desviacion' => $totalReal - $totalPlan,
                'porcentaje' => $totalPlan > 0 ? (($totalPlan - $totalReal) / $totalPlan) * 100 : null,
            ]);
        }

        $totales = [
            'meses' => collect($meses)->mapWithKeys(fn (array $mes) => [
                $mes['key'] => [
                    'plan' => 0.0,
                    'real' => 0.0,
                    'desviacion' => 0.0,
                ],
            ])->all(),
            'plan' => 0.0,
            'real' => 0.0,
            'desviacion' => 0.0,
            'porcentaje' => null,
        ];

        foreach ($filas as $fila) {
            foreach ($fila['meses'] as $monthKey => $valores) {
                $totales['meses'][$monthKey]['plan'] += $valores['plan'];
                $totales['meses'][$monthKey]['real'] += $valores['real'];
                $totales['meses'][$monthKey]['desviacion'] += $valores['desviacion'];
            }

            $totales['plan'] += $fila['total_plan'];
            $totales['real'] += $fila['total_real'];
            $totales['desviacion'] += $fila['total_desviacion'];
        }

        $totales['porcentaje'] = $totales['plan'] > 0
            ? (($totales['plan'] - $totales['real']) / $totales['plan']) * 100
            : null;

        return [
            'cultivoSeleccionado' => $cultivoSeleccionado,
            'meses' => $meses,
            'filas' => $filas,
            'totales' => $totales,
            'metricas' => [
                'cultivos' => $cultivos->count(),
                'categorias' => count($categorias),
                'meses' => count($meses),
                'costo_plan' => $totales['plan'],
                'costo_real' => $totales['real'],
                'desviacion' => $totales['desviacion'],
            ],
        ];
    }

    private function buildGeneralMonthlyBaseQuery(Request $request, bool $includeCultivoFilter = true)
    {
        $fechaInicio = trim((string) $request->input('fecha_inicio', ''));
        $fechaFin = trim((string) $request->input('fecha_fin', ''));

        return Cultivo::query()
            ->with([
                'lote:id,nombre',
                'planes' => function ($query) use ($fechaInicio, $fechaFin) {
                    $query->with('detalles')
                        ->when($fechaInicio !== '', fn ($planes) => $planes->whereDate('fecha_plan', '>=', $fechaInicio))
                        ->when($fechaFin !== '', fn ($planes) => $planes->whereDate('fecha_plan', '<=', $fechaFin))
                        ->orderBy('fecha_plan');
                },
                'consumos' => function ($query) use ($fechaInicio, $fechaFin) {
                    $query->with(['detalles.insumo', 'detalles.bodega'])
                        ->when($fechaInicio !== '', fn ($consumos) => $consumos->whereDate('fecha_consumo', '>=', $fechaInicio))
                        ->when($fechaFin !== '', fn ($consumos) => $consumos->whereDate('fecha_consumo', '<=', $fechaFin))
                        ->orderBy('fecha_consumo');
                },
            ])
            ->when($includeCultivoFilter && $request->filled('cultivo_id'), fn ($query) => $query->whereKey($request->cultivo_id))
            ->when($request->filled('lote_id'), fn ($query) => $query->where('lotes_id', $request->lote_id))
            ->when($request->filled('estado'), fn ($query) => $query->where('estado', $request->estado), fn ($query) => $query->where('estado', 'Activo'))
            ->orderBy('nombre');
    }

    private function buildConsumosGeneralCultivos(Request $request): Collection
    {
        return $this->buildConsumosGeneralResumenFromCultivos(
            $this->buildGeneralMonthlyBaseQuery($request, false)
                ->get(['id', 'codigo', 'nombre', 'lotes_id', 'estado', 'fecha_siembra', 'hectareas'])
        );
    }

    private function buildConsumosGeneralResumenFromCultivos(Collection $cultivos): Collection
    {
        return $cultivos->map(function (Cultivo $cultivo) {
            $totalPlan = (float) $cultivo->planes
                ->flatMap(fn ($plan) => $plan->detalles)
                ->sum('subtotal');

            $totalReal = (float) $cultivo->consumos
                ->flatMap(fn ($consumo) => $consumo->detalles)
                ->sum('subtotal');

            $desviacion = $totalReal - $totalPlan;

            return [
                'id' => (int) $cultivo->id,
                'codigo' => $cultivo->codigo,
                'nombre' => $cultivo->nombre,
                'lote' => $cultivo->lote->nombre ?? '-',
                'estado' => $cultivo->estado,
                'fecha_siembra' => $cultivo->fecha_siembra,
                'hectareas' => $cultivo->hectareas !== null ? (float) $cultivo->hectareas : null,
                'total_plan' => $totalPlan,
                'total_real' => $totalReal,
                'desviacion' => $desviacion,
                'porcentaje' => $totalPlan > 0 ? (($totalPlan - $totalReal) / $totalPlan) * 100 : null,
                'sheet_name' => $this->buildConsumosGeneralSheetName($cultivo),
            ];
        })->values();
    }

    private function buildConsumosGeneralSheetName(Cultivo $cultivo): string
    {
        $base = 'Resumen ' . trim((string) ($cultivo->nombre ?: ('Cultivo ' . $cultivo->id)));
        $base = preg_replace('#[\\/?*\[\]:]#', ' ', $base) ?? $base;
        $base = trim(preg_replace('/\s+/', ' ', $base) ?? $base);
        $suffix = ' ' . $cultivo->id;
        $maxLength = max(1, 31 - strlen($suffix));

        return mb_substr($base, 0, $maxLength) . $suffix;
    }

    private function buildConsumosGeneralDetailSheetName(Cultivo $cultivo): string
    {
        $base = 'Detalle ' . trim((string) ($cultivo->nombre ?: ('Cultivo ' . $cultivo->id)));
        $base = preg_replace('#[\\/?*\[\]:]#', ' ', $base) ?? $base;
        $base = trim(preg_replace('/\s+/', ' ', $base) ?? $base);
        $suffix = ' ' . $cultivo->id;
        $maxLength = max(1, 31 - strlen($suffix));

        return mb_substr($base, 0, $maxLength) . $suffix;
    }

    private function buildGeneralMonthlyBuckets(Collection $cultivos): array
    {
        $monthKeys = $cultivos->flatMap(function (Cultivo $cultivo) {
            $planMonths = $cultivo->planes->flatMap(function ($plan) use ($cultivo) {
                return $plan->detalles->map(function ($detalle) use ($cultivo, $plan) {
                    return $this->monthKeyForPlanDetalle($cultivo, $detalle->semana ?? null, $plan->fecha_plan ?? null);
                });
            });

            return $planMonths->merge($cultivo->consumos->pluck('fecha_consumo'));
        })->map(fn ($fecha) => $this->monthKey($fecha))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return $monthKeys->map(function (string $monthKey) {
            $month = Carbon::createFromFormat('Y-m', $monthKey)->locale('es');

            return [
                'key' => $monthKey,
                'label' => ucfirst($month->translatedFormat('F')),
            ];
        })->all();
    }

    private function buildGeneralMonthlyCategories(Collection $cultivos): array
    {
        $categoriasPlan = $cultivos->flatMap(fn (Cultivo $cultivo) => $cultivo->planes)
            ->flatMap(fn ($plan) => $plan->detalles)
            ->map(fn ($detalle) => $this->normalizarCategoriaReporte($detalle->categoria ?? null))
            ->filter()
            ->unique()
            ->values();

        if ($categoriasPlan->isEmpty()) {
            $categoriasPlan = collect(array_column($this->categoriasConsumoGeneralConfig(), 'key'));
        }

        $prioridad = collect(array_column($this->categoriasConsumoGeneralConfig(), 'key'));
        $ordenadas = $prioridad->filter(fn (string $categoria) => $categoriasPlan->contains($categoria))
            ->merge($categoriasPlan->reject(fn (string $categoria) => $prioridad->contains($categoria))->sort()->values())
            ->values();

        return $ordenadas->map(fn (string $categoria) => [
            'key' => $categoria,
            'label' => $categoria,
        ])->all();
    }

    private function monthKey(mixed $fecha): ?string
    {
        if ($fecha === null || $fecha === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $fecha)->format('Y-m');
        } catch (\Throwable $error) {
            return null;
        }
    }

    private function monthKeyForPlanDetalle(Cultivo $cultivo, mixed $semana, mixed $fallbackFecha = null): ?string
    {
        $semana = is_numeric($semana) ? (int) $semana : 0;

        if ($semana > 0 && ! empty($cultivo->fecha_siembra)) {
            try {
                return Carbon::parse((string) $cultivo->fecha_siembra)
                    ->addDays(max(0, $semana - 1) * 7)
                    ->format('Y-m');
            } catch (\Throwable $error) {
                // Fallback below.
            }
        }

        return $this->monthKey($fallbackFecha);
    }

    private function buildConsumosGeneralMatrix(Collection $cultivos): array
    {
        $categorias = $this->buildGeneralMonthlyCategories($cultivos);
        $columnas = $cultivos->map(function (Cultivo $cultivo) {
            $planTotals = $this->aggregateCategoryTotals($cultivo->planes->flatMap(fn ($plan) => $plan->detalles), 'cantidad_estimada');
            $realTotals = $this->aggregateCategoryTotals($cultivo->consumos->flatMap(fn ($consumo) => $consumo->detalles), 'cantidad');

            return [
                'id' => (int) $cultivo->id,
                'nombre' => $cultivo->nombre,
                'codigo' => $cultivo->codigo,
                'lote' => $cultivo->lote->nombre ?? '-',
                'fecha_siembra' => $cultivo->fecha_siembra,
                'hectareas' => $cultivo->hectareas,
                'sheet_name' => $this->buildConsumosGeneralSheetName($cultivo),
                'detail_general_sheet_name' => $this->buildConsumosGeneralDetailSheetName($cultivo),
                'plan_totals' => $planTotals,
                'real_totals' => $realTotals,
            ];
        })->values();

        $filas = collect($categorias)->map(function (array $categoria) use ($columnas) {
            $cultivosFila = $columnas->mapWithKeys(function (array $cultivo) use ($categoria) {
                $plan = (float) (($cultivo['plan_totals'][$categoria['key']]['subtotal'] ?? 0));
                $real = (float) (($cultivo['real_totals'][$categoria['key']]['subtotal'] ?? 0));

                return [
                    $cultivo['id'] => [
                        'plan' => $plan,
                        'real' => $real,
                        'desviacion' => $real - $plan,
                    ],
                ];
            })->all();

            return [
                'categoria' => $categoria['label'],
                'cultivos' => $cultivosFila,
            ];
        })->values();

        $totales = $columnas->mapWithKeys(function (array $cultivo) {
            $plan = collect($cultivo['plan_totals'])->sum('subtotal');
            $real = collect($cultivo['real_totals'])->sum('subtotal');

            return [
                $cultivo['id'] => [
                    'plan' => (float) $plan,
                    'real' => (float) $real,
                    'desviacion' => (float) ($real - $plan),
                ],
            ];
        })->all();

        $totalesGenerales = [
            'plan' => (float) collect($totales)->sum('plan'),
            'real' => (float) collect($totales)->sum('real'),
            'desviacion' => (float) collect($totales)->sum('desviacion'),
            'hectareas' => (float) $columnas->sum(fn (array $cultivo) => (float) ($cultivo['hectareas'] ?? 0)),
        ];

        $filas = $filas->map(function (array $fila) {
            $fila['general'] = [
                'plan' => (float) collect($fila['cultivos'])->sum('plan'),
                'real' => (float) collect($fila['cultivos'])->sum('real'),
                'desviacion' => (float) collect($fila['cultivos'])->sum('desviacion'),
            ];

            return $fila;
        });

        return [
            'cultivos' => $columnas,
            'filas' => $filas,
            'totales' => $totales,
            'general' => $totalesGenerales,
        ];
    }

    private function buildConsumosGeneralDetalleRows(Collection $cultivos): Collection
    {
        return $cultivos
            ->flatMap(function (Cultivo $cultivo) {
                return $cultivo->consumos
                    ->sortByDesc('fecha_consumo')
                    ->flatMap(function ($consumo) use ($cultivo) {
                        return $consumo->detalles->map(function ($detalle) use ($cultivo, $consumo) {
                            $categoria = $this->resolverCategoriaDetalleConsumo($detalle);
                            $esManoObra = $categoria === 'Mano De Obra';

                            return [
                                'cultivo' => $cultivo->nombre ?: ('Cultivo ' . $cultivo->id),
                                'cultivo_codigo' => $cultivo->codigo ?: '-',
                                'lote_cultivo' => $cultivo->lote->nombre ?? '-',
                                'fecha' => (string) ($consumo->fecha_consumo ?? ''),
                                'consumo_id' => (int) $consumo->id,
                                'estado' => (string) ($consumo->estado_normalizado ?? $consumo->estado ?? '-'),
                                'categoria' => $categoria,
                                'codigo' => $esManoObra ? '-' : (string) ($detalle->insumo->codigo ?? '-'),
                                'insumo' => $esManoObra ? 'N/A (Labor)' : (string) ($detalle->insumo->nombre ?? '-'),
                                'bodega' => (string) ($detalle->bodega->nombre ?? '-'),
                                'lote' => (string) ($detalle->lote ?: '-'),
                                'descripcion' => (string) ($detalle->descripcion ?: '-'),
                                'cantidad' => (float) ($detalle->cantidad ?? 0),
                                'unidad_medida' => (string) ($detalle->unidad_medida ?: '-'),
                                'costo_unitario' => (float) ($detalle->costo_unitario ?? 0),
                                'subtotal' => (float) ($detalle->subtotal ?? 0),
                            ];
                        });
                    });
            })
            ->sortBy([
                ['cultivo', 'asc'],
                ['fecha', 'desc'],
                ['consumo_id', 'desc'],
                ['categoria', 'asc'],
            ])
            ->values();
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
                        'categoria' => $this->resolverCategoriaDetalleConsumo($detalle),
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

    private function buildCategoriasDisponibles(Cultivo $cultivo): Collection
    {
        return collect(['Todas'])
            ->merge($cultivo->consumos
            ->flatMap(fn ($consumo) => $consumo->detalles)
            ->map(fn ($detalle) => $this->resolverCategoriaDetalleConsumo($detalle))
            ->filter()
            ->unique()
            ->sort()
            ->values())
            ->values();
    }

    private function resolverCategoriaDetalleConsumo(object $detalle): string
    {
        $categoriaDetalle = $this->normalizarCategoriaConsumo($detalle->categoria ?? null);

        if ($categoriaDetalle !== 'Otros Insumos') {
            return $categoriaDetalle;
        }

        $categoriaInsumo = $this->resolverCategoriaDesdeInsumo($detalle->insumo ?? null);

        return $categoriaInsumo !== '' ? $categoriaInsumo : $categoriaDetalle;
    }

    private function normalizarCategoriaConsumo(?string $categoria): string
    {
        $texto = trim((string) $categoria);

        if ($texto === '') {
            return 'Otros Insumos';
        }

        return mb_convert_case($texto, MB_CASE_TITLE, 'UTF-8');
    }

    private function resolverCategoriaDesdeInsumo(mixed $insumo): string
    {
        if (! $insumo || empty($insumo->categoria_id)) {
            return '';
        }

        $categoriaId = (int) $insumo->categoria_id;

        if (! array_key_exists($categoriaId, $this->categoriasPorId)) {
            $this->categoriasPorId[$categoriaId] = (string) optional(Categorias::query()->find($categoriaId))->nombre;
        }

        return $this->normalizarCategoriaConsumo($this->categoriasPorId[$categoriaId] ?: null);
    }

    private function normalizarTextoBusqueda(mixed $texto): string
    {
        $texto = trim((string) ($texto ?? ''));

        if ($texto === '') {
            return '';
        }

        $normalizado = mb_strtolower($texto, 'UTF-8');
        $normalizado = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalizado) ?: $normalizado;
        $normalizado = preg_replace('/[^a-z0-9]+/', ' ', $normalizado) ?? $normalizado;

        return trim(preg_replace('/\s+/', ' ', $normalizado) ?? $normalizado);
    }

    private function normalizarCategoriaReporte(mixed $categoria): string
    {
        $categoria = trim((string) ($categoria ?? ''));

        if ($categoria === '') {
            return 'Otros Insumos';
        }

        $normalizada = strtolower($categoria);
        $normalizada = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalizada) ?: $normalizada;
        $normalizada = preg_replace('/[^a-z0-9]+/', ' ', $normalizada) ?? $normalizada;
        $normalizada = trim(preg_replace('/\s+/', ' ', $normalizada) ?? $normalizada);

        return match ($normalizada) {
            'otros', 'otro insumo', 'otros insumo', 'otros insumos' => 'Otros Insumos',
            'mano de obra', 'mano obra' => 'Mano de Obra',
            'fitosanitario', 'fitosanitarios' => 'Fitosanitario',
            'fertilizante', 'fertilizantes' => 'Fertilizante',
            'preparacion de suelo', 'preparacion suelo', 'mecanizacion', 'mecanizacion agricola' => 'Preparacion de Suelo',
            'indirecto', 'indirectos' => 'Indirectos',
            default => ucwords($categoria),
        };
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
        $actividad = trim((string) $request->query('descripcion', $request->query('actividad', '')));
        $todasLasCategorias = mb_strtolower($categoria, 'UTF-8') === 'todas' || $categoria === '__ALL__';
        $categoriaNormalizada = $todasLasCategorias ? 'Todas' : $this->normalizarCategoriaConsumo($categoria);

        $detallesCategoria = $cultivo->consumos
            ->sortByDesc('fecha_consumo')
            ->flatMap(function ($consumo) use ($categoriaNormalizada, $todasLasCategorias) {
                return $consumo->detalles
                    ->filter(function ($detalle) use ($categoriaNormalizada, $todasLasCategorias) {
                        if ($todasLasCategorias) {
                            return true;
                        }

                        return $this->resolverCategoriaDetalleConsumo($detalle) === $categoriaNormalizada;
                    })
                    ->map(function ($detalle) use ($consumo, $categoriaNormalizada, $todasLasCategorias) {
                        $categoriaDetalle = $todasLasCategorias
                            ? $this->resolverCategoriaDetalleConsumo($detalle)
                            : $categoriaNormalizada;
                        $esManoObra = $categoriaDetalle === 'Mano De Obra';
                        $descripcionFiltro = $esManoObra
                            ? (string) ($detalle->descripcion ?: '-')
                            : (string) ($detalle->insumo->nombre ?? $detalle->descripcion ?? '-');

                        return (object) [
                            'consumo_id' => $consumo->id,
                            'fecha' => (string) $consumo->fecha_consumo,
                            'estado' => $consumo->estado_normalizado,
                            'categoria' => $categoriaDetalle,
                            'codigo' => $esManoObra ? null : ($detalle->insumo->codigo ?? null),
                            'descripcion' => $detalle->descripcion,
                            'insumo' => $esManoObra ? null : ($detalle->insumo->nombre ?? null),
                            'bodega' => $detalle->bodega->nombre ?? '-',
                            'lote' => $detalle->lote ?: '-',
                            'cantidad' => (float) $detalle->cantidad,
                            'unidad_medida' => $detalle->unidad_medida ?: '-',
                            'costo_unitario' => (float) $detalle->costo_unitario,
                            'subtotal' => (float) $detalle->subtotal,
                            'descripcion_filtro' => $descripcionFiltro,
                            'busqueda_filtro' => trim(implode(' ', array_filter([
                                $detalle->insumo->codigo ?? null,
                                $detalle->insumo->nombre ?? null,
                                $detalle->descripcion ?? null,
                                $descripcionFiltro,
                            ], fn ($valor) => trim((string) ($valor ?? '')) !== ''))),
                        ];
                    });
            })
            ->filter(function ($detalle) use ($fecha, $actividad) {
                $matchesFecha = $fecha === '' || $detalle->fecha === $fecha;
                $matchesActividad = $actividad === ''
                    || str_contains(
                        $this->normalizarTextoBusqueda($detalle->busqueda_filtro ?? $detalle->descripcion_filtro),
                        $this->normalizarTextoBusqueda($actividad)
                    );

                return $matchesFecha && $matchesActividad;
            })
            ->values();

        return [$cultivo, $categoriaNormalizada, $detallesCategoria, $fecha, $actividad];
    }
}
