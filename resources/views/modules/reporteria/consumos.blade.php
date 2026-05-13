@extends('layouts.main')

@section('titulo', 'Reportería de Consumos')

@section('contenido')
<main id="main" class="main">
    <style>
        .reporte-consumo-detalle-lista {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            min-width: 280px;
        }

        .reporte-consumo-detalle-item {
            font-size: 0.92rem;
            line-height: 1.35;
        }

        .reporte-consumo-detalle-item strong {
            color: #1b4332;
        }

        .reporte-consumo-detalle-mas {
            font-size: 0.85rem;
            color: #6c757d;
        }
    </style>

    <div class="pagetitle">
        <h1>Reportería de Consumos</h1>
        <p class="text-muted mb-0">Filtra por lote, cultivo y fechas para descargar el historial completo en Excel o PDF.</p>
    </div>

    <section class="section">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-4">
                <form method="GET" action="{{ route('reporteria.consumos') }}" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Lote</label>
                        <select name="lote_id" id="lote_id" class="form-select">
                            <option value="">Todos</option>
                            @foreach($lotes as $lote)
                                <option value="{{ $lote->id }}" {{ request('lote_id') == $lote->id ? 'selected' : '' }}>{{ $lote->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-bold">Cultivo</label>
                        <select name="cultivo_id" id="cultivo_id" class="form-select">
                            <option value="">Todos</option>
                            @foreach($cultivos as $cultivo)
                                <option value="{{ $cultivo->id }}" data-lote="{{ $cultivo->lotes_id }}" {{ request('cultivo_id') == $cultivo->id ? 'selected' : '' }}>
                                    {{ $cultivo->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-bold">Fecha inicio</label>
                        <input type="date" name="fecha_inicio" value="{{ request('fecha_inicio') }}" class="form-control">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-bold">Fecha fin</label>
                        <input type="date" name="fecha_fin" value="{{ request('fecha_fin') }}" class="form-control">
                    </div>

                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                    </div>

                    <div class="col-12 d-flex flex-wrap gap-2">
                        <a href="{{ route('reporteria.consumos') }}" class="btn btn-outline-secondary">Limpiar</a>
                        <a href="{{ route('reporteria.consumos.excel', request()->query()) }}" class="btn btn-success">
                            <i class="bi bi-file-earmark-excel me-1"></i>Descargar Excel
                        </a>
                        <a href="{{ route('reporteria.consumos.pdf', request()->query()) }}" class="btn btn-danger">
                            <i class="bi bi-file-earmark-pdf me-1"></i>Descargar PDF
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted text-uppercase fw-bold">Registros</small><h3 class="mt-2 mb-0">{{ agro_number($metricas['registros']) }}</h3></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted text-uppercase fw-bold">Líneas detalle</small><h3 class="mt-2 mb-0">{{ agro_number($metricas['lineas']) }}</h3></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted text-uppercase fw-bold">Total consumo</small><h3 class="mt-2 mb-0 text-warning">{{ agro_number($metricas['total'], 2) }} Lps</h3></div></div></div>
            <div class="col-md-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><small class="text-muted text-uppercase fw-bold">Promedio</small><h3 class="mt-2 mb-0">{{ agro_number($metricas['promedio'], 2) }} Lps</h3></div></div></div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 pb-0"><h5 class="card-title mb-0">Detalle de consumos</h5></div>
            <div class="card-body pt-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Lote</th>
                            <th>Cultivo</th>
                            <th>Detalle de consumo</th>
                            <th>Líneas</th>
                            <th>Total</th>
                            <th class="text-center">Ver consumo</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($consumos as $consumo)
                            @php
                                $detallesPreview = $consumo->detalles
                                    ->map(function ($detalle) {
                                        $categoria = trim((string) ($detalle->categoria ?? ''));

                                        return [
                                            'categoria' => $categoria !== '' ? $categoria : 'General',
                                        ];
                                    })
                                    ->unique(fn ($item) => strtolower($item['categoria']))
                                    ->values();
                            @endphp
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($consumo->fecha_consumo)->format('d/m/Y') }}</td>
                                <td>{{ $consumo->cultivo->lote->nombre ?? '-' }}</td>
                                <td>{{ $consumo->cultivo->nombre ?? '-' }}</td>
                                <td>
                                    <div class="reporte-consumo-detalle-lista">
                                        @forelse($detallesPreview->take(3) as $detalle)
                                            <div class="reporte-consumo-detalle-item">
                                                <strong>{{ $detalle['categoria'] }}</strong>
                                            </div>
                                        @empty
                                            <div class="reporte-consumo-detalle-item">-</div>
                                        @endforelse

                                        @if($detallesPreview->count() > 3)
                                            <div class="reporte-consumo-detalle-mas">+{{ $detallesPreview->count() - 3 }} detalle(s) más en el show</div>
                                        @endif
                                    </div>
                                </td>
                                <td>{{ $consumo->detalles->count() }}</td>
                                <td class="fw-bold">{{ agro_number($consumo->total, 2) }} Lps</td>
                                <td class="text-center text-nowrap">
                                    <a href="{{ route('consumo.show', $consumo->id) }}" class="btn btn-outline-primary btn-sm" title="Ver detalle del consumo">
                                        <i class="fa-solid fa-eye me-1"></i> Ver
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No hay consumos con los filtros seleccionados.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $consumos->links() }}
                </div>
            </div>
        </div>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const loteSelect = document.getElementById('lote_id');
    const cultivoSelect = document.getElementById('cultivo_id');

    if (!loteSelect || !cultivoSelect) {
        return;
    }

    const allOptions = Array.from(cultivoSelect.options).map(function (option) {
        return {
            value: option.value,
            text: option.text,
            lote: option.getAttribute('data-lote') || '',
            selected: option.selected,
        };
    });

    function renderCultivos() {
        const loteId = loteSelect.value;
        const selected = cultivoSelect.value;

        cultivoSelect.innerHTML = '';
        allOptions.forEach(function (item) {
            if (item.value === '' || loteId === '' || item.lote === loteId) {
                const option = document.createElement('option');
                option.value = item.value;
                option.textContent = item.text;
                option.setAttribute('data-lote', item.lote);
                if (item.value === selected) {
                    option.selected = true;
                }
                cultivoSelect.appendChild(option);
            }
        });
    }

    loteSelect.addEventListener('change', function () {
        renderCultivos();
        if (cultivoSelect.selectedOptions.length && cultivoSelect.selectedOptions[0].getAttribute('data-lote') !== loteSelect.value) {
            cultivoSelect.value = '';
        }
    });

    renderCultivos();
});
</script>
@endsection
