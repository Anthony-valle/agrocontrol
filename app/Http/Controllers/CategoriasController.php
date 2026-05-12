<?php

namespace App\Http\Controllers;

use App\Models\Categorias;
use App\Models\Sucursale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class CategoriasController extends Controller
{
    private array $columnasTablaCache = [];

    // Listar categorías
    public function index()
    {
        $titulo = 'Categorías de Insumos';
        $categoria = Categorias::with('sucursal', 'creador')
            ->orderBy('nombre')
            ->get()
            ->reject(fn (Categorias $item) => $this->categoriaEsRuido($item->nombre))
            ->values();
        return view('modules.categorias.index', compact('titulo', 'categoria'));
    }

    // Formulario crear
    public function create()
    {
        // Solo sucursales activas
        $sucursales = Sucursale::query()
            ->when(Schema::hasColumn('sucursales', 'estado'), fn ($query) => $query->where('estado', 1))
            ->orderBy('nombre')
            ->get();

        $soportaEstado = Schema::hasColumn('categorias', 'estado');

        return view('modules.categorias.create', compact('sucursales', 'soportaEstado'));
    }

    // Guardar categoría
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:50|unique:categorias,nombre',
            'sucursal_id' => 'required|exists:sucursales,id',
        ]);

        $sucursal = Sucursale::findOrFail($request->sucursal_id);

        $payload = $this->filtrarColumnasPersistidas('categorias', [
            'nombre' => $request->nombre,
            'estado' => 1,
            'sucursal_id' => $request->sucursal_id,
            'usuarios_id' => Auth::id(),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        if (Schema::hasColumn('categorias', 'empresa_id')) {
            $payload['empresa_id'] = $sucursal->empresa_id;
        }

        $categoriaCreada = Categorias::create($payload);

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'success' => 'Categoría creada correctamente',
                'categoria_id' => $categoriaCreada->id,
            ], 200);
        }

        return redirect()->route('categorias.index')->with('success', 'Categoría creada correctamente');
    }

    // Formulario de edición
    public function edit(Categorias $categoria)
    {
        // Solo sucursales activas
        $sucursales = Sucursale::query()
            ->when(Schema::hasColumn('sucursales', 'estado'), fn ($query) => $query->where('estado', 1))
            ->orderBy('nombre')
            ->get();

        $soportaEstado = Schema::hasColumn('categorias', 'estado');

        return view('modules.categorias.edit', compact('categoria', 'sucursales', 'soportaEstado'));
    }


    // Actualizar categoría
    public function update(Request $request, Categorias $categoria)
    {
        $rules = [
            'nombre' => 'required|string|max:50|unique:categorias,nombre,' . $categoria->id,
            'sucursal_id' => 'required|exists:sucursales,id',
        ];

        if (Schema::hasColumn('categorias', 'estado')) {
            $rules['estado'] = 'required|in:0,1';
        }

        $request->validate($rules);

        $categoria->update($this->filtrarColumnasPersistidas('categorias', [
            'nombre' => $request->nombre,
            'estado'=> $request->input('estado', 1),
            'sucursal_id' => $request->sucursal_id,
            'updated_by' =>Auth::id(),
        ]));

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['success' => 'Categoría actualizada correctamente'], 200);
        }

        return redirect()->route('categorias.index')->with('success', 'Categoría actualizada correctamente');
    }

    // Eliminar categoría
    public function destroy(Categorias $categoria)
    {
        $categoria->delete();
        return response()->json(['success' => 'Categoría eliminada correctamente'], 200);

    }

    private function filtrarColumnasPersistidas(string $tabla, array $payload): array
    {
        if (! isset($this->columnasTablaCache[$tabla])) {
            $this->columnasTablaCache[$tabla] = array_flip(Schema::getColumnListing($tabla));
        }

        return array_intersect_key($payload, $this->columnasTablaCache[$tabla]);
    }

    private function categoriaEsRuido(?string $nombre): bool
    {
        $normalizada = strtoupper(trim((string) $nombre));

        return in_array($normalizada, ['C/U', 'CU', 'G', 'KG', 'KGS', 'L', 'LT', 'LTS', 'ML', 'M', 'GR', 'UND', 'UNIDAD', 'UNIDADES'], true);
    }
}
