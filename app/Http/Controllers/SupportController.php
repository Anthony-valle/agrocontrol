<?php

namespace App\Http\Controllers;

use App\Models\Bodega;
use App\Models\Categorias;
use App\Models\Consumo;
use App\Models\Cosecha;
use App\Models\CosechaFactura;
use App\Models\Cultivo;
use App\Models\Empresa;
use App\Models\FacturaInventario;
use App\Models\Insumo;
use App\Models\Labore;
use App\Models\Lote;
use App\Models\MovimientoInventario;
use App\Models\PreparacionSueloActividad;
use App\Models\planes_cultivo;
use App\Models\planes_detalles;
use App\Models\Role;
use App\Models\SupportTicket;
use App\Models\Sucursale;
use App\Models\User;
use App\Services\InventarioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use ZipArchive;

class SupportController extends Controller
{
    public function __construct(private readonly InventarioService $inventarioService)
    {
    }

    public function recoveryIndex(Request $request): View
    {
        abort_unless($request->user()?->isSuperUser(), 403);

        $eliminados = collect();

        foreach ($this->recoverableResources() as $resource) {
            if (! $this->recoverableResourceAvailable($resource)) {
                continue;
            }

            $modelClass = $resource['model'];
            $items = $modelClass::onlyTrashed()
                ->orderByDesc('deleted_at')
                ->get()
                ->map(function ($item) use ($resource) {
                    $deletedByUser = null;

                    if (isset($item->deleted_by) && $item->deleted_by) {
                        $deletedByUser = User::withTrashed()->find($item->deleted_by)?->usuario;
                    }

                    return [
                        'id' => $item->id,
                        'tipo' => $resource['label'],
                        'route_key' => $resource['key'],
                        'nombre' => $this->resolveRecoverableValue($item, $resource['name_fields'] ?? ['nombre']),
                        'codigo' => $this->resolveRecoverableValue($item, $resource['code_fields'] ?? ['codigo']),
                        'deleted_at' => $item->deleted_at,
                        'deleted_by' => $deletedByUser,
                        'delete_reason' => $item->delete_reason ?? null,
                    ];
                });

            $eliminados = $eliminados->concat($items);
        }

        $eliminados = $eliminados
            ->sortByDesc(fn (array $item) => optional($item['deleted_at'])->timestamp ?? 0)
            ->values();

        return view('soporte.recuperar', compact('eliminados'));
    }

    public function tecnico(Request $request): View
    {
        $user = $request->user();
        abort_unless($user, 403);

        $ticketsQuery = SupportTicket::query()
            ->with(['usuario', 'atendidoPor'])
            ->orderByDesc('id');

        if (! $user->isSuperUser()) {
            $ticketsQuery->where('user_id', $user->id);
        }

        $titulo = 'Soporte Técnico';
        $tickets = $ticketsQuery->get();

        return view('modules.soporte.tecnico', compact('titulo', 'tickets'));
    }

    public function index(Request $request): View
    {
        abort_unless($request->user()?->isSuperUser(), 403);

        $backupsDisk = Storage::disk('local');
        $backupPath = 'backups';

        if (! $backupsDisk->exists($backupPath)) {
            $backupsDisk->makeDirectory($backupPath);
        }

        $files = collect($backupsDisk->files($backupPath))
            ->filter(fn ($file) => str_ends_with(strtolower($file), '.zip'))
            ->map(function ($file) use ($backupsDisk) {
                return [
                    'file' => basename($file),
                    'path' => $file,
                    'size' => $backupsDisk->size($file),
                    'last_modified' => $backupsDisk->lastModified($file),
                ];
            })
            ->sortByDesc('last_modified')
            ->values();

        return view('modules.soporte.index', compact('files'));
    }

    public function storeTechnicalRequest(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 403);

        $validated = $request->validate([
            'asunto' => 'required|string|max:120',
            'prioridad' => 'required|in:baja,media,alta',
            'descripcion' => 'required|string|max:3000',
        ]);

        SupportTicket::create([
            'empresa_id' => $this->resolveEmpresaId($user),
            'user_id' => $user->id,
            'asunto' => $validated['asunto'],
            'prioridad' => $validated['prioridad'],
            'descripcion' => $validated['descripcion'],
            'estado' => 'pendiente',
        ]);

