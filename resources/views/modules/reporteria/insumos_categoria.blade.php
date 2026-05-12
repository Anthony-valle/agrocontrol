@extends('layouts.main')

@section('contenido')
<main id="main" class="main">
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 fw-bold">
                <i class="fa-solid fa-flask-vial me-2 text-primary"></i>Reporte de Insumos por Categoría
            </h5>
        </div>
        <div class="card-body">
            <form id="formCategoriaInsumo" class="row g-2 align-items-end mb-4">
                <div class="col-12 col-md-6">
                    <label for="categoria_id" class="form-label fw-bold">Selecciona una categoría</label>
                    <select id="categoria_id" name="categoria_id" class="form-select select2" required>
                        <option value="">-- Selecciona --</option>
                        @foreach($categorias as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3 d-grid">
                    <button type="button" class="btn btn-success btn-lg" onclick="cargarReporteCategoria()">
                        <i class="fa-solid fa-chart-area me-1"></i>Ver reporte
                    </button>
                </div>
            </form>
            <div id="reporteCategoriaContainer"></div>
        </div>
    </div>
</main>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if(window.jQuery) {
        $('#categoria_id').select2({
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

function cargarReporteCategoria() {
    const catId = document.getElementById('categoria_id').value;
    if (!catId) return alert('Selecciona una categoría.');
    fetch(`/reporteria/insumos-categoria/${catId}`)
        .then(res => res.text())
        .then(html => {
            const container = document.getElementById('reporteCategoriaContainer');
            container.innerHTML = html;
            ejecutarScriptsEnContenedor(container);
        });
}
</script>
@endsection
