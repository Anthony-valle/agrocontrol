<?php

namespace App\Http\Controllers;

use App\Imports\CultivosImport;
use App\Models\Cultivo;
use App\Models\Lote;
use Illuminate\Http\Request;
use App\Http\Requests\CultivoRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CultivoController extends Controller
{
    use AuthorizesRequests;

    // Mostrar lista de cultivos
    public function index(Request $request)
    {
        $estado = $request->get('estado');
        $titulo = $estado && strtolower($estado) === 'cerrado' ? 'Cultivos Cerrados' : 'Cultivo';

        $cultivos = Cultivo::query()
            ->select($this->selectExistingColumns('cultivos', [
                'id',
                'lotes_id',
                'codigo',
                'nombre',
                'variedad',
                'ciclo',
                'fecha_siembra',
                'duracion_ciclo',
                'fecha_cosecha',
                'hectareas',
                'cosecha_estimada',
                'unidad_medida',
                'estado',
                'created_by',
            ]))
            ->with([
                'lote:id,nombre',
                'creador:id,usuario',
            ])
            ->when($estado, fn ($query) => $query->where('estado', $estado))
            ->get();

        return $this->noCacheResponse(
            response()->view('modules.cultivo.index', compact('titulo', 'cultivos'))
        );
    }

    public function getUnidad(int $id)
    {
        $cultivo = \App\Models\Cultivo::find($id);

        return response()->json([
            'unidad_medida' => $cultivo ? $cultivo->unidad_medida : ''
        ]);
    }
    
    // Mostrar formulario de creación
    public function create()
    {
        $titulo = 'Crear Cultivo';
        $lotes = $this->obtenerLotesActivos();
        return $this->noCacheResponse(
            response()->view('modules.cultivo.create', compact('titulo', 'lotes'))
        );
    }

    public function show(Cultivo $cultivo)
    {
        $this->authorize('view', $cultivo);

        $cultivo->load(['lote', 'creador', 'actualizador']);

        return $this->noCacheResponse(
            response()->view('modules.cultivo.show', compact('cultivo'))
        );
    }

    
    // Guardar nuevo cultivo
    public function store(CultivoRequest $request)
    {


        // Calcular fecha de cosecha automáticamente
        $fechaCosecha = date('Y-m-d', strtotime($request->fecha_siembra. ' + '.$request->duracion_ciclo.' days'));

        $lote = Lote::findOrFail($request->lotes_id);

        $cultivo = Cultivo::create($this->filterPersistedColumns('cultivos', [
            'empresa_id'          => $lote->empresa_id,
            'lotes_id'             => $request->lotes_id,
            'nombre'              => $request->nombre,
            'codigo'              => $request->codigo,
            'variedad'            => $request->variedad,
            'unidad_medida'       => $request->unidad_medida,
            'ciclo'               => $request->ciclo,
            'fecha_siembra'       => $request->fecha_siembra,
            'duracion_ciclo'      => $request->duracion_ciclo,
            'fecha_cosecha'       => $fechaCosecha,
            'hectareas'           => $request->hectareas,
            'cosecha_estimada'    => $request->cosecha_estimada,
            'observaciones'       => $request->observaciones,
            'estado'              => $request->estado ?? 'Activo',
            'created_by'          => Auth::id(),
        ]));

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'success' => 'Cultivo registrado correctamente',
                'cultivo_id' => $cultivo->id,
            ], 200);
        }

        return redirect()->route('cultivo.index')->with('success', 'Cultivo registrado correctamente');
    }

    // Mostrar formulario de edición
    public function edit(Cultivo $cultivo)
    {
        $this->authorize('update', $cultivo);
        $titulo = 'Editar Cultivo';
        $lotes = $this->obtenerLotesActivos();
        return $this->noCacheResponse(
            response()->view('modules.cultivo.edit', compact('titulo', 'lotes', 'cultivo'))
        );
    }

    // Actualizar cultivo
    public function update(CultivoRequest $request, Cultivo $cultivo)
    {
        $this->authorize('update', $cultivo);
        $fechaCosecha = date('Y-m-d', strtotime($request->fecha_siembra. ' + '.$request->duracion_ciclo.' days'));
        $cultivo->update($this->filterPersistedColumns('cultivos', [
            'lotes_id'             => $request->lotes_id,
            'nombre'              => $request->nombre,
            'codigo'              => $request->codigo,
            'variedad'            => $request->variedad,
            'ciclo'               => $request->ciclo,
            'unidad_medida'       => $request->unidad_medida,
            'fecha_siembra'       => $request->fecha_siembra,
            'duracion_ciclo'      => $request->duracion_ciclo,
            'fecha_cosecha'       => $fechaCosecha,
            'hectareas'           => $request->hectareas,
            'cosecha_estimada'    => $request->cosecha_estimada,
            'observaciones'       => $request->observaciones,
            'estado'              => $request->estado ?? $cultivo->estado,
            'updated_by'          => Auth::id(),
        ]));

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['success' => 'Cultivo actualizado correctamente'], 200);
        }

        return redirect()->route('cultivo.index')->with('success', 'Cultivo actualizado correctamente');
    }

    // Eliminar cultivo
    public function cerrar(Cultivo $cultivo)
    {
        $this->authorize('close', $cultivo);

        if (strtolower($cultivo->estado) === 'cerrado') {
            if (request()->ajax() || request()->expectsJson()) {
                return response()->json(['info' => 'El cultivo ya está cerrado.'], 200);
            }

            return redirect()->back()->with('info', 'El cultivo ya está cerrado.');
        }

        $cultivo->estado = 'Cerrado';
        $cultivo->updated_by = Auth::id();
        $cultivo->save();

        if (request()->ajax() || request()->expectsJson()) {
            return response()->json(['success' => 'Cultivo cerrado correctamente.'], 200);
        }

        return redirect()->back()->with('success', 'Cultivo cerrado correctamente.');
    }

    public function reactivar(Cultivo $cultivo)
    {
        $this->authorize('reactivate', $cultivo);

        if (strtolower($cultivo->estado) !== 'cerrado') {
            if (request()->ajax() || request()->expectsJson()) {
                return response()->json(['info' => 'El cultivo ya se encuentra activo.'], 200);
            }

            return redirect()->back()->with('info', 'El cultivo ya se encuentra activo.');
        }

        $cultivo->estado = 'Activo';
        $cultivo->updated_by = Auth::id();
        $cultivo->save();

        if (request()->ajax() || request()->expectsJson()) {
            return response()->json(['success' => 'Cultivo reactivado correctamente.'], 200);
        }

        return redirect()->back()->with('success', 'Cultivo reactivado correctamente.');
    }

    public function destroy(Cultivo $cultivo)
    {
        $this->authorize('delete', $cultivo);
        $cultivo->delete();

        if (request()->ajax() || request()->expectsJson()) {
            return response()->json(['success' => 'Cultivo eliminado correctamente'], 200);
        }

        return redirect()->back()->with('success', 'Cultivo eliminado correctamente');
    }

    public function importar(Request $request)
    {
        $this->authorizeMassImports();

        $request->validate([
            'archivo_excel' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $import = new CultivosImport(Auth::id());

        try {
            Excel::import($import, $request->file('archivo_excel'));

            $stats = $import->getStats();
            $errores = $import->getErrores();
            $summaryHtml = $this->buildImportSummaryHtml($import->getSummaryLines(), $errores);

            if (($stats['filas_importadas'] ?? 0) === 0) {
                return $this->responderImportacion($request, 422, 'No se importo ningun cultivo. Revisa el archivo e intenta de nuevo.', $summaryHtml);
            }

            return $this->responderImportacion($request, 200, 'Carga masiva de cultivos completada correctamente.', $summaryHtml, true);
        } catch (\Throwable $error) {
            return $this->responderImportacion($request, 422, $this->resolverMensajeErrorImportacion($request, $error));
        }
    }

    public function descargarPlantillaImportacion()
    {
        $this->authorizeMassImports();

        return Excel::download(new \App\Exports\CultivosHistoricosTemplateExport(), 'plantilla_cultivos_historicos.xlsx');
    }

    private function filterPersistedColumns(string $table, array $payload): array
    {
        $availableColumns = array_flip(Schema::getColumnListing($table));

        return array_intersect_key($payload, $availableColumns);
    }

    private function obtenerLotesActivos()
    {
        $query = Lote::query()->select($this->selectExistingColumns('lotes', ['id', 'nombre', 'area', 'estado']));

        if (Schema::hasColumn('lotes', 'estado')) {
            $query->where('estado', 1);
        }

        return $query->orderBy('nombre')->get();
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

    private function responderImportacion(Request $request, int $status, string $message, ?string $summaryHtml = null, bool $success = false)
    {
        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(array_filter([
                $success ? 'success' : 'message' => $message,
                'summary_html' => $summaryHtml,
                'redirect' => $success ? route('cultivo.index') : null,
            ], fn ($value) => $value !== null), $status);
        }

        return redirect()
            ->route('cultivo.index')
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

        if (! empty($errores)) {
            $html .= '<div class="mt-3"><b>Errores detectados:</b><ul class="text-start ps-3 mb-0">';
            foreach ($errores as $error) {
                $html .= '<li>' . e($error) . '</li>';
            }
            $html .= '</ul></div>';
        }

        return $html;
    }

    private function selectExistingColumns(string $table, array $columns): array
    {
        $availableColumns = array_flip(Schema::getColumnListing($table));
        $selectedColumns = array_values(array_intersect($columns, array_keys($availableColumns)));

        return ! empty($selectedColumns) ? $selectedColumns : ['id'];
    }

    private function noCacheResponse(Response $response): Response
    {
        return $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');
    }

    private function authorizeMassImports(): void
    {
        $user = Auth::user();

        abort_unless($user instanceof \App\Models\User && $user->canManageMassImports(), 403);
    }
}