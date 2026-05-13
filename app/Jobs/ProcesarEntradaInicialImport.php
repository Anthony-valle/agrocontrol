<?php

namespace App\Jobs;

use App\Imports\EntradaInicialImport;
use App\Models\Notificaciones;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ProcesarEntradaInicialImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 7200;

    public function __construct(
        public readonly string $rutaArchivo,
        public readonly int $userId,
        public readonly ?int $sucursalId,
        public readonly ?int $empresaId,
        public readonly string $nombreOriginal
    ) {
    }

    public function handle(): void
    {
        $import = new EntradaInicialImport($this->userId, $this->sucursalId, $this->empresaId);

        Excel::import($import, Storage::disk('local')->path($this->rutaArchivo));

        $stats = $import->getStats();
        $errores = $import->getErrores();

        $mensaje = sprintf(
            'Importacion completada para %s. Filas importadas: %d. Filas con error: %d.',
            $this->nombreOriginal,
            (int) ($stats['filas_importadas'] ?? 0),
            (int) ($stats['filas_error'] ?? 0)
        );

        if (!empty($errores)) {
            $mensaje .= ' Primer error: ' . (string) $errores[0];
        }

        $this->crearNotificacion($mensaje, 'importacion_entrada_inicial');
        Storage::disk('local')->delete($this->rutaArchivo);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Fallo la importacion en cola de entrada inicial.', [
            'ruta_archivo' => $this->rutaArchivo,
            'nombre_original' => $this->nombreOriginal,
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
        ]);

        $this->crearNotificacion(
            'La importacion de entrada inicial fallo para ' . $this->nombreOriginal . '. Error: ' . $exception->getMessage(),
            'importacion_entrada_inicial_error'
        );

        Storage::disk('local')->delete($this->rutaArchivo);
    }

    private function crearNotificacion(string $mensaje, string $tipo): void
    {
        Notificaciones::create([
            'empresa_id' => $this->empresaId,
            'cultivo_id' => null,
            'user_id' => $this->userId,
            'mensaje' => $mensaje,
            'tipo' => $tipo,
            'leido' => false,
        ]);
    }
}