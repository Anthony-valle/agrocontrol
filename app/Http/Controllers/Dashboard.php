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
            ->get($this->selectExistingColumns('cultivos', ['id', 'nombre', 'codigo']));

        $lotesActivos = Lote::latest('id')
            ->take(5)
            ->get($this->selectExistingColumns('lotes', ['id', 'nombre', 'codigo']));

        $insumosTotales = Insumo::query()->activos()->count();

        $totalCultivos = Cultivo::count();
        $totalLotes = Lote::count();
        $alertasStock = 0;
        $stockThresholdColumn = collect(['stock_minimo', 'stock_min', 'minimo'])
            ->first(fn (string $column) => Schema::hasColumn('insumos', $column));

        if ($stockThresholdColumn) {
            $alertasStock = Insumo::query()
                ->activos()
                ->select(['id', $stockThresholdColumn])
                ->withSum('inventarioBodegas as stock_total', 'stock_actual')
                ->havingRaw("COALESCE(stock_total, 0) <= COALESCE({$stockThresholdColumn}, 0)")
                ->count();
        }

        return view('modules.dashboard.home', compact(
            'cultivosActivos',
            'lotesActivos',
            'insumosTotales',
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
