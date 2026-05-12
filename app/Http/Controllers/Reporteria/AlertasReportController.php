<?php

namespace App\Http\Controllers\Reporteria;

use App\Http\Controllers\Controller;
use App\Models\InventarioBodega;
use App\Models\Notificaciones;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AlertasReportController extends Controller
{
    public function index()
    {
        abort_unless(request()->user()?->isSuperUser(), 403);

        $notificaciones = Notificaciones::with(['usuario', 'cultivo'])
            ->latest()
            ->take(20)
            ->get();

        $hoy = Carbon::today();
        $limite = Carbon::today()->addDays(30);

        $inventarios = InventarioBodega::with(['insumo', 'bodega.sucursal'])->get();

        $stockBajo = $inventarios
            ->filter(function ($item) {
                return $item->insumo
                    && $item->insumo->stock_minimo !== null
                    && $item->stock_actual <= $item->insumo->stock_minimo;
            })
            ->sortBy(fn ($item) => $item->stock_actual - ($item->insumo->stock_minimo ?? 0))
            ->values();

        $vencimientos = $inventarios
            ->filter(function ($item) use ($hoy, $limite) {
                if (!$item->fecha_vencimiento) {
                    return false;
                }

                $fecha = Carbon::parse($item->fecha_vencimiento);

                return $fecha->lt($hoy) || $fecha->between($hoy, $limite);
            })
            ->sortBy('fecha_vencimiento')
            ->values();

        $metricas = [
            'no_leidas' => $notificaciones->where('leido', false)->count(),
            'registradas' => $notificaciones->count(),
            'stock_bajo' => $stockBajo->count(),
            'vencimientos' => $vencimientos->count(),
        ];

        return view('modules.reporteria.alertas', compact('notificaciones', 'stockBajo', 'vencimientos', 'metricas'));
    }
}
