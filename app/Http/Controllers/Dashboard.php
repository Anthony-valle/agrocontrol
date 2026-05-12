<?php

namespace App\Http\Controllers;

use App\Models\Cultivo;
use App\Models\Insumo;
use App\Models\Lote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class Dashboard extends Controller
{
    public function index()
    {
        $cultivosActivos = Cultivo::latest('id')
            ->take(5)
            ->get($this->selectExistingColumns('cultivos', ['nombre', 'codigo']));

        $lotesActivos = Lote::latest('id')
            ->take(5)
            ->get($this->selectExistingColumns('lotes', ['nombre', 'codigo']));

        $insumosTotales = Insumo::count();

        $listaInsumos = Insumo::withSum('inventarioBodegas as stock_total', 'stock_actual')
            ->orderBy('nombre')
            ->take(8)
            ->get(['id', 'nombre', 'stock_minimo']);

        $insumosBajoStock = Insumo::withSum('inventarioBodegas as stock_total', 'stock_actual')
            ->get(['id', 'nombre', 'stock_minimo'])
            ->filter(function ($insumo) {
                return (float) ($insumo->stock_total ?? 0) <= (float) ($insumo->stock_minimo ?? 0);
            })
            ->sortBy('stock_total')
            ->values();

        $totalCultivos = Cultivo::count();
        $totalLotes = Lote::count();
        $alertasStock = $insumosBajoStock->count();

        return view('modules.dashboard.home', compact(
            'cultivosActivos',
            'lotesActivos',
            'insumosTotales',
            'listaInsumos',
            'insumosBajoStock',
            'totalCultivos',
            'totalLotes',
            'alertasStock'
        ));
    }

    public function marcarAlertasLeidas(Request $request)
    {
        session(['alertas_leidas' => true]);

        return response()->json(['ok' => true]);
    }

    private function selectExistingColumns(string $table, array $columns): array
    {
        $availableColumns = array_flip(Schema::getColumnListing($table));
        $selectedColumns = array_values(array_intersect($columns, array_keys($availableColumns)));

        return !empty($selectedColumns) ? $selectedColumns : ['id'];
    }
}