        return redirect()
            ->route('soporte.tecnico.index')
            ->with('success', 'Solicitud de soporte enviada correctamente.');
    }

    public function updateTechnicalRequest(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->isSuperUser(), 403);

        $validated = $request->validate([
            'estado' => 'required|in:pendiente,en_proceso,resuelto,cerrado',
            'respuesta' => 'nullable|string|max:3000',
        ]);

        $ticket->update([
            'estado' => $validated['estado'],
            'respuesta' => $validated['respuesta'] ?? null,
            'atendido_por' => $user->id,
            'atendido_en' => now(),
        ]);

        return redirect()
            ->route('soporte.tecnico.index')
            ->with('success', 'Solicitud de soporte actualizada correctamente.');
    }

    public function createBackup(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isSuperUser(), 403);

        // Evita corte por timeout en respaldos grandes ejecutados desde web.
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');

        $timestamp = now()->format('Ymd_His');
        $backupDir = storage_path('app/backups');
        $zipFileName = "agrocontrol_backup_{$timestamp}.zip";
        $zipPath = $backupDir . DIRECTORY_SEPARATOR . $zipFileName;

        File::ensureDirectoryExists($backupDir);

        $sqlDumpFile = $this->buildSqlDumpFile();

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'No se pudo crear el archivo de respaldo.');
        }

        try {
            $this->addProjectFilesToZip($zip);
            $this->addReportSnapshotToZip($zip);
            $zip->addFile($sqlDumpFile, 'database_backup.sql');
            $zip->addFromString('README_BACKUP.txt', $this->backupReadme());
            $zip->close();
        } finally {
            if (File::exists($sqlDumpFile)) {
                File::delete($sqlDumpFile);
            }
        }

        return back()
            ->with('success', 'Backup completo generado correctamente.')
            ->with('backup_file', $zipFileName)
            ->with('backup_generated_at', now()->format('d/m/Y H:i:s'));
    }

    public function downloadBackup(Request $request, string $file)
    {
        abort_unless($request->user()?->isSuperUser(), 403);

        $safeFile = basename($file);
        if (! str_ends_with(strtolower($safeFile), '.zip')) {
            abort(404);
        }

        $path = storage_path('app/backups/' . $safeFile);
        if (! File::exists($path)) {
            abort(404);
        }

        return response()->download($path);
    }

    public function restoreDeleted(Request $request, string $tipo, int $id): RedirectResponse
    {
        abort_unless($request->user()?->isSuperUser(), 403);

        $resource = collect($this->recoverableResources())
            ->firstWhere('key', $tipo);

        abort_unless($resource && $this->recoverableResourceAvailable($resource), 404);

        $modelClass = $resource['model'];
        $record = $modelClass::onlyTrashed()->findOrFail($id);

        match ($resource['key'] ?? '') {
            'planes-cultivo' => $this->restorePlan($record->id),
            'consumos' => $this->inventarioService->restaurarConsumo($record->id),
            'cosecha-facturas' => $this->restoreCosechaFactura($record->id),
            'facturas-inventario' => $this->restoreFacturaInventario($record->id),
            default => $record->restore(),
        };

        return redirect()
            ->route('soporte.recuperar.index')
            ->with('success', 'Registro restaurado correctamente.');
    }

    private function addProjectFilesToZip(ZipArchive $zip): void
    {
        $basePath = base_path();

        $excluded = [
            'storage\\app\\backups',
            'storage\\framework\\cache',
            'storage\\framework\\sessions',
            'storage\\framework\\views',
            'storage\\logs',
            'vendor',
            'node_modules',
            '.git',
        ];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($basePath, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $fileInfo) {
            if (! $fileInfo->isFile()) {
                continue;
            }

            $absolutePath = $fileInfo->getRealPath();
            if ($absolutePath === false) {
                continue;
            }

            $relativePath = ltrim(str_replace($basePath, '', $absolutePath), DIRECTORY_SEPARATOR);
            $relativePathWindows = str_replace('/', '\\', $relativePath);

            $skip = false;
            foreach ($excluded as $prefix) {
                if (str_starts_with($relativePathWindows, $prefix)) {
                    $skip = true;
                    break;
                }
            }

            if ($skip) {
                continue;
            }

            $entryName = 'project/' . str_replace('\\', '/', $relativePathWindows);
            $zip->addFile($absolutePath, $entryName);

            // Guarda sin compresión para acelerar backups en servidor local/XAMPP.
            if (method_exists($zip, 'setCompressionName') && defined('ZipArchive::CM_STORE')) {
                $zip->setCompressionName($entryName, ZipArchive::CM_STORE);
            }
        }
    }

    private function addReportSnapshotToZip(ZipArchive $zip): void
    {
        $snapshotPath = 'reporteria_snapshot/';
        $generatedAt = now()->format('Y-m-d H:i:s');
        $datasets = $this->reportDatasets();
        $manifest = [];

        foreach ($datasets as $dataset) {
            $table = $dataset['table'];
            if (! Schema::hasTable($table)) {
                continue;
            }

            $csv = $this->buildTableCsv($table);
            if ($csv === null) {
                continue;
            }

            $fileName = $dataset['file'];
            $zip->addFromString($snapshotPath . $fileName, $csv);

            $manifest[] = [
                'tabla' => $table,
                'archivo' => $fileName,
                'titulo' => $dataset['title'],
                'generado_en' => $generatedAt,
            ];
        }

        $zip->addFromString($snapshotPath . 'LEEME.txt', $this->reportSnapshotReadme($manifest, $generatedAt));
        $zip->addFromString(
            $snapshotPath . 'manifest.json',
            json_encode([
                'generado_en' => $generatedAt,
                'archivos' => $manifest,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    private function reportDatasets(): array
    {
        return [
            ['table' => 'cultivos', 'file' => 'cultivos.csv', 'title' => 'Cultivos'],
            ['table' => 'lotes', 'file' => 'lotes.csv', 'title' => 'Lotes'],
            ['table' => 'insumos', 'file' => 'insumos.csv', 'title' => 'Insumos'],
            ['table' => 'inventario_bodegas', 'file' => 'inventario_bodegas.csv', 'title' => 'Inventario por bodega'],
            ['table' => 'movimiento_inventarios', 'file' => 'movimientos_inventario.csv', 'title' => 'Movimientos de inventario'],
            ['table' => 'consumos', 'file' => 'consumos.csv', 'title' => 'Consumos'],
            ['table' => 'consumo_detalles', 'file' => 'consumo_detalles.csv', 'title' => 'Detalles de consumo'],
            ['table' => 'cosechas', 'file' => 'cosechas.csv', 'title' => 'Cosechas'],
            ['table' => 'planes_cultivos', 'file' => 'planes_cultivo.csv', 'title' => 'Planes de cultivo'],
            ['table' => 'planes_detalles', 'file' => 'planes_detalles.csv', 'title' => 'Detalles de planes'],
            ['table' => 'preparacion_suelo_actividades', 'file' => 'preparacion_suelo_actividades.csv', 'title' => 'Actividades de preparación de suelo'],
            ['table' => 'support_tickets', 'file' => 'soporte_tecnico.csv', 'title' => 'Solicitudes de soporte'],
        ];
    }

    private function buildTableCsv(string $table): ?string
    {
        if (! Schema::hasTable($table)) {
            return null;
        }

        $columns = Schema::getColumnListing($table);
        if ($columns === []) {
            return null;
        }

        $query = DB::table($table);
        if (in_array('id', $columns, true)) {
            $query->orderBy('id');
        }

        $rows = $query->get($columns);
        $stream = fopen('php://temp', 'w+');
        if ($stream === false) {
            return null;
        }

        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, $columns);

        foreach ($rows as $row) {
            $values = [];
            foreach ($columns as $column) {
                $value = $row->{$column} ?? null;
                if (is_bool($value)) {
                    $value = $value ? '1' : '0';
                }
                $values[] = $value;
            }
            fputcsv($stream, $values);
        }

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        return $csv === false ? null : $csv;
    }

    private function buildSqlDumpFile(): string
    {
        $connection = DB::connection();
        $database = $connection->getDatabaseName();
        $dumpPath = storage_path('app/backups/sql_tmp_' . now()->format('Ymd_His_u') . '.sql');
        File::ensureDirectoryExists(dirname($dumpPath));

        $handle = fopen($dumpPath, 'wb');
        if ($handle === false) {
            throw new \RuntimeException('No se pudo crear el archivo temporal SQL.');
        }

        $header = [
            '-- AgroControl SQL Backup',
            '-- Fecha: ' . now()->toDateTimeString(),
            '-- Base de datos: ' . $database,
            'SET FOREIGN_KEY_CHECKS=0;',
            '',
        ];

        fwrite($handle, implode(PHP_EOL, $header) . PHP_EOL);

        $tables = collect($connection->select('SHOW TABLES'))
            ->map(fn ($row) => array_values((array) $row)[0])
            ->values();

        foreach ($tables as $table) {
            $createResult = $connection->select("SHOW CREATE TABLE `{$table}`");
            $createSql = $createResult[0]->{'Create Table'} ?? null;

            if (! $createSql) {
                continue;
            }

            fwrite($handle, PHP_EOL . "-- Tabla: {$table}" . PHP_EOL);
            fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;" . PHP_EOL);
            fwrite($handle, $createSql . ';' . PHP_EOL . PHP_EOL);

            foreach ($connection->table($table)->cursor() as $row) {
                $values = array_map(function ($value) {
                    if ($value === null) {
                        return 'NULL';
                    }

                    return DB::getPdo()->quote((string) $value);
                }, (array) $row);

                fwrite($handle, "INSERT INTO `{$table}` VALUES (" . implode(', ', $values) . ');' . PHP_EOL);
            }

            fwrite($handle, PHP_EOL);
        }

        fwrite($handle, 'SET FOREIGN_KEY_CHECKS=1;' . PHP_EOL);
        fclose($handle);

        return $dumpPath;
    }

    private function backupReadme(): string
    {
        return implode(PHP_EOL, [
            'AgroControl Backup',
            'Este ZIP contiene:',
            '- project/: archivos del sistema (sin vendor y sin caches temporales)',
            '- database_backup.sql: respaldo SQL completo',
            '- reporteria_snapshot/: exportes CSV de cultivos, insumos, inventario, movimientos y otros modulos listos para abrir en Excel',
            '',
            'Para restaurar:',
            '1) Copiar project/ al servidor destino.',
            '2) Ejecutar composer install para reconstruir vendor/.',
            '3) Importar database_backup.sql en MySQL.',
            '4) Ajustar .env y ejecutar php artisan migrate --force si aplica.',
        ]);
    }

    private function reportSnapshotReadme(array $manifest, string $generatedAt): string
    {
        $lines = [
            'Snapshot de reporteria AgroControl',
            'Generado: ' . $generatedAt,
            '',
            'Archivos incluidos en esta carpeta:',
        ];

        foreach ($manifest as $item) {
            $lines[] = '- ' . $item['archivo'] . ' => ' . $item['titulo'];
        }

        $lines[] = '';
        $lines[] = 'Los archivos CSV se pueden abrir directamente en Excel.';

        return implode(PHP_EOL, $lines);
    }

    private function recoverableResources(): array
    {
        return [
            [
                'key' => 'empresas',
                'label' => 'Empresa',
                'model' => Empresa::class,
                'name_fields' => ['nombre'],
                'code_fields' => ['rtn', 'nit'],
            ],
            [
                'key' => 'sucursales',
                'label' => 'Sucursal',
                'model' => Sucursale::class,
                'name_fields' => ['nombre'],
                'code_fields' => ['email'],
            ],
            [
                'key' => 'usuarios',
                'label' => 'Usuario',
                'model' => User::class,
                'name_fields' => ['nombre_completo', 'name', 'usuario'],
                'code_fields' => ['usuario', 'email'],
            ],
            [
                'key' => 'roles',
                'label' => 'Rol',
                'model' => Role::class,
                'name_fields' => ['nombre'],
                'code_fields' => ['descripcion'],
            ],
            [
                'key' => 'categorias',
                'label' => 'Categoría',
                'model' => Categorias::class,
                'name_fields' => ['nombre'],
                'code_fields' => ['nombre'],
            ],
            [
                'key' => 'cultivos',
                'label' => 'Cultivo',
                'model' => Cultivo::class,
                'name_fields' => ['nombre'],
                'code_fields' => ['codigo'],
            ],
            [
                'key' => 'consumos',
                'label' => 'Consumo',
                'model' => Consumo::class,
                'name_fields' => ['fecha_consumo'],
                'code_fields' => ['id'],
            ],
            [
                'key' => 'cosechas',
                'label' => 'Cosecha',
                'model' => Cosecha::class,
                'name_fields' => ['fecha_cosecha'],
                'code_fields' => ['id'],
            ],
            [
                'key' => 'cosecha-facturas',
                'label' => 'Factura de Cosecha',
                'model' => CosechaFactura::class,
                'name_fields' => ['numero_factura', 'cliente'],
                'code_fields' => ['numero_factura'],
            ],
            [
                'key' => 'labores',
                'label' => 'Labor',
                'model' => Labore::class,
                'name_fields' => ['nombre', 'actividad_secundaria'],
                'code_fields' => ['codigo'],
            ],
            [
                'key' => 'lotes',
                'label' => 'Lote',
                'model' => Lote::class,
                'name_fields' => ['nombre'],
                'code_fields' => ['codigo'],
            ],
            [
                'key' => 'insumos',
                'label' => 'Insumo',
                'model' => Insumo::class,
                'name_fields' => ['nombre'],
                'code_fields' => ['codigo'],
            ],
            [
                'key' => 'bodegas',
                'label' => 'Bodega',
                'model' => Bodega::class,
                'name_fields' => ['nombre'],
                'code_fields' => ['codigo'],
            ],
            [
                'key' => 'planes-cultivo',
                'label' => 'Plan de Cultivo',
                'model' => planes_cultivo::class,
                'name_fields' => ['fecha_plan'],
                'code_fields' => ['id'],
            ],
            [
                'key' => 'movimientos-inventario',
                'label' => 'Movimiento de Inventario',
                'model' => MovimientoInventario::class,
                'name_fields' => ['tipo', 'descripcion'],
                'code_fields' => ['referencia', 'id'],
            ],
            [
                'key' => 'facturas-inventario',
                'label' => 'Factura de Inventario',
                'model' => FacturaInventario::class,
                'name_fields' => ['proveedor'],
                'code_fields' => ['numero_lote', 'id'],
            ],
            [
                'key' => 'preparacion-suelo-actividades',
                'label' => 'Actividad de Preparación de Suelo',
                'model' => PreparacionSueloActividad::class,
                'name_fields' => ['nombre'],
                'code_fields' => ['codigo'],
            ],
        ];
    }

    private function recoverableResourceAvailable(array $resource): bool
    {
        $modelClass = $resource['model'] ?? null;

        if (! is_string($modelClass) || ! class_exists($modelClass)) {
            return false;
        }

        $model = new $modelClass();
        $table = $model->getTable();

        return Schema::hasTable($table) && Schema::hasColumn($table, 'deleted_at');
    }

    private function resolveRecoverableValue(object $item, array $fields): ?string
    {
        foreach ($fields as $field) {
            $value = $item->{$field} ?? null;
            if ($value === null) {
                continue;
            }

            $text = trim((string) $value);
            if ($text !== '') {
                return $text;
            }
        }

        return null;
    }

    private function restorePlan(int $planId): void
    {
        $plan = planes_cultivo::onlyTrashed()->findOrFail($planId);
        $plan->restore();
        planes_detalles::onlyTrashed()->where('plan_cultivo_id', $planId)->restore();
    }

    private function restoreCosechaFactura(int $facturaId): void
    {
        $factura = CosechaFactura::onlyTrashed()->findOrFail($facturaId);
        $cosecha = Cosecha::withTrashed()->findOrFail($factura->cosecha_id);

        if ($cosecha->trashed()) {
            $cosecha->restore();
        }

        if ((float) $cosecha->cantidad_disponible < (float) $factura->cantidad_vendida) {
            throw new \RuntimeException('No hay disponibilidad suficiente para restaurar la factura de cosecha #' . $facturaId . '.');
        }

        $cosecha->decrement('cantidad_disponible', $factura->cantidad_vendida);
        $factura->restore();
    }

    private function restoreFacturaInventario(int $facturaId): void
    {
        $factura = FacturaInventario::onlyTrashed()->findOrFail($facturaId);

        if (!empty($factura->movimiento_id)) {
            $movimiento = MovimientoInventario::withTrashed()->find($factura->movimiento_id);
            if ($movimiento && $movimiento->trashed()) {
                $movimiento->restore();
            }
        }

        $factura->restore();
    }

    private function resolveEmpresaId(object $user): ?int
    {
        $empresaId = $user->sucursal->empresa_id ?? null;

        if (! $empresaId && ! empty($user->sucursal_id)) {
            $empresaId = Sucursale::query()
                ->withoutGlobalScopes()
                ->whereKey($user->sucursal_id)
                ->value('empresa_id');
        }

        return $empresaId ? (int) $empresaId : null;
    }
}
