@extends('layouts.main')

@section('titulo', $titulo ?? 'Facturación de Cosechas')

@section('contenido')
<main id="main" class="main">

    <div class="pagetitle">
        <h1>Facturación de Cosechas</h1>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Registrar facturas y ventas de cosecha</h5>

                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 p-2 bg-light rounded shadow-sm gap-3">
                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                <div class="d-flex align-items-center gap-2">
                                    <select id="customPerPageVentas" class="form-select form-select-sm" style="width: auto;">
                                        <option value="5">5</option>
                                        <option value="30" selected>30</option>
                                        <option value="50">50</option>
                                        <option value="150">150</option>
                                    </select>
                                    <small class="text-muted text-nowrap">registros</small>
                                </div>

                                <div class="input-group input-group-sm" style="max-width: 260px;">
                                    <span class="input-group-text bg-white border-end-0">
                                        <i class="fa-solid fa-magnifying-glass text-muted"></i>
                                    </span>
                                    <input type="text" id="inputBusquedaVentas" class="form-control border-start-0" placeholder="Buscar cosecha o cultivo...">
                                </div>
                            </div>

                            <div class="d-flex flex-wrap gap-2 justify-content-end">
                                <a href="{{ route('cosecha.index') }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="fa-solid fa-arrow-left me-1"></i> Ir a cosechas
                                </a>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-lg-3 col-sm-6">
                                <div class="border rounded p-3 h-100 bg-light-subtle">
                                    <div class="text-muted small text-uppercase fw-bold">Facturas emitidas</div>
                                    <div class="fs-4 fw-bold">{{ agro_number($metricas['total_facturas']) }}</div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <div class="border rounded p-3 h-100 bg-light-subtle">
                                    <div class="text-muted small text-uppercase fw-bold">Total facturado</div>
                                    <div class="fs-4 fw-bold text-success">{{ agro_number($metricas['total_facturado'], 2) }} Lps</div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <div class="border rounded p-3 h-100 bg-light-subtle">
                                    <div class="text-muted small text-uppercase fw-bold">Cantidad vendida</div>
                                    <div class="fs-4 fw-bold">{{ agro_number($metricas['total_vendido'], 2) }}</div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-sm-6">
                                <div class="border rounded p-3 h-100 bg-light-subtle">
                                    <div class="text-muted small text-uppercase fw-bold">Cosechas disponibles</div>
                                    <div class="fs-4 fw-bold text-primary">{{ agro_number($metricas['cosechas_disponibles']) }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive border rounded">
                            <table class="table table-hover w-100 mb-0" id="tablaVentasCosecha">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Cultivo</th>
                                        <th>Fecha</th>
                                        <th>Cantidad</th>
                                        <th>Disponible</th>
                                        <th>Unidad</th>
                                        <th>Precio</th>
                                        <th>Ingreso</th>
                                        <th>Facturado</th>
                                        <th>Estado Venta</th>
                                        <th>Descarte</th>
                                        <th>Observación</th>
                                        <th>Registrado por</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($cosechas as $item)
                                    @php
                                        $cantidadNeta = (float) $item->cantidad_neta;
                                        $cantidadDisponible = (float) $item->cantidad_disponible;
                                        $cantidadVendida = max($cantidadNeta - $cantidadDisponible, 0);
                                        $porcentajeVendido = $cantidadNeta > 0 ? ($cantidadVendida / $cantidadNeta) * 100 : 0;
                                        $descarteAcumulado = max(
                                            (float) ($item->cantidad_descarte ?? 0),
                                            (float) ($item->descarte ?? 0)
                                        );
                                        $motivoDescarte = trim((string) ($item->motivo_descarte ?? ''));
                                        $observacion = trim((string) ($item->observaciones ?? ''));

                                        if ($descarteAcumulado <= 0 && preg_match('/Baja por descarte:\s*([\d.,]+)/u', $observacion, $descarteCoincide) === 1) {
                                            $descarteAcumulado = (float) str_replace(',', '', $descarteCoincide[1]);
                                        }

                                        if ($motivoDescarte === '' && preg_match('/Motivo:\s*(.+)$/u', $observacion, $motivoCoincide) === 1) {
                                            $motivoDescarte = trim($motivoCoincide[1]);
                                        }

                                        $observacionLimpia = preg_replace('/\s*Baja por descarte:.*$/u', '', $observacion) ?? $observacion;
                                        $observacionLimpia = trim($observacionLimpia);

                                        if ($cantidadVendida <= 0) {
                                            $estadoVenta = 'Sin vender';
                                            $estadoClase = 'bg-secondary';
                                        } elseif ($cantidadDisponible <= 0.0001) {
                                            $estadoVenta = 'Vendida';
                                            $estadoClase = 'bg-success';
                                        } else {
                                            $estadoVenta = 'Parcial';
                                            $estadoClase = 'bg-warning text-dark';
                                        }
                                    @endphp
                                    <tr>
                                        <td>{{ $item->id }}</td>
                                        <td>{{ $item->cultivo->nombre }}</td>
                                        <td>{{ $item->fecha_cosecha }}</td>
                                        <td>{{ agro_number($item->cantidad_neta, 2, '.', ',') }}</td>
                                        <td>{{ agro_number($item->cantidad_disponible, 2, '.', ',') }}</td>
                                        <td>{{ $item->unidad_medida }}</td>
                                        <td>{{ $item->precio_venta_unitario !== null ? agro_number($item->precio_venta_unitario, 2, '.', ',') . ' Lps' : 'N/D' }}</td>
                                        <td>{{ $item->precio_venta_unitario !== null ? agro_number($item->cantidad_neta * $item->precio_venta_unitario, 2, '.', ',') . ' Lps' : 'N/D' }}</td>
                                        <td>{{ $item->facturas_sum_total !== null ? agro_number($item->facturas_sum_total, 2, '.', ',') . ' Lps' : '0.00 Lps' }}</td>
                                        <td>
                                            <span class="badge {{ $estadoClase }}">{{ $estadoVenta }}</span>
                                            <div class="small text-muted mt-1">{{ agro_number($porcentajeVendido, 1, '.', ',') }}% vendido</div>
                                        </td>
                                        <td>
                                            @if($descarteAcumulado > 0)
                                                <div class="fw-semibold text-warning-emphasis">{{ agro_number($descarteAcumulado, 2, '.', ',') }} {{ $item->unidad_medida }}</div>
                                                <div class="small text-muted">{{ $motivoDescarte !== '' ? $motivoDescarte : 'Sin motivo registrado' }}</div>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ $observacionLimpia !== '' ? $observacionLimpia : '-' }}</td>
                                        <td>{{ $item->usuario->usuario ?? 'Sistema' }}</td>
                                        <td class="text-nowrap d-flex flex-wrap gap-2">
                                            <a href="{{ route('cosecha.facturas', $item) }}" class="btn btn-success btn-sm" title="Facturar cosecha">
                                                <i class="fa fa-file-invoice-dollar me-1"></i> Facturar
                                            </a>
                                            <a href="{{ route('cosecha.descarte', $item) }}" class="btn btn-outline-warning btn-sm" title="Dar de baja por descarte">
                                                <i class="fa-solid fa-triangle-exclamation me-1"></i> Descarte
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const tabla = document.getElementById('tablaVentasCosecha');
    const filas = tabla && tabla.tBodies.length ? Array.from(tabla.tBodies[0].rows) : [];
    const inputBusqueda = document.getElementById('inputBusquedaVentas');
    const perPageSelect = document.getElementById('customPerPageVentas');

    function mostrarFilas(filasVisibles) {
        filas.forEach((fila) => {
            fila.style.display = 'none';
        });

        filasVisibles.forEach((fila) => {
            fila.style.display = '';
        });
    }

    function filtrarTabla() {
        const texto = (inputBusqueda?.value || '').toLowerCase();
        const porPagina = parseInt(perPageSelect?.value || '30', 10);
        const filtradas = filas.filter((fila) => Array.from(fila.cells).some((cell) => cell.textContent.toLowerCase().includes(texto)));
        mostrarFilas(filtradas.slice(0, porPagina));
    }

    if (inputBusqueda) {
        inputBusqueda.addEventListener('input', filtrarTabla);
    }

    if (perPageSelect) {
        perPageSelect.addEventListener('change', filtrarTabla);
    }

    filtrarTabla();
});
</script>
@endsection