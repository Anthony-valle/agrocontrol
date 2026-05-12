<?php

namespace App\Http\Controllers;

use App\Models\Lotes;
use Illuminate\Http\Request;

class LotesController extends Controller
{
    // Listar
    public function index()
    {
        $titulo = 'Gestión de Lotes';
        $lotes = Lotes::all();
        return view('modules.lotes.index', compact('titulo','lotes'));
    }

      // Formulario crear
    public function create()
    {
        $titulo = 'Gestión de Lotes';
        $lotes = Lotes::all();
        return view('modules.lotes.create', compact('titulo'));
    }

    // Guardar categoría
    public function store(Request $request)
    {
        $request->validate([
            'nombre_lote' => 'required|string|max:20',
            'ubicacion' => 'required|string|max:150',
            'tamaño' => 'required|numeric|min:0',
            'tipo_suelo' => 'requiered|string|max:50',
        ]);

        Lotes::create($request->all());

        return redirect()->route('modules.lotes.index')->with('success', 'Lote registrado correctamente');
        
    }

    // Formulario de edición
    public function edit(Lotes $lotes)
    {
        return view('modules.lotes.edit', compact('titulo', 'lotes'));
    }

    // Actualizar 
    public function update(Request $request, Lotes $lotes)
    {
        $request->validate([
            'nombre_lote' => 'required|string|max:20',
            'ubicacion' => 'required|string|max:150',
            'tamaño' => 'required|numeric|min:0',
            'tipo_suelo' => 'requiered|string|max:50',
        ]);

        Lotes::create($request->all());

        return redirect()->route('modules.lotes.index')->with('success', 'Lote actualizado correctamente');
        

    }

    // Eliminar
    public function destroy(Lotes $lotes)
    {
        $lotes->delete();
        return response()->json(['success' => true]);
    }
}
