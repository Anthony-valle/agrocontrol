<?php

namespace App\Http\Controllers;

use App\Models\Labore;
use App\Models\Sucursale;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class LaboreController extends Controller
{
    // LISTA
    public function index()
    {
        $titulo = 'Configuración de Mano de Obra';
        // Ordenamos por id descendente para ver lo último creado primero
        $labores = Labore::orderBy('id','desc')->get();
        return view('modules.labores.index', compact('titulo', 'labores'));
    }

    // FORMULARIO CREAR
    public function create()
    {
        $titulo = 'Crear Mano de Obra';
        // Retornamos solo la vista del formulario para el modal AJAX
        return view('modules.labores.create', compact('titulo'));
    }

    // GUARDAR
    public function store(Request $request)
    {
        $request->validate([
            'nombre'               => 'required|string|max:50',
            'actividad_secundaria' => 'required|string|max:30',
            'unidad_medida'        => 'required|string|max:50',
            'costo_unitario'       => 'required|numeric|min:0',
            'observaciones'        => 'nullable|string|max:100',
            'estado'               => 'nullable|in:0,1',
        ]);

        // Generar código automáticamente en el backend
        $iniciales = function($texto) {
            return collect(explode(' ', $texto))
                ->map(fn($palabra) => strtoupper(substr($palabra,0,1)))
                ->implode('');
        };

        $codigoBase = $iniciales($request->nombre) . '-' . $iniciales($request->actividad_secundaria);

        // Verificar si ya existe y agregar número
        $contador = 1;
        $codigoFinal = $codigoBase;

        while (Labore::where('codigo', $codigoFinal)->exists()) {
            $codigoFinal = $codigoBase . $contador;
            $contador++;
        }

        $empresaId = $this->resolveEmpresaId();

        if (!$empresaId) {
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json(['message' => 'No se pudo determinar la empresa del usuario autenticado.'], 422);
            }

            return back()->withInput()->with('error', 'No se pudo determinar la empresa del usuario autenticado.');
        }

        $laborCreada = Labore::create($this->filterPersistedColumns('labores', [
            'empresa_id'           => $empresaId,
            'codigo'               => $codigoFinal,
            'nombre'               => $request->nombre,
            'actividad_secundaria' => $request->actividad_secundaria,
            'unidad_medida'        => $request->unidad_medida,
            'costo_unitario'       => $request->costo_unitario,
            'observaciones'        => $request->observaciones,
            'estado'               => (int) ($request->estado ?? 1),
            'created_by'           => Auth::id(),
        ]));

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'success' => 'Labor registrada correctamente',
                'labor_id' => $laborCreada->id,
            ], 200);
        }

        return redirect()->route('labores.index')->with('success', 'Labor registrada correctamente');
    }

    // FORMULARIO EDITAR
    public function edit(Labore $labore)
    {
        $titulo = 'Editar Mano de Obra';
        // Retornamos la vista del formulario para el modal AJAX
        return view('modules.labores.edit', compact('titulo', 'labore'));
    }

    // ACTUALIZAR
    public function update(Request $request, Labore $labore)
    {
        // Validación ignorando el ID actual para el código único
        $request->validate([
            'codigo' => 'required|string|max:20|unique:labores,codigo,' . $labore->id,
            'nombre' => 'required|string|max:50',
            'actividad_secundaria' => 'required|string|max:30',
            'unidad_medida' => 'required|string|max:50',
            'costo_unitario' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string|max:100',
            'estado' => 'nullable|in:0,1',
        ]);

        $labore->update($this->filterPersistedColumns('labores', [
            'codigo'               => $request->codigo, // <--- Actualizar código si se modifica
            'nombre'               => $request->nombre,
            'actividad_secundaria' => $request->actividad_secundaria,
            'unidad_medida'        => $request->unidad_medida,
            'costo_unitario'       => $request->costo_unitario,
            'observaciones'        => $request->observaciones,
            'estado'               => (int) ($request->estado ?? $labore->estado ?? 1),
            'updated_by'           => Auth::id(), // Registrar usuario que actualiza
        ]));

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['success' => 'Labor actualizada correctamente'], 200);
        }

        return redirect()->route('labores.index')->with('success', 'Labor actualizada correctamente');
    }

    // ELIMINAR
    public function destroy(Labore $labore)
    {
        $labore->delete();

        if (request()->ajax() || request()->expectsJson()) {
            return response()->json(['success' => 'Labor eliminada correctamente'], 200);
        }

        return redirect()->back()->with('success', 'Labor eliminada correctamente');
    }

    private function resolveEmpresaId(): ?int
    {
        $user = Auth::user();

        if (!$user) {
            return null;
        }

        $empresaId = $user->sucursal?->empresa_id;

        if (!$empresaId && !empty($user->sucursal_id)) {
            $empresaId = Sucursale::query()
                ->withoutGlobalScopes()
                ->whereKey($user->sucursal_id)
                ->value('empresa_id');
        }

        if (!$empresaId && Schema::hasColumn('users', 'empresa_id')) {
            $empresaId = $user->empresa_id;
        }

        return $empresaId ? (int) $empresaId : null;
    }

    private function filterPersistedColumns(string $table, array $payload): array
    {
        $availableColumns = array_flip(Schema::getColumnListing($table));

        return array_intersect_key($payload, $availableColumns);
    }
}
