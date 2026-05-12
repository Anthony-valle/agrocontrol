<?php

namespace App\Http\Controllers;

use App\Models\Bodega;
use App\Models\Sucursale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class BodegaController extends Controller
{

    
    public function index()
    {
        $titulo = 'Configuración de Bodegas';
        $bodegas = Bodega::with('sucursal')->get();
        return view('modules.bodegas.index', compact('titulo','bodegas'));
    }

    public function create()
    {
        $sucursales = Sucursale::where('estado', 1)->get();
        return view('modules.bodegas.create', compact('sucursales'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'codigo' => 'required|unique:bodegas,codigo',
            'nombre' => 'required|string|max:50',
            'sucursal_id' => 'required|exists:sucursales,id',
        ]);

        $sucursal = Sucursale::findOrFail($request->sucursal_id);

        $bodegaCreada = Bodega::create($this->filterPersistedColumns('bodegas', [
            'empresa_id' => $sucursal->empresa_id,
            'codigo' => $request->codigo,
            'nombre' => $request->nombre,
            'responsable' => $request->responsable,
            'ubicacion' => $request->ubicacion,
            'estado' => 1,
            'sucursal_id' => $request->sucursal_id,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]));

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'success' => 'Bodega creada correctamente.',
                'bodega_id' => $bodegaCreada->id,
            ], 200);
        }

        return redirect()->route('bodegas.index')->with('success', 'Bodega creada correctamente.');
    }

    public function edit(Bodega $bodega)
    {
        $sucursales = Sucursale::where('estado', 1)->get();
        return view('modules.bodegas.edit', compact('bodega','sucursales'));
    }

    public function update(Request $request, Bodega $bodega)
    {
        $request->validate([
            'codigo' => 'required|unique:bodegas,codigo,' . $bodega->id,
            'nombre' => 'required|string|max:50',
            'sucursal_id' => 'required|exists:sucursales,id',
            'estado' => 'required|boolean',
        ]);

        $bodega->update($this->filterPersistedColumns('bodegas', [
            'codigo' => $request->codigo,
            'nombre' => $request->nombre,
            'responsable' => $request->responsable,
            'ubicacion' => $request->ubicacion,
            'sucursal_id' => $request->sucursal_id,
            'estado' => $request->estado,
            'updated_by' => Auth::id(),
        ]));

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['success' => 'Bodega actualizada correctamente.'], 200);
        }

        return redirect()->route('bodegas.index')->with('success', 'Bodega actualizada correctamente.');
    }

    public function destroy(Bodega $bodega)
    {
        $bodega->delete();

        if (request()->ajax() || request()->expectsJson()) {
            return response()->json(['success' => 'Bodega eliminada correctamente.'], 200);
        }

        return back()->with('success', 'Bodega eliminada correctamente.');
    }

    private function filterPersistedColumns(string $table, array $payload): array
    {
        $availableColumns = array_flip(Schema::getColumnListing($table));

        return array_intersect_key($payload, $availableColumns);
    }
}