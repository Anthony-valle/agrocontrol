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
    protected array $planesImportadosCache = [];
    protected array $columnasPorTabla = [];
    protected array $cultivosPorId = [];
    protected array $cultivosPorCodigo = [];
    protected array $cultivosPorNombre = [];
    protected array $stats = [
        'filas_procesadas' => 0,
        'filas_omitidas' => 0,
        'filas_error' => 0,
        'filas_con_fecha_por_defecto' => 0,
        'filas_multiplicadas_por_hectareas' => 0,
        'planes_creados' => 0,
        'detalles_creados' => 0,
    ];

    protected array $errores = [];

    public function __construct(int $userId)
    {
        $this->userId = $userId;
    }

    public function collection(Collection $rows): void
    {
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
        @ini_set('memory_limit', '1024M');

        $grupos = [];

        foreach ($rows as $index => $row) {
            $this->stats['filas_procesadas']++;
            $fila = $this->getCurrentChunkOffset() + $index;

            $cultivoIdRaw = $this->obtenerValor($row, ['cultivo_id', 'id_cultivo']);
            $cultivoCodigoRaw = $this->obtenerValor($row, ['cultivo_codigo', 'codigo_cultivo', 'codigo']);
            $cultivoNombreRaw = $this->obtenerValor($row, ['cultivo_nombre', 'cultivo', 'nombre_cultivo', 'nombre']);
            $cultivo = $this->resolverCultivoPrincipal($cultivoIdRaw, $cultivoCodigoRaw, $cultivoNombreRaw);

            if (!$cultivo) {
                $this->registrarError(
                    $fila,
                    'No existe cultivo para cultivo_id=' . ($cultivoIdRaw ?? 'vacio') . ' (codigo=' . ($cultivoCodigoRaw ?? 'vacio') . ', nombre=' . ($cultivoNombreRaw ?? 'vacio') . '). Cree primero el cultivo en el modulo Cultivos y luego vuelva a importar la receta.'
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
                $fechaPlan = $this->obtenerFechaPorDefecto();
                $this->stats['filas_con_fecha_por_defecto']++;
            }

            $semana = $this->resolverSemanaFila($row);
            if ($semana < 0 || $semana > 52) {
                $this->registrarError($fila, 'La semana debe estar entre 0 y 52.');
                continue;
            }

            $categoria = $this->limpiarTexto($row['categoria'] ?? null);
            $descripcion = $this->limpiarTexto($row['descripcion'] ?? null);
            $unidad = $this->limpiarTexto($row['unidad_medida'] ?? null);
            $cantidadPorHectarea = round($this->toFloat($this->obtenerValor($row, ['cantidad_estimada', 'cantidad_por_ha', 'cantidad_ha', 'cantidad_por_hectarea'])), 3);
            $costo = round($this->toFloat($row['costo_unitario'] ?? null), 3);
            $hectareasCultivo = round($this->toFloat($cultivo->hectareas ?? null), 3);

            if (!$categoria || !$descripcion || !$unidad) {
                $this->registrarError($fila, 'categoria, descripcion y unidad_medida son obligatorias.');
                continue;
            }

            if ($hectareasCultivo <= 0) {
                $this->registrarError($fila, 'El cultivo seleccionado no tiene hectareas validas para multiplicar la receta base de 1 HA.');
                continue;
            }

            if ($cantidadPorHectarea <= 0) {
                $this->stats['filas_omitidas']++;
                continue;
            }

            if ($costo < 0) {
                $this->registrarError($fila, 'costo_unitario no puede ser negativo.');
                continue;
            }

            $cantidad = round($cantidadPorHectarea * $hectareasCultivo, 3);
            $this->stats['filas_multiplicadas_por_hectareas']++;

            $cosechaEstimada = (float) ($cultivo->cosecha_estimada ?? 0);
            $clave = 'IMPORT|' . $cultivo->id;

            if (!isset($grupos[$clave])) {
                $grupos[$clave] = [
                    'clave' => $clave,
                    'cultivo' => $cultivo,
                    'fecha_plan' => $fechaPlan,
                    'cosecha_estimada' => $cosechaEstimada,
                    'detalles' => [],
                ];
            } elseif ($fechaPlan < $grupos[$clave]['fecha_plan']) {
                $grupos[$clave]['fecha_plan'] = $fechaPlan;
            }

            $grupos[$clave]['detalles'][] = [
                'semana' => $semana,
                'categoria' => $categoria,
                'descripcion' => $descripcion,
                'cantidad_estimada' => $cantidad,
                'unidad_medida' => $unidad,
                'costo_unitario' => $costo,
                'subtotal' => round($cantidad * $costo, 3),
            ];
        }

        $totalDetalles = collect($grupos)->sum(fn ($g) => count($g['detalles']));
        if ($totalDetalles === 0) {
            return;
        }

        DB::transaction(function () use ($grupos) {
            foreach ($grupos as $grupo) {
                $cultivo = $grupo['cultivo'];
                $claveGrupo = $grupo['clave'] ?? null;
                $plan = $this->resolverPlanImportado($claveGrupo, $cultivo, $grupo);

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

                $plan->update($this->filterPersistedColumns('planes_cultivos', [
                    'semana' => min($this->resolverSemanaCabecera($grupo['detalles']), (int) ($plan->semana ?: 52)),
                    'total_presupuesto' => ((float) $plan->total_presupuesto) + $total,
                    'updated_by' => $this->userId,
                ]));
                if ($claveGrupo !== null) {
                    $this->planesImportadosCache[$claveGrupo] = $plan->id;
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
            'Filas con fecha por defecto: ' . $this->stats['filas_con_fecha_por_defecto'],
            'Filas multiplicadas por hectareas del cultivo (base 1 HA): ' . $this->stats['filas_multiplicadas_por_hectareas'],
            'Planes creados: ' . $this->stats['planes_creados'],
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

        foreach (['d/m/Y', 'j/n/Y', 'd-m-Y', 'j-n-Y'] as $formato) {
            try {
                return Carbon::createFromFormat($formato, $texto)->format('Y-m-d');
            } catch (\Throwable $e) {
                continue;
            }
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

    protected function obtenerFechaPorDefecto(): string
    {
        return Carbon::today()->format('Y-m-d');
    }

    protected function resolverCultivo(mixed $cultivoId, mixed $cultivoCodigo, mixed $cultivoNombre): ?Cultivo
    {
        if ($cultivoId !== null && trim((string) $cultivoId) !== '' && is_numeric($cultivoId)) {
            $cultivoId = (int) $cultivoId;
            if (! array_key_exists($cultivoId, $this->cultivosPorId)) {
                $this->cultivosPorId[$cultivoId] = Cultivo::query()->find($cultivoId);
            }

            $cultivo = $this->cultivosPorId[$cultivoId];
            if ($cultivo) {
                return $cultivo;
            }
        }

        $codigo = $this->limpiarTexto($cultivoCodigo);
        if ($codigo !== '') {
            if (! array_key_exists($codigo, $this->cultivosPorCodigo)) {
                $this->cultivosPorCodigo[$codigo] = Cultivo::query()->where('codigo', $codigo)->first();
            }

            $cultivo = $this->cultivosPorCodigo[$codigo];
            if ($cultivo) {
                return $cultivo;
            }
        }

        $nombre = $this->limpiarTexto($cultivoNombre);
        if ($nombre !== '') {
            if (! array_key_exists($nombre, $this->cultivosPorNombre)) {
                $this->cultivosPorNombre[$nombre] = Cultivo::query()->where('nombre', $nombre)->first();
            }

            return $this->cultivosPorNombre[$nombre];
        }

        return null;
    }

    protected function buscarCoincidenciasCultivo(mixed $cultivoId, mixed $cultivoCodigo, mixed $cultivoNombre): array
    {
        return [
            'cultivo_id' => $this->resolverCultivo($cultivoId, null, null),
            'cultivo_codigo' => $this->resolverCultivo(null, $cultivoCodigo, null),
            'cultivo_nombre' => $this->resolverCultivo(null, null, $cultivoNombre),
        ];
    }

    protected function resolverCultivoPrincipal(mixed $cultivoId, mixed $cultivoCodigo, mixed $cultivoNombre): ?Cultivo
    {
        $cultivoPorId = $this->resolverCultivo($cultivoId, null, null);
        if ($cultivoPorId instanceof Cultivo) {
            return $cultivoPorId;
        }

        $coincidenciasCultivo = $this->buscarCoincidenciasCultivo(null, $cultivoCodigo, $cultivoNombre);

        if ($this->hayConflictoEntreCoincidenciasCultivo($coincidenciasCultivo)) {
            return null;
        }

        return $this->resolverCultivoDesdeCoincidencias($coincidenciasCultivo);
    }

    protected function hayConflictoEntreCoincidenciasCultivo(array $coincidenciasCultivo): bool
    {
        $ids = collect($coincidenciasCultivo)
            ->filter(fn ($cultivo) => $cultivo instanceof Cultivo)
            ->map(fn (Cultivo $cultivo) => (int) $cultivo->id)
            ->unique()
            ->values();

        return $ids->count() > 1;
    }

    protected function resolverCultivoDesdeCoincidencias(array $coincidenciasCultivo): ?Cultivo
    {
        foreach (['cultivo_id', 'cultivo_codigo', 'cultivo_nombre'] as $origen) {
            $cultivo = $coincidenciasCultivo[$origen] ?? null;
            if ($cultivo instanceof Cultivo) {
                return $cultivo;
            }
        }

        return null;
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

    protected function filterPersistedColumns(string $table, array $payload): array
    {
        if (! isset($this->columnasPorTabla[$table])) {
            $this->columnasPorTabla[$table] = array_flip(Schema::getColumnListing($table));
        }

        return array_intersect_key($payload, $this->columnasPorTabla[$table]);
    }

    protected function resolverSemanaCabecera(array $detalles): int
    {
        $semanas = collect($detalles)
            ->pluck('semana')
            ->map(fn ($semana) => (int) $semana)
            ->filter(fn ($semana) => $semana >= 0 && $semana <= 52)
            ->values();

        return (int) ($semanas->min() ?: 1);
    }

    protected function resolverSemanaFila(Collection $row): int
    {
        $semanaRaw = $this->obtenerValor($row, ['semana', 'semana_cultivo', 'numero_semana', 'semana_plan']);
        $semana = $this->extraerNumeroSemana($semanaRaw);

        if ($semana >= 0 && $semana <= 52) {
            return $semana;
        }

        $fechaSemanaRaw = $this->obtenerValor($row, ['fecha_semana', 'semana_fecha']);
        $fechaSemana = $this->normalizarFecha($fechaSemanaRaw);
        if ($fechaSemana) {
            try {
                return max(1, min(52, (int) Carbon::parse($fechaSemana)->isoWeek()));
            } catch (\Throwable $error) {
                return 0;
            }
        }

        return 0;
    }

    protected function extraerNumeroSemana(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        if (is_numeric($value)) {
            return (int) $this->toFloat($value);
        }

        $texto = trim((string) $value);
        if ($texto === '') {
            return 0;
        }

        if (preg_match('/(\d{1,2})/', $texto, $match) === 1) {
            return (int) $match[1];
        }

        return 0;
    }

}
