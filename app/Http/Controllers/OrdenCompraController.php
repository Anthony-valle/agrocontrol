<?php

namespace App\Http\Controllers;

use App\Models\Detalle_orden_compras;
use App\Models\Insumos;
use App\Models\Inventario;
use App\Models\Orden_compra;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrdenCompraController extends Controller
{
     // Listar
    public function index()
    {
        $titulo = 'Administrar Orden de Compras';
        $orden_compra = Orden_compra::with('provedor')->get();
        return view('modules.ordenes_compras.index', compact('titulo', 'orden_compra'));
    }

    // Formulario crear
    public function create()
    {
        $proveedor = Proveedor::all();
        $insumos = Insumos::all();
        return view('modules.ordenes_compras.create', compact('proveedor','insumos'));
    }

    // Guardar 
    public function store(Request $request)
    {
         DB::transaction(function () use ($request) {

            $orden_compra = Orden_compra::create([
                'provedor_id' => $request->proveedor_id,
                'fecha_orden' => now(),
                'estado' => 'BORRADOR',
                'observacion' => $request->observacion,
                'total' => 0
            ]);

            $total = 0;

            foreach ($request->insumos as $item) {
                $subtotal = $item['cantidad'] * $item['precio'];

                Detalle_orden_compras::create([
                    'orden_compra_id' => $orden_compra->id,
                    'insumos_id' => $item['insumos_id'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio'],
                    'subtotal' => $subtotal
                ]);

                $total += $subtotal;
            }

            $orden_compra->update(['total' => $total]);
        });

        return redirect()->route('ordenes.index') ->with('success','Orden creada correctamente');
        
    }


      // ✔ APROBAR ORDEN
    public function aprobar($id)
    {
        $orden = Orden_compra::findOrFail($id);

        if ($orden->estado !== 'BORRADOR') {
            return back()->with('error','Solo se pueden aprobar órdenes en borrador');
        }

        $orden->update(['estado' => 'APROBADA']);

        return back()->with('success','Orden aprobada correctamente');
    }

    // ✔ RECIBIR ORDEN (INGRESA INVENTARIO)
    public function recibir($id)
    {
        $orden = Orden_compra::with('detalles.insumo')->findOrFail($id);

        if ($orden->estado !== 'APROBADA') {
            return back()->with('error','La orden debe estar aprobada');
        }

        DB::transaction(function () use ($orden) {

            foreach ($orden->detalles as $detalle) {

                Inventario::create([
                    'insumos_id' => $detalle->insumo_id,
                    'tipo' => 'ENTRADA',
                    'cantidad' => $detalle->cantidad,
                    'referencia' => 'OC-'.$orden->id,
                    'fecha' => now()
                ]);

                $detalle->insumo->increment('stock_actual', $detalle->cantidad);
            }

            $orden->update(['estado' => 'RECIBIDA']);
        });

        return back()->with('success','Orden recibida e inventario actualizado');
    }
    /**
     * Display the specified resource.
     */
    public function show(Orden_compra $orden_compra)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Orden_compra $orden_compra)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Orden_compra $orden_compra)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Orden_compra $orden_compra)
    {
        //
    }
}
