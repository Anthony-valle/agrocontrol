<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmpresaController extends Controller
{

    // Mostrar todas las empresas
    public function index()
    {
        $titulo = 'Configuración de Empresa';
        $empresa = Empresa::all();
        return view('modules.empresas.index', compact('titulo', 'empresa'));
    }

    //Formulario para crear
    public function create()
    {
        $titulo = 'Configurar Empresa';
        return view('modules.empresas.create', compact('titulo'));
    }

    //Guardamos
    public function store(Request $request)
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

        Empresa::create([
            'nombre' => $request->nombre,
            'rtn' => $request->rtn,
            'direccion' => $request->direccion,
            'telefono' => $request->telefono,
            'email' => $request->email,
            'pais' => $request->pais,
            'departamento' => $request->departamento,
            'tipo_empresa' => $request->tipo_empresa,
            'logo' => $request->file('logo')?->store('logos', 'public'),
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('empresas.index')->with('success', 'Empresa registrada correctamente');
    }

    //Edicion 
    public function edit(Empresa $empresa)
    {
        $titulo = 'Editar Empresa';
        return view('modules.empresas.edit', compact('empresa', 'titulo'));
    }

    //Actualizar
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

        $empresa->update([
            'nombre' => $request->nombre,
            'rtn' => $request->rtn,
            'direccion' => $request->direccion,
            'telefono' => $request->telefono,
            'email' => $request->email,
            'pais' => $request->pais,
            'departamento' => $request->departamento,
            'tipo_empresa' => $request->tipo_empresa,
            'logo' => $request->file('logo')?->store('logos', 'public'),
            'created_by' => Auth::id(),
        ]);

        if ($request->hasFile('logo')) {
            $empresa->update([
                'logo' => $request->file('logo')->store('logos', 'public'),
            ]);
        }

        return redirect()->route('empresas.index')->with('success', 'Empresa actualizada correctamente');
    }

    //Eliminar
    public function destroy(Empresa $empresa)
    {
        $empresa->delete();
        return redirect()->back()->with('success', 'Empresa eliminada correctamente');
    }
}
