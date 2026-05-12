<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Sucursal;
use App\Models\Sucursale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SucursaleController extends Controller
{
    public function getSucursales($empresaId)
    {
        // Verificamos que lleguen los datos filtrados por la columna que acabas de crear
        $sucursales = \App\Models\Sucursale::where('empresa_id', $empresaId)
                        ->where('estado', 1)
                        ->get(['id', 'nombre']);

        return response()->json($sucursales);
    }
     //Mostrar listas
    public function index()
    {
        $titulo = 'Sucursal';
        $sucursal = Sucursale::with('empresa','creador')->get();
        return view('modules.sucursal.index', compact('titulo', 'sucursal'));
    }

    //Mostarmos el formulario creación
    public function create()
    {
        $titulo = 'Configuración de Sucursal';
        $empresas = Empresa::orderBy('nombre')->get();
        return view('modules.sucursal.create', compact('titulo', 'empresas'));
    }

    // Guardar
    public function store(Request $request)
    {
        $request->validate([
            'empresa_id' => 'required|exists:empresas,id',
            'nombre'     => 'required|string|max:50',
            'direccion'  => 'required|string|max:150',
            'telefono'   => 'required|string|max:20',
            'email'      => 'required|string|max:50',
            'responsable'=> 'required|string|max:30',
        ]);

        $sucursalCreada = Sucursale::create([
            'empresa_id' => $request->empresa_id,
            'nombre'     => $request->nombre,
            'direccion'  => $request->direccion,
            'telefono'   => $request->telefono,
            'email'      => $request->email,
            'responsable'=> $request->responsable,
            'estado'     => $request->estado,
            'created_by' => Auth::id(),
        ]);

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'success' => 'Sucursal registrada correctamente',
                'sucursal_id' => $sucursalCreada->id,
            ], 200);
        }

        return redirect()->route('sucursal.index')->with('success', 'Sucursal registrada correctamente');
    }

    // Formulario
    public function edit(Sucursale $sucursal)
    {
        $titulo = 'Editar Sucursal';
        $empresas = Empresa::orderBy('nombre')->get();

        return view('modules.sucursal.edit', compact('titulo', 'sucursal', 'empresas'));
    }

    // Actualizar 
    public function update(Request $request, Sucursale $sucursal)
    {
        $request->validate([
            'empresa_id' => 'required|exists:empresas,id',
            'nombre'     => 'required|string|max:100',
            'direccion'  => 'required|string|max:150',
            'telefono'   => 'required|string|max:20',
            'email'      => 'required|string|max:50',
            'responsable'=> 'required|string|max:30',
        ]);

        $sucursal->update([
            'empresa_id' => $request->empresa_id,
            'nombre'     => $request->nombre,
            'direccion'  => $request->direccion,
            'telefono'   => $request->telefono,
            'email'      => $request->email,
            'responsable'=> $request->responsable,
            'estado'     => $request->estado,
            'updated_by' => Auth::id(),
        ]);

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['success' => 'Sucursal actualizada correctamente'], 200);
        }

        return redirect()->route('sucursal.index')->with('success', 'Sucursal actualizada correctamente');
    }

    // Eliminar
    public function destroy(Sucursale $sucursal)
    {
        $sucursal->delete();

        if (request()->ajax() || request()->expectsJson()) {
            return response()->json(['success' => 'Sucursal eliminada correctamente'], 200);
        }

        return redirect()->back()->with('success', 'Sucursal eliminada correctamente');
    }

}
