<?php

namespace App\Imports;

use App\Models\Cultivo;
use App\Models\planes_cultivo;
use App\Models\planes_detalles;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\RemembersChunkOffset;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class PlanesCultivoImport implements ToCollection, WithHeadingRow, SkipsEmptyRows, WithChunkReading
{
    use RemembersChunkOffset;

    protected int $userId;
    protected ?int $planIdBase;
    protected ?Collection $planesCache = null;
    protected array $planesImportadosCache = [];
    protected ?array $planImportacionUnica = null;

    protected array $stats = [
        'filas_procesadas' => 0,
        'filas_omitidas' => 0,
        'filas_error' => 0,
        'filas_plan_base_invalido' => 0,
        'filas_plan_id_usado_como_cultivo_id' => 0,
        'filas_con_fecha_por_defecto' => 0,
        'planes_creados' => 0,
        'planes_actualizados' => 0,
        'detalles_creados' => 0,
    ];

    protected array $errores = [];

    public function __construct(int $userId, ?int $planIdBase = null)
    {
        $this->userId = $userId;
        $this->planIdBase = $planIdBase;
    }

    public function collection(Collection $rows): void
    {
        $grupos = [];

        foreach ($rows as $index => $row) {
            $this->stats['filas_procesadas']++;
            $fila = $this->getCurrentChunkOffset() + $index;

            $planIdRaw = $this->obtenerValor($row, ['plan_id_base', 'plan_id', 'id_plan']);
            $planIdNumerico = is_numeric($planIdRaw) ? (int) $planIdRaw : null;

            $planBaseFila = $this->resolverPlanBase($row);
            $cultivoDesdePlanId = null;
            if ($planBaseFila === false) {
                $this->stats['filas_plan_base_invalido']++;

                // Compatibilidad: si plan_id_base no existe como plan, intentar usarlo como cultivo_id.
                if ($planIdNumerico) {
                    $cultivoDesdePlanId = Cultivo::query()->find($planIdNumerico);
                    if ($cultivoDesdePlanId) {
                        $this->stats['filas_plan_id_usado_como_cultivo_id']++;
                    }
                }
            }

            $planBase = $planBaseFila instanceof planes_cultivo ? $planBaseFila : null;

            $cultivoIdRaw = $this->obtenerValor($row, ['cultivo_id', 'id_cultivo']);
            if ($cultivoIdRaw === null || trim((string) $cultivoIdRaw) === '') {
                $this->registrarError($fila, 'La columna cultivo_id es obligatoria.');
                continue;
            }

            $cultivo = $planBase
                ? Cultivo::query()->find($planBase->cultivo_id)
                : ($cultivoDesdePlanId ?: Cultivo::query()->find((int) $cultivoIdRaw));
            if (!$cultivo) {
                $codigoRaw = $this->obtenerValor($row, ['cultivo_codigo', 'codigo_cultivo', 'codigo']);
                $nombreRaw = $this->obtenerValor($row, ['cultivo_nombre', 'cultivo', 'nombre_cultivo', 'nombre']);

                $this->registrarError(
                    $fila,
                    'No existe cultivo para cultivo_id=' . ($cultivoIdRaw ?? 'vacio') . ' (codigo=' . ($codigoRaw ?? 'vacio') . ', nombre=' . ($nombreRaw ?? 'vacio') . '). Cree primero el cultivo en el modulo Cultivos y luego vuelva a importar la receta.'
                );
                continue;
            }

            $fechaRaw = $this->obtenerValor($row, [
                'fecha_plan',
                'fecha',
                'fecha_inicio',
                'fecha_inicial',
                'plan_fecha',
            ]);
            $fechaPlan = $this->normalizarFecha($fechaRaw);
            if (!$fechaPlan) {
                $fechaPlan = $this->obtenerFechaPorDefecto($planBase);
                $this->stats['filas_con_fecha_por_defecto']++;
            }

            if (! $planBase && ! $this->filaPerteneceAPlanUnico($cultivo, $fechaPlan, $fila)) {
                continue;
            }

            $semana = (int) $this->toFloat($row['semana'] ?? null);
            if ($semana < 1 || $semana > 52) {
                $this->registrarError($fila, 'La semana debe estar entre 1 y 52.');
                continue;
            }

            $categoria = $this->limpiarTexto($row['categoria'] ?? null);
            $descripcion = $this->limpiarTexto($row['descripcion'] ?? null);
            $unidad = $this->limpiarTexto($row['unidad_medida'] ?? null);
            $cantidad = round($this->toFloat($row['cantidad_estimada'] ?? null), 2);
            $costo = round($this->toFloat($row['costo_unitario'] ?? null), 2);

            if (!$categoria || !$descripcion || !$unidad) {
                $this->registrarError($fila, 'categoria, descripcion y unidad_medida son obligatorias.');
                continue;
            }

            if ($cantidad <= 0) {
                $this->stats['filas_omitidas']++;
                continue;
            }

            if ($costo < 0) {
                $this->registrarError($fila, 'costo_unitario no puede ser negativo.');
                continue;
            }

            $cosechaEstimada = (float) ($cultivo->cosecha_estimada ?? 0);
            $planIdGrupo = $planBase?->id;
            $clave = $planIdGrupo ? ('PLAN_BASE|' . $planIdGrupo) : 'IMPORT_UNICA';

            if (!isset($grupos[$clave])) {
                $grupos[$clave] = [
                    'clave' => $clave,
                    'plan_id_base' => $planIdGrupo,
                    'cultivo' => $cultivo,
                    'fecha_plan' => $fechaPlan,
                    'cosecha_estimada' => $cosechaEstimada,
                    'detalles' => [],
                ];
            }

            $grupos[$clave]['detalles'][] = [
                'semana' => $semana,
                'categoria' => $categoria,
                'descripcion' => $descripcion,
                'cantidad_estimada' => $cantidad,
                'unidad_medida' => $unidad,
                'costo_unitario' => $costo,
                'subtotal' => round($cantidad * $costo, 2),
            ];
        }

        $totalDetalles = collect($grupos)->sum(fn ($g) => count($g['detalles']));
        if ($totalDetalles === 0) {
            return;
        }

        DB::transaction(function () use ($grupos) {
            foreach ($grupos as $grupo) {
                $cultivo = $grupo['cultivo'];
                $planIdBaseGrupo = $grupo['plan_id_base'] ?? null;
                $claveGrupo = $grupo['clave'] ?? null;

                if ($planIdBaseGrupo) {
                    $plan = planes_cultivo::query()->lockForUpdate()->findOrFail($planIdBaseGrupo);
                } else {
                    $plan = $this->resolverPlanImportado($claveGrupo, $cultivo, $grupo);
                }

                $total = 0;
                foreach ($grupo['detalles'] as $detalle) {
                    $detalle = $this->filterPersistedColumns('planes_detalles', array_merge($detalle, [
                        'plan_cultivo_id' => $plan->id,
                        'created_by' => $this->userId,
                    ]));
                    planes_detalles::create($detalle);
                    $total += $detalle['subtotal'];
                    $this->stats['detalles_creados']++;
                }

                if ($planIdBaseGrupo) {
                    $plan->update($this->filterPersistedColumns('planes_cultivos', [
                        'semana' => min($this->resolverSemanaCabecera($grupo['detalles']), (int) ($plan->semana ?: 52)),
                        'total_presupuesto' => ((float) $plan->total_presupuesto) + $total,
                        'updated_by' => $this->userId,
                    ]));
                    $this->stats['planes_actualizados']++;
                } else {
                    $plan->update($this->filterPersistedColumns('planes_cultivos', [
                        'semana' => min($this->resolverSemanaCabecera($grupo['detalles']), (int) ($plan->semana ?: 52)),
                        'total_presupuesto' => ((float) $plan->total_presupuesto) + $total,
                        'updated_by' => $this->userId,
                    ]));
                    if ($claveGrupo !== null) {
                        $this->planesImportadosCache[$claveGrupo] = $plan->id;
                    }
                }
            }
        });
    }

    public function getStats(): array
    {
        return $this->stats;
    }

    public function getErrores(): array
    {
        return array_slice($this->errores, 0, 20);
    }

    public function getSummaryLines(): array
    {
        return [
            'Filas procesadas: ' . $this->stats['filas_procesadas'],
            'Filas omitidas (cantidad <= 0): ' . $this->stats['filas_omitidas'],
            'Filas con error: ' . $this->stats['filas_error'],
            'Filas con plan_id_base invalido (se ignoro y continuo): ' . $this->stats['filas_plan_base_invalido'],
            'Filas donde plan_id_base se uso como cultivo_id: ' . $this->stats['filas_plan_id_usado_como_cultivo_id'],
            'Filas con fecha por defecto: ' . $this->stats['filas_con_fecha_por_defecto'],
            'Planes creados: ' . $this->stats['planes_creados'],
            'Planes actualizados: ' . $this->stats['planes_actualizados'],
            'Detalles creados: ' . $this->stats['detalles_creados'],
        ];
    }

    public function chunkSize(): int
    {
        return 250;
    }

    protected function getCurrentChunkOffset(): int
    {
        return (int) ($this->getChunkOffset() ?? 2);
    }

    protected function obtenerValor(Collection $row, array $keys)
    {
        foreach ($keys as $key) {
            if ($row->has($key)) {
                $valor = $row->get($key);
                if ($valor !== null && trim((string) $valor) !== '') {
                    return $valor;
                }
            }
        }

        return null;
    }

    protected function toFloat(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $normalizado = str_replace([' ', ','], ['', '.'], (string) $value);
        return (float) $normalizado;
    }

    protected function limpiarTexto(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $texto = trim((string) $value);
        return $texto === '' ? null : $texto;
    }

    protected function normalizarFecha(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }

        $texto = trim((string) $value);
        if ($texto === '') {
            return null;
        }

        try {
            return Carbon::parse($texto)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function registrarError(int $fila, string $mensaje): void
    {
        $this->stats['filas_error']++;
        $this->errores[] = 'Fila ' . $fila . ': ' . $mensaje;
    }

    protected function obtenerFechaPorDefecto(?planes_cultivo $planBase = null): string
    {
        if ($planBase && !empty($planBase->fecha_plan)) {
            return (string) $planBase->fecha_plan;
        }

        if ($this->planIdBase) {
            $plan = planes_cultivo::query()->find($this->planIdBase);
            if ($plan && !empty($plan->fecha_plan)) {
                return (string) $plan->fecha_plan;
            }
        }

        return Carbon::today()->format('Y-m-d');
    }

    protected function resolverPlanBase(Collection $row)
    {
        $planIdRaw = $this->obtenerValor($row, ['plan_id_base', 'plan_id', 'id_plan']);
        if ($planIdRaw !== null && trim((string) $planIdRaw) !== '') {
            $plan = $this->buscarPlanBase((int) $planIdRaw);
            return $plan ?: false;
        }

        if ($this->planIdBase) {
            $plan = $this->buscarPlanBase($this->planIdBase);
            return $plan ?: false;
        }

        return null;
    }

    protected function buscarPlanBase(int $planId): ?planes_cultivo
    {
        if ($this->planesCache === null) {
            $this->planesCache = planes_cultivo::query()->select(['id', 'cultivo_id', 'fecha_plan'])->get();
        }

        return $this->planesCache->first(fn (planes_cultivo $plan) => (int) $plan->id === $planId);
    }

    protected function resolverPlanImportado(?string $claveGrupo, Cultivo $cultivo, array $grupo): planes_cultivo
    {
        if ($claveGrupo !== null && isset($this->planesImportadosCache[$claveGrupo])) {
            return planes_cultivo::query()->lockForUpdate()->findOrFail($this->planesImportadosCache[$claveGrupo]);
        }

        $plan = planes_cultivo::create($this->filterPersistedColumns('planes_cultivos', [
            'empresa_id' => $cultivo->empresa_id,
            'cultivo_id' => $cultivo->id,
            'semana' => $this->resolverSemanaCabecera($grupo['detalles']),
            'fecha_plan' => $grupo['fecha_plan'],
            'cosecha_estimada' => $grupo['cosecha_estimada'],
            'total_presupuesto' => 0,
            'estado' => 'PLANIFICADO',
            'created_by' => $this->userId,
        ]));

        $this->stats['planes_creados']++;

        if ($claveGrupo !== null) {
            $this->planesImportadosCache[$claveGrupo] = $plan->id;
        }

        return $plan;
    }

    protected function filaPerteneceAPlanUnico(Cultivo $cultivo, string $fechaPlan, int $fila): bool
    {
        if ($this->planImportacionUnica === null) {
            $this->planImportacionUnica = [
                'cultivo_id' => (int) $cultivo->id,
                'fecha_plan' => $fechaPlan,
            ];

            return true;
        }

        if ((int) $this->planImportacionUnica['cultivo_id'] !== (int) $cultivo->id) {
            $this->registrarError($fila, 'La carga masiva solo puede crear un plan por importacion. Todas las filas deben pertenecer al mismo cultivo_id.');

            return false;
        }

        if ((string) $this->planImportacionUnica['fecha_plan'] !== $fechaPlan) {
            $this->registrarError($fila, 'La carga masiva solo puede crear un plan por importacion. Todas las filas deben usar la misma fecha_plan.');

            return false;
        }

        return true;
    }

    protected function filterPersistedColumns(string $table, array $payload): array
    {
        $availableColumns = array_flip(Schema::getColumnListing($table));

        return array_intersect_key($payload, $availableColumns);
    }

    protected function resolverSemanaCabecera(array $detalles): int
    {
        $semanas = collect($detalles)
            ->pluck('semana')
            ->map(fn ($semana) => (int) $semana)
            ->filter(fn ($semana) => $semana >= 1 && $semana <= 52)
            ->values();

        return (int) ($semanas->min() ?: 1);
    }
}
