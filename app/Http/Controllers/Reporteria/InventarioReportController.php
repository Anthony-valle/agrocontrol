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
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class InventarioReportController extends Controller
{
    public function index(Request $request)
    {
        $sucursales = Sucursale::orderBy('nombre')->get(['id', 'nombre']);
        $bodegas = Bodega::orderBy('nombre')->get(['id', 'nombre', 'sucursal_id']);
        $categorias = $this->obtenerCategoriasDisponibles();

        $inventarios = $this->inventariosFiltrados($request)->get();

        $hoy = Carbon::today();
        $limite = Carbon::today()->addDays(30);

        $metricas = [
            'lotes' => $inventarios->count(),
            'stock_total' => $inventarios->sum('stock_actual'),
            'valor_total' => $inventarios->sum(fn ($item) => $item->stock_actual * $item->costo_promedio),
            'stock_bajo' => $inventarios->filter(fn ($item) => $item->insumo && $item->insumo->stock_minimo !== null && $item->stock_actual <= $item->insumo->stock_minimo)->count(),
            'vencidos' => $inventarios->filter(fn ($item) => $item->fecha_vencimiento && Carbon::parse($item->fecha_vencimiento)->lt($hoy))->count(),
            'proximos' => $inventarios->filter(fn ($item) => $item->fecha_vencimiento && Carbon::parse($item->fecha_vencimiento)->between($hoy, $limite))->count(),
        ];

        $resumenCategorias = $inventarios
            ->groupBy(fn ($item) => $this->resolverNombreCategoria($item->insumo))
            ->map(function ($items, $categoria) {
                return [
                    'categoria' => $categoria,
                    'lotes' => $items->count(),
                    'stock' => $items->sum('stock_actual'),
                    'valor' => $items->sum(fn ($item) => $item->stock_actual * $item->costo_promedio),
                ];
            })
            ->sortByDesc('valor')
            ->values();

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

        $pdf = Pdf::loadView('modules.reporteria.inventario_pdf', compact('inventarios', 'filtros'));

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
                ->whereNotNull('categoria_nombre')
                ->distinct()
                ->orderBy('categoria_nombre')
                ->pluck('categoria_nombre');
        }

        if (Schema::hasColumn('insumos', 'categoria_id')) {
            return Categorias::query()
                ->whereIn('id', Insumo::query()->whereNotNull('categoria_id')->select('categoria_id'))
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
}
