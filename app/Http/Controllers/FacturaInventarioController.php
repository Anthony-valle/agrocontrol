<?php

namespace App\Http\Controllers;

use App\Models\Bodega;
use App\Models\InsumoEntrada;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class FacturaInventarioController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 15);
        $perPage = in_array($perPage, [10, 15, 20, 50, 100], true) ? $perPage : 15;

        $bodegas = Bodega::query()
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        if (! Schema::hasTable('insumo_entradas')) {
            return view('modules.reporteria.facturas_entradas', [
                'entradas' => $this->emptyPaginator($request, $perPage),
                'bodegas' => $bodegas,
                'metricas' => [
                    'total_registros' => 0,
                    'total_inversion' => 0,
                    'con_anexo' => 0,
                    'costo_promedio' => 0,
                ],
                'tablaDisponible' => false,
            ]);
        }

        $query = InsumoEntrada::query()
            ->select('insumo_entradas.*')
            ->selectRaw('cantida as cantidad')
            ->with([
                'insumo:id,codigo,nombre,unidad_medida',
                'bodega:id,nombre',
                'creador:id,usuario',
            ])
            ->whereHas('bodega');

        if ($search = trim((string) $request->input('q'))) {
            $query->where(function ($innerQuery) use ($search) {
                $innerQuery->where('proveedor', 'like', "%{$search}%")
                    ->orWhere('factura', 'like', "%{$search}%")
                    ->orWhereHas('insumo', function ($insumoQuery) use ($search) {
                        $insumoQuery->where('nombre', 'like', "%{$search}%")
                            ->orWhere('codigo', 'like', "%{$search}%");
                    });
            });
        }

        if ($bodegaId = $request->input('bodega_id')) {
            $query->where('bodega_id', $bodegaId);
        }

        if ($desde = $request->input('fecha_desde')) {
            $query->whereDate('fecha_ingreso', '>=', $desde);
        }

        if ($hasta = $request->input('fecha_hasta')) {
            $query->whereDate('fecha_ingreso', '<=', $hasta);
        }

        if ($request->input('anexo') === 'con') {
            $query->whereNotNull('factura')->where('factura', '!=', '');
        }

        if ($request->input('anexo') === 'sin') {
            $query->where(function ($innerQuery) {
                $innerQuery->whereNull('factura')->orWhere('factura', '=','');
            });
        }

        $metricasQuery = clone $query;

        $metricas = [
            'total_registros' => (clone $metricasQuery)->count(),
            'total_inversion' => (float) ((clone $metricasQuery)
                ->sum(DB::raw('cantida * costo_unitario')) ?? 0),
            'con_anexo' => (clone $metricasQuery)
                ->whereNotNull('factura')
                ->where('factura', '!=', '')
                ->count(),
            'costo_promedio' => (float) ((clone $metricasQuery)
                ->avg('costo_unitario') ?? 0),
        ];

        $entradas = $query
            ->orderByDesc('fecha_ingreso')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('modules.reporteria.facturas_entradas', [
            'entradas' => $entradas,
            'bodegas' => $bodegas,
            'metricas' => $metricas,
            'tablaDisponible' => true,
        ]);
    }

    public function show(InsumoEntrada $factura_inventario)
    {
        abort_if(blank($factura_inventario->factura), 404);
        $rutaFactura = ltrim((string) $factura_inventario->factura, '/');

        abort_unless(Storage::disk('public')->exists($rutaFactura), 404);

        $rutaFisicaFactura = Storage::disk('public')->path($rutaFactura);
        $mimeType = is_file($rutaFisicaFactura) ? (string) (mime_content_type($rutaFisicaFactura) ?: '') : '';
        $extension = strtolower(pathinfo($rutaFactura, PATHINFO_EXTENSION));
        $esImagen = Str::startsWith($mimeType, 'image/');
        $esPdf = $mimeType === 'application/pdf' || $extension === 'pdf';

        $factura_inventario->loadMissing([
            'insumo:id,codigo,nombre,unidad_medida',
            'bodega:id,nombre',
        ]);

        return view('modules.reporteria.factura_entrada_show', [
            'entrada' => $factura_inventario,
            'facturaUrl' => route('reporteria.facturas_entradas.archivo', $factura_inventario),
            'facturaNombre' => basename($rutaFactura),
            'mimeType' => $mimeType,
            'esImagen' => $esImagen,
            'esPdf' => $esPdf,
        ]);
    }

    public function archivo(InsumoEntrada $factura_inventario)
    {
        abort_if(blank($factura_inventario->factura), 404);

        $rutaFactura = ltrim((string) $factura_inventario->factura, '/');
        abort_unless(Storage::disk('public')->exists($rutaFactura), 404);

        $rutaFisicaFactura = Storage::disk('public')->path($rutaFactura);
        abort_unless(is_file($rutaFisicaFactura), 404);

        $mimeType = (string) (mime_content_type($rutaFisicaFactura) ?: 'application/octet-stream');

        return response()->file($rutaFisicaFactura, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . basename($rutaFactura) . '"',
        ]);
    }

    public function create()
    {
        abort(404);
    }

    public function store(Request $request)
    {
        abort(404);
    }

    public function edit(InsumoEntrada $factura_inventario)
    {
        abort(404);
    }

    public function update(Request $request, InsumoEntrada $factura_inventario)
    {
        abort(404);
    }

    public function destroy(InsumoEntrada $factura_inventario)
    {
        abort(404);
    }

    private function emptyPaginator(Request $request, int $perPage): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            items: collect(),
            total: 0,
            perPage: $perPage,
            currentPage: LengthAwarePaginator::resolveCurrentPage(),
            options: [
                'path' => $request->url(),
                'query' => $request->query(),
            ],
        );
    }
}
