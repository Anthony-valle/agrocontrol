<?php

namespace App\Http\Controllers;

use App\Models\Productos;
use Illuminate\Http\Request;

class ProductosController extends Controller
{
    public function index()
    {
        $productos = Productos::all();
        return view('modules.productos.index', compact('productos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre_producto' => 'required|string|max:255',
            'categoria' => 'required|string',
            'cantidad' => 'nullable|integer|min:0',
            'precio_compra' => 'nullable|numeric|min:0',
            'imagen_producto' => 'nullable|image|mimes:jpg,png|max:2048',
        ]);

        $imagen = null;

        if ($request->hasFile('imagen_producto')) {
            $imagen = $request->file('imagen_producto')->store('productos', 'public');
        }

        Productos::create([
            'codigo_sap' => $request->codigo_sap,
            'nombre_producto' => $request->nombre_producto,
            'ingrediente_activo' => $request->ingrediente_activo,
            'categoria' => $request->categoria,
            'unidad_medida' => $request->unidad_medida,
            'cantidad' => $request->cantidad,
            'lote' => $request->lote,
            'precio_compra' => $request->precio_compra,
            'proveedor' => $request->proveedor,
            'numero_factura' => $request->numero_factura,
            'fecha_vencimiento' => $request->fecha_vencimiento,
            'imagen_producto' => $imagen,
        ]);

        return redirect()->route('productos.index')->with('success', 'Producto registrado correctamente');
    }

    public function destroy($id)
    {
        Productos::findOrFail($id)->delete();
        return back()->with('success', 'Producto eliminado correctamente');
    }
}
