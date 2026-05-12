<?php

namespace App\Http\Controllers;

use App\Exports\LoteCultivosReportExport;
use App\Models\Lote;
use App\Models\Cultivo;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReporteriaLoteController extends Controller
{
    // Vista principal de selección de lote
    public function index()
    {
        $lotes = Lote::orderBy('nombre')->get();
        return view('modules.reporteria.lote_cultivos', compact('lotes'));
    }

    // Cargar reporte de un lote específico (AJAX)
    public function show(int $loteId)
    {
        $data = $this->reporteData($loteId);

        return view('modules.reporteria.partials.lote_cultivos_reporte', $data);
    }

    public function exportExcel(int $loteId)
    {
        $data = $this->reporteData($loteId);
        $fileName = 'reporte_lote_' . $data['lote']->id . '_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new LoteCultivosReportExport($data['lote'], $data['cultivosData']), $fileName);
    }

    public function exportPdf(int $loteId)
    {
        $data = $this->reporteData($loteId);

        $pdf = Pdf::loadView('modules.reporteria.lote_cultivos_pdf', $data);

        return $pdf->download('reporte_lote_' . $data['lote']->id . '_' . now()->format('Ymd_His') . '.pdf');
    }

    private function reporteData(int $loteId): array
    {
        $lote = Lote::findOrFail($loteId);
        $cultivos = Cultivo::where('lotes_id', $lote->id)->get();
        $areaTotal = (float) ($lote->area ?? 0);

        $cultivosActivos = $cultivos->filter(function ($cultivo) {
            return strcasecmp((string) ($cultivo->estado ?? 'Activo'), 'Activo') === 0;
        })->values();

        $cultivosCerradosData = $cultivos->reject(function ($cultivo) {
            return strcasecmp((string) ($cultivo->estado ?? 'Activo'), 'Activo') === 0;
        })->map(function ($cultivo) {
            return [
                'nombre' => $cultivo->nombre,
                'variedad' => $cultivo->variedad,
                'hectareas' => (float) ($cultivo->hectareas ?? 0),
                'estado' => $cultivo->estado ?: 'Cerrado',
            ];
        })->values();

        $cultivosData = $cultivosActivos->map(function ($cultivo) use ($areaTotal) {
            $areaCultivo = (float) ($cultivo->hectareas ?? 0);
            $porcentaje = $areaTotal > 0 ? round(($areaCultivo / $areaTotal) * 100, 2) : 0;

            return [
                'nombre' => $cultivo->nombre,
                'variedad' => $cultivo->variedad,
                'hectareas' => $areaCultivo,
                'porcentaje' => $porcentaje,
            ];
        });

        $areaOcupada = (float) $cultivosData->sum('hectareas');
        $areaDisponible = max($areaTotal - $areaOcupada, 0);
        $poligono = $lote->poligono ? json_decode($lote->poligono, true) : [];

        return compact('lote', 'cultivosData', 'cultivosCerradosData', 'areaTotal', 'areaOcupada', 'areaDisponible', 'poligono');
    }
}
