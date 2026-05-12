<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class EmpresaController extends Controller
{

    public function index()
    {
        $titulo = 'Empresa';
        $empresa = Empresa::all();
        return view('modules.empresas.index', compact('titulo', 'empresa'));
    }

    public function create()
    {
        $titulo = 'Configurar Empresa';
        return view('modules.empresas.create', compact('titulo'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:50',
            'rtn' => 'required|string|max:20',
            'direccion' => 'nullable|string|max:150',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:50',
            'pais' => 'nullable|string|max:50',
            'departamento' => 'nullable|string|max:50',
            'tipo_empresa' => 'nullable|string|max:50',
            'logo' => 'nullable|image|max:2048',
        ]);

        Empresa::create($this->filterPersistedColumns('empresas', [
            'nombre'       => $request->nombre,
            'rtn'          => $request->rtn,
            'nit'          => $request->rtn,
            'direccion'    => $request->direccion,
            'telefono'     => $request->telefono,
            'email'        => $request->email,
            'pais'         => $request->pais,
            'departamento' => $request->departamento,
            'tipo_empresa' => $request->tipo_empresa,
            'logo'         => $request->file('logo')?->store('logos', 'public'),
            'created_by'   => Auth::id(),
            'updated_by'   => Auth::id(),
        ]));

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['success' => 'Empresa registrada correctamente'], 200);
        }

        return redirect()->route('empresas.index')->with('success', 'Empresa registrada correctamente');
    }

    public function edit(Empresa $empresa)
    {
        $titulo = 'Editar Empresa';
        return view('modules.empresas.edit', compact('empresa', 'titulo'));
    }

    public function update(Request $request, Empresa $empresa)
    {
        $request->validate([
            'nombre' => 'required|string|max:50',
            'rtn' => 'required|string|max:20',
            'direccion' => 'nullable|string|max:150',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'pais' => 'nullable|string|max:50',
            'departamento' => 'nullable|string|max:50',
            'tipo_empresa' => 'nullable|string|max:50',
            'logo' => 'nullable|image|max:2048',
        ]);

        $data = [
            'nombre' => $request->nombre,
            'rtn' => $request->rtn,
            'nit' => $request->rtn,
            'direccion' => $request->direccion,
            'telefono' => $request->telefono,
            'email' => $request->email,
            'pais' => $request->pais,
            'departamento' => $request->departamento,
            'tipo_empresa' => $request->tipo_empresa,
            'updated_by' => Auth::id(),
        ];

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $empresa->update($this->filterPersistedColumns('empresas', $data));

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['success' => 'Empresa actualizada correctamente'], 200);
        }

        return redirect()->route('empresas.index')->with('success', 'Empresa actualizada correctamente');
    }

    public function destroy(Empresa $empresa)
    {
        $empresa->delete();

        if (request()->ajax() || request()->expectsJson()) {
            return response()->json(['success' => 'Empresa eliminada correctamente'], 200);
        }

        return redirect()->back()->with('success', 'Empresa eliminada correctamente');
    }

    private function filterPersistedColumns(string $table, array $payload): array
    {
        $availableColumns = array_flip(Schema::getColumnListing($table));

        return array_intersect_key($payload, $availableColumns);
    }
}