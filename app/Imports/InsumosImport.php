<?php

namespace App\Imports;

use App\Models\Bodega;
use App\Models\InventarioBodega;
use App\Models\Insumo;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class InsumosImport implements ToModel, WithHeadingRow, WithChunkReading
{
    protected int $user_id;
    protected ?int $sucursal_id;
    protected ?int $empresa_id;

    public function __construct(int $user_id, ?int $sucursal_id, ?int $empresa_id)
    {
        $this->user_id = $user_id;
        $this->sucursal_id = $sucursal_id;
        $this->empresa_id = $empresa_id;
    }

    public function model(array $row): ?Insumo
    {
        $codigo = trim((string) ($row['codigo'] ?? ''));
        $nombre = trim((string) ($row['nombre'] ?? ''));
        $unidad = trim((string) ($row['unidad_medida'] ?? ($row['unidad'] ?? '')));
        $categoria = trim((string) ($row['categoria_nombre'] ?? ''));
        $ingrediente = trim((string) ($row['ingrediente_activo'] ?? ''));

        $stock_minimo = isset($row['stock_minimo']) && trim((string) $row['stock_minimo']) !== ''
            ? (float) trim((string) $row['stock_minimo'])
            : 0;

        $estado = isset($row['estado']) && trim((string) $row['estado']) !== ''
            ? (int) trim((string) $row['estado'])
            : 1;

        if (empty($codigo) || empty($nombre) || empty($unidad)) {
            return null;
        }

        if ($categoria === '') {
            throw new \InvalidArgumentException('La columna categoria_nombre es obligatoria y solo acepta Fertilizante o Fitosanitario.');
        }

        $categoriaNormalizada = $this->normalizarCategoria($categoria);
        if ($categoriaNormalizada === null) {
            throw new \InvalidArgumentException('Categoria invalida: ' . $categoria . '. Solo se permite Fertilizante o Fitosanitario.');
        }

        $insumo = Insumo::updateOrCreate(
            ['codigo' => $codigo, 'sucursal_id' => $this->sucursal_id],
            [
                'empresa_id' => $this->empresa_id,
                'nombre' => $nombre,
                'ingrediente_activo' => $ingrediente ?: null,
                'categoria_nombre' => $categoriaNormalizada,
                'unidad_medida' => $unidad,
                'stock_minimo' => $stock_minimo,
                'estado' => $estado,
                'created_by' => $this->user_id,
                'updated_by' => $this->user_id,
            ]
        );

        // Opcional: crear/actualizar inventario inicial por bodega para habilitar consumo inmediato.
        $bodegaId = isset($row['bodega_id']) && trim((string) $row['bodega_id']) !== ''
            ? (int) trim((string) $row['bodega_id'])
            : null;

        $stockInicial = isset($row['stock_inicial']) && trim((string) $row['stock_inicial']) !== ''
            ? (float) trim((string) $row['stock_inicial'])
            : null;

        if ($bodegaId && $stockInicial !== null) {
            $bodega = Bodega::where('id', $bodegaId)
                ->where('sucursal_id', $this->sucursal_id)
                ->first();

            if ($bodega) {
                $numeroLote = trim((string) ($row['numero_lote'] ?? ''));
                if ($numeroLote === '') {
                    $numeroLote = 'IMP-' . $codigo;
                }

                $costoPromedio = isset($row['costo_promedio']) && trim((string) $row['costo_promedio']) !== ''
                    ? (float) trim((string) $row['costo_promedio'])
                    : 0;

                InventarioBodega::updateOrCreate(
                    [
                        'insumo_id' => $insumo->id,
                        'bodega_id' => $bodega->id,
                        'numero_lote' => $numeroLote,
                    ],
                    [
                        'empresa_id' => $this->empresa_id,
                        'stock_actual' => $stockInicial,
                        'costo_promedio' => $costoPromedio,
                        'fecha_fabricacion' => !empty($row['fecha_fabricacion']) ? $row['fecha_fabricacion'] : null,
                        'fecha_vencimiento' => !empty($row['fecha_vencimiento']) ? $row['fecha_vencimiento'] : null,
                    ]
                );
            }
        }

        return $insumo;
    }

    private function normalizarCategoria(string $categoria): ?string
    {
        $categoria = strtolower(trim($categoria));

        if ($categoria === 'fertilizante') {
            return 'Fertilizante';
        }

        if ($categoria === 'fitosanitario') {
            return 'Fitosanitario';
        }

        return null;
    }

    public function chunkSize(): int
    {
        return 250;
    }
}