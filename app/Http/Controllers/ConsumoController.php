<?php

namespace App\Http\Controllers;

use App\Models\Consumo;
use App\Models\Consumo_detalles;
use App\Models\Categorias;
use App\Models\Cultivo;
use App\Models\Insumo;
use App\Models\InventarioBodega;
use App\Models\Labore;
use App\Models\MovimientoInventario;
use App\Models\Notificaciones;
use App\Services\InventarioService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ConsumoController extends Controller
{
    protected InventarioService $inventarioService;

    public function __construct(InventarioService $inventarioService)
    {
        $this->inventarioService = $inventarioService;
    }

    /** Mostrar un consumo específico */
    public function show(int $id)
    {
        $consumo = Consumo::with(['detalles.insumo', 'detalles.bodega', 'cultivo', 'usuario'])
                          ->findOrFail($id);

        return view('modules.consumo.show', compact('consumo'));
    }

    /** Listado de consumos */
    public function index()
    {
        $cultivos = $this->obtenerCultivosActivos();

        $consumos = Consumo::with(['cultivo', 'usuario', 'creador', 'detalles.insumo'])
                            ->orderByDesc('id')
                            ->get();

        $notificaciones = Notificaciones::with('cultivo')
                                        ->latest()
                                        ->take(5)
                                        ->get();

        return view('modules.consumo.index', compact('consumos', 'notificaciones', 'cultivos'));
    }

    /** Formulario para crear un consumo */
    public function create()
    {
        $cultivos = $this->obtenerCultivosActivos();

        $insumos = $this->obtenerInsumosParaConsumo();
        $labores = $this->obtenerLaboresParaConsumo();

        return view('modules.consumo.create', compact('cultivos','insumos','labores'));
    }

    /** Guardar un consumo */
    public function store(Request $request)
    {
        $request->validate([
            'cultivo_id'    => 'required|exists:cultivos,id',
            'fecha_consumo' => 'required|date',
            'items'         => 'required|array|min:1',
        ]);

        DB::beginTransaction();
        try {
            $cultivo = Cultivo::findOrFail($request->cultivo_id);

            if (strtolower($cultivo->estado) === 'cerrado') {
                return back()->with('error', 'No se puede registrar consumo en un cultivo cerrado.');
            }

            if (strtolower($cultivo->estado) !== 'activo') {
                $cultivo->estado = 'Activo';
                $cultivo->save();
            }

            // Crear consumo principal
            $consumo = Consumo::create([
                'empresa_id'       => $cultivo->empresa_id,
                'cultivo_id'       => $request->cultivo_id,
                'fecha_consumo'    => $request->fecha_consumo,
                'total'            => 0, // se calculará luego
                'estado'           => 'PENDIENTE',
                'created_by'       => Auth::id()
            ]);

            $totalConsumo = 0;

            // Crear detalles del consumo y calcular total
            foreach ($request->items as $item) {
                $subtotal = $item['cantidad'] * $item['precio'];
                $totalConsumo += $subtotal;

                Consumo_detalles::create([
                    'consumo_id'      => $consumo->id,
                    'insumo_id'       => $item['id'],
                    'categoria'       => $item['categoria'], 
                    'descripcion'     => $item['nombre'],
                    'cantidad'        => $item['cantidad'],
                    'unidad_medida'   => $item['unidad'] ?? null, 
                    'costo_unitario'  => $item['precio'], 
                    'subtotal'        => $subtotal,
                    'bodega_id'       => $item['bodega_id'] ?? null,
                    'lote'            => $item['lote'] ?? null,
                    'created_by'      => Auth::id()
                ]);
            }

            // Actualizar total en consumo
            $consumo->total = $totalConsumo;
            $consumo->save();

            // Actualizar stock de insumos en InventarioService
            $this->inventarioService->registrarConsumo([
                'consumo_id' => $consumo->id,
                'cultivo_id' => $request->cultivo_id,
                'items'      => $request->items
            ]);

            // Crear notificación para el usuario actual
            Notificaciones::registrarParaSupervision([
                'empresa_id' => $cultivo->empresa_id,
                'cultivo_id' => $consumo->cultivo_id,
                'user_id'    => Auth::id(),
                'mensaje'    => "Se registró un consumo para el cultivo {$consumo->cultivo->nombre}",
                'tipo'       => 'consumo',
                'leido'      => false
            ]);

            DB::commit();

            if ($request->ajax() || $request->expectsJson()) {
                return response()->json(['success' => 'Consumo registrado y stock actualizado.'], 200);
            }

            // Flash message
            return redirect()->route('consumo.index')
                             ->with('success', 'Consumo registrado y stock actualizado.');

        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->ajax() || $request->expectsJson()) {
                return response()->json(['message' => 'Error al registrar el consumo: ' . $e->getMessage()], 422);
            }

            return back()->with('error', 'Error al registrar el consumo: ' . $e->getMessage())
                         ->withInput();
        }
    }

    public function edit(Consumo $consumo)
    {
        $cultivos = $this->obtenerCultivosActivos();

        $insumos = $this->obtenerInsumosParaConsumo();
        $labores = $this->obtenerLaboresParaConsumo();

        $consumo->load('detalles');

        $detallesIniciales = $consumo->detalles->map(function ($detalle) {
            return [
                'id' => $detalle->insumo_id,
                'nombre' => $detalle->descripcion,
                'categoria' => $detalle->categoria,
                'precio' => (float) $detalle->costo_unitario,
                'unidad' => $detalle->unidad_medida,
                'bodega_id' => $detalle->bodega_id,
                'lote' => $detalle->lote,
                'cantidad' => (float) $detalle->cantidad,
                'subtotal' => (float) $detalle->subtotal,
            ];
        })->values();

        return view('modules.consumo.edit', [
            'cultivos' => $cultivos,
            'insumos' => $insumos,
            'labores' => $labores,
            'consumo' => $consumo,
            'detallesIniciales' => $detallesIniciales,
            'modoEdicion' => true,
        ]);
    }

    public function update(Request $request, Consumo $consumo)
    {
        $request->validate([
            'cultivo_id' => 'required|exists:cultivos,id',
            'fecha_consumo' => 'required|date',
            'items' => 'required|array|min:1',
        ]);

        DB::beginTransaction();

        try {
            $cultivo = Cultivo::findOrFail($request->cultivo_id);

            if (strtolower((string) $cultivo->estado) === 'cerrado') {
                return back()->with('error', 'No se puede registrar consumo en un cultivo cerrado.');
            }

            if (strtolower((string) $cultivo->estado) !== 'activo') {
                $cultivo->estado = 'Activo';
                $cultivo->save();
            }

            $this->inventarioService->revertirStockDeConsumo($consumo);
            $this->inventarioService->eliminarMovimientosDeConsumo($consumo->id);
            $consumo->detalles()->delete();

            $totalConsumo = 0;

            foreach ($request->items as $item) {
                $subtotal = $item['cantidad'] * $item['precio'];
                $totalConsumo += $subtotal;

                Consumo_detalles::create([
                    'consumo_id' => $consumo->id,
                    'insumo_id' => $item['id'],
                    'categoria' => $item['categoria'],
                    'descripcion' => $item['nombre'],
                    'cantidad' => $item['cantidad'],
                    'unidad_medida' => $item['unidad'] ?? null,
                    'costo_unitario' => $item['precio'],
                    'subtotal' => $subtotal,
                    'bodega_id' => $item['bodega_id'] ?? null,
                    'lote' => $item['lote'] ?? null,
                    'updated_by' => Auth::id(),
                    'created_by' => $consumo->created_by,
                ]);
            }

            $consumo->update([
                'empresa_id' => $cultivo->empresa_id,
                'cultivo_id' => $request->cultivo_id,
                'fecha_consumo' => $request->fecha_consumo,
                'total' => $totalConsumo,
                'updated_by' => Auth::id(),
            ]);

            $this->inventarioService->registrarConsumo([
                'consumo_id' => $consumo->id,
                'cultivo_id' => $request->cultivo_id,
                'items' => $request->items,
            ]);

            DB::commit();

            if ($request->ajax() || $request->expectsJson()) {
                return response()->json(['success' => 'Consumo actualizado y stock sincronizado.'], 200);
            }

            return redirect()->route('consumo.index')->with('success', 'Consumo actualizado y stock sincronizado.');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->ajax() || $request->expectsJson()) {
                return response()->json(['message' => 'Error al actualizar el consumo: ' . $e->getMessage()], 422);
            }

            return back()->with('error', 'Error al actualizar el consumo: ' . $e->getMessage())->withInput();
        }
    }

    /** Obtener bodegas y lotes según el insumo */
    public function getHistorialConsumo(int $cultivo_id)
    {
        $cultivo = Cultivo::findOrFail($cultivo_id);

        $consumos = $cultivo->consumos()
            ->with('detalles.insumo')
            ->orderBy('fecha_consumo', 'desc')
            ->get()
            ->map(function ($consumo) {
                return [
                    'id' => $consumo->id,
                    'fecha_consumo' => $consumo->fecha_consumo,
                    'semana' => Carbon::parse($consumo->fecha_consumo)->weekOfYear,
                    'total' => $consumo->total,
                    'detalles' => $consumo->detalles->map(function ($det) {
                        return [
                            'categoria' => $det->categoria,
                            'descripcion' => $det->descripcion,
                            'cantidad' => $det->cantidad,
                            'unidad_medida' => $det->unidad_medida,
                            'subtotal' => $det->subtotal,
                        ];
                    }),
                ];
            });

        return response()->json([
            'cultivo' => [
                'id' => $cultivo->id,
                'nombre' => $cultivo->nombre,
                'estado' => $cultivo->estado,
                'unidad_medida' => $cultivo->unidad_medida,
            ],
            'consumos' => $consumos,
        ]);
    }

    public function getBodegasLotes(int $insumo_id)
    {
        $inventarios = InventarioBodega::with('bodega')
            ->where('insumo_id', $insumo_id)
            ->where('stock_actual', '>', 0)
            ->get();

        return response()->json($inventarios->map(function($inv){
            return [
                'bodega_id'     => $inv->bodega_id,
                'bodega_nombre' => $inv->bodega->nombre,
                'numero_lote'   => $inv->numero_lote,
                'stock_actual'  => $inv->stock_actual,
                'precio_lote'   => $inv->costo_promedio, 
            ];
        }));
    }

    public function finalizar(Consumo $consumo)
    {
        $estadoActual = $consumo->estado_normalizado;

        if (in_array($estadoActual, ['FINALIZADO', 'ANULADO'], true)) {
            if (request()->ajax() || request()->expectsJson()) {
                return response()->json(['info' => 'El consumo ya no se puede finalizar.'], 200);
            }

            return redirect()->route('consumo.show', $consumo)->with('info', 'El consumo ya no se puede finalizar.');
        }

        $consumo->update([
            'estado' => 'FINALIZADO',
            'validated_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        if (request()->ajax() || request()->expectsJson()) {
            return response()->json(['success' => 'Consumo finalizado correctamente.'], 200);
        }

        return redirect()->route('consumo.show', $consumo)->with('success', 'Consumo finalizado correctamente.');
    }

    public function destroy(Consumo $consumo)
    {
        try {
            $this->inventarioService->revertirConsumo($consumo->id);

            if (request()->ajax() || request()->expectsJson()) {
                return response()->json(['success' => 'Consumo eliminado y stock restaurado correctamente.'], 200);
            }

            return redirect()->route('consumo.index')->with('success', 'Consumo eliminado y stock restaurado correctamente.');
        } catch (\Exception $e) {
            if (request()->ajax() || request()->expectsJson()) {
                return response()->json(['message' => 'Error al eliminar el consumo: ' . $e->getMessage()], 422);
            }

            return redirect()->route('consumo.index')->with('error', 'Error al eliminar el consumo: ' . $e->getMessage());
        }
    }

    private function obtenerInsumosParaConsumo()
    {
        $columnas = [
            'id',
            'codigo',
            'nombre',
            'unidad_medida',
            'costo_estimado',
        ];

        if (Schema::hasColumn('insumos', 'categoria_nombre')) {
            $columnas[] = 'categoria_nombre';
        }

        if (Schema::hasColumn('insumos', 'categoria_id')) {
            $columnas[] = 'categoria_id';
        }

        $query = Insumo::query()->select($columnas);

        if (Schema::hasColumn('insumos', 'estado')) {
            $query->where('estado', 1);
        }

        $insumos = $query->orderBy('nombre')->get();

        $insumoIds = $insumos->pluck('id')->all();

        $inventariosAgrupados = InventarioBodega::query()
            ->select(['insumo_id', 'stock_actual', 'costo_promedio'])
            ->whereIn('insumo_id', $insumoIds)
            ->get()
            ->groupBy('insumo_id');

        $categoriasPorId = collect();
        if (Schema::hasColumn('insumos', 'categoria_id')) {
            $categoriaIds = $insumos->pluck('categoria_id')->filter()->unique()->values();
            if ($categoriaIds->isNotEmpty()) {
                $categoriasPorId = DB::table('categorias')
                    ->whereIn('id', $categoriaIds)
                    ->pluck('nombre', 'id');
            }
        }

        return $insumos->map(function ($insumo) use ($inventariosAgrupados, $categoriasPorId) {
            $inventarios = $inventariosAgrupados->get($insumo->id, collect());
            $inventario = $inventarios->first();

            return [
                'id' => $insumo->id,
                'codigo' => $insumo->codigo,
                'nombre' => $insumo->nombre,
                'categoria' => $this->resolverCategoriaConsumo($insumo, $categoriasPorId),
                'unidad_medida' => $insumo->unidad_medida,
                'existencia_total' => (float) $inventarios->sum('stock_actual'),
                'precio' => $inventario ? $inventario->costo_promedio : (float) ($insumo->costo_estimado ?? 0),
            ];
        });
    }

    private function resolverCategoriaConsumo(Insumo $insumo, $categoriasPorId = null): string
    {
        if (Schema::hasColumn('insumos', 'categoria_nombre') && ! empty($insumo->categoria_nombre)) {
            return (string) $insumo->categoria_nombre;
        }

        if (Schema::hasColumn('insumos', 'categoria_id') && ! empty($insumo->categoria_id)) {
            $categoriaNombre = $categoriasPorId instanceof \Illuminate\Support\Collection
                ? $categoriasPorId->get($insumo->categoria_id)
                : DB::table('categorias')->where('id', $insumo->categoria_id)->value('nombre');

            if (! empty($categoriaNombre)) {
                return (string) $categoriaNombre;
            }
        }

        return 'Otros Insumos';
    }

    private function obtenerLaboresParaConsumo()
    {
        $query = Labore::query()->select([
            'id',
            'nombre',
            'unidad_medida',
            'costo_unitario',
            'actividad_secundaria',
            'estado',
        ]);

        if (Schema::hasColumn('labores', 'estado')) {
            $query->where('estado', 1);
        }

        return $query->orderBy('nombre')->get()->map(function ($labore) {
            return [
                'id' => $labore->id,
                'nombre' => $labore->nombre,
                'categoria' => 'Mano de Obra',
                'unidad_medida' => $labore->unidad_medida,
                'precio' => $labore->costo_unitario,
                'actividades_secundarias' => is_array($labore->actividad_secundaria)
                    ? $labore->actividad_secundaria
                    : explode(',', (string) $labore->actividad_secundaria),
            ];
        });
    }

    private function obtenerCultivosActivos()
    {
        $query = Cultivo::query()->select([
            'id',
            'nombre',
            'cosecha_estimada',
            'unidad_medida',
            'estado',
        ]);

        if (Schema::hasColumn('cultivos', 'estado')) {
            $query->where('estado', 'Activo');
        }

        return $query->orderBy('nombre')->get();
    }
}