<?php

namespace App\Http\Controllers;

use App\Imports\InsumosImport;
use App\Models\Insumo;
use App\Models\Categorias;
use App\Models\Sucursale;
use App\Models\Bodega;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class InsumosController extends Controller
{
    private array $columnasTablaCache = [];
    private ?array $categoriasPorIdCache = null;
    private array $costoReporteCache = [];
    private array $costoMovimientoCache = [];

    public function index(){
        $titulo = 'Catálogo de Insumos';
        $insumos = Insumo::get();

        $insumos->each(function (Insumo $insumo): void {
            $insumo->setAttribute('ingrediente_activo_resuelto', $this->resolverIngredienteActivo($insumo));
            $insumo->setAttribute('categoria_nombre_resuelto', $this->resolverCategoriaNombre($insumo));
            $insumo->setAttribute('stock_minimo_resuelto', $insumo->stock_minimo ?? 0);
            $insumo->setAttribute('estado_resuelto', ! $this->tablaTieneColumna('insumos', 'estado') || (int) ($insumo->estado ?? 1) === 1);
        });

        return view('modules.insumos.index', compact('titulo','insumos'));
    }

    public function create(){
        $categorias = Categorias::query()
            ->when(Schema::hasColumn('categorias', 'estado'), fn ($query) => $query->where('estado', 1))
            ->orderBy('nombre')
            ->get()
            ->reject(fn (Categorias $categoria) => $this->categoriaEsRuido($categoria->nombre))
            ->values();
        $sucursales = Sucursale::query()
            ->when(Schema::hasColumn('sucursales', 'estado'), fn ($query) => $query->where('estado', 1))
            ->orderBy('nombre')
            ->get();
        $bodegas = Bodega::query()
            ->when(Schema::hasColumn('bodegas', 'estado'), fn ($query) => $query->where('estado', 1))
            ->orderBy('nombre')
            ->get();
        $titulo = 'Registrar Insumo';

        $soportaEstado = $this->tablaTieneColumna('insumos', 'estado');
        $soportaStockMinimo = $this->tablaTieneColumna('insumos', 'stock_minimo');
        $soportaSucursal = $this->tablaTieneColumna('insumos', 'sucursal_id');

        return view('modules.insumos.create', compact('categorias','sucursales','bodegas','titulo', 'soportaEstado', 'soportaStockMinimo', 'soportaSucursal'));
    }

    public function store(Request $request){
        $rules = [
            'codigo'            => 'required|unique:insumos,codigo',
            'nombre'            => 'required|string|max:100',
            'ingrediente_activo'=> 'nullable|string|max:150',
            'categoria_nombre'  => 'required|string|max:255',
            'unidad_medida'     => 'required|in:Kg,L,Unidad,Saco',
        ];

        if ($this->tablaTieneColumna('insumos', 'stock_minimo')) {
            $rules['stock_minimo'] = 'nullable|numeric|min:0';
        }

        if ($this->tablaTieneColumna('insumos', 'sucursal_id')) {
            $rules['sucursal_id'] = 'required|exists:sucursales,id';
        }

        if ($this->tablaTieneColumna('insumos', 'estado')) {
            $rules['estado'] = 'required|in:0,1';
        }

        $request->validate($rules);

        $sucursal = null;
        if ($request->filled('sucursal_id')) {
            $sucursal = Sucursale::findOrFail($request->sucursal_id);
        }

        $payload = $this->filtrarColumnasPersistidas('insumos', array_merge([
            'empresa_id'        => $sucursal?->empresa_id,
            'codigo'            => $request->codigo,
            'nombre'            => $request->nombre,
            'unidad_medida'     => $request->unidad_medida,
            'stock_minimo'      => $request->input('stock_minimo', 0),
            'estado'            => $request->input('estado', 1),
            'sucursal_id'       => $request->input('sucursal_id'),
            'created_by'        => Auth::id(),
            'updated_by'        => Auth::id()
        ], $this->resolverPayloadIngrediente($request->ingrediente_activo), $this->resolverPayloadCategoria($request->categoria_nombre)));

        $insumo = Insumo::create($payload);

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'success' => 'Insumo creado correctamente.',
                'insumo_id' => $insumo->id,
            ], 200);
        }

        return redirect()->route('insumos.index')->with('success','Insumo creado correctamente.');
    }

    public function edit(Insumo $insumo){
        $categorias = Categorias::query()
            ->when(Schema::hasColumn('categorias', 'estado'), fn ($query) => $query->where('estado', 1))
            ->orderBy('nombre')
            ->get()
            ->reject(fn (Categorias $categoria) => $this->categoriaEsRuido($categoria->nombre))
            ->values();
        $sucursales = Sucursale::query()
            ->when(Schema::hasColumn('sucursales', 'estado'), fn ($query) => $query->where('estado', 1))
            ->orderBy('nombre')
            ->get();
        $bodegas = Bodega::query()
            ->when(Schema::hasColumn('bodegas', 'estado'), fn ($query) => $query->where('estado', 1))
            ->orderBy('nombre')
            ->get();

        $insumo->setAttribute('ingrediente_activo_resuelto', $this->resolverIngredienteActivo($insumo));
        $insumo->setAttribute('categoria_nombre_resuelto', $this->resolverCategoriaNombre($insumo));
        $insumo->setAttribute('estado_resuelto', ! $this->tablaTieneColumna('insumos', 'estado') || (int) ($insumo->estado ?? 1) === 1);

        $soportaEstado = $this->tablaTieneColumna('insumos', 'estado');
        $soportaStockMinimo = $this->tablaTieneColumna('insumos', 'stock_minimo');
        $soportaSucursal = $this->tablaTieneColumna('insumos', 'sucursal_id');

        return view('modules.insumos.edit', compact('insumo','categorias','sucursales','bodegas', 'soportaEstado', 'soportaStockMinimo', 'soportaSucursal'));
    }

    public function update(Request $request, Insumo $insumo){
        $rules = [
            'codigo'=>'required|unique:insumos,codigo,'.$insumo->id,
            'nombre'=>'required|string|max:100',
            'ingrediente_activo'=>'nullable|string|max:150',
            'categoria_nombre'=>'required|string|max:255',
            'unidad_medida'=>'required|in:Kg,L,Unidad,Saco',
        ];

        if ($this->tablaTieneColumna('insumos', 'stock_minimo')) {
            $rules['stock_minimo'] = 'nullable|numeric|min:0';
        }

        if ($this->tablaTieneColumna('insumos', 'sucursal_id')) {
            $rules['sucursal_id'] = 'required|exists:sucursales,id';
        }

        if ($this->tablaTieneColumna('insumos', 'estado')) {
            $rules['estado'] = 'required|in:0,1';
        }

        $request->validate($rules);

        $sucursal = null;
        if ($request->filled('sucursal_id')) {
            $sucursal = Sucursale::findOrFail($request->sucursal_id);
        }

        $insumo->update($this->filtrarColumnasPersistidas('insumos', array_merge([
            'empresa_id'=>$sucursal?->empresa_id,
            'codigo'=>$request->codigo,
            'nombre'=>$request->nombre,
            'unidad_medida'=>$request->unidad_medida,
            'stock_minimo'=>$request->input('stock_minimo', 0),
            'sucursal_id'=>$request->input('sucursal_id'),
            'estado'=>$request->input('estado', 1),
            'updated_by'=>Auth::id()
        ], $this->resolverPayloadIngrediente($request->ingrediente_activo), $this->resolverPayloadCategoria($request->categoria_nombre))));

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['success' => 'Insumo actualizado correctamente.'], 200);
        }

        return redirect()->route('insumos.index')->with('success','Insumo actualizado correctamente.');
    }

    public function destroy(Insumo $insumo){
        $insumo->delete();

        if (request()->ajax() || request()->expectsJson()) {
            return response()->json(['success' => 'Insumo eliminado correctamente.'], 200);
        }

        return back()->with('success','Insumo eliminado correctamente.');
    }

     // Importar formulario
    public function importar() {
        $titulo = 'Importar Insumos';
        return view('modules.insumos.importar', compact('titulo'));
    }

    // Importar Excel
    public function importarExcel(Request $request)
    {
        $request->validate([
            'archivo_excel' => 'required|file|mimes:xlsx,xls,csv'
        ]);

        try {
            Excel::import(
                new InsumosImport(Auth::id(), Auth::user()->sucursal_id, Auth::user()->empresa_id),
                $request->file('archivo_excel')
            );

            if ($request->ajax() || $request->expectsJson()) {
                return response()->json(['success' => 'Insumos importados correctamente.'], 200);
            }

            return back()->with('success', 'Insumos importados correctamente.');
        } catch (\Exception $e) {
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json(['message' => 'Error al importar: ' . $e->getMessage()], 422);
            }

            return back()->with('error', 'Error al importar: ' . $e->getMessage());
        }
    }

    public function reporteCategoriaView()
    {
        $categorias = Categorias::orderBy('nombre')->get(['id', 'nombre'])
            ->reject(fn (Categorias $categoria) => $this->categoriaEsRuido($categoria->nombre))
            ->values();

        return view('modules.reporteria.insumos_categoria', compact('categorias'));
    }

    public function reporteCategoriaDetalle(Categorias $categoria)
    {
        $insumosQuery = Insumo::with(['inventarioBodegas.bodega.sucursal']);

        if (Schema::hasColumn('insumos', 'categoria_nombre')) {
            $insumosQuery->where('categoria_nombre', $categoria->nombre);
        } elseif (Schema::hasColumn('insumos', 'categoria_id')) {
            $insumosQuery->where('categoria_id', $categoria->id);
        } else {
            $insumosQuery->whereRaw('1 = 0');
        }

        $insumos = $insumosQuery
            ->orderBy('nombre')
            ->get();

        $soportaStockMinimo = Schema::hasColumn('insumos', 'stock_minimo');

        $insumos->each(function (Insumo $insumo) use ($soportaStockMinimo): void {
            $stockMinimo = $soportaStockMinimo ? (float) ($insumo->stock_minimo ?? 0) : 0.0;
            $valorTotalReporte = 0.0;

            foreach ($insumo->inventarioBodegas as $lote) {
                $costoReporte = $this->resolverCostoReporteCategoria(
                    $insumo,
                    $lote->numero_lote ?? null,
                    (float) ($lote->costo_promedio ?? 0)
                );

                $lote->setAttribute('costo_reporte', $costoReporte);
                $valorTotalReporte += (float) ($lote->stock_actual ?? 0) * $costoReporte;
            }

            $insumo->setAttribute('stock_minimo_resuelto', $stockMinimo);
            $insumo->setAttribute('valor_total_reporte', $valorTotalReporte);
        });

        $metricas = [
            'insumos' => $insumos->count(),
            'stock_total' => $insumos->sum(fn ($insumo) => $insumo->inventarioBodegas->sum('stock_actual')),
            'valor_total' => $insumos->sum(fn ($insumo) => (float) ($insumo->valor_total_reporte ?? 0)),
            'stock_bajo' => $insumos->filter(function ($insumo) use ($soportaStockMinimo) {
                if (! $soportaStockMinimo) {
                    return false;
                }

                $stock = $insumo->inventarioBodegas->sum('stock_actual');

                return ($insumo->stock_minimo_resuelto ?? 0) > 0 && $stock <= $insumo->stock_minimo_resuelto;
            })->count(),
        ];

        return view('modules.reporteria.partials.insumos_categoria_resultado', compact('categoria', 'insumos', 'metricas'));
    }

    private function resolverIngredienteActivo(Insumo $insumo): string
    {
        return (string) ($insumo->ingrediente_activo ?? $insumo->ingredientes_activo ?? '-');
    }

    private function resolverCostoReporteCategoria(Insumo $insumo, ?string $numeroLote, float $costoLote): float
    {
        if ($costoLote > 0) {
            return round($costoLote, 4);
        }

        $cacheKey = $insumo->id . '|' . strtolower(trim((string) ($numeroLote ?? '*')));

        if (array_key_exists($cacheKey, $this->costoReporteCache)) {
            return $this->costoReporteCache[$cacheKey];
        }

        $costoInventario = $insumo->inventarioBodegas
            ->map(fn ($lote) => (float) ($lote->costo_promedio ?? 0))
            ->first(fn (float $costo) => $costo > 0);

        $costoResuelto = $this->resolverCostoMovimientoCategoria((int) $insumo->id, $numeroLote)
            ?? ($costoInventario > 0 ? $costoInventario : null)
            ?? $this->resolverCostoMovimientoCategoria((int) $insumo->id, null)
            ?? ($this->tablaTieneColumna('insumos', 'costo_estimado') ? (float) ($insumo->costo_estimado ?? 0) : 0);

        $this->costoReporteCache[$cacheKey] = round(max(0, (float) $costoResuelto), 4);

        return $this->costoReporteCache[$cacheKey];
    }

    private function resolverCostoMovimientoCategoria(int $insumoId, ?string $numeroLote): ?float
    {
        $cacheKey = $insumoId . '|' . strtolower(trim((string) ($numeroLote ?? '*')));

        if (array_key_exists($cacheKey, $this->costoMovimientoCache)) {
            return $this->costoMovimientoCache[$cacheKey];
        }

        if (! $this->tablaTieneColumna('movimiento_inventarios', 'insumo_id')) {
            $this->costoMovimientoCache[$cacheKey] = null;

            return null;
        }

        $precio = null;

        foreach (['precio_unitario', 'costo_unitario'] as $columnaCosto) {
            if (! $this->tablaTieneColumna('movimiento_inventarios', $columnaCosto)) {
                continue;
            }

            $query = DB::table('movimiento_inventarios')
                ->where('insumo_id', $insumoId)
                ->where($columnaCosto, '>', 0);

            if ($numeroLote !== null && $this->tablaTieneColumna('movimiento_inventarios', 'numero_lote')) {
                $query->where('numero_lote', $numeroLote);
            }

            if ($this->tablaTieneColumna('movimiento_inventarios', 'created_at')) {
                $query->orderByDesc('created_at');
            }

            $precio = $query->value($columnaCosto);

            if ($precio !== null) {
                break;
            }
        }

        $this->costoMovimientoCache[$cacheKey] = $precio !== null ? (float) $precio : null;

        return $this->costoMovimientoCache[$cacheKey];
    }

    private function resolverCategoriaNombre(Insumo $insumo): string
    {
        if (!empty($insumo->categoria_nombre)) {
            return (string) $insumo->categoria_nombre;
        }

        if (!empty($insumo->categoria_id)) {
            return $this->obtenerCategoriasPorId()[(int) $insumo->categoria_id] ?? 'Sin categoría';
        }

        return 'Sin categoría';
    }

    private function obtenerCategoriasPorId(): array
    {
        if ($this->categoriasPorIdCache === null) {
            $this->categoriasPorIdCache = Categorias::query()
                ->get(['id', 'nombre'])
                ->reject(fn (Categorias $categoria) => $this->categoriaEsRuido($categoria->nombre))
                ->pluck('nombre', 'id')
                ->all();
        }

        return $this->categoriasPorIdCache;
    }

    private function categoriaEsRuido(?string $nombre): bool
    {
        $normalizada = strtoupper(trim((string) $nombre));

        return in_array($normalizada, ['C/U', 'CU', 'G', 'KG', 'KGS', 'L', 'LT', 'LTS', 'ML', 'M', 'GR', 'UND', 'UNIDAD', 'UNIDADES'], true);
    }

    private function resolverPayloadIngrediente(?string $ingredienteActivo): array
    {
        $ingredienteActivo = trim((string) ($ingredienteActivo ?? ''));

        if ($ingredienteActivo === '') {
            return [];
        }

        $payload = [];

        if ($this->tablaTieneColumna('insumos', 'ingrediente_activo')) {
            $payload['ingrediente_activo'] = $ingredienteActivo;
        }

        if ($this->tablaTieneColumna('insumos', 'ingredientes_activo')) {
            $payload['ingredientes_activo'] = $ingredienteActivo;
        }

        return $payload;
    }

    private function resolverPayloadCategoria(?string $categoriaNombre): array
    {
        $categoriaNombre = trim((string) ($categoriaNombre ?? ''));

        if ($categoriaNombre === '') {
            return [];
        }

        $payload = [];

        if ($this->tablaTieneColumna('insumos', 'categoria_nombre')) {
            $payload['categoria_nombre'] = $categoriaNombre;
        }

        if ($this->tablaTieneColumna('insumos', 'categoria_id')) {
            $payload['categoria_id'] = Categorias::query()->where('nombre', $categoriaNombre)->value('id');
        }

        return $payload;
    }

    private function filtrarColumnasPersistidas(string $tabla, array $payload): array
    {
        if (! isset($this->columnasTablaCache[$tabla])) {
            $this->columnasTablaCache[$tabla] = array_flip(Schema::getColumnListing($tabla));
        }

        return array_intersect_key($payload, $this->columnasTablaCache[$tabla]);
    }

    private function tablaTieneColumna(string $tabla, string $columna): bool
    {
        return array_key_exists($columna, $this->filtrarColumnasPersistidas($tabla, [$columna => true]));
    }

}