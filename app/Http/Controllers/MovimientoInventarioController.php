<?php

namespace App\Http\Controllers;

use App\Exports\EntradaInicialTemplateExport;
use App\Imports\EntradaInicialImport;
use App\Jobs\ProcesarEntradaInicialImport;
use App\Models\Bodega;
use App\Models\FacturaInventario;
use App\Models\Insumo;
use App\Models\InventarioBodega;
use App\Models\MovimientoInventario;
use App\Services\EntradaLegacySyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class MovimientoInventarioController extends Controller
{
    private array $columnasTablaCache = [];

    public function __construct(private readonly EntradaLegacySyncService $entradaLegacySyncService)
    {
    }

    protected function responderExito(Request $request, string $mensaje)
    {
        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'success' => $mensaje,
                'redirect' => route('movimientos.index'),
            ], 200);
        }

        return redirect()->route('movimientos.index')->with('success', $mensaje);
    }

    protected function responderError(Request $request, \Throwable $error, string $mensajePorDefecto)
    {
        $mensaje = $error->getMessage() ?: $mensajePorDefecto;

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['message' => $mensaje], 422);
        }

        return back()->with('error', $mensaje)->withInput();
    }

    public function getLotesPorInsumo(Request $request)
    {
        $request->validate([
            'insumo_id' => 'required|exists:insumos,id',
            'bodega_id' => 'nullable|exists:bodegas,id',
        ]);

        $lotes = InventarioBodega::query()
            ->where('insumo_id', $request->insumo_id)
            ->when($request->filled('bodega_id'), fn($query) => $query->where('bodega_id', $request->bodega_id))
            ->where('stock_actual', '>', 0)
            ->with('bodega:id,nombre')
            ->orderBy('numero_lote')
            ->get()
            ->map(function ($inventario) {
                $numeroLoteReal = $inventario->numero_lote;
                $numeroLoteMostrar = filled($numeroLoteReal) ? $numeroLoteReal : 'SIN LOTE';
                $numeroLoteValue = filled($numeroLoteReal) ? $numeroLoteReal : '__SIN_LOTE__';

                return [
                    'numero_lote' => $numeroLoteReal,
                    'numero_lote_mostrar' => $numeroLoteMostrar,
                    'numero_lote_value' => $numeroLoteValue,
                    'bodega_id' => $inventario->bodega_id,
                    'bodega_nombre' => $inventario->bodega->nombre ?? 'Sin bodega',
                    'stock_actual' => $inventario->stock_actual,
                    'fecha_fabricacion' => $inventario->fecha_fabricacion,
                    'fecha_vencimiento' => $inventario->fecha_vencimiento,
                    'costo_promedio' => $inventario->costo_promedio,
                ];
            })
            ->values();

        return response()->json($lotes);
    }

    // ----------------------------
    // Historial de Movimientos
    // ----------------------------
    public function index(Request $request)
    {
        $titulo = 'Historial de Movimientos';
        $tipo = $request->tipo;
        $desde = $request->desde;
        $hasta = $request->hasta;
        $search = trim((string) $request->search);

        $movimientos = MovimientoInventario::with([
            'insumo',
            'bodegaOrigen',
            'bodegaDestino',

        ])
            ->when($tipo, fn($q) => $q->where('tipo', $tipo))
            ->when($desde, fn($q) => $q->whereDate('created_at', '>=', $desde))
            ->when($hasta, fn($q) => $q->whereDate('created_at', '<=', $hasta))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->whereHas('insumo', function ($insumoQuery) use ($search) {
                        $insumoQuery->where('codigo', 'like', '%' . $search . '%')
                            ->orWhere('nombre', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('bodegaOrigen', fn($bodegaQuery) => $bodegaQuery->where('nombre', 'like', '%' . $search . '%'))
                    ->orWhereHas('bodegaDestino', fn($bodegaQuery) => $bodegaQuery->where('nombre', 'like', '%' . $search . '%'))
                    ->orWhere('numero_lote', 'like', '%' . $search . '%')
                    ->orWhere('descripcion', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return view('modules.movimientos.index', compact('movimientos', 'titulo', 'tipo', 'desde', 'hasta', 'search'));
    }

    // ----------------------------
    // Formulario Entrada
    // ----------------------------
    public function entrada()
    {
        $columnasInsumos = ['id', 'codigo', 'nombre', 'unidad_medida'];

        if (Schema::hasColumn('insumos', 'ingrediente_activo')) {
            $columnasInsumos[] = 'ingrediente_activo';
        } elseif (Schema::hasColumn('insumos', 'ingredientes_activo')) {
            $columnasInsumos[] = DB::raw('ingredientes_activo as ingrediente_activo');
        }

        $insumos = Insumo::query()
            ->select($columnasInsumos)
            ->with(['inventarioBodegas:insumo_id,stock_actual,costo_promedio'])
            ->when(Schema::hasColumn('insumos', 'estado'), fn ($query) => $query->where('estado', 1))
            ->orderBy('nombre')
            ->get();

        $bodegas = Bodega::query()
            ->select(['id', 'nombre'])
            ->when(Schema::hasColumn('bodegas', 'estado'), fn ($query) => $query->where('estado', 1))
            ->orderBy('nombre')
            ->get();

        return view('modules.movimientos.entradas.entrada', compact('insumos', 'bodegas'));
    }

    public function entradaIndex()
    {
        $entradasRecientes = MovimientoInventario::with(['insumo', 'bodegaDestino'])
            ->where('tipo', 'ENTRADA')
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();

        $totalEntradasHoy = MovimientoInventario::query()
            ->where('tipo', 'ENTRADA')
            ->whereDate('created_at', today())
            ->count();

        $totalBodegas = Bodega::query()->count();
        $totalInsumos = Insumo::query()->count();

        return view('modules.movimientos.entradas.index', compact(
            'entradasRecientes',
            'totalEntradasHoy',
            'totalBodegas',
            'totalInsumos'
        ));
    }

    public function entradaImportar()
    {
        return view('modules.movimientos.entradas.importar_excel');
    }

    public function entradaImportarStore(Request $request)
    {
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
        @ini_set('memory_limit', '1024M');

        $request->validate([
            'archivo_excel' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $empresaId = $this->resolverEmpresaIdActual();

        if (config('queue.default') !== 'sync') {
            $archivo = $request->file('archivo_excel');
            $extension = strtolower((string) $archivo?->getClientOriginalExtension());
            $nombreArchivo = (string) ($archivo?->getClientOriginalName() ?: 'entrada_inicial.' . $extension);
            $rutaArchivo = Storage::disk('local')->putFileAs(
                'imports/entradas-iniciales',
                $archivo,
                now()->format('YmdHis') . '-' . Str::uuid() . '.' . $extension
            );

            ProcesarEntradaInicialImport::dispatch(
                $rutaArchivo,
                (int) Auth::id(),
                Auth::user()->sucursal_id !== null ? (int) Auth::user()->sucursal_id : null,
                $empresaId,
                $nombreArchivo
            );

            return response()->json([
                'title' => 'Carga masiva en cola',
                'success' => 'El archivo fue enviado a la cola de procesamiento.',
                'summary_html' => $this->buildImportSummaryHtml([
                    'Archivo recibido: ' . $nombreArchivo,
                    'Modo de ejecucion: cola en segundo plano',
                    'La importacion ya no bloquea la peticion web mientras se procesa.',
                    'Recibiras una notificacion interna cuando termine o falle.',
                ]),
                'redirect' => route('movimientos.index'),
                'queued' => true,
            ], 202);
        }

        $import = new EntradaInicialImport(
            Auth::id(),
            Auth::user()->sucursal_id ?? null,
            $empresaId
        );

        try {
            Excel::import($import, $request->file('archivo_excel'));

            $stats = $import->getStats();
            $errores = $import->getErrores();

            if (($stats['filas_importadas'] ?? 0) === 0 && !empty($errores)) {
                return response()->json([
                    'message' => 'No se importo ninguna fila. Revisa el archivo e intenta de nuevo.',
                    'summary_html' => $this->buildImportSummaryHtml($import->getSummaryLines(), $errores),
                ], 422);
            }

            return response()->json([
                'success' => 'Carga masiva completada.',
                'stats' => $stats,
                'summary_html' => $this->buildImportSummaryHtml($import->getSummaryLines(), $errores),
                'redirect' => route('movimientos.index'),
            ], 200);
        } catch (\Throwable $error) {
            return response()->json([
                'message' => $this->resolverMensajeErrorImportacion($request, $error),
            ], 422);
        }
    }

    public function descargarPlantillaEntradaInicial()
    {
        return Excel::download(new EntradaInicialTemplateExport(), 'plantilla_entrada_inicial.xlsx');
    }

    private function resolverMensajeErrorImportacion(Request $request, \Throwable $error): string
    {
        $mensaje = $error->getMessage();
        $extension = strtolower((string) $request->file('archivo_excel')?->getClientOriginalExtension());

        if (Str::contains($mensaje, 'ZipArchive') && in_array($extension, ['xlsx', 'xls'], true)) {
            return 'El servidor no tiene habilitada la extension ZIP de PHP para leer archivos Excel. Mientras se habilita, importa la plantilla en formato CSV o reinicia PHP despues de activar extension=zip en php.ini.';
        }

        return 'Error al importar el archivo: ' . $mensaje;
    }

    private function resolverEmpresaIdActual(): ?int
    {
        $user = Auth::user();
        $empresaId = $user?->empresa_id ?? $user?->sucursal?->empresa_id;

        return $empresaId !== null ? (int) $empresaId : null;
    }

    // ----------------------------
    // Guardar Entradas
    // ----------------------------
    public function entradaStore(Request $request)
    {
        // Validar que el cultivo esté activo antes de registrar consumo
        if ($request->has('cultivo_id')) {
            $cultivo = \App\Models\Cultivo::find($request->cultivo_id);
            if ($cultivo && strtolower($cultivo->estado) === 'cerrado') {
                $error = new \RuntimeException('Este cultivo está cerrado. Solo puedes ver su historial, no registrar consumos.');
                return $this->responderError($request, $error, 'No se pudo registrar la entrada.');
            }
        }

        $request->validate([
            'insumo_ids' => 'required|array|min:1',
            'insumo_ids.*' => 'exists:insumos,id',
            'bodega_ids.*' => 'required|exists:bodegas,id',
            'cantidades.*' => 'required|numeric|min:0.01',
            'precios.*' => 'required|numeric|min:0',
            'lotes.*' => 'nullable|string',
            'fechas_fabricacion.*' => 'nullable|date',
            'fechas_vencimiento.*' => 'nullable|date',
            'proveedores.*' => 'nullable|string',
            'archivos.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        try {
            DB::transaction(function () use ($request) {
                foreach ($request->insumo_ids as $index => $insumo_id) {
                    $insumo = Insumo::findOrFail($insumo_id);
                    $bodega_id = $request->bodega_ids[$index];
                    $cantidad = $request->cantidades[$index];
                    $precio = $request->precios[$index];
                    $lote = $request->lotes[$index] ?? null;
                    $fechaFab = $request->fechas_fabricacion[$index] ?? null;
                    $fechaVen = $request->fechas_vencimiento[$index] ?? null;
                    $proveedor = $request->proveedores[$index] ?? null;

                    $bodega = Bodega::findOrFail($bodega_id);

                    $numeroLotePersistible = $this->tablaTieneColumna('inventario_bodegas', 'numero_lote') ? $lote : null;

                    $inventario = InventarioBodega::firstOrCreate(
                        $this->filtrarColumnasPersistidas('inventario_bodegas', [
                            'empresa_id' => $bodega->empresa_id,
                            'insumo_id' => $insumo->id,
                            'bodega_id' => $bodega_id,
                            'numero_lote' => $numeroLotePersistible,
                        ]),
                        $this->filtrarColumnasPersistidas('inventario_bodegas', [
                            'stock_actual' => 0,
                            'costo_promedio' => $precio,
                            'fecha_fabricacion' => $fechaFab,
                            'fecha_vencimiento' => $fechaVen,
                        ])
                    );

                    $stockAnterior = $inventario->stock_actual;
                    $nuevoStock = $stockAnterior + $cantidad;

                    $nuevoCosto = $stockAnterior == 0
                        ? $precio
                        : (($inventario->costo_promedio * $stockAnterior) + ($precio * $cantidad)) / $nuevoStock;

                    $this->actualizarInventarioPersistido($inventario, $this->filtrarColumnasPersistidas('inventario_bodegas', [
                        'stock_actual' => $nuevoStock,
                        'costo_promedio' => $nuevoCosto,
                        'fecha_fabricacion' => $fechaFab,
                        'fecha_vencimiento' => $fechaVen
                    ]));

                    $movimiento = MovimientoInventario::create($this->filtrarColumnasPersistidas('movimiento_inventarios', [
                        'empresa_id' => $bodega->empresa_id,
                        'insumo_id' => $insumo->id,
                        'bodega_destino_id' => $bodega_id,
                        'tipo' => 'ENTRADA',
                        'cantidad' => $cantidad,
                        'precio_unitario' => $precio,
                        'costo_unitario' => $nuevoCosto,
                        'stock_anterior' => $stockAnterior,
                        'stock_actual' => $nuevoStock,
                        'sucursal_id' => $bodega->sucursal_id,
                        'descripcion' => $proveedor,
                        'numero_lote' => $numeroLotePersistible,
                        'fecha_fabricacion' => $fechaFab,
                        'fecha_vencimiento' => $fechaVen,
                        'created_by' => Auth::id(),
                    ]));

                    $archivo = $request->file('archivos')[$index] ?? null;
                    $rutaArchivo = null;

                    if ($archivo) {
                        $nombreArchivo = time() . '_' . $archivo->getClientOriginalName();
                        $rutaArchivo = 'facturas/' . $nombreArchivo;
                        $archivo->storeAs('facturas', $nombreArchivo, 'public');
                    }

                    $facturaPayload = $this->filtrarColumnasPersistidas('factura_inventarios', [
                        'empresa_id' => $bodega->empresa_id,
                        'movimiento_id' => $movimiento->id,
                        'insumo_id' => $insumo->id,
                        'bodega_id' => $bodega_id,
                        'cantidad' => $cantidad,
                        'precio_unitario' => $precio,
                        'total' => $cantidad * $precio,
                        'proveedor' => $proveedor,
                        'numero_lote' => $numeroLotePersistible,
                        'fecha_fabricacion' => $fechaFab,
                        'fecha_vencimiento' => $fechaVen,
                        'archivo' => $rutaArchivo,
                        'created_by' => Auth::id(),
                    ]);

                    if ($facturaPayload !== []) {
                        FacturaInventario::create($facturaPayload);
                    }

                    $this->entradaLegacySyncService->registrar([
                        'insumo_id' => $insumo->id,
                        'bodega_id' => $bodega_id,
                        'tipo' => 'compra',
                        'cantidad' => $cantidad,
                        'costo_unitario' => $precio,
                        'factura' => $rutaArchivo,
                        'proveedor' => $proveedor,
                        'fecha_ingreso' => now()->toDateString(),
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ]);
                }
            });

            return $this->responderExito($request, 'Entradas múltiples registradas correctamente.');
        } catch (\Throwable $error) {
            return $this->responderError($request, $error, 'No se pudo registrar la entrada.');
        }
    }

    // ----------------------------
    // Ajustes
    // ----------------------------
    public function ajuste()
    {
        $insumos = $this->getActivos(Insumo::class, 'insumos');
        $bodegas = $this->getActivos(Bodega::class, 'bodegas');
        $lotes = InventarioBodega::all();

        return view('modules.movimientos.ajuste.ajuste', array_merge(
            compact('insumos', 'bodegas', 'lotes'),
            $this->buildMovimientoFormConfig('ajuste')
        ));
    }

    public function ajusteStore(Request $request)
    {
        return $this->procesarAjustes($request, false);
    }

    public function salida()
    {
        $insumos = $this->getActivos(Insumo::class, 'insumos');
        $bodegas = $this->getActivos(Bodega::class, 'bodegas');
        $lotes = InventarioBodega::all();

        return view('modules.movimientos.ajuste.ajuste', array_merge(
            compact('insumos', 'bodegas', 'lotes'),
            $this->buildMovimientoFormConfig('salida')
        ));
    }

    public function salidaStore(Request $request)
    {
        return $this->procesarAjustes($request, true);
    }

    protected function procesarAjustes(Request $request, bool $soloResta)
    {
        $request->validate([
            'insumo_ids' => 'required|array',
            'insumo_ids.*' => 'exists:insumos,id',
            'bodega_origen_ids' => 'required|array',
            'bodega_origen_ids.*' => 'exists:bodegas,id',
            'cantidades' => 'required|array',
            'cantidades.*' => 'required|numeric|min:0.01',
            'lotes' => 'required|array',
            'lotes.*' => 'required|string',
            'tipo_ajuste' => 'required|array',
            'tipo_ajuste.*' => 'required|in:SUMA,RESTA',
            'descripcion' => 'nullable|array',
        ]);

        try {
            DB::transaction(function () use ($request, $soloResta) {
                foreach ($request->insumo_ids as $i => $insumoId) {
                    $bodegaId = $request->bodega_origen_ids[$i];
                    $bodega = Bodega::findOrFail($bodegaId);
                    $cantidad = $request->cantidades[$i];
                    $lote = $this->normalizarValorLote($request->lotes[$i] ?? null);
                    $tipoAjuste = strtoupper($request->tipo_ajuste[$i]);
                    $descripcion = $request->descripcion[$i] ?? 'Ajuste de inventario';

                    if ($soloResta && $tipoAjuste !== 'RESTA') {
                        throw new \RuntimeException('La salida solo permite movimientos de resta.');
                    }

                    $inventario = $this->buscarInventarioPorLote($insumoId, $bodegaId, $lote);

                    $stockAnterior = $inventario->stock_actual;

                    if ($tipoAjuste === 'RESTA') {
                        if ($inventario->stock_actual < $cantidad) {
                            throw new \RuntimeException("Stock insuficiente en lote {$lote}.");
                        }
                        $inventario->stock_actual -= $cantidad;
                    } else {
                        $inventario->stock_actual += $cantidad;
                    }

                    $this->actualizarInventarioPersistido($inventario, $this->filtrarColumnasPersistidas('inventario_bodegas', [
                        'stock_actual' => $inventario->stock_actual,
                    ]));

                    MovimientoInventario::create($this->filtrarColumnasPersistidas('movimiento_inventarios', [
                        'empresa_id'      => $bodega->empresa_id,
                        'insumo_id'       => $insumoId,
                        'bodega_origen_id'=> $bodegaId,
                        'tipo'            => $soloResta ? 'SALIDA' : 'AJUSTE',
                        'cantidad'        => $cantidad,
                        'precio_unitario' => $inventario->costo_promedio,
                        'costo_unitario'  => $inventario->costo_promedio,
                        'stock_anterior'  => $stockAnterior,
                        'stock_actual'    => $inventario->stock_actual,
                        'descripcion'     => $descripcion,
                        'sucursal_id'     => $bodega->sucursal_id,
                        'numero_lote'     => $lote,
                        'created_by'      => Auth::id()
                    ]));
                }
            });

            return $this->responderExito($request, $soloResta ? 'Salidas registradas correctamente.' : 'Ajustes registrados correctamente.');
        } catch (\Throwable $error) {
            return $this->responderError($request, $error, $soloResta ? 'No se pudo registrar la salida.' : 'No se pudo registrar el ajuste.');
        }
    }

    // ----------------------------
    // Traslados
    // ----------------------------
    public function traslado()
    {
        $insumos = $this->getActivos(Insumo::class, 'insumos');
        $bodegas = $this->getActivos(Bodega::class, 'bodegas');
        $lotes = InventarioBodega::all();

        return view('modules.movimientos.traslados', compact('insumos', 'bodegas', 'lotes'));
    }

    public function trasladoStore(Request $request)
    {
        $request->validate([
            'insumo_ids' => 'required|array',
            'insumo_ids.*' => 'exists:insumos,id',
            'bodega_origen_ids' => 'required|array',
            'bodega_origen_ids.*' => 'exists:bodegas,id',
            'bodega_destino_ids' => 'required|array',
            'bodega_destino_ids.*' => 'exists:bodegas,id',
            'lotes_origen.*' => 'required|string',
            'lotes_destino.*' => 'required|string',
            'cantidades.*' => 'required|numeric|min:0.01',
            'descripcion.*' => 'nullable|string',
            'fechas_fabricacion_destino.*' => 'nullable|date',
            'fechas_vencimiento_destino.*' => 'nullable|date',
        ]);

        try {
            DB::transaction(function () use ($request) {
                foreach ($request->insumo_ids as $i => $insumoId) {
                    $bodegaOrigenId = $request->bodega_origen_ids[$i];
                    $bodegaDestinoId = $request->bodega_destino_ids[$i];
                    $bodegaDestino = Bodega::findOrFail($bodegaDestinoId);
                    $loteOrigen = $this->normalizarValorLote($request->lotes_origen[$i] ?? null);
                    $loteDestino = $this->normalizarValorLote($request->lotes_destino[$i] ?? null);
                    $cantidad = $request->cantidades[$i];
                    $descripcion = $request->descripcion[$i] ?? null;

                    $fechaFabDestino = $request->fechas_fabricacion_destino[$i] ?? now()->format('Y-m-d');
                    $fechaVenceDestino = $request->fechas_vencimiento_destino[$i] ?? now()->addYears(2)->format('Y-m-d');

                    if ($bodegaOrigenId == $bodegaDestinoId) {
                        throw new \RuntimeException("Bodega origen y destino no pueden ser iguales para el insumo {$insumoId}.");
                    }

                    $invOrigen = $this->buscarInventarioPorLote($insumoId, $bodegaOrigenId, $loteOrigen);

                    if ($invOrigen->stock_actual < $cantidad) {
                        throw new \RuntimeException("Stock insuficiente en bodega origen para insumo {$insumoId}, lote {$loteOrigen}.");
                    }

                    $stockAnterior = $invOrigen->stock_actual;
                    $invOrigen->stock_actual -= $cantidad;
                    $this->actualizarInventarioPersistido($invOrigen, $this->filtrarColumnasPersistidas('inventario_bodegas', [
                        'stock_actual' => $invOrigen->stock_actual,
                    ]));

                    $invDestino = InventarioBodega::firstOrNew($this->filtrarColumnasPersistidas('inventario_bodegas', [
                        'empresa_id' => $bodegaDestino->empresa_id,
                        'insumo_id' => $insumoId,
                        'bodega_id' => $bodegaDestinoId,
                        'numero_lote' => $loteDestino
                    ]));

                    if (!$invDestino->exists) {
                        $invDestino->forceFill($this->filtrarColumnasPersistidas('inventario_bodegas', [
                            'stock_actual' => $cantidad,
                            'costo_promedio' => $invOrigen->costo_promedio,
                            'fecha_fabricacion' => $fechaFabDestino,
                            'fecha_vencimiento' => $fechaVenceDestino,
                        ]));
                        $invDestino->save();
                    } else {
                        $this->actualizarInventarioPersistido($invDestino, $this->filtrarColumnasPersistidas('inventario_bodegas', [
                            'stock_actual' => $invDestino->stock_actual + $cantidad,
                            'fecha_fabricacion' => $fechaFabDestino,
                            'fecha_vencimiento' => $fechaVenceDestino,
                        ]));
                    }

                    MovimientoInventario::create($this->filtrarColumnasPersistidas('movimiento_inventarios', [
                        'empresa_id' => $bodegaDestino->empresa_id,
                        'insumo_id' => $insumoId,
                        'bodega_origen_id' => $bodegaOrigenId,
                        'bodega_destino_id' => $bodegaDestinoId,
                        'tipo' => 'TRASLADO',
                        'cantidad' => $cantidad,
                        'precio_unitario' => $invOrigen->costo_promedio,
                        'costo_unitario' => $invOrigen->costo_promedio,
                        'stock_anterior' => $stockAnterior,
                        'stock_actual' => $invOrigen->stock_actual,
                        'descripcion' => $descripcion,
                        'sucursal_id' => Auth::user()->sucursal_id,
                        'numero_lote' => $loteDestino,
                        'fecha_fabricacion' => $invDestino->fecha_fabricacion,
                        'fecha_vencimiento' => $invDestino->fecha_vencimiento,
                        'created_by' => Auth::id()
                    ]));
                }
            });

            return $this->responderExito($request, 'Traslados registrados correctamente.');
        } catch (\Throwable $error) {
            return $this->responderError($request, $error, 'No se pudo registrar el traslado.');
        }
    }

    protected function buildImportSummaryHtml(array $summaryLines, array $errores = []): string
    {
        $html = '<ul class="text-start ps-3 mb-2">';

        foreach ($summaryLines as $line) {
            $html .= '<li>' . e($line) . '</li>';
        }

        $html .= '</ul>';

        if (!empty($errores)) {
            $html .= '<div class="mt-3"><b>Errores detectados:</b><ul class="text-start ps-3 mb-0">';
            foreach ($errores as $error) {
                $html .= '<li>' . e($error) . '</li>';
            }
            $html .= '</ul></div>';
        }

        return $html;
    }

    private function filtrarColumnasPersistidas(string $tabla, array $payload): array
    {
        $columnas = $this->obtenerColumnasTabla($tabla);

        return array_filter(
            $payload,
            static fn ($valor, $columna) => in_array($columna, $columnas, true),
            ARRAY_FILTER_USE_BOTH
        );
    }

    private function obtenerColumnasTabla(string $tabla): array
    {
        if (!isset($this->columnasTablaCache[$tabla])) {
            $this->columnasTablaCache[$tabla] = Schema::getColumnListing($tabla);
        }

        return $this->columnasTablaCache[$tabla];
    }

    private function tablaTieneColumna(string $tabla, string $columna): bool
    {
        return in_array($columna, $this->obtenerColumnasTabla($tabla), true);
    }

    private function actualizarInventarioPersistido(InventarioBodega $inventario, array $payload): void
    {
        if ($payload === []) {
            return;
        }

        if ($this->tablaTieneColumna('inventario_bodegas', 'updated_at') && ! array_key_exists('updated_at', $payload)) {
            $payload['updated_at'] = now();
        }

        $query = InventarioBodega::query()
            ->where('insumo_id', $inventario->insumo_id)
            ->where('bodega_id', $inventario->bodega_id);

        if ($this->tablaTieneColumna('inventario_bodegas', 'empresa_id') && isset($inventario->empresa_id)) {
            $query->where('empresa_id', $inventario->empresa_id);
        }

        if ($this->tablaTieneColumna('inventario_bodegas', 'numero_lote')) {
            if ($inventario->numero_lote === null) {
                $query->whereNull('numero_lote');
            } else {
                $query->where('numero_lote', $inventario->numero_lote);
            }
        }

        $query->update($payload);
        $inventario->forceFill($payload);
    }

    private function getActivos(string $modelClass, string $table)
    {
        $query = $modelClass::query();

        if (Schema::hasColumn($table, 'estado')) {
            $query->where('estado', 1);
        }

        return $query->get();
    }

    private function normalizarValorLote(mixed $valor): ?string
    {
        $texto = trim((string) ($valor ?? ''));

        if ($texto === '' || $texto === '__SIN_LOTE__') {
            return null;
        }

        return $texto;
    }

    private function buscarInventarioPorLote(int $insumoId, int $bodegaId, ?string $lote): InventarioBodega
    {
        return InventarioBodega::query()
            ->where('insumo_id', $insumoId)
            ->where('bodega_id', $bodegaId)
            ->when(
                $this->tablaTieneColumna('inventario_bodegas', 'numero_lote'),
                function ($query) use ($lote) {
                    if ($lote === null) {
                        $query->whereNull('numero_lote');
                    } else {
                        $query->where('numero_lote', $lote);
                    }
                }
            )
            ->firstOrFail();
    }

    private function buildMovimientoFormConfig(string $modo): array
    {
        if ($modo === 'salida') {
            return [
                'modoMovimiento' => 'salida',
                'tituloMovimiento' => 'Salida de Inventario',
                'iconoMovimiento' => 'fa fa-arrow-up-right-dots',
                'colorMovimiento' => 'danger',
                'accionMovimiento' => route('movimientos.salida.store'),
                'textoBotonMovimiento' => 'Procesar Salida',
                'textoAgregarMovimiento' => 'Agregar salida al listado',
                'descripcionPlaceholder' => 'Ej. Merma, vencimiento o salida manual',
            ];
        }

        return [
            'modoMovimiento' => 'ajuste',
            'tituloMovimiento' => 'Ajuste de Inventario (+ / -)',
            'iconoMovimiento' => 'fa fa-exchange-alt',
            'colorMovimiento' => 'warning',
            'accionMovimiento' => route('movimientos.ajuste.store'),
            'textoBotonMovimiento' => 'Procesar Ajuste',
            'textoAgregarMovimiento' => 'Agregar al listado',
            'descripcionPlaceholder' => 'Ej. Ajuste por pérdida',
        ];
    }
}