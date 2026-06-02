<?php

namespace App\Http\Controllers;

use App\Models\Bodega;
use App\Models\Categorias;
use App\Models\Empresa;
use App\Models\Insumo;
use App\Models\Notificaciones;
use App\Models\OrdenCompra;
use App\Models\SolicitudCompra;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SolicitudCompraController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user && $this->canAccessPurchaseModule($user), 403);

        $insumos = $this->catalogoInsumos();
        $categorias = Categorias::query()
            ->select(['id', 'nombre'])
            ->orderBy('nombre')
            ->get();

        $bodegas = Bodega::query()
            ->select(['id', 'nombre'])
            ->orderBy('nombre')
            ->get();

        $titulo = 'Solicitud de suministro de insumos o materiales';

        return view('modules.compras.solicitudes.index', compact(
            'titulo',
            'insumos',
            'categorias',
            'bodegas'
        ));
    }

    public function review(Request $request): View
    {
        $user = $request->user();
        abort_unless($user && $this->canAccessPurchaseModule($user), 403);

        $estado = trim((string) $request->query('estado', ''));
        $perPage = (int) $request->query('per_page', 20);

        if (! in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 20;
        }

        $solicitudesQuery = SolicitudCompra::query()
            ->with([
                'solicitante',
                'aprobador',
                'gestorCompra',
                'receptor',
                'insumo',
                'bodegaDestino',
                'movimientoInventario',
                'facturaInventario',
                'ordenCompra',
            ])
            ->orderByDesc('id');

        if (! $this->canManagePurchaseBoard($user)) {
            $solicitudesQuery->where('solicitante_id', $user->id);
        }

        if ($estado !== '') {
            $solicitudesQuery->where('estado', $estado);
        }

        $solicitudes = $solicitudesQuery
            ->paginate($perPage)
            ->withQueryString();

        $titulo = 'Historial y revisión de solicitudes';
        $estados = [
            SolicitudCompra::ESTADO_PENDIENTE_APROBACION,
            SolicitudCompra::ESTADO_APROBADA,
            SolicitudCompra::ESTADO_EN_PROCESO,
            SolicitudCompra::ESTADO_RECHAZADA,
            SolicitudCompra::ESTADO_RECIBIDA,
        ];

        return view('modules.compras.solicitudes.review', compact(
            'titulo',
            'solicitudes',
            'estado',
            'estados',
            'perPage'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $this->canAccessPurchaseModule($user), 403);

        $validated = $request->validate([
            'bodega_destino_id' => 'nullable|exists:bodegas,id',
            'departamento' => 'nullable|string|max:120',
            'asunto' => 'required|string|max:150',
            'prioridad' => 'required|in:baja,media,alta',
            'fecha_requerida' => 'nullable|date',
            'descripcion' => 'required|string|max:3000',
            'detalles' => 'required|array|min:1',
            'detalles.*.descripcion' => 'required|string|max:190',
            'detalles.*.categoria' => 'nullable|string|max:120',
            'detalles.*.unidad' => 'nullable|string|max:60',
            'detalles.*.cantidad' => 'required|numeric|min:0.01',
        ]);

        $detalleItems = $this->normalizeDetalleItems($validated['detalles']);
        $primerDetalle = $detalleItems[0];
        $unSoloDetalle = count($detalleItems) === 1;

        $empresaId = $user->empresa_id ?? $user->sucursal?->empresa_id;

        $solicitud = SolicitudCompra::create([
            'empresa_id' => $empresaId,
            'solicitante_id' => $user->id,
            'insumo_id' => $unSoloDetalle ? ($primerDetalle['insumo_id'] ?: null) : null,
            'bodega_destino_id' => $validated['bodega_destino_id'] ?? null,
            'codigo' => $this->generateCode(),
            'departamento' => $validated['departamento'] ?? ($user->rol->nombre ?? 'General'),
            'asunto' => $validated['asunto'],
            'unidad' => $unSoloDetalle ? ($primerDetalle['unidad'] ?: null) : null,
            'cantidad' => $unSoloDetalle ? $primerDetalle['cantidad'] : array_sum(array_column($detalleItems, 'cantidad')),
            'precio_estimado' => $unSoloDetalle ? $primerDetalle['precio_estimado'] : null,
            'prioridad' => $validated['prioridad'],
            'descripcion' => $validated['descripcion'],
            'detalle_items' => $detalleItems,
            'fecha_requerida' => $validated['fecha_requerida'] ?? null,
            'estado' => SolicitudCompra::ESTADO_PENDIENTE_APROBACION,
        ]);

        Notificaciones::registrarParaCompras([
            'empresa_id' => $empresaId,
            'mensaje' => 'Nueva solicitud de compra ' . $solicitud->codigo . ': ' . $solicitud->asunto . ' (' . count($detalleItems) . ' item(s))',
            'tipo' => 'compra',
            'user_id' => null,
        ]);

        return redirect()
            ->route('compras.solicitudes.index')
            ->with('success', 'Solicitud de compra enviada correctamente.');
    }

    public function show(Request $request, SolicitudCompra $solicitud): View
    {
        $user = $request->user();
        abort_unless($user && $this->canAccessPurchaseModule($user), 403);
        $this->assertCanView($user, $solicitud);

        $this->loadDocumentRelations($solicitud);

        $empresa = $this->resolveEmpresa($solicitud, $user);
        $logoEmpresa = $this->resolverLogoEmpresa($empresa);

        return view('modules.compras.solicitudes.show', [
            'titulo' => 'Detalle de solicitud de compra',
            'solicitud' => $solicitud,
            'empresa' => $empresa,
            'logoEmpresa' => $logoEmpresa,
        ]);
    }

    public function downloadPdf(Request $request, SolicitudCompra $solicitud)
    {
        $user = $request->user();
        abort_unless($user && $this->canAccessPurchaseModule($user), 403);
        $this->assertCanView($user, $solicitud);

        $this->loadDocumentRelations($solicitud);

        $empresa = $this->resolveEmpresa($solicitud, $user);
        $logoEmpresa = $this->resolverLogoEmpresa($empresa);

        $pdf = Pdf::loadView('modules.compras.solicitudes.pdf', [
            'solicitud' => $solicitud,
            'empresa' => $empresa,
            'logoEmpresa' => $logoEmpresa,
        ]);

        $fileName = 'solicitud_compra_' . ($solicitud->codigo ?: $solicitud->id) . '.pdf';

        if ($request->boolean('inline')) {
            return $pdf->stream($fileName);
        }

        return $pdf->download($fileName);
    }

    public function approve(Request $request, SolicitudCompra $solicitud): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->isSuperUser(), 403);

        if (in_array($solicitud->estado, [SolicitudCompra::ESTADO_RECHAZADA, SolicitudCompra::ESTADO_RECIBIDA], true)) {
            return back()->with('error', 'La solicitud ya fue cerrada y no puede aprobarse.');
        }

        $validated = $request->validate([
            'observaciones_compra' => 'nullable|string|max:3000',
        ]);

        $solicitud->update([
            'estado' => SolicitudCompra::ESTADO_APROBADA,
            'aprobado_por' => $user->id,
            'aprobado_en' => now(),
            'observaciones_compra' => $validated['observaciones_compra'] ?? $solicitud->observaciones_compra,
        ]);

        return back()->with('success', 'Solicitud aprobada por gerencia correctamente.');
    }

    public function createOrderForm(Request $request, SolicitudCompra $solicitud): View|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $this->canManagePurchaseBoard($user), 403);

        if (! in_array($solicitud->estado, [SolicitudCompra::ESTADO_APROBADA, SolicitudCompra::ESTADO_EN_PROCESO], true)) {
            return redirect()->route('compras.solicitudes.index')
                ->with('error', 'Solo las solicitudes aprobadas pueden generar una orden de compra.');
        }

        if ($solicitud->ordenCompra()->exists()) {
            return redirect()->route('compras.ordenes.show', $solicitud->ordenCompra)
                ->with('error', 'La solicitud ya tiene una orden de compra generada.');
        }

        $this->loadDocumentRelations($solicitud);

        $detalleItems = collect($solicitud->detalle_items_resolved)
            ->map(function (array $item) {
                $cantidad = (float) ($item['cantidad'] ?? 0);
                $precioUnitario = isset($item['precio_unitario']) && $item['precio_unitario'] !== ''
                    ? (float) $item['precio_unitario']
                    : null;

                return [
                    'descripcion' => $item['descripcion'] ?? '',
                    'categoria' => $item['categoria'] ?? '',
                    'unidad' => $item['unidad'] ?? '',
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precioUnitario,
                ];
            })
            ->values();

        return view('modules.compras.ordenes.create', [
            'titulo' => 'Generar orden de compra',
            'solicitud' => $solicitud,
            'detalleItems' => $detalleItems,
        ]);
    }

    public function storeOrder(Request $request, SolicitudCompra $solicitud): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $this->canManagePurchaseBoard($user), 403);

        if (! in_array($solicitud->estado, [SolicitudCompra::ESTADO_APROBADA, SolicitudCompra::ESTADO_EN_PROCESO], true)) {
            return back()->with('error', 'Solo las solicitudes aprobadas pueden generar una orden de compra.');
        }

        if ($solicitud->ordenCompra()->exists()) {
            return redirect()->route('compras.ordenes.show', $solicitud->ordenCompra)
                ->with('error', 'La solicitud ya tiene una orden de compra generada.');
        }

        $validated = $request->validate([
            'proveedor' => 'required|string|max:160',
            'fecha_emision' => 'required|date',
            'total_estimado' => 'nullable|numeric|min:0',
            'observaciones' => 'nullable|string|max:3000',
            'detalles' => 'required|array|min:1',
            'detalles.*.descripcion' => 'required|string|max:190',
            'detalles.*.categoria' => 'nullable|string|max:120',
            'detalles.*.unidad' => 'nullable|string|max:60',
            'detalles.*.cantidad' => 'required|numeric|min:0.01',
            'detalles.*.precio_unitario' => 'nullable|numeric|min:0',
        ]);

        $detalleItems = collect($validated['detalles'])
            ->map(function (array $item) {
                $cantidad = (float) ($item['cantidad'] ?? 0);
                $precioUnitario = isset($item['precio_unitario']) && $item['precio_unitario'] !== ''
                    ? (float) $item['precio_unitario']
                    : null;

                return [
                    'descripcion' => trim((string) ($item['descripcion'] ?? '')),
                    'categoria' => trim((string) ($item['categoria'] ?? '')),
                    'unidad' => trim((string) ($item['unidad'] ?? '')),
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precioUnitario,
                    'subtotal' => $precioUnitario !== null ? $cantidad * $precioUnitario : null,
                ];
            })
            ->values()
            ->all();

        $totalCalculado = collect($detalleItems)
            ->sum(fn (array $item) => (float) ($item['subtotal'] ?? 0));

        $orden = OrdenCompra::create([
            'empresa_id' => $solicitud->empresa_id,
            'solicitud_compra_id' => $solicitud->id,
            'generado_por' => $user->id,
            'codigo' => $this->generateOrderCode(),
            'proveedor' => trim($validated['proveedor']),
            'fecha_emision' => $validated['fecha_emision'],
            'estado' => 'BORRADOR',
            'total_estimado' => $validated['total_estimado'] ?? $totalCalculado,
            'observaciones' => $validated['observaciones'] ?? null,
            'detalle_items' => $detalleItems,
        ]);

        $solicitud->update([
            'estado' => SolicitudCompra::ESTADO_EN_PROCESO,
            'gestionado_por' => $user->id,
            'gestionado_en' => now(),
            'observaciones_compra' => $solicitud->observaciones_compra
                ? trim($solicitud->observaciones_compra . ' | O.C. ' . $orden->codigo . ' generada')
                : 'O.C. ' . $orden->codigo . ' generada',
        ]);

        return redirect()->route('compras.ordenes.show', $orden)
            ->with('success', 'Orden de compra generada correctamente.');
    }

    public function showOrder(Request $request, OrdenCompra $orden): View
    {
        $user = $request->user();
        abort_unless($user && $this->canAccessPurchaseModule($user), 403);

        $orden->load([
            'solicitudCompra.solicitante',
            'solicitudCompra.aprobador',
            'solicitudCompra.bodegaDestino',
            'creador',
            'receptor',
            'aprobadorDiferencias',
        ]);

        abort_unless(
            $this->canManagePurchaseBoard($user)
            || (int) ($orden->solicitudCompra?->solicitante_id) === (int) $user->id,
            403
        );

        $empresa = $orden->solicitudCompra ? $this->resolveEmpresa($orden->solicitudCompra, $user) : null;
        $logoEmpresa = $this->resolverLogoEmpresa($empresa);

        return view('modules.compras.ordenes.show', [
            'titulo' => 'Orden de compra',
            'orden' => $orden,
            'empresa' => $empresa,
            'logoEmpresa' => $logoEmpresa,
        ]);
    }

    public function validationIndex(Request $request): View
    {
        $user = $request->user();
        abort_unless($user && $this->canManagePurchaseBoard($user), 403);

        $recepcionEstado = trim((string) $request->query('recepcion_estado', 'pendiente'));
        $perPage = (int) $request->query('per_page', 10);

        if (! in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 10;
        }

        $ordenesQuery = OrdenCompra::query()
            ->with(['solicitudCompra.solicitante'])
            ->orderByDesc('id');

        if ($recepcionEstado === 'pendiente') {
            $ordenesQuery->where(function ($query) {
                $query->whereNull('recepcion_estado')
                    ->orWhere('recepcion_estado', '');
            });
        } elseif ($recepcionEstado !== '') {
            $ordenesQuery->where('recepcion_estado', $recepcionEstado);
        }

        return view('modules.compras.ordenes.validation-index', [
            'titulo' => 'Validación de llegada O.C.',
            'ordenes' => $ordenesQuery->paginate($perPage)->withQueryString(),
            'recepcionEstado' => $recepcionEstado,
            'estadosRecepcion' => ['pendiente', 'completa', 'con_diferencias', 'diferencias_aprobadas'],
            'perPage' => $perPage,
        ]);
    }

    public function validationForm(Request $request, OrdenCompra $orden): View
    {
        $user = $request->user();
        abort_unless($user && $this->canManagePurchaseBoard($user), 403);

        $orden->load([
            'solicitudCompra.solicitante',
            'solicitudCompra.bodegaDestino',
            'receptor',
            'aprobadorDiferencias',
        ]);

        return view('modules.compras.ordenes.validation', [
            'titulo' => 'Validar llegada de orden de compra',
            'orden' => $orden,
        ]);
    }

    public function receiveOrder(Request $request, OrdenCompra $orden): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $this->canManagePurchaseBoard($user), 403);

        $orden->loadMissing('solicitudCompra');

        if (! empty($orden->recepcion_estado) && $orden->recepcion_estado !== 'con_diferencias') {
            return redirect()
                ->route('compras.ordenes.validation.form', $orden)
                ->with('error', 'La validacion de llegada ya fue registrada y ahora solo puede visualizarse.');
        }

        $validated = $request->validate([
            'recepcion_observaciones' => 'nullable|string|max:3000',
            'detalles' => 'required|array|min:1',
            'detalles.*.cantidad_recibida' => 'required|numeric|min:0',
        ]);

        $detalleBase = collect($orden->detalle_items_resolved)->values();

        $detalleRecibido = $detalleBase->map(function (array $item, int $index) use ($validated) {
            $cantidadSolicitada = (float) ($item['cantidad'] ?? 0);
            $cantidadRecibida = (float) ($validated['detalles'][$index]['cantidad_recibida'] ?? 0);
            $faltante = max($cantidadSolicitada - $cantidadRecibida, 0);
            $excedente = max($cantidadRecibida - $cantidadSolicitada, 0);

            return [
                'descripcion' => $item['descripcion'],
                'categoria' => $item['categoria'],
                'unidad' => $item['unidad'],
                'cantidad' => $cantidadSolicitada,
                'precio_unitario' => $item['precio_unitario'],
                'subtotal' => $item['subtotal'],
                'cantidad_recibida' => $cantidadRecibida,
                'cantidad_faltante' => $faltante,
                'cantidad_excedente' => $excedente,
                'estado_recepcion' => $faltante > 0 ? 'faltante' : ($excedente > 0 ? 'excedente' : 'completa'),
            ];
        })->all();

        $hayDiferencias = collect($detalleRecibido)->contains(fn (array $item) => ($item['cantidad_faltante'] ?? 0) > 0 || ($item['cantidad_excedente'] ?? 0) > 0);

        $orden->update([
            'estado' => 'RECIBIDA',
            'recibido_por' => $user->id,
            'recibido_en' => now(),
            'recepcion_estado' => $hayDiferencias ? 'con_diferencias' : 'completa',
            'recepcion_observaciones' => $validated['recepcion_observaciones'] ?? null,
            'detalle_items' => $detalleRecibido,
            'diferencias_aprobadas_por' => $hayDiferencias ? $orden->diferencias_aprobadas_por : null,
            'diferencias_aprobadas_en' => $hayDiferencias ? $orden->diferencias_aprobadas_en : null,
            'diferencias_observaciones' => $hayDiferencias ? $orden->diferencias_observaciones : null,
        ]);

        if ($orden->solicitudCompra) {
            $orden->solicitudCompra->update([
                'estado' => SolicitudCompra::ESTADO_RECIBIDA,
                'recibido_por' => $user->id,
                'recibido_en' => now(),
                'observaciones_compra' => $hayDiferencias
                    ? 'Orden recibida con diferencias pendientes de completar o aprobar.'
                    : 'Orden recibida completa.',
            ]);
        }

        return back()->with('success', $hayDiferencias
            ? 'Recepción registrada con diferencias. Puedes completar la O.C. después o aprobar la diferencia.'
            : 'Recepción registrada y orden completada sin diferencias.');
    }

    public function approveOrderDifferences(Request $request, OrdenCompra $orden): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->isSuperUser(), 403);

        $orden->loadMissing('solicitudCompra');

        if ($orden->recepcion_estado !== 'con_diferencias') {
            return back()->with('error', 'La orden no tiene diferencias pendientes de aprobación.');
        }

        $validated = $request->validate([
            'diferencias_observaciones' => 'nullable|string|max:3000',
        ]);

        $orden->update([
            'recepcion_estado' => 'diferencias_aprobadas',
            'diferencias_aprobadas_por' => $user->id,
            'diferencias_aprobadas_en' => now(),
            'diferencias_observaciones' => $validated['diferencias_observaciones'] ?? null,
        ]);

        if ($orden->solicitudCompra) {
            $orden->solicitudCompra->update([
                'estado' => SolicitudCompra::ESTADO_RECIBIDA,
                'observaciones_compra' => 'Diferencias de la orden aprobadas por gerencia.',
            ]);
        }

        return back()->with('success', 'Las diferencias de la orden fueron aprobadas.');
    }

    public function orderReport(Request $request): View
    {
        $user = $request->user();
        abort_unless($user && $this->canManagePurchaseBoard($user), 403);

        $recepcionEstado = trim((string) $request->query('recepcion_estado', ''));
        $perPage = (int) $request->query('per_page', 10);

        if (! in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 10;
        }

        $ordenesQuery = OrdenCompra::query()
            ->with(['solicitudCompra.solicitante', 'receptor', 'aprobadorDiferencias'])
            ->orderByDesc('id');

        if ($recepcionEstado === 'pendiente') {
            $ordenesQuery->where(function ($query) {
                $query->whereNull('recepcion_estado')
                    ->orWhere('recepcion_estado', '');
            });
        } elseif ($recepcionEstado !== '') {
            $ordenesQuery->where('recepcion_estado', $recepcionEstado);
        }

        $ordenes = $ordenesQuery->paginate($perPage)->withQueryString();

        return view('modules.compras.ordenes.report', [
            'titulo' => 'Reporte de órdenes de compra',
            'ordenes' => $ordenes,
            'recepcionEstado' => $recepcionEstado,
            'estadosRecepcion' => ['pendiente', 'completa', 'con_diferencias', 'diferencias_aprobadas'],
            'perPage' => $perPage,
        ]);
    }

    public function reject(Request $request, SolicitudCompra $solicitud): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->isSuperUser(), 403);

        if ($solicitud->estado === SolicitudCompra::ESTADO_RECIBIDA) {
            return back()->with('error', 'La solicitud ya fue recibida y no puede rechazarse.');
        }

        $validated = $request->validate([
            'motivo_rechazo' => 'required|string|max:3000',
        ]);

        $solicitud->update([
            'estado' => SolicitudCompra::ESTADO_RECHAZADA,
            'motivo_rechazo' => $validated['motivo_rechazo'],
            'rechazado_en' => now(),
        ]);

        return back()->with('success', 'Solicitud rechazada correctamente.');
    }

    public function markInProgress(Request $request, SolicitudCompra $solicitud): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $this->canManagePurchaseBoard($user), 403);

        if (! in_array($solicitud->estado, [SolicitudCompra::ESTADO_APROBADA, SolicitudCompra::ESTADO_EN_PROCESO], true)) {
            return back()->with('error', 'Solo las solicitudes aprobadas pueden pasar a compra en proceso.');
        }

        $validated = $request->validate([
            'observaciones_compra' => 'nullable|string|max:3000',
        ]);

        $solicitud->update([
            'estado' => SolicitudCompra::ESTADO_EN_PROCESO,
            'gestionado_por' => $user->id,
            'gestionado_en' => now(),
            'observaciones_compra' => $validated['observaciones_compra'] ?? $solicitud->observaciones_compra,
        ]);

        return back()->with('success', 'La solicitud fue enviada al proceso de compra.');
    }

    private function generateCode(): string
    {
        return 'SC-' . now()->format('Ymd-His');
    }

    private function generateOrderCode(): string
    {
        return 'OC-' . now()->format('Ymd') . '-' . Str::upper(Str::random(4));
    }

    private function normalizeDetalleItems(array $detalles): array
    {
        return collect($detalles)
            ->map(function (array $detalle) {
                $descripcion = trim((string) ($detalle['descripcion'] ?? ''));
                $categoria = trim((string) ($detalle['categoria'] ?? ''));
                $unidad = trim((string) ($detalle['unidad'] ?? ''));

                return [
                    'insumo_id' => null,
                    'descripcion' => $descripcion,
                    'categoria' => $categoria,
                    'unidad' => $unidad,
                    'cantidad' => (float) ($detalle['cantidad'] ?? 0),
                    'precio_estimado' => null,
                ];
            })
            ->values()
            ->all();
    }

    private function catalogoInsumos($ids = null)
    {
        $columnas = ['id', 'codigo', 'nombre', 'unidad_medida'];

        if (Schema::hasColumn('insumos', 'categoria_nombre')) {
            $columnas[] = 'categoria_nombre';
        }

        if (Schema::hasColumn('insumos', 'categoria_id')) {
            $columnas[] = 'categoria_id';
        }

        $query = Insumo::query()
            ->activos()
            ->select($columnas)
            ->orderBy('nombre');

        if ($ids !== null) {
            $query->whereIn('id', $ids);
        }

        $insumos = $query->get();

        $categoriasPorId = collect();
        if (Schema::hasColumn('insumos', 'categoria_id')) {
            $categoriaIds = $insumos->pluck('categoria_id')->filter()->unique()->values();

            if ($categoriaIds->isNotEmpty()) {
                $categoriasPorId = Categorias::query()
                    ->whereIn('id', $categoriaIds)
                    ->pluck('nombre', 'id');
            }
        }

        return $insumos->map(function ($insumo) use ($categoriasPorId) {
            $categoriaNombre = '';

            if (Schema::hasColumn('insumos', 'categoria_nombre') && ! empty($insumo->categoria_nombre)) {
                $categoriaNombre = (string) $insumo->categoria_nombre;
            } elseif (Schema::hasColumn('insumos', 'categoria_id') && ! empty($insumo->categoria_id)) {
                $categoriaNombre = (string) ($categoriasPorId->get($insumo->categoria_id) ?? '');
            }

            $insumo->setAttribute('categoria_nombre_resuelta', $categoriaNombre);

            return $insumo;
        });
    }

    private function loadDocumentRelations(SolicitudCompra $solicitud): void
    {
        $solicitud->load([
            'solicitante',
            'aprobador',
            'gestorCompra',
            'receptor',
            'insumo',
            'bodegaDestino',
            'movimientoInventario',
            'facturaInventario',
        ]);
    }

    private function resolveEmpresa(SolicitudCompra $solicitud, User $user): ?Empresa
    {
        $empresaId = $solicitud->empresa_id
            ?? $solicitud->solicitante?->empresa_id
            ?? $user->empresa_id
            ?? $user->sucursal?->empresa_id;

        return $empresaId ? Empresa::find($empresaId) : null;
    }

    private function resolverLogoEmpresa(?Empresa $empresa): ?string
    {
        $rutas = [
            $empresa?->logo,
            $empresa ? ltrim(str_replace('storage/', '', (string) $empresa->logo), '/') : null,
            $empresa && ! empty($empresa->logo) ? 'logos/' . ltrim(basename((string) $empresa->logo), '/') : null,
            'NiceAdmin/assets/img/agrocontrol.png',
            'NiceAdmin/assets/img/logo.png',
        ];

        foreach (array_unique($rutas) as $ruta) {
            if (! $ruta) {
                continue;
            }

            if (Storage::disk('public')->exists($ruta)) {
                $absolutePath = Storage::disk('public')->path($ruta);
                $mimeType = mime_content_type($absolutePath) ?: 'image/png';
                $contenido = base64_encode(file_get_contents($absolutePath));

                return 'data:' . $mimeType . ';base64,' . $contenido;
            }

            $publicPath = public_path($ruta);

            if (file_exists($publicPath)) {
                $mimeType = mime_content_type($publicPath) ?: 'image/png';
                $contenido = base64_encode(file_get_contents($publicPath));

                return 'data:' . $mimeType . ';base64,' . $contenido;
            }
        }

        return null;
    }

    private function canManagePurchaseBoard(User $user): bool
    {
        return $user->isSuperUser() || $user->hasRole('compra');
    }

    private function canAccessPurchaseModule(User $user): bool
    {
        return $user->isSuperUser() || $user->hasRole('compra') || $user->hasAccess('compras');
    }

    private function assertCanView(User $user, SolicitudCompra $solicitud): void
    {
        if ($this->canManagePurchaseBoard($user) || (int) $solicitud->solicitante_id === (int) $user->id) {
            return;
        }

        abort(403);
    }
}