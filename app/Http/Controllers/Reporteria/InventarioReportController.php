<?php

namespace App\Http\Controllers\Reporteria;

use App\Exports\InventarioReportExport;
use App\Http\Controllers\Controller;
use App\Models\Bodega;
use App\Models\Categorias;
use App\Models\Insumo;
use App\Models\InventarioBodega;
use App\Models\MovimientoInventario;
use App\Models\Sucursale;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class InventarioReportController extends Controller
{
    public function index(Request $request)
    {
        $sucursales = Sucursale::orderBy('nombre')->get(['id', 'nombre']);
        $bodegas = Bodega::orderBy('nombre')->get(['id', 'nombre', 'sucursal_id']);
        $categorias = $this->obtenerCategoriasDisponibles();
        $perPage = $this->resolverPerPage($request);

        $inventarioQuery = $this->inventariosFiltrados($request);
        $inventarios = (clone $inventarioQuery)
            ->paginate($perPage)
            ->withQueryString();

        $metricas = $this->calcularMetricasInventario(clone $inventarioQuery);
        $resumenCategorias = $this->resumenCategoriasInventario(clone $inventarioQuery);

        $movimientos = MovimientoInventario::with(['insumo', 'bodegaOrigen', 'bodegaDestino'])
            ->when($request->filled('sucursal_id'), fn ($query) => $query->where('sucursal_id', $request->sucursal_id))
            ->when($request->filled('bodega_id'), fn ($query) => $query->where(function ($subquery) use ($request) {
                $subquery->where('bodega_origen_id', $request->bodega_id)
                    ->orWhere('bodega_destino_id', $request->bodega_id);
            }))
            ->latest()
            ->take(15)
            ->get();

        return view('modules.reporteria.inventario', compact(
            'sucursales',
            'bodegas',
            'categorias',
            'inventarios',
            'perPage',
            'metricas',
            'resumenCategorias',
            'movimientos'
        ));
    }

    public function exportExcel(Request $request)
    {
        $inventarios = $this->inventariosFiltrados($request)->get();
        $fileName = 'reporte_inventario_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new InventarioReportExport($inventarios), $fileName);
    }

    public function exportPdf(Request $request)
    {
        $inventarios = $this->inventariosFiltrados($request)->get();
        $filtros = $request->only(['sucursal_id', 'bodega_id', 'categoria', 'estado_vencimiento']);

        $pdf = app('dompdf.wrapper')->loadView('modules.reporteria.inventario_pdf', compact('inventarios', 'filtros'));

        return $pdf->download('reporte_inventario_' . now()->format('Ymd_His') . '.pdf');
    }

    private function inventariosFiltrados(Request $request)
    {
        return InventarioBodega::with(['insumo', 'bodega.sucursal'])
            ->when($request->filled('sucursal_id'), fn ($query) => $query->whereHas('bodega', fn ($bodega) => $bodega->where('sucursal_id', $request->sucursal_id)))
            ->when($request->filled('bodega_id'), fn ($query) => $query->where('bodega_id', $request->bodega_id))
            ->when($request->filled('categoria'), fn ($query) => $this->aplicarFiltroCategoria($query, $request->categoria))
            ->when($request->filled('estado_vencimiento'), function ($query) use ($request) {
                $hoy = Carbon::today();
                $limite = Carbon::today()->addDays(30);

                if ($request->estado_vencimiento === 'vencido') {
                    $query->whereDate('fecha_vencimiento', '<', $hoy);
                }

                if ($request->estado_vencimiento === 'proximo') {
                    $query->whereDate('fecha_vencimiento', '>=', $hoy)
                        ->whereDate('fecha_vencimiento', '<=', $limite);
                }

                if ($request->estado_vencimiento === 'vigente') {
                    $query->where(function ($subquery) use ($limite) {
                        $subquery->whereNull('fecha_vencimiento')
                            ->orWhereDate('fecha_vencimiento', '>', $limite);
                    });
                }
            })
            ->orderByDesc('stock_actual');
    }

    private function obtenerCategoriasDisponibles()
    {
        if (Schema::hasColumn('insumos', 'categoria_nombre')) {
            return Insumo::query()
                ->activos()
                ->whereNotNull('categoria_nombre')
                ->distinct()
                ->orderBy('categoria_nombre')
                ->pluck('categoria_nombre');
        }

        if (Schema::hasColumn('insumos', 'categoria_id')) {
            return Categorias::query()
                ->whereIn('id', Insumo::query()->activos()->whereNotNull('categoria_id')->select('categoria_id'))
                ->orderBy('nombre')
                ->pluck('nombre');
        }

        return collect();
    }

    private function resolverNombreCategoria(?Insumo $insumo): string
    {
        if (! $insumo) {
            return 'Sin categoría';
        }

        if (Schema::hasColumn('insumos', 'categoria_nombre')) {
            return $insumo->categoria_nombre ?: 'Sin categoría';
        }

        if (Schema::hasColumn('insumos', 'categoria_id')) {
            $categoria = Categorias::query()->find($insumo->categoria_id);
            return $categoria?->nombre ?: 'Sin categoría';
        }

        return 'Sin categoría';
    }

    private function aplicarFiltroCategoria(mixed $query, string $categoria): mixed
    {
        if (Schema::hasColumn('insumos', 'categoria_nombre')) {
            return $query->whereHas('insumo', fn ($insumo) => $insumo->where('categoria_nombre', $categoria));
        }

        if (Schema::hasColumn('insumos', 'categoria_id')) {
            return $query->whereHas('insumo', function ($insumoQuery) use ($categoria) {
                $insumoQuery->whereIn('categoria_id', Categorias::query()->where('nombre', $categoria)->select('id'));
            });
        }

        return $query;
    }

    private function resolverPerPage(Request $request): int
    {
        $perPage = (int) $request->input('per_page', 15);

        return in_array($perPage, [10, 15], true) ? $perPage : 15;
    }

    private function calcularMetricasInventario(mixed $query): array
    {
        $hoy = Carbon::today()->toDateString();
        $limite = Carbon::today()->addDays(30)->toDateString();
        $stockBajoExpr = Schema::hasColumn('insumos', 'stock_minimo')
            ? 'COALESCE(SUM(CASE WHEN insumos.stock_minimo IS NOT NULL AND inventario_bodegas.stock_actual <= insumos.stock_minimo THEN 1 ELSE 0 END), 0) as stock_bajo'
            : '0 as stock_bajo';

        $metricas = (clone $query)
            ->reorder()
            ->leftJoin('insumos', 'inventario_bodegas.insumo_id', '=', 'insumos.id')
            ->selectRaw('COUNT(*) as lotes')
            ->selectRaw('COALESCE(SUM(inventario_bodegas.stock_actual), 0) as stock_total')
            ->selectRaw('COALESCE(SUM(inventario_bodegas.stock_actual * inventario_bodegas.costo_promedio), 0) as valor_total')
            ->selectRaw($stockBajoExpr)
            ->selectRaw('COALESCE(SUM(CASE WHEN inventario_bodegas.fecha_vencimiento IS NOT NULL AND DATE(inventario_bodegas.fecha_vencimiento) < ? THEN 1 ELSE 0 END), 0) as vencidos', [$hoy])
            ->selectRaw('COALESCE(SUM(CASE WHEN inventario_bodegas.fecha_vencimiento IS NOT NULL AND DATE(inventario_bodegas.fecha_vencimiento) BETWEEN ? AND ? THEN 1 ELSE 0 END), 0) as proximos', [$hoy, $limite])
            ->first();

        return [
            'lotes' => (int) ($metricas->lotes ?? 0),
            'stock_total' => (float) ($metricas->stock_total ?? 0),
            'valor_total' => (float) ($metricas->valor_total ?? 0),
            'stock_bajo' => (int) ($metricas->stock_bajo ?? 0),
            'vencidos' => (int) ($metricas->vencidos ?? 0),
            'proximos' => (int) ($metricas->proximos ?? 0),
        ];
    }

    private function resumenCategoriasInventario(mixed $query)
    {
        $categoriaExpr = $this->categoriaSqlExpression();
        $categoryGroupBy = $this->categoriaGroupByExpression();

        $summaryQuery = (clone $query)
            ->reorder()
            ->leftJoin('insumos', 'inventario_bodegas.insumo_id', '=', 'insumos.id')
            ->leftJoin('categorias', 'insumos.categoria_id', '=', 'categorias.id')
            ->selectRaw($categoriaExpr . ' as categoria')
            ->selectRaw('COUNT(*) as lotes')
            ->selectRaw('COALESCE(SUM(inventario_bodegas.stock_actual), 0) as stock')
            ->selectRaw('COALESCE(SUM(inventario_bodegas.stock_actual * inventario_bodegas.costo_promedio), 0) as valor')
            ->orderByDesc('valor');

        if ($categoryGroupBy !== null) {
            $summaryQuery->groupBy(DB::raw($categoryGroupBy));
        }

        return $summaryQuery
            ->get()
            ->map(fn ($fila) => [
                'categoria' => (string) ($fila->categoria ?: 'Sin categoría'),
                'lotes' => (int) ($fila->lotes ?? 0),
                'stock' => (float) ($fila->stock ?? 0),
                'valor' => (float) ($fila->valor ?? 0),
            ]);
    }

    private function categoriaSqlExpression(): string
    {
        if (Schema::hasColumn('insumos', 'categoria_nombre')) {
            return "COALESCE(NULLIF(insumos.categoria_nombre, ''), 'Sin categoría')";
        }

        if (Schema::hasColumn('insumos', 'categoria_id')) {
            return "COALESCE(NULLIF(categorias.nombre, ''), 'Sin categoría')";
        }

        return "'Sin categoría'";
    }

    private function categoriaGroupByExpression(): ?string
    {
        if (Schema::hasColumn('insumos', 'categoria_nombre')) {
            return 'insumos.categoria_nombre';
        }

        if (Schema::hasColumn('insumos', 'categoria_id')) {
            return 'categorias.nombre';
        }

        return null;
    }
}
