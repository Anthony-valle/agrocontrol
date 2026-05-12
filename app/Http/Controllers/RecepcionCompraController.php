<?php

namespace App\Http\Controllers;

use App\Models\Inventario;
use App\Models\Orden_compra;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecepcionCompraController extends Controller
{
     public function recibir($id)
    {
        $orden = Orden_compra::with('detalles.insumo')->findOrFail($id);

        DB::transaction(function () use ($orden) {

            foreach ($orden->detalles as $detalle) {

                Inventario::create([
                    'insumo_id' => $detalle->insumo_id,
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
}
