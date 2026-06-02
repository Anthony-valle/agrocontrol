<?php

namespace App\Http\Controllers;

use App\Exports\PlanesCultivoTemplateExport;
use App\Exports\PlanCultivoExport;
use App\Imports\PlanesCultivoImport;
use App\Models\Categorias;
use App\Models\Cultivo;
use App\Models\Insumo;
use App\Models\planes_cultivo;
use App\Models\planes_detalles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class PlanesCultivoController extends Controller
{

    // CARGAR DESCRIPCIONES AJAX
    public function descripciones(mixed $categoria)
    {
        $resultado = collect();

        // Mano de obra
        if ($categoria === 'Mano de Obra') {
            // Agrupar por nombre y recolectar todas las actividades secundarias asociadas a cada nombre
            $laboresQuery = \App\Models\Labore::query();
            if (Schema::hasColumn('labores', 'estado')) {
                $laboresQuery->where('estado', 1);
            }

            $labores = $laboresQuery->get();
            $agrupadas = $labores->groupBy('nombre');
            $resultado = $agrupadas->map(function($items, $nombre) {
                $unidad = $items->first()->unidad_medida;
                $precio = $items->first()->costo_unitario;
                $actividades = [];
                foreach ($items as $l) {
                    if (!empty($l->actividad_secundaria)) {
                        if (strpos($l->actividad_secundaria, ',') !== false) {
                            $acts = array_map('trim', explode(',', $l->actividad_secundaria));
                            $actividades = array_merge($actividades, $acts);
                        } else {
                            $actividades[] = $l->actividad_secundaria;
                        }
                    }
                }
                // Eliminar duplicados y vacíos
                $actividades = array_values(array_filter(array_unique($actividades), function($a){ return $a && trim($a) !== ''; }));
                return [
                    'nombre' => $nombre,
                    'unidad_medida' => $unidad,
                    'precio_unitario' => $precio,
                    'actividades_secundarias' => $actividades
                ];
            })->values();
        } else {
            // Insumos según la categoría
            $insumos = $this->obtenerInsumosPorCategoria($categoria);

            $resultado = $insumos->map(function($i) {
                // Obtenemos el costo promedio del inventario (primer registro que exista)
                $inventario = \App\Models\InventarioBodega::where('insumo_id', $i->id)->first();
                $costoPromedio = $inventario?->costo_promedio ?? $i->costo_estimado ?? 0;

                return [
                    'nombre' => $i->nombre,
                    'unidad_medida' => $i->unidad_medida,
                    'precio_unitario' => $costoPromedio
                ];
            });
        }

        return response()->json($resultado);
    }

    public function show(int $id)
    {
        $plan = $this->obtenerPlanReporte($id);
        $plan->setAttribute('cosecha_estimada', $this->resolverCosechaEstimada($plan->cultivo, $plan->cosecha_estimada));

        return view('modules.planes.show', compact('plan'));
    }

    public function exportExcel(int $id)
    {
        $plan = $this->obtenerPlanReporte($id);
        $fileName = 'plan_cultivo_' . $plan->id . '_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new PlanCultivoExport($plan), $fileName);
    }

    public function exportPdf(int $id)
    {
        $plan = $this->obtenerPlanReporte($id);

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('modules.planes.show_pdf', compact('plan'));

        return $pdf->download('plan_cultivo_' . $plan->id . '_' . now()->format('Ymd_His') . '.pdf');
    }
    
    // LISTA DE PLANES
    public function index()
    {
        $perPage = (int) request('per_page', 10);
        if (!in_array($perPage, [10, 25, 50], true)) {
            $perPage = 10;
        }

        $planes = planes_cultivo::query()
            ->with(['cultivo:id,nombre'])
            ->withMin('detalles', 'semana')
            ->withMax('detalles', 'semana')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('modules.planes.index', compact('planes'));

    }

    // FORMULARIO CREAR
    public function create()
    {
        $cultivos = $this->obtenerCultivosActivos();

        $categoriasInsumos = $this->obtenerCategoriasInsumos();

        return view('modules.planes.create',compact('cultivos', 'categoriasInsumos'));

    }

    // GUARDAR PLAN
    public function store(Request $request)
    {

        $request->validate([

            'cultivo_id' => 'required|exists:cultivos,id',
            'fecha_plan' => 'required|date',
            'cosecha_estimada' => 'nullable|numeric'

        ]);

        DB::beginTransaction();

        try{

            $cultivo = Cultivo::findOrFail($request->cultivo_id);
            $cosechaEstimada = $this->resolverCosechaEstimada($cultivo, $request->cosecha_estimada);

            // GUARDAR CABECERA
            $plan = planes_cultivo::create($this->filterPersistedColumns('planes_cultivos', [
                'empresa_id' => $cultivo->empresa_id,
                'cultivo_id' => $request->cultivo_id,
                'semana' => $this->resolverSemanaCabecera($request->semana ?? []),
                'fecha_plan' => $request->fecha_plan,
                'cosecha_estimada' => $cosechaEstimada,
                'estado' => 'PLANIFICADO',
                'created_by' => Auth::id()
            ]));


            $totalGeneral = 0;


            // GUARDAR DETALLES
           if($request->has('semana')) {
                foreach($request->semana as $i => $semana) {
                    $cant = $request->cantidad_estimada[$i];
                    $precio = $request->costo_unitario[$i];

                    $subtotal = $cant * $precio;

                    planes_detalles::create($this->filterPersistedColumns('planes_detalles', [
                        'plan_cultivo_id' => $plan->id,
                        'semana' => $semana,
                        'categoria' => $request->categoria[$i],
                        'descripcion' => $request->descripcion[$i],
                        'cantidad_estimada' => $cant,
                        'unidad_medida' => $request->unidad_medida[$i],
                        'costo_unitario' => $precio,
                        'subtotal' => $subtotal,
                        'created_by' => Auth::id(),
                    ]));

                    $totalGeneral += $cant * $precio;
                }
            }

            // ACTUALIZAR TOTAL
            $plan->update($this->filterPersistedColumns('planes_cultivos', [
                'semana' => $this->resolverSemanaCabecera($request->semana ?? []),
                'total_presupuesto' => $totalGeneral
            ]));


            DB::commit();

            return redirect()
            ->route('planes.index')
            ->with('success','Plan guardado correctamente');


        }catch(\Exception $e){

            DB::rollBack();

            return back()->with('error',$e->getMessage());

        }

    }

    // EDITAR PLAN
    public function edit(int $id)
    {

        $plan = planes_cultivo::with('detalles')->findOrFail($id);

        $cultivos = $this->obtenerCultivosActivos();

        $categoriasInsumos = $this->obtenerCategoriasInsumos();

        return view('modules.planes.edit',compact('plan','cultivos', 'categoriasInsumos'));

    }

    // ACTUALIZAR PLAN
    public function update(Request $request, int $id)
    {
        $plan = planes_cultivo::findOrFail($id);
        $cultivo = Cultivo::findOrFail($request->cultivo_id);
        $cosechaEstimada = $this->resolverCosechaEstimada($cultivo, $request->cosecha_estimada);

        $request->validate([
            'cultivo_id' => 'required|exists:cultivos,id',
            'fecha_plan' => 'required|date'
        ]);

        DB::beginTransaction();

        try {
            $plan->update($this->filterPersistedColumns('planes_cultivos', [
                'cultivo_id' => $request->cultivo_id,
                'semana' => $this->resolverSemanaCabecera($request->semana ?? []),
                'fecha_plan' => $request->fecha_plan,
                'cosecha_estimada' => $cosechaEstimada,
                'updated_by' => Auth::id()
            ]));

            // Borrar detalles antiguos
            $plan->detalles()->delete();

            $totalGeneral = 0;
            $semanas = $request->semana ?? [];

            foreach($semanas as $i => $semana) {
                $cant = $request->cantidad_estimada[$i] ?? 0;
                $precio = $request->costo_unitario[$i] ?? 0;
                $subtotal = $cant * $precio;

                planes_detalles::create($this->filterPersistedColumns('planes_detalles', [
                    'plan_cultivo_id' => $plan->id,
                    'semana' => $semana,
                    'categoria' => $request->categoria[$i] ?? null,
                    'descripcion' => $request->descripcion[$i] ?? null,
                    'cantidad_estimada' => $cant,
                    'unidad_medida' => $request->unidad_medida[$i] ?? null,
                    'costo_unitario' => $precio,
                    'subtotal' => $subtotal,
                    'updated_by' => Auth::id()
                ]));

                $totalGeneral += $subtotal;
            }

            // Actualizar total
            $plan->update($this->filterPersistedColumns('planes_cultivos', [
                'semana' => $this->resolverSemanaCabecera($request->semana ?? []),
                'total_presupuesto' => $totalGeneral
            ]));

            DB::commit();

            return redirect()->route('planes.index')->with('success','Plan actualizado correctamente');

        } catch(\Exception $e) {
            DB::rollBack();
            return back()->with('error',$e->getMessage());
        }
    }

    // ELIMINAR PLAN
    public function destroy(int $id)
    {

        $plan = planes_cultivo::findOrFail($id);

        $plan->detalles()->delete();

        $plan->delete();

        return redirect()
        ->route('planes.index')
        ->with('success','Plan eliminado');

    }

    public function importar(Request $request)
    {
        $this->authorizeMassImports();

        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
        @ini_set('memory_limit', '1024M');

        $request->validate([
            'archivo_excel' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $import = new PlanesCultivoImport(Auth::id());

        try {
            Excel::import($import, $request->file('archivo_excel'));

            $stats = $import->getStats();
            $errores = $import->getErrores();
            $summaryHtml = $this->buildImportSummaryHtml($import->getSummaryLines(), $errores);

            if (($stats['detalles_creados'] ?? 0) === 0) {
                return $this->responderImportacion($request, 422, 'No se importo ningun detalle. Revisa el archivo e intenta de nuevo.', $summaryHtml);
            }

            return $this->responderImportacion($request, 200, 'Carga masiva de planes completada correctamente.', $summaryHtml, true);
        } catch (\Throwable $error) {
            return $this->responderImportacion($request, 422, $this->resolverMensajeErrorImportacion($request, $error));
        }
    }

    public function descargarPlantillaImportacion()
    {
        $this->authorizeMassImports();

        return Excel::download(new PlanesCultivoTemplateExport(), 'plantilla_planes_cultivo.xlsx');
    }

    private function obtenerCategoriasInsumos()
    {
        if (Schema::hasColumn('insumos', 'categoria_id')) {
            $query = Categorias::query()
                ->join('insumos', 'insumos.categoria_id', '=', 'categorias.id')
                ->select('categorias.nombre');

            if (Schema::hasColumn('insumos', 'estado')) {
                $query->where('insumos.estado', 1);
            }

            if (Schema::hasColumn('categorias', 'estado')) {
                $query->where('categorias.estado', 1);
            }

            return $query->distinct()
                ->orderBy('categorias.nombre')
                ->pluck('categorias.nombre');
        }

        if (Schema::hasColumn('insumos', 'categoria_nombre')) {
            $query = Insumo::query()
                ->activos()
                ->whereNotNull('categoria_nombre');

            return $query
                ->distinct()
                ->orderBy('categoria_nombre')
                ->pluck('categoria_nombre');
        }

        return collect();
    }

    private function obtenerPlanReporte(int $id): planes_cultivo
    {
        return planes_cultivo::with('detalles', 'cultivo')->findOrFail($id);
    }

    private function resolverCosechaEstimada(?Cultivo $cultivo, mixed $fallback = null): float
    {
        if ($cultivo !== null && $cultivo->cosecha_estimada !== null && $cultivo->cosecha_estimada !== '') {
            return (float) $cultivo->cosecha_estimada;
        }

        return (float) ($fallback ?? 0);
    }

    private function resolverMensajeErrorImportacion(Request $request, \Throwable $error): string
    {
        $mensaje = $error->getMessage();
        $extension = strtolower((string) $request->file('archivo_excel')?->getClientOriginalExtension());

        if (Str::contains($mensaje, 'ZipArchive') && in_array($extension, ['xlsx', 'xls'], true)) {
            return 'El servidor no tiene habilitada la extension ZIP de PHP para leer archivos Excel. Mientras se habilita, importe la plantilla en formato CSV o reinicie PHP despues de activar extension=zip en php.ini.';
        }

        return 'Error al importar el archivo: ' . $mensaje;
    }

    private function obtenerInsumosPorCategoria(string $categoria)
    {
        if (Schema::hasColumn('insumos', 'categoria_id')) {
            $query = Insumo::query()
                ->activos()
                ->join('categorias', 'insumos.categoria_id', '=', 'categorias.id')
                ->where('categorias.nombre', $categoria)
                ->select('insumos.*');

            if (Schema::hasColumn('categorias', 'estado')) {
                $query->where('categorias.estado', 1);
            }

            return $query->orderBy('insumos.nombre')->get();
        }

        $query = Insumo::query()
            ->activos()
            ->where('categoria_nombre', $categoria);

        return $query->orderBy('nombre')->get();
    }

    private function obtenerCultivosActivos()
    {
        $query = Cultivo::query();

        if (Schema::hasColumn('cultivos', 'estado')) {
            $query->where('estado', 'Activo');
        }

        return $query->orderBy('nombre')->get();
    }

    private function responderImportacion(Request $request, int $status, string $message, ?string $summaryHtml = null, bool $success = false)
    {
        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(array_filter([
                $success ? 'success' : 'message' => $message,
                'summary_html' => $summaryHtml,
                'redirect' => $success ? route('planes.index') : null,
            ], fn ($value) => $value !== null), $status);
        }

        return redirect()
            ->route('planes.index')
            ->with($success ? 'success' : 'error', $message)
            ->with('import_summary_html', $summaryHtml);
    }

    private function buildImportSummaryHtml(array $summaryLines, array $errores = []): string
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

    private function filterPersistedColumns(string $table, array $payload): array
    {
        $availableColumns = array_flip(Schema::getColumnListing($table));

        return array_intersect_key($payload, $availableColumns);
    }

    private function resolverSemanaCabecera(array $semanas): int
    {
        $semanasNormalizadas = collect($semanas)
            ->map(fn ($semana) => (int) $semana)
            ->filter(fn ($semana) => $semana >= 1 && $semana <= 52)
            ->values();

        return (int) ($semanasNormalizadas->min() ?: 1);
    }

    private function authorizeMassImports(): void
    {
        $user = Auth::user();

        abort_unless($user instanceof \App\Models\User && $user->canManageMassImports(), 403);
    }

}