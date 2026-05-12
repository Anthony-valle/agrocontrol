@extends('layouts.main')

@section('contenido')
<main id="main" class="main">
    <div class="pagetitle mb-3">
        <h1 class="mb-1">Reporte de Lote y Cultivos</h1>
        <p class="text-muted mb-0">Consulta el comportamiento de cada lote con un panel claro y rápido.</p>
    </div>

    <div class="card reporte-shell border-0 shadow-sm overflow-hidden">
        <div class="card-header reporte-head py-3 px-4 d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 fw-bold text-white">
                <i class="fa-solid fa-warehouse me-2"></i>Panel de consulta
            </h5>
            <span class="badge rounded-pill bg-light text-dark fw-semibold px-3 py-2">Lotes activos</span>
        </div>

        <div class="card-body p-4">
            <div class="reporte-filter p-3 p-lg-4 mb-4">
                <form id="formLoteReporte">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-lg-8">
                            <label for="lote_id" class="form-label fw-semibold text-secondary mb-2">Selecciona un lote</label>
                            <select id="lote_id" name="lote_id" class="form-select select2 form-select-lg" required>
                                <option value="">-- Selecciona --</option>
                                @foreach($lotes as $lote)
                                    <option value="{{ $lote->id }}">{{ $lote->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-lg-4 d-grid">
                            <button id="btnVerReporte" type="button" class="btn btn-reporte btn-lg" onclick="cargarReporteLote()">
                                <i class="fa-solid fa-chart-area me-2"></i>Ver reporte
                            </button>
                        </div>
                        <div class="col-12 d-flex flex-wrap gap-2 mt-1">
                            <a id="btnLoteExcel" href="#" class="btn btn-success btn-sm disabled" aria-disabled="true">
                                <i class="bi bi-file-earmark-excel me-1"></i>Excel
                            </a>
                            <a id="btnLotePdf" href="#" class="btn btn-danger btn-sm disabled" aria-disabled="true">
                                <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <div id="reporteLoteContainer" class="reporte-result"></div>
        </div>
    </div>
</main>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
    .reporte-shell {
        border-radius: 1rem;
        background: #f7f9fc;
    }

    .reporte-head {
        background: linear-gradient(130deg, #0f766e 0%, #0ea5a4 55%, #14b8a6 100%);
        border-bottom: 0;
    }

    .reporte-filter {
        border: 1px solid #dbe3ef;
        border-radius: 0.9rem;
        background: #ffffff;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
    }

    .btn-reporte {
        background: #0b7a75;
        border-color: #0b7a75;
        color: #fff;
        font-weight: 600;
        letter-spacing: 0.2px;
    }

    .btn-reporte:hover,
    .btn-reporte:focus {
        background: #096864;
        border-color: #096864;
        color: #fff;
    }

    .reporte-result {
        min-height: 120px;
    }

    .reporte-loading {
        border: 1px dashed #cbd5e1;
        border-radius: 0.9rem;
        background: #fff;
        padding: 1.25rem;
    }

    .select2-container--bootstrap-5 .select2-selection {
        border-radius: 0.75rem;
        border-color: #cfd8e3;
    }

    .select2-container--bootstrap-5.select2-container--focus .select2-selection {
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.12);
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if(window.jQuery) {
        $('#lote_id').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: '-- Selecciona --',
            allowClear: true
        });
    }
});

function ejecutarScriptsEnContenedor(container) {
    const scripts = Array.from(container.querySelectorAll('script'));

    scripts.forEach((script) => {
        const nuevoScript = document.createElement('script');

        Array.from(script.attributes).forEach((attr) => {
            nuevoScript.setAttribute(attr.name, attr.value);
        });

        if (!script.src) {
            nuevoScript.textContent = script.textContent;
        }

        script.parentNode.replaceChild(nuevoScript, script);
    });
}

function cargarReporteLote() {
    const loteId = document.getElementById('lote_id').value;
    const container = document.getElementById('reporteLoteContainer');
    const btn = document.getElementById('btnVerReporte');
    const btnExcel = document.getElementById('btnLoteExcel');
    const btnPdf = document.getElementById('btnLotePdf');

    if (!loteId) {
        alert('Seleccione un lote.');
        return;
    }

    btnExcel.href = `/reporteria/lote-cultivos/${loteId}/excel`;
    btnPdf.href = `/reporteria/lote-cultivos/${loteId}/pdf`;
    btnExcel.classList.remove('disabled');
    btnPdf.classList.remove('disabled');
    btnExcel.removeAttribute('aria-disabled');
    btnPdf.removeAttribute('aria-disabled');

    btn.disabled = true;
    container.innerHTML = `
        <div class="reporte-loading d-flex align-items-center gap-3">
            <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
            <div>
                <div class="fw-semibold">Cargando reporte...</div>
                <small class="text-muted">Procesando información del lote seleccionado.</small>
            </div>
        </div>
    `;

    fetch(`/reporteria/lote-cultivos/${loteId}`)
        .then(res => res.text())
        .then(html => {
            container.innerHTML = html;
            ejecutarScriptsEnContenedor(container);
        })
        .catch(() => {
            container.innerHTML = `
                <div class="alert alert-danger mb-0" role="alert">
                    No se pudo cargar el reporte. Intenta nuevamente.
                </div>
            `;
        })
        .finally(() => {
            btn.disabled = false;
        });
}
</script>
@endsection
