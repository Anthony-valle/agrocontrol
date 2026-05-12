<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class RoleController extends Controller
{
    private ?array $rolesColumns = null;

    public function index()
    {
        $titulo = 'Roles';
        $roles = Role::all();
        return view('modules.rol.index', compact('titulo', 'roles'));
    }

    public function create()
    {
        return view('modules.rol.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|unique:roles',
            'descripcion' => 'nullable'
        ]);

        $rolCreado = Role::create($this->filtrarColumnasRoles([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion
        ]));

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'success' => 'Rol creado correctamente',
                'rol_id' => $rolCreado->id,
            ], 200);
        }

        return redirect()->route('rol.index')->with('success', 'Rol creado correctamente');
    }

    public function edit(Role $rol)
    {
        return view('modules.rol.edit', compact('rol'));
    }

    public function update(Request $request, Role $rol)
    {
        $request->validate([
            'nombre' => 'required|unique:roles,nombre,' . $rol->id,
            'descripcion' => 'nullable'
        ]);

        $rol->update($this->filtrarColumnasRoles([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion
        ]));

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['success' => 'Rol actualizado correctamente'], 200);
        }

        return redirect()->route('rol.index')->with('success', 'Rol actualizado correctamente');
    }

    public function destroy(Role $rol)
    {
        $rol->delete();
        return response()->json(['success' => 'Rol eliminado correctamente.'], 200);
    }

    private function filtrarColumnasRoles(array $payload): array
    {
        return array_filter(
            $payload,
            fn ($value, $column) => in_array($column, $this->obtenerColumnasRoles(), true),
            ARRAY_FILTER_USE_BOTH
        );
    }

    private function obtenerColumnasRoles(): array
    {
        if ($this->rolesColumns === null) {
            $this->rolesColumns = Schema::hasTable('roles')
                ? Schema::getColumnListing('roles')
                : [];
        }

        return $this->rolesColumns;
    }
}
