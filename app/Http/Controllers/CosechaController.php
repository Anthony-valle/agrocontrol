<?php

namespace App\Http\Controllers;

use App\Models\CosechaFactura;
use App\Models\Cosecha;
use App\Models\Cultivo;
use App\Models\Empresa;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class CosechaController extends Controller
{
    private ?array $cosechasColumns = null;

    public function index()
    {
        $titulo = 'Cosechas';
        $cosechas = $this->baseCosechasQuery()
            ->latest()
            ->get();

        return view('modules.cosechas.index', compact('titulo', 'cosechas'));
    }

    public function facturadasIndex()
    {
        $titulo = 'Ventas Facturadas';
        $cosechas = $this->baseCosechasQuery()
            ->latest()
            ->get();

        $metricas = [
            'total_facturas' => $this->cosechaFacturasDisponible() ? CosechaFactura::count() : 0,
            'total_facturado' => $this->cosechaFacturasDisponible() ? (float) CosechaFactura::sum('total') : 0,
            'total_vendido' => $this->cosechaFacturasDisponible() ? (float) CosechaFactura::sum('cantidad_vendida') : 0,
            'cosechas_disponibles' => (int) $cosechas->filter(function ($cosecha) {
                return (float) $cosecha->cantidad_disponible > 0;
            })->count(),
        ];

        return view('modules.cosechas.facturadas_index', compact('titulo', 'cosechas', 'metricas'));
    }

    public function create()
    {
        $cultivos = $this->obtenerCultivosActivos();
        return view('modules.cosechas.create', compact('cultivos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'cultivo_id' => 'required|exists:cultivos,id',
            'fecha_cosecha' => 'required|date',
            'cantidad_bruta' => 'required|numeric|min:0',
            'descarte' => 'nullable|numeric|min:0',
            'precio_venta_unitario' => 'nullable|numeric|min:0',
            'unidad_medida' => 'required|string',
        ]);

        $descarte = $request->descarte ?? 0;
        $cantidadNeta = $request->cantidad_bruta - $descarte;

        if ($cantidadNeta < 0) {
            return back()->with('error', 'El descarte no puede ser mayor que la cantidad bruta');
        }

        $cultivo = Cultivo::findOrFail($request->cultivo_id);

        if (strtolower($cultivo->estado) === 'cerrado') {
            return back()->with('error', 'No se puede registrar cosecha en un cultivo cerrado.');
        }
        $cosecha = Cosecha::create($this->buildCosechaPayload([
            'empresa_id' => $cultivo->empresa_id,
            'cultivo_id' => $request->cultivo_id,
            'fecha_cosecha' => $request->fecha_cosecha,
            'cantidad_bruta' => $request->cantidad_bruta,
            'descarte' => $descarte,
            'cantidad_neta' => $cantidadNeta,
            'cantidad_disponible' => $cantidadNeta,
            'precio_venta_unitario' => $request->precio_venta_unitario,
            'unidad_medida' => $request->unidad_medida,
            'observaciones' => $request->observaciones,
            'created_by' => Auth::id(),
        ]));

        // Notificación para la campana
        \App\Models\Notificaciones::registrarParaSupervision([
            'empresa_id' => $cultivo->empresa_id,
            'cultivo_id' => $cultivo->id,
            'user_id'    => Auth::id(),
            'mensaje'    => "Se registró una cosecha para el cultivo {$cultivo->nombre}",
            'tipo'       => 'cosecha',
            'leido'      => false
        ]);

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['success' => 'Cosecha registrada correctamente'], 200);
        }

        return redirect()->route('cosecha.index')->with('success', 'Cosecha registrada correctamente');
    }

    public function edit(Cosecha $cosecha)
    {
        $cultivos = $this->obtenerCultivosActivos();
        return view('modules.cosechas.edit', compact('cultivos', 'cosecha'));
    }

    public function update(Request $request, Cosecha $cosecha)
    {
        $request->validate([
            'cultivo_id' => 'required|exists:cultivos,id',
            'fecha_cosecha' => 'required|date',
            'cantidad_bruta' => 'required|numeric|min:0',
            'descarte' => 'nullable|numeric|min:0',
            'precio_venta_unitario' => 'nullable|numeric|min:0',
            'unidad_medida' => 'required|string',
        ]);

        $cultivo = Cultivo::findOrFail($request->cultivo_id);
        $descarte = $request->descarte ?? 0;
        $cantidadNeta = $request->cantidad_bruta - $descarte;

        if ($cantidadNeta < 0) {
            return back()->with('error', 'El descarte no puede ser mayor que la cantidad bruta');
        }

        $cosecha->update($this->buildCosechaPayload([
            'empresa_id' => $cultivo->empresa_id,
            'cultivo_id' => $request->cultivo_id,
            'fecha_cosecha' => $request->fecha_cosecha,
            'cantidad_bruta' => $request->cantidad_bruta,
            'descarte' => $descarte,
            'cantidad_neta' => $cantidadNeta,
            'cantidad_disponible' => $cantidadNeta,
            'precio_venta_unitario' => $request->precio_venta_unitario,
            'unidad_medida' => $cultivo->unidad_medida,
            'observaciones' => $request->observaciones,
            'updated_by' => Auth::id(),
        ]));

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['success' => 'Cosecha actualizada correctamente'], 200);
        }

        return redirect()->route('cosecha.index')->with('success', 'Cosecha actualizada correctamente');
    }

    public function destroy(Cosecha $cosecha)
    {
        $cosecha->delete();

        if (request()->ajax() || request()->expectsJson()) {
            return response()->json(['success' => 'Cosecha eliminada correctamente'], 200);
        }

        return back()->with('success', 'Cosecha eliminada');
    }

    public function facturas(Cosecha $cosecha)
    {
        if ($this->cosechaFacturasDisponible()) {
            $cosecha->load([
                'cultivo:id,nombre',
                'facturas' => function ($query) {
                    $query->select([
                        'id',
                        'cosecha_id',
                        'numero_factura',
                        'cliente',
                        'fecha_factura',
                        'cantidad_vendida',
                        'precio_unitario',
                        'total',
                        'archivo',
                        'observaciones',
                        'created_by',
                        'created_at',
                    ])->orderByDesc('fecha_factura')->orderByDesc('id');
                },
                'facturas.creador:id,usuario',
            ]);
        } else {
            $cosecha->load(['cultivo']);
            $cosecha->setRelation('facturas', collect());
        }

        $empresa = Empresa::find($cosecha->empresa_id);
        $logoEmpresa = $this->resolverLogoEmpresa($empresa);

        if (! (request()->ajax() || request()->expectsJson())) {
            return view('modules.cosechas.facturas_page', [
                'cosecha' => $cosecha,
                'empresa' => $empresa,
                'logoEmpresa' => $logoEmpresa,
                'titulo' => 'Factura de cosecha',
                'renderInModal' => false,
            ]);
        }

        return view('modules.cosechas.facturas', [
            'cosecha' => $cosecha,
            'empresa' => $empresa,
            'logoEmpresa' => $logoEmpresa,
            'renderInModal' => true,
        ]);
    }

    public function descarte(Cosecha $cosecha)
    {
        $cosecha->load(['cultivo:id,nombre']);

        $empresa = Empresa::find($cosecha->empresa_id);
        $logoEmpresa = $this->resolverLogoEmpresa($empresa);
        $cantidadVendida = $this->cosechaFacturasDisponible()
            ? (float) $cosecha->facturas()->sum('cantidad_vendida')
            : 0;

        return view('modules.cosechas.descarte_page', [
            'cosecha' => $cosecha,
            'empresa' => $empresa,
            'logoEmpresa' => $logoEmpresa,
            'cantidadVendida' => $cantidadVendida,
            'titulo' => 'Baja por descarte',
        ]);
    }

    public function storeFactura(Request $request, Cosecha $cosecha)
    {
        if (! $this->cosechaFacturasDisponible()) {
            $message = 'La tabla de facturas de cosecha no existe en la base de datos actual.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => $message], 422);
            }

            return redirect()->route('cosecha.facturadas.index')->with('error', $message);
        }

        $request->validate([
            'numero_factura' => 'required|string|max:100',
            'cliente' => 'nullable|string|max:150',
            'fecha_factura' => 'required|date',
            'cantidad_vendida' => 'required|numeric|min:0.01',
            'precio_unitario' => 'required|numeric|min:0',
            'archivo' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'observaciones' => 'nullable|string',
        ]);

        if ($request->cantidad_vendida > $cosecha->cantidad_disponible) {
            $message = 'La cantidad vendida no puede ser mayor que la cantidad disponible de la cosecha.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => $message], 422);
            }

            return back()->withInput()->withErrors(['cantidad_vendida' => $message]);
        }

        try {
            DB::transaction(function () use ($request, $cosecha) {
                $rutaArchivo = null;

                if ($request->hasFile('archivo')) {
                    $rutaArchivo = $request->file('archivo')->store('facturas_cosechas', 'public');
                }

                $cantidadVendida = (float) $request->cantidad_vendida;
                $precioUnitario = (float) $request->precio_unitario;

                CosechaFactura::create([
                    'empresa_id' => $cosecha->empresa_id,
                    'cosecha_id' => $cosecha->id,
                    'numero_factura' => $request->numero_factura,
                    'cliente' => $request->cliente,
                    'fecha_factura' => $request->fecha_factura,
                    'cantidad_vendida' => $cantidadVendida,
                    'precio_unitario' => $precioUnitario,
                    'total' => $cantidadVendida * $precioUnitario,
                    'archivo' => $rutaArchivo,
                    'observaciones' => $request->observaciones,
                    'created_by' => Auth::id(),
                ]);

                $cosecha->decrement('cantidad_disponible', $cantidadVendida);
            });

            $message = 'Factura de venta registrada correctamente.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => $message], 200);
            }

            return redirect()->route('cosecha.facturadas.index')->with('success', $message);
        } catch (\Throwable $error) {
            $message = $error->getMessage() ?: 'No se pudo registrar la factura.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => $message], 422);
            }

            return back()->withInput()->with('error', $message);
        }
    }

    public function editFactura(int $factura)
    {
        if (! $this->cosechaFacturasDisponible()) {
            abort(404);
        }

        $factura = CosechaFactura::with(['cosecha.cultivo:id,nombre'])->findOrFail($factura);
        $cosecha = $factura->cosecha;
        $empresa = Empresa::find($factura->empresa_id);
        $logoEmpresa = $this->resolverLogoEmpresa($empresa);
        $cantidadDisponibleEditable = (float) $cosecha->cantidad_disponible + (float) $factura->cantidad_vendida;

        return view('modules.cosechas.factura_edit_page', [
            'factura' => $factura,
            'cosecha' => $cosecha,
            'empresa' => $empresa,
            'logoEmpresa' => $logoEmpresa,
            'cantidadDisponibleEditable' => $cantidadDisponibleEditable,
            'titulo' => 'Editar factura de cosecha',
        ]);
    }

    public function updateFactura(Request $request, int $factura)
    {
        if (! $this->cosechaFacturasDisponible()) {
            return redirect()->route('cosecha.facturadas.index')->with('error', 'La tabla de facturas de cosecha no existe en la base de datos actual.');
        }

        $factura = CosechaFactura::with('cosecha')->findOrFail($factura);
        $cosecha = $factura->cosecha;

        $request->validate([
            'numero_factura' => 'required|string|max:100',
            'cliente' => 'nullable|string|max:150',
            'fecha_factura' => 'required|date',
            'cantidad_vendida' => 'required|numeric|min:0.01',
            'precio_unitario' => 'required|numeric|min:0',
            'archivo' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'observaciones' => 'nullable|string',
        ]);

        $cantidadVendidaNueva = (float) $request->cantidad_vendida;
        $cantidadVendidaActual = (float) $factura->cantidad_vendida;
        $cantidadDisponibleEditable = (float) $cosecha->cantidad_disponible + $cantidadVendidaActual;

        if ($cantidadVendidaNueva > $cantidadDisponibleEditable) {
            return back()->withInput()->withErrors([
                'cantidad_vendida' => 'La cantidad vendida no puede ser mayor que la cantidad disponible editable de la cosecha.'
            ]);
        }

        try {
            DB::transaction(function () use ($request, $factura, $cosecha, $cantidadVendidaNueva, $cantidadVendidaActual) {
                $rutaArchivo = $factura->archivo;

                if ($request->hasFile('archivo')) {
                    $rutaArchivo = $request->file('archivo')->store('facturas_cosechas', 'public');
                }

                $precioUnitario = (float) $request->precio_unitario;
                $nuevaDisponible = ((float) $cosecha->cantidad_disponible + $cantidadVendidaActual) - $cantidadVendidaNueva;

                $factura->update([
                    'numero_factura' => $request->numero_factura,
                    'cliente' => $request->cliente,
                    'fecha_factura' => $request->fecha_factura,
                    'cantidad_vendida' => $cantidadVendidaNueva,
                    'precio_unitario' => $precioUnitario,
                    'total' => $cantidadVendidaNueva * $precioUnitario,
                    'archivo' => $rutaArchivo,
                    'observaciones' => $request->observaciones,
                    'updated_by' => Auth::id(),
                ]);

                $cosecha->update([
                    'cantidad_disponible' => max($nuevaDisponible, 0),
                    'updated_by' => Auth::id(),
                ]);
            });

            return redirect()->route('cosecha.facturas', $cosecha)->with('success', 'Factura actualizada correctamente.');
        } catch (\Throwable $error) {
            return back()->withInput()->with('error', $error->getMessage() ?: 'No se pudo actualizar la factura.');
        }
    }

    public function registrarDescarte(Request $request, Cosecha $cosecha)
    {
        $request->validate([
            'cantidad_descarte' => 'required|numeric|min:0.01',
            'motivo_descarte' => 'required|string|max:255',
        ]);

        $cantidadDescarte = (float) $request->cantidad_descarte;
        $cantidadDisponible = (float) $cosecha->cantidad_disponible;

        if ($cantidadDescarte > $cantidadDisponible) {
            return back()
                ->withInput()
                ->withErrors(['cantidad_descarte' => 'La baja por descarte no puede ser mayor que la cantidad disponible de la cosecha.']);
        }

        try {
            DB::transaction(function () use ($cosecha, $cantidadDescarte, $request) {
                $columns = $this->getCosechasColumns();
                $descarteActual = (float) ($cosecha->descarte ?? 0);
                $cantidadDescarteActual = (float) ($cosecha->cantidad_descarte ?? 0);
                $cantidadNetaActual = (float) ($cosecha->cantidad_neta ?? 0);
                $motivo = trim((string) $request->motivo_descarte);

                $payload = [
                    'cantidad_disponible' => max($cantidadNetaActual - $cantidadDescarte - ((float) $cosecha->facturas()->sum('cantidad_vendida')), 0),
                    'cantidad_neta' => max($cantidadNetaActual - $cantidadDescarte, 0),
                    'descarte' => $descarteActual + $cantidadDescarte,
                    'cantidad_descarte' => $cantidadDescarteActual + $cantidadDescarte,
                    'motivo_descarte' => $motivo,
                    'updated_by' => Auth::id(),
                ];

                $cosecha->update(array_intersect_key($payload, array_flip($columns)));
            });

            return redirect()
                ->route('cosecha.descarte', $cosecha)
                ->with('success', 'Baja por descarte registrada correctamente.');
        } catch (\Throwable $error) {
            return back()
                ->withInput()
                ->with('error', $error->getMessage() ?: 'No se pudo registrar la baja por descarte.');
        }
    }

    public function destroyFactura(int $factura)
    {
        if (! $this->cosechaFacturasDisponible()) {
            return response()->json(['message' => 'La tabla de facturas de cosecha no existe en la base de datos actual.'], 422);
        }

        $factura = CosechaFactura::find($factura);

        if (! $factura) {
            return response()->json(['message' => 'La factura solicitada no existe.'], 404);
        }

        try {
            DB::transaction(function () use ($factura) {
                $cosecha = $factura->cosecha;

                if ($factura->archivo) {
                    // Se conserva el archivo para permitir restaurar la factura desde la papelera.
                }

                $cosecha->increment('cantidad_disponible', $factura->cantidad_vendida);
                $factura->delete();
            });

            return response()->json(['success' => 'Factura eliminada y saldo restaurado correctamente.'], 200);
        } catch (\Throwable $error) {
            return response()->json(['message' => $error->getMessage() ?: 'No se pudo eliminar la factura.'], 422);
        }
    }

    public function exportFactura(int $factura)
    {
        if (! $this->cosechaFacturasDisponible()) {
            abort(404);
        }

        $factura = CosechaFactura::findOrFail($factura);

        $factura->load(['cosecha.cultivo', 'creador']);
        $empresa = Empresa::find($factura->empresa_id);
        $logoEmpresa = $this->resolverLogoEmpresa($empresa);

        $pdf = Pdf::loadView('modules.cosechas.factura_pdf', compact('factura', 'empresa', 'logoEmpresa'));

        return $pdf->download('factura_cosecha_' . $factura->numero_factura . '.pdf');
    }

    private function resolverLogoEmpresa(?Empresa $empresa): ?string
    {
        $rutas = [
            $empresa?->logo,
            $empresa ? ltrim(str_replace('storage/', '', (string) $empresa->logo), '/') : null,
            $empresa && !empty($empresa->logo) ? 'logos/' . ltrim(basename((string) $empresa->logo), '/') : null,
            'NiceAdmin/assets/img/agrocontrol.png',
            'NiceAdmin/assets/img/logo.png',
        ];

        foreach (array_unique($rutas) as $ruta) {
            if (!$ruta) {
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

    private function baseCosechasQuery()
    {
        $query = Cosecha::with('cultivo', 'usuario');

        if ($this->cosechaFacturasDisponible()) {
            $query->withSum('facturas', 'cantidad_vendida')
                ->withSum('facturas', 'total');
        }

        return $query;
    }

    private function obtenerCultivosActivos()
    {
        $query = Cultivo::query();

        if (Schema::hasColumn('cultivos', 'estado')) {
            $query->where('estado', 'Activo');
        }

        return $query->orderBy('nombre')->get();
    }

    private function buildCosechaPayload(array $data): array
    {
        $columns = $this->getCosechasColumns();
        $payload = [];

        foreach ($data as $key => $value) {
            if ($key === 'descarte' && ! in_array('descarte', $columns, true) && in_array('cantidad_descarte', $columns, true)) {
                $payload['cantidad_descarte'] = $value;
                continue;
            }

            if (in_array($key, $columns, true)) {
                $payload[$key] = $value;
            }
        }

        return $payload;
    }

    private function getCosechasColumns(): array
    {
        if ($this->cosechasColumns === null) {
            $this->cosechasColumns = Schema::hasTable('cosechas')
                ? Schema::getColumnListing('cosechas')
                : [];
        }

        return $this->cosechasColumns;
    }

    private function cosechaFacturasDisponible(): bool
    {
        return Schema::hasTable('cosecha_facturas');
    }
}
