<?php

namespace App\Http\Controllers;

use App\Exports\CultivoHistorialExport;
use App\Models\Cultivo;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class ReporteController extends Controller
{
    public function reporteFinal(int $cultivo_id)
    {
        $relations = ['consumos.detalles.insumo', 'cosechas', 'planes.detalles'];

        if ($this->cosechaFacturasDisponible()) {
            $relations[] = 'cosechas.facturas';
        }

        $cultivo = Cultivo::with($relations)->findOrFail($cultivo_id);

        if (! $this->cosechaFacturasDisponible()) {
            $cultivo->cosechas->each(function ($cosecha) {
                $cosecha->setRelation('facturas', collect());
            });
        }

        $plan = $cultivo->planes->sortByDesc('fecha_plan')->first();
        $planDetalles = $plan ? $plan->detalles : collect();
        $planCosecha = (float) ($cultivo->cosecha_estimada ?? ($plan?->cosecha_estimada ?? 0));
        $planPresupuesto = $plan ? $plan->total_presupuesto : 0;

        $planCategoryTotals = $planDetalles->groupBy(function ($detalle) {
            return $this->normalizarCategoriaReporte($detalle->categoria);
        })->map(function ($group) {
            return [
                'cantidad' => $group->sum('cantidad_estimada'),
                'subtotal' => $group->sum('subtotal'),
            ];
        })->toArray();

        $consumoDetalles = $cultivo->consumos->flatMap(function ($consumo) {
            return $consumo->detalles;
        });

        $realCategoryTotals = $consumoDetalles->groupBy(function ($detalle) {
            return $this->normalizarCategoriaReporte($detalle->categoria);
        })->map(function ($group) {
            return [
                'cantidad' => $group->sum('cantidad'),
                'subtotal' => $group->sum('subtotal'),
            ];
        })->toArray();

        $categoryNames = collect(array_keys($planCategoryTotals))
            ->merge(array_keys($realCategoryTotals))
            ->unique()
            ->values();

        $categoryComparisons = $categoryNames->mapWithKeys(function ($categoria) use ($planCategoryTotals, $realCategoryTotals) {
            $planData = $planCategoryTotals[$categoria] ?? ['cantidad' => 0, 'subtotal' => 0];
            $realData = $realCategoryTotals[$categoria] ?? ['cantidad' => 0, 'subtotal' => 0];
            $difference = $realData['subtotal'] - $planData['subtotal'];

            return [$categoria => [
                'plan_cantidad' => $planData['cantidad'],
                'plan_costo' => $planData['subtotal'],
                'real_cantidad' => $realData['cantidad'],
                'real_costo' => $realData['subtotal'],
                'diferencia_costo' => $difference,
                'sobre_plan_costo' => $difference > 0,
            ]];
        })->toArray();

        $getCategoryComparison = function ($target) use ($categoryComparisons) {
            foreach ($categoryComparisons as $nombre => $data) {
                if (strcasecmp($nombre, $target) === 0) {
                    return array_merge(['categoria' => $nombre], $data);
                }
            }

            return [
                'categoria' => $target,
                'plan_cantidad' => 0,
                'plan_costo' => 0,
                'real_cantidad' => 0,
                'real_costo' => 0,
                'diferencia_costo' => 0,
                'sobre_plan_costo' => false,
            ];
        };

        $manoObraComparison = $getCategoryComparison('Mano de Obra');
        $fertilizanteComparison = $getCategoryComparison('Fertilizante');
        $fitosanitarioComparison = $getCategoryComparison('Fitosanitario');

        // 1. Inversión total real (Consumos)
        $totalInversion = $cultivo->consumos->sum('total');

        // 2. Producción y descarte real
        $bruto = $cultivo->cosechas->sum('cantidad_bruta');
        $descarte = $cultivo->cosechas->sum('descarte');
        $neta = $cultivo->cosechas->sum('cantidad_neta');
        $disponible = $cultivo->cosechas->sum('cantidad_disponible');

        $facturasVenta = $cultivo->cosechas->flatMap->facturas;
        $tieneFacturasVenta = $facturasVenta->isNotEmpty();

        $cosechasConPrecio = $cultivo->cosechas->filter(function ($cosecha) {
            return array_key_exists('precio_venta_unitario', $cosecha->getAttributes())
                && $cosecha->precio_venta_unitario !== null;
        });

        $tienePrecioVenta = $tieneFacturasVenta || $cosechasConPrecio->isNotEmpty();

        // 3. Ventas reales, priorizando facturas registradas por venta
        $ingresos = $tieneFacturasVenta
            ? $facturasVenta->sum('total')
            : ($cosechasConPrecio->isNotEmpty()
                ? $cosechasConPrecio->sum(fn ($cosecha) => $cosecha->cantidad_neta * $cosecha->precio_venta_unitario)
                : null);

        // 4. Utilidad real
        $utilidad = $tienePrecioVenta ? $ingresos - $totalInversion : null;

        $planVsReal = [
            'cosecha_esperada' => $planCosecha,
            'cosecha_real_neta' => $neta,
            'cosecha_real_disponible' => $disponible,
            'descarte_real' => $descarte,
            'presupuesto_plan' => $planPresupuesto,
            'costo_real' => $totalInversion,
            'diferencia' => $planPresupuesto - $totalInversion,
            'costo_unitario_plan' => $planCosecha > 0 ? ($planPresupuesto / $planCosecha) : null,
            'rendimiento_real' => $bruto > 0 ? ($neta / $bruto) * 100 : 0,
        ];

        $kpiCostoProduccion = [
            'categorias' => collect($categoryComparisons)
                ->map(function (array $comparacion, string $categoria) {
                    return array_merge(['categoria' => $categoria], $comparacion);
                })
                ->values()
                ->all(),
            'total' => $totalInversion,
        ];

        $haSembradas = (float) ($cultivo->hectareas ?? 0);
        $haCosechadas = $bruto > 0 ? $haSembradas : 0.0;
        $kpiProduccion = [
            'ha_cosechadas' => $haCosechadas,
            'ha_sembradas' => $haSembradas,
            'produccion_por_ha_cosechada' => $haCosechadas > 0 ? ($neta / $haCosechadas) : 0,
            'produccion_cosecha_kg' => $neta,
        ];

        $planPaginas = $this->paginateGroupedWeeks(
            $planDetalles->sortBy([
                ['semana', 'asc'],
                ['categoria', 'asc'],
                ['descripcion', 'asc'],
            ]),
            static fn ($detalle) => (string) ($detalle->semana ?? 'Sin semana')
        );

        $consumoItems = $cultivo->consumos
            ->sortBy('fecha_consumo')
            ->flatMap(function ($consumo) use ($cultivo) {
                $semanaCultivo = $cultivo->calcularSemanaCultivoParaFecha($consumo->fecha_consumo) ?? 'Sin semana';

                return $consumo->detalles->map(function ($detalle) use ($consumo, $semanaCultivo) {
                    return [
                        'semana_cultivo' => $semanaCultivo,
                        'fecha_consumo' => $consumo->fecha_consumo,
                        'insumo' => $detalle->insumo->nombre ?? $detalle->descripcion ?? '-',
                        'categoria' => $detalle->categoria,
                        'descripcion' => $detalle->descripcion,
                        'cantidad' => (float) $detalle->cantidad,
                        'unidad_medida' => $detalle->unidad_medida,
                        'subtotal' => (float) $detalle->subtotal,
                    ];
                });
            })
            ->values();

        return view('modules.reporteria.cultivo_final', compact(
            'cultivo',
            'plan',
            'planDetalles',
            'totalInversion',
            'bruto',
            'descarte',
            'neta',
            'disponible',
            'ingresos',
            'utilidad',
            'tienePrecioVenta',
            'planVsReal',
            'kpiCostoProduccion',
            'kpiProduccion',
            'categoryComparisons',
            'manoObraComparison',
            'fertilizanteComparison',
            'fitosanitarioComparison',
            'planPaginas',
            'consumoItems'
        ));
    }

    public function categoriaDetalle(Request $request, int $cultivo_id)
    {
        $cultivo = Cultivo::with(['consumos.detalles.insumo', 'planes.detalles'])->findOrFail($cultivo_id);

        $categoria = trim((string) $request->query('categoria', ''));

        if ($categoria === '') {
            abort(422, 'La categoria es requerida.');
        }

        $categoriaNormalizada = $this->normalizarCategoriaReporte($categoria);
        $plan = $cultivo->planes->sortByDesc('fecha_plan')->first();
        $planDetallesCategoria = collect($plan?->detalles ?? [])
            ->filter(fn ($detalle) => $this->normalizarCategoriaReporte($detalle->categoria) === $categoriaNormalizada)
            ->sortBy([['semana', 'asc'], ['descripcion', 'asc']])
            ->values();

        $consumosCategoria = $cultivo->consumos
            ->sortByDesc('fecha_consumo')
            ->flatMap(function ($consumo) use ($cultivo, $categoriaNormalizada) {
                $semanaCultivo = $cultivo->calcularSemanaCultivoParaFecha($consumo->fecha_consumo) ?? 'Sin semana';
                $esManoDeObra = $categoriaNormalizada === 'Mano de Obra';

                return $consumo->detalles
                    ->filter(fn ($detalle) => $this->normalizarCategoriaReporte($detalle->categoria) === $categoriaNormalizada)
                    ->map(function ($detalle) use ($consumo, $semanaCultivo, $esManoDeObra) {
                        return (object) [
                            'consumo_id' => $consumo->id,
                            'semana_cultivo' => $semanaCultivo,
                            'fecha_consumo' => $consumo->fecha_consumo,
                            'insumo' => $esManoDeObra
                                ? ($detalle->descripcion ?: 'Mano de Obra')
                                : ($detalle->insumo->nombre ?? $detalle->descripcion ?? '-'),
                            'descripcion' => $detalle->descripcion,
                            'cantidad' => (float) $detalle->cantidad,
                            'unidad_medida' => $detalle->unidad_medida,
                            'subtotal' => (float) $detalle->subtotal,
                        ];
                    });
            })
            ->values();

        return view('modules.reporteria.cultivo_categoria_show', [
            'cultivo' => $cultivo,
            'categoria' => $categoriaNormalizada,
            'plan' => $plan,
            'planDetallesCategoria' => $planDetallesCategoria,
            'consumosCategoria' => $consumosCategoria,
            'planCantidadTotal' => (float) $planDetallesCategoria->sum('cantidad_estimada'),
            'planCostoTotal' => (float) $planDetallesCategoria->sum('subtotal'),
            'realCantidadTotal' => (float) $consumosCategoria->sum('cantidad'),
            'realCostoTotal' => (float) $consumosCategoria->sum('subtotal'),
        ]);
    }

    private function paginateGroupedWeeks(mixed $items, callable $weekResolver, int $weeksPerPage = 4)
    {
        $items = collect($items);

        if ($items->isEmpty()) {
            return collect();
        }

        $grouped = $items->groupBy($weekResolver);
        $weekKeys = $grouped->keys()->sort(function ($left, $right) {
            if (is_numeric($left) && is_numeric($right)) {
                return (int) $left <=> (int) $right;
            }

            return strcmp((string) $left, (string) $right);
        })->values();

        return $weekKeys
            ->chunk($weeksPerPage)
            ->values()
            ->map(function ($weekChunk, $index) use ($grouped) {
                $weeks = collect($weekChunk)->mapWithKeys(function ($week) use ($grouped) {
                    return [$week => $grouped->get($week, collect())->values()];
                });

                return [
                    'numero' => $index + 1,
                    'semanas' => $weeks,
                ];
            });
    }

    public function historialConsumo(Request $request, int $cultivo_id)
    {
        $cultivo = Cultivo::with(['consumos.detalles.insumo'])
            ->findOrFail($cultivo_id);

        $fechaDesde = (string) $request->query('fecha_desde', '');
        $fechaHasta = (string) $request->query('fecha_hasta', '');
        $perPage = (int) $request->query('per_page', 15);
        if (! in_array($perPage, [5, 10, 15, 20, 50], true)) {
            $perPage = 15;
        }

        $consumosBaseQuery = $cultivo->consumos()
            ->orderByDesc('fecha_consumo');

        if ($fechaDesde !== '') {
            $consumosBaseQuery->whereDate('fecha_consumo', '>=', $fechaDesde);
        }

        if ($fechaHasta !== '') {
            $consumosBaseQuery->whereDate('fecha_consumo', '<=', $fechaHasta);
        }

        $consumosFiltrados = (clone $consumosBaseQuery)
            ->with('detalles.insumo')
            ->get();

        $ultimoConsumo = $consumosFiltrados->sortByDesc('fecha_consumo')->first();

        $consumos = (clone $consumosBaseQuery)
            ->paginate($perPage)
            ->withQueryString();

        $consumosAgrupadosPorMes = $consumos->getCollection()
            ->groupBy(function ($consumo) {
                return Carbon::parse($consumo->fecha_consumo)->format('Y-m');
            })
            ->map(function ($items, $mes) {
                $fechaMes = Carbon::createFromFormat('Y-m', $mes);

                return [
                    'mes' => $mes,
                    'titulo' => $fechaMes->translatedFormat('F Y'),
                    'total' => (float) $items->sum('total'),
                    'registros' => $items->count(),
                    'items' => $items,
                ];
            });

        $consumoDetalles = $consumosFiltrados->flatMap(function ($consumo) {
            return $consumo->detalles->map(function ($detalle) use ($consumo) {
                return [
                    'consumo_id' => $consumo->id,
                    'fecha_consumo' => $consumo->fecha_consumo,
                    'semana' => Carbon::parse($consumo->fecha_consumo)->weekOfYear,
                    'categoria' => $this->normalizarCategoriaReporte($detalle->categoria),
                    'descripcion' => $detalle->descripcion,
                    'cantidad' => $detalle->cantidad,
                    'unidad_medida' => $detalle->unidad_medida,
                    'subtotal' => $detalle->subtotal,
                ];
            });
        });

        $categoryTotals = $consumoDetalles->groupBy('categoria')->map(function ($items, $categoria) {
            return [
                'cantidad' => $items->sum('cantidad'),
                'subtotal' => $items->sum('subtotal'),
            ];
        });

        $totalConsumo = $consumosFiltrados->sum('total');
        $totalConsumos = $consumosFiltrados->count();

        return view('modules.reporteria.cultivo_historial', compact(
            'cultivo',
            'consumos',
            'consumosAgrupadosPorMes',
            'consumoDetalles',
            'categoryTotals',
            'totalConsumo',
            'totalConsumos',
            'ultimoConsumo',
            'fechaDesde',
            'fechaHasta',
            'perPage'
        ));
    }

    public function historialConsumoDetalle(int $cultivo_id, int $consumo_id)
    {
        $cultivo = Cultivo::findOrFail($cultivo_id);

        $consumo = $cultivo->consumos()
            ->with(['detalles.insumo'])
            ->whereKey($consumo_id)
            ->firstOrFail();

        return view('modules.reporteria.partials.cultivo_historial_consumo_detalle', [
            'cultivo' => $cultivo,
            'consumo' => $consumo,
        ]);
    }

    public function historialConsumoExcel(int $cultivo_id)
    {
        $cultivo = Cultivo::with(['consumos.detalles.insumo'])
            ->findOrFail($cultivo_id);

        return Excel::download(
            new CultivoHistorialExport($cultivo),
            "historial_cultivo_{$cultivo->id}.xlsx"
        );
    }

    public function historialConsumoPdf(int $cultivo_id)
    {
        $cultivo = Cultivo::with(['consumos.detalles.insumo'])
            ->findOrFail($cultivo_id);

        $consumos = $cultivo->consumos->sortByDesc('fecha_consumo');

        $consumoDetalles = $consumos->flatMap(function ($consumo) {
            return $consumo->detalles->map(function ($detalle) use ($consumo) {
                return [
                    'consumo_id' => $consumo->id,
                    'fecha_consumo' => $consumo->fecha_consumo,
                    'semana' => Carbon::parse($consumo->fecha_consumo)->weekOfYear,
                    'categoria' => $this->normalizarCategoriaReporte($detalle->categoria),
                    'descripcion' => $detalle->descripcion,
                    'cantidad' => $detalle->cantidad,
                    'unidad_medida' => $detalle->unidad_medida,
                    'subtotal' => $detalle->subtotal,
                ];
            });
        });

        $categoryTotals = $consumoDetalles->groupBy('categoria')->map(function ($items, $categoria) {
            return [
                'cantidad' => $items->sum('cantidad'),
                'subtotal' => $items->sum('subtotal'),
            ];
        });

        $totalConsumo = $consumos->sum('total');
        $totalConsumos = $consumos->count();

        $pdf = Pdf::loadView('modules.reporteria.cultivo_historial_pdf', compact(
            'cultivo',
            'consumos',
            'consumoDetalles',
            'categoryTotals',
            'totalConsumo',
            'totalConsumos'
        ));

        return $pdf->download("historial_cultivo_{$cultivo->id}.pdf");
    }

    private function cosechaFacturasDisponible(): bool
    {
        return Schema::hasTable('cosecha_facturas');
    }

    private function normalizarCategoriaReporte(mixed $categoria): string
    {
        $categoria = trim((string) ($categoria ?? ''));

        if ($categoria === '') {
            return 'Otros Insumos';
        }

        $normalizada = strtolower($categoria);
        $normalizada = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalizada) ?: $normalizada;
        $normalizada = preg_replace('/[^a-z0-9]+/', ' ', $normalizada) ?? $normalizada;
        $normalizada = trim(preg_replace('/\s+/', ' ', $normalizada) ?? $normalizada);

        return match ($normalizada) {
            'otros', 'otro insumo', 'otros insumo', 'otros insumos' => 'Otros Insumos',
            'mano de obra', 'mano obra' => 'Mano de Obra',
            'fitosanitario', 'fitosanitarios' => 'Fitosanitario',
            'fertilizante', 'fertilizantes' => 'Fertilizante',
            'preparacion de suelo', 'preparacion suelo' => 'Preparacion de Suelo',
            'indirecto', 'indirectos' => 'Indirectos',
            default => ucwords($categoria),
        };
    }
}
