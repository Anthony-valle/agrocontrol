<?php

namespace App\Http\Controllers;

use App\Models\Categorias;
use App\Models\InventarioBodega;
use App\Models\MovimientoInventario;
use App\Models\Bodega;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class InventarioController extends Controller
{

    public function index(Request $request)
    {
        $sucursalId = Auth::user()->sucursal_id;
        $search = trim((string) $request->query('q', ''));
        $bodegaId = $request->bodega_id;
        $perPage = (int) $request->query('per_page', 10);

        if (! in_array($perPage, [5, 10, 20, 50], true)) {
            $perPage = 10;
        }

        $bodegas = Bodega::where('sucursal_id',$sucursalId)->get();

        $inventarios = InventarioBodega::with('insumo','bodega')
            ->whereHas('bodega', function($q) use ($sucursalId){
                $q->where('sucursal_id',$sucursalId);
            })
            ->where('stock_actual', '>', 0)
            ->when($bodegaId,function($q) use ($bodegaId){
                $q->where('bodega_id',$bodegaId);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->whereHas('insumo', function ($insumoQuery) use ($search) {
                        $insumoQuery->where('nombre', 'like', "%{$search}%")
                            ->orWhere('codigo', 'like', "%{$search}%");
                    })->orWhereHas('bodega', function ($bodegaQuery) use ($search) {
                        $bodegaQuery->where('nombre', 'like', "%{$search}%");
                    });
                });
            })
            ->orderBy('insumo_id')
            ->paginate($perPage)
            ->withQueryString();

        $inventarios->getCollection()->each(function ($item) {
            $item->categoria_resuelta = $this->resolverCategoriaNombre($item->insumo);
        });

        $titulo = "Inventario por Bodega";

        return view('modules.inventario.index',compact(
            'inventarios', 
            
            'bodegas',
            'titulo',
            'bodegaId',
            'search',
            'perPage'
        ));
    }

    public function detalle(int $id)
    {
        $inventario = InventarioBodega::with(['insumo', 'bodega.sucursal'])
            ->when(Schema::hasColumn('inventario_bodegas', 'id'), fn ($query) => $query->where('id', $id))
            ->when(! Schema::hasColumn('inventario_bodegas', 'id'), fn ($query) => $query->where('insumo_id', $id))
            ->firstOrFail();

        $inventario->categoria_resuelta = $this->resolverCategoriaNombre($inventario->insumo);

        $movimientos = MovimientoInventario::with(['bodegaOrigen', 'bodegaDestino', 'creador'])
            ->where('insumo_id', $inventario->insumo_id)
            ->where(function ($query) use ($inventario) {
                $query->where('bodega_origen_id', $inventario->bodega_id)
                    ->orWhere('bodega_destino_id', $inventario->bodega_id);
            })
            ->where('numero_lote', $inventario->numero_lote)
            ->latest()
            ->take(20)
            ->get();

        return view('modules.inventario.detalle', compact('inventario', 'movimientos'));
    }

    private function resolverCategoriaNombre(mixed $insumo): string
    {
        if (! $insumo) {
            return '-';
        }

        if (Schema::hasColumn('insumos', 'categoria_nombre') && ! empty($insumo->categoria_nombre)) {
            return (string) $insumo->categoria_nombre;
        }

        if (Schema::hasColumn('insumos', 'categoria_id') && ! empty($insumo->categoria_id)) {
            $categoria = Categorias::query()->find($insumo->categoria_id);
            return $categoria?->nombre ?: '-';
        }

        return '-';
    }
}