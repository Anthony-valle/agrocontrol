<?php

namespace App\Http\Controllers;

use App\Models\MovimientoInventario;
use App\Models\Insumo;
use App\Models\Bodega;
use Illuminate\Http\Request;

class MovimientoInventarioController extends Controller
{
    // Historial de movimientos
    public function index()
    {
        $titulo = 'Historial de Movimientos';
        $movimientos = MovimientoInventario::with('insumo','bodegaOrigen','bodegaDestino')->latest()->get();
        return view('modules.movimientos.index', compact('movimientos','titulo'));
    }

    // Mostrar formulario de entrada
    public function entrada(Request $request)
    {
        $insumos = Insumo::all();
        $bodegas = Bodega::all();

       return view('modules.movimientos.entrada', compact('insumos','bodegas'));
    }

    // Mostrar formulario de salida
    public function salida()
    {
        $insumos = Insumo::all();
        $bodegas = Bodega::all();
        return view('modules.movimientos.salida.form', compact('insumos','bodegas'));
    }

    // Mostrar formulario de traslado
    public function traslado()
    {
        $insumos = Insumo::all();
        $bodegas = Bodega::all();
        return view('modules.movimientos.traslado.form', compact('insumos','bodegas'));
    }
}