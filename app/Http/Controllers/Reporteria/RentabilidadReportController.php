<?php

namespace App\Http\Controllers\Reporteria;

use App\Http\Controllers\Controller;
use App\Models\Cultivo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class RentabilidadReportController extends Controller
{
    public function index()
    {
        $relations = ['lote', 'cosechas'];

        if (Schema::hasTable('cosecha_facturas')) {
            $relations[] = 'cosechas.facturas';
        }

        $rentabilidad = Cultivo::with($relations)
            ->withSum('consumos', 'total')
            ->orderBy('nombre')
            ->get()
            ->map(function ($cultivo) {
                $facturas = Schema::hasTable('cosecha_facturas')
                    ? $cultivo->cosechas->flatMap->facturas
                    : collect();
                $ingresos = (float) $facturas->sum('total');
                $inversion = (float) ($cultivo->consumos_sum_total ?? 0);
                $utilidad = $ingresos - $inversion;

                return [
                    'id' => $cultivo->id,
                    'nombre' => $cultivo->nombre,
                    'lote' => $cultivo->lote->nombre ?? '-',
                    'estado' => $cultivo->estado,
                    'produccion' => (float) $cultivo->cosechas->sum('cantidad_neta'),
                    'disponible' => (float) $cultivo->cosechas->sum('cantidad_disponible'),
                    'inversion' => $inversion,
                    'ingresos' => $ingresos,
                    'utilidad' => $utilidad,
                    'margen' => $ingresos > 0 ? ($utilidad / $ingresos) * 100 : null,
                ];
            })
            ->sortByDesc('utilidad')
            ->values();

        $metricas = [
            'cultivos' => $rentabilidad->count(),
            'inversion' => $rentabilidad->sum('inversion'),
            'ingresos' => $rentabilidad->sum('ingresos'),
            'utilidad' => $rentabilidad->sum('utilidad'),
        ];

        return view('modules.reporteria.rentabilidad', compact('rentabilidad', 'metricas'));
    }
}
