<?php

namespace App\Http\Controllers;

use App\Models\Lote;
use App\Models\Sucursale;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Requests\LoteRequest;
use Illuminate\Support\Facades\Schema;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class LoteController extends Controller
{
    use AuthorizesRequests;
    //listas
    public function index()
    {
        $titulo = 'Configuración de Lote';
        $lotes = Lote::with(['sucursal', 'creador'])->get();
        return view('modules.lotes.index', compact('titulo', 'lotes'));
    }

    //Formulario crear
    public function create()
    {
        $titulo = 'Configuración de Lote';
        $sucursales = $this->getSucursalesDisponibles();
        return view('modules.lotes.create', compact('titulo', 'sucursales'));
    }

    //Guardar la informacion 
    public function store(LoteRequest $request)
    {
        $sucursal = $this->resolveSucursal((int) $request->sucursal_id);

        $lote = Lote::create($this->filterPersistedColumns('lotes', [
            'empresa_id'  => $sucursal->empresa_id,
            'codigo'      => $request->codigo,
            'nombre'      => $request->nombre,
            'area'        => $request->area,
            'poligono'    => $request->poligono,
            'sucursal_id' => $request->sucursal_id,
            'estado'      => $request->estado,
            'created_by'  => Auth::id(),
        ]));

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'success' => 'Lote registrado correctamente',
                'lote_id' => $lote->id,
            ], 200);
        }

        return redirect()->route('lotes.index')->with('success', 'Lote registrada correctamente');
    }


    //Formulario edit
    public function edit(Lote $lote)
    {
        $this->authorize('update', $lote);
        $titulo = 'Editar Lote';
        $sucursales = $this->getSucursalesDisponibles();
        return view('modules.lotes.edit', compact('titulo', 'lote', 'sucursales'));
    }

    //actualizar 
    public function update(LoteRequest $request, Lote $lote)
    {
        $this->authorize('update', $lote);
        $sucursal = $this->resolveSucursal((int) $request->sucursal_id);

        $lote->update($this->filterPersistedColumns('lotes', [
            'empresa_id'  => $sucursal->empresa_id,
            'codigo'      => $request->codigo,
            'nombre'      => $request->nombre,
            'area'        => $request->area,
            'poligono'    => $request->poligono,
            'sucursal_id' => $request->sucursal_id,
            'estado'      => $request->estado,
            'updated_by'  => Auth::id(),
        ]));

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['success' => 'Lote actualizado correctamente'], 200);
        }

        return redirect()->route('lotes.index')->with('success', 'Lote actualizada correctamente');
    }

    //Eliminar
    public function destroy(Lote $lote)
    {
        $this->authorize('delete', $lote);
        $lote->delete();

        if (request()->ajax() || request()->expectsJson()) {
            return response()->json(['success' => 'Lote eliminado correctamente'], 200);
        }

        return redirect()->back()->with('success', 'Lote eliminada correctamente');
    }

    private function filterPersistedColumns(string $table, array $payload): array
    {
        $availableColumns = array_flip(Schema::getColumnListing($table));

        return array_intersect_key($payload, $availableColumns);
    }

    private function getSucursalesDisponibles()
    {
        $query = Sucursale::query()->orderBy('nombre');

        if (Schema::hasColumn('sucursales', 'estado')) {
            $query->where('estado', 1);
        }

        return $query->get();
    }

    private function resolveSucursal(int $sucursalId): Sucursale
    {
        $query = Sucursale::withoutGlobalScopes()->whereKey($sucursalId);

        if (Schema::hasColumn('sucursales', 'estado')) {
            $query->where('estado', 1);
        }

        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if ($user && ! $user->isSuperUser()) {
            $empresaId = $user->sucursal->empresa_id ?? $user->empresa_id ?? null;

            if ($empresaId && Schema::hasColumn('sucursales', 'empresa_id')) {
                $query->where('empresa_id', $empresaId);
            }

            if ($user->sucursal_id) {
                $query->where('id', $user->sucursal_id);
            }
        }

        return $query->firstOrFail();
    }
}
