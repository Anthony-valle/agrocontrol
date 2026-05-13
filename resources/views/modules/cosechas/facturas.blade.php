@php
    $renderInModal = $renderInModal ?? true;
@endphp

@if($renderInModal)
<div class="modal-header border-0 text-white" style="background: linear-gradient(135deg, #0f5132 0%, #198754 55%, #44a96f 100%);">
    <div>
        <h5 class="modal-title mb-1">Ventas Facturadas de la Cosecha</h5>
        <small class="opacity-75">Historial de ventas, saldo disponible y exportación de factura.</small>
    </div>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">
@else
<main id="main" class="main">
    <div class="pagetitle d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h1>Factura de Cosecha</h1>
            <p class="mb-0 text-muted">Registro de ventas y control del saldo cosechado.</p>
        </div>
        <a href="{{ route('cosecha.facturadas.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i> Volver a facturadas
        </a>
    </div>

    <section class="section">
        <div class="card">
            <div class="card-body pt-4">
@endif
    @php
        $facturasOrdenadas = $cosecha->facturas->values();
        $totalFacturado = $facturasOrdenadas->sum('total');
        $cantidadVendida = $facturasOrdenadas->sum('cantidad_vendida');
    @endphp

    <div class="factura-hero rounded-4 p-3 p-md-4 mb-4">
        <div class="row g-3 align-items-center">
            <div class="col-md-8">
                <div class="d-flex align-items-center gap-3">
                    <div class="factura-hero-badge">
                        <i class="fa-solid fa-wheat-awn"></i>
                    </div>
                    <div>
                        <div class="small text-uppercase text-muted fw-bold">Cosecha vinculada</div>
                        <h4 class="mb-1">{{ $cosecha->cultivo->nombre ?? '-' }}</h4>
                        <div class="text-muted small">
                            Empresa: {{ $empresa->nombre ?? 'Sin empresa registrada' }}
                            @if(!empty($empresa?->rtn))
                                | RTN: {{ $empresa->rtn }}
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-md-end">
                <img src="{{ $logoEmpresa }}" alt="Logo empresa" class="factura-logo-preview">
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="factura-metric h-100">
                <span class="factura-metric-label">Cantidad Neta</span>
                <strong>{{ agro_number($cosecha->cantidad_neta, 2) }} {{ $cosecha->unidad_medida }}</strong>
            </div>
        </div>
        <div class="col-md-3">
            <div class="factura-metric h-100">
                <span class="factura-metric-label">Vendido</span>
                <strong class="text-danger">{{ agro_number($cantidadVendida, 2) }} {{ $cosecha->unidad_medida }}</strong>
            </div>
        </div>
        <div class="col-md-3">
            <div class="factura-metric h-100">
                <span class="factura-metric-label">Disponible</span>
                <strong class="text-success">{{ agro_number($cosecha->cantidad_disponible, 2) }} {{ $cosecha->unidad_medida }}</strong>
            </div>
        </div>
        <div class="col-md-3">
            <div class="factura-metric h-100">
                <span class="factura-metric-label">Total Facturado</span>
                <strong class="text-primary">{{ agro_number($totalFacturado, 2) }} Lps</strong>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4 factura-form-card">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h6 class="mb-1 fw-bold">Registrar Nueva Factura</h6>
                    <small class="text-muted">Cada venta se tabula como una factura relacionada con esta cosecha.</small>
                </div>
                <span class="badge text-bg-light border">Saldo actual: {{ agro_number($cosecha->cantidad_disponible, 2) }} {{ $cosecha->unidad_medida }}</span>
            </div>

            <form id="formFacturaCosecha" action="{{ route('cosecha.facturas.store', $cosecha) }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="row g-3">

            <div class="col-md-3">
                <label class="form-label small fw-bold">N° Factura</label>
                <input type="text" name="numero_factura" class="form-control" required>
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold">Cliente</label>
                <input type="text" name="cliente" class="form-control">
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold">Fecha Venta</label>
                <input type="date" name="fecha_factura" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold">Cantidad Vendida</label>
                <input type="number" step="0.01" min="0.01" max="{{ $cosecha->cantidad_disponible }}"
                       name="cantidad_vendida" id="cantidad_vendida"
                       class="form-control" required>
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold">Precio Unitario</label>
                <input type="number" step="0.01" min="0"
                       name="precio_unitario" id="precio_unitario"
                       class="form-control" required>
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold">Total</label>
                <input type="text" id="total_factura" class="form-control bg-light" readonly>
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold">Archivo</label>
                <input type="file" name="archivo" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold">Observaciones</label>
                <input type="text" name="observaciones" class="form-control">
            </div>

        </div>

        <div class="mt-3 d-flex justify-content-end">
            <button type="submit" class="btn btn-success">
                <i class="fa fa-file-invoice-dollar me-1"></i> Registrar Factura
            </button>
        </div>
    </form>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h6 class="fw-bold mb-1">Historial de Ventas Registradas</h6>
            <small class="text-muted">Detalle de facturas emitidas, archivo adjunto, usuario y saldo restante por movimiento.</small>
        </div>
        <span class="badge bg-dark-subtle text-dark border">{{ $facturasOrdenadas->count() }} facturas registradas</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 factura-table">
            <thead>
                <tr>
                    <th>Factura</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Cantidad</th>
                    <th>Precio</th>
                    <th>Total</th>
                    <th>Saldo</th>
                    <th>Registro</th>
                    <th>Archivo</th>
                    <th>Exportar</th>
                    <th>Accion</th>
                    <th></th>
                </tr>
            </thead>

            <tbody>
                @php $saldo = $cosecha->cantidad_neta; @endphp

                @forelse($facturasOrdenadas as $factura)
                    @php $saldo -= $factura->cantidad_vendida; @endphp

                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $factura->numero_factura }}</div>
                            <small class="text-muted">{{ $factura->observaciones ?: 'Sin observación' }}</small>
                        </td>
                        <td>{{ \Carbon\Carbon::parse($factura->fecha_factura)->format('d/m/Y') }}</td>
                        <td>{{ $factura->cliente ?: '-' }}</td>
                        <td>{{ agro_number($factura->cantidad_vendida, 2) }} {{ $cosecha->unidad_medida }}</td>
                        <td>{{ agro_number($factura->precio_unitario, 2) }} Lps</td>
                        <td class="text-success fw-bold">{{ agro_number($factura->total, 2) }} Lps</td>
                        <td>
                            <span class="badge rounded-pill text-bg-light border">
                                {{ agro_number(max($saldo, 0), 2) }} {{ $cosecha->unidad_medida }}
                            </span>
                        </td>
                        <td>
                            <div>{{ $factura->creador->usuario ?? 'Sistema' }}</div>
                            <small class="text-muted">{{ $factura->created_at?->format('d/m/Y H:i') }}</small>
                        </td>

                        <td>
                            @if($factura->archivo)
                                <a href="{{ asset('storage/' . $factura->archivo) }}" target="_blank"
                                   class="btn btn-outline-secondary btn-sm"
                                   title="Ver archivo adjunto de la factura"
                                   aria-label="Ver archivo adjunto de la factura">
                                    <i class="fa fa-paperclip me-1"></i>
                                    <span class="d-none d-md-inline">Archivo</span>
                                </a>
                            @else
                                -
                            @endif
                        </td>

                        <td>
                            <a href="{{ route('cosecha.facturas.export', $factura) }}" class="btn btn-outline-primary btn-sm">
                                <i class="fa fa-download"></i>
                            </a>
                        </td>

                        <td>
                            <a href="{{ route('cosecha.facturas.edit', $factura) }}" class="btn btn-outline-warning btn-sm me-1" title="Editar factura">
                                <i class="fa fa-pen"></i>
                            </a>
                            <button type="button"
                                    class="btn btn-outline-danger btn-sm btnAnularFacturaVenta"
                                    data-id="{{ $factura->id }}"
                                    data-url="{{ route('cosecha.facturas.destroy', $factura) }}"
                                    data-cosecha="{{ $cosecha->id }}">
                                <i class="fa fa-trash"></i>
                            </button>
                        </td>
                    </tr>

                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            Todavía no hay ventas facturadas para esta cosecha.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

@if($renderInModal)
</div>
@else
            </div>
        </div>
    </section>
</main>
@endif

<!-- =========================
     JS TOTAL AUTOMÁTICO
========================= -->
<style>
.factura-hero {
    background: linear-gradient(180deg, #f3faf5 0%, #ffffff 100%);
    border: 1px solid #d9efe0;
}

.factura-hero-badge {
    width: 54px;
    height: 54px;
    border-radius: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #dff3e6;
    color: #198754;
    font-size: 1.35rem;
}

.factura-logo-preview {
    max-width: 90px;
    max-height: 90px;
    object-fit: contain;
    background: #fff;
    border: 1px solid #dfe3e8;
    border-radius: 16px;
    padding: 10px;
}

.factura-metric {
    background: #fff;
    border: 1px solid #e8ecef;
    border-radius: 18px;
    padding: 16px 18px;
    box-shadow: 0 10px 24px rgba(16, 24, 40, 0.05);
}

.factura-metric-label {
    display: block;
    margin-bottom: 6px;
    font-size: 0.76rem;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #6c757d;
    font-weight: 700;
}

.factura-form-card {
    border-radius: 22px;
    overflow: hidden;
}

.factura-table thead th {
    background: #f4f6f8;
    border-bottom: 0;
    white-space: nowrap;
}
</style>

<script>
function formatearNumero(valor) {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(valor);
}

function mostrarErrorFacturaVenta(error) {
    if (error && error.errors) {
        let mensajes = '<ul style="text-align:left; margin:0; padding-left:18px;">';
        Object.values(error.errors).flat().forEach(msg => {
            mensajes += `<li>${msg}</li>`;
        });
        mensajes += '</ul>';
        Swal.fire({ title: 'Error', html: mensajes, icon: 'error' });
        return;
    }

    Swal.fire('Error', error?.message || 'No se pudo completar la operación.', 'error');
}

function calcularTotal() {
    const cantidadInput = document.getElementById('cantidad_vendida');
    const precioInput = document.getElementById('precio_unitario');
    const totalInput = document.getElementById('total_factura');

    if (!cantidadInput || !precioInput || !totalInput) {
        return;
    }

    let cantidad = parseFloat(cantidadInput.value) || 0;
    let precio = parseFloat(precioInput.value) || 0;

    let total = cantidad * precio;

    totalInput.value = formatearNumero(total);
}

document.getElementById('cantidad_vendida')?.addEventListener('input', calcularTotal);
document.getElementById('precio_unitario')?.addEventListener('input', calcularTotal);
calcularTotal();

document.addEventListener('click', function (event) {
    const button = event.target.closest('.btnAnularFacturaVenta');
    if (!button) {
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    Swal.fire({
        title: 'Anular venta',
        text: 'La cantidad vendida volverá al saldo disponible de la cosecha.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, anular venta',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (!result.isConfirmed) {
            return;
        }

        fetch(button.dataset.url, {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
            .then(async (response) => {
                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    throw data;
                }

                return data;
            })
            .then((data) => {
                Swal.fire('Éxito', data.success || 'Venta anulada correctamente.', 'success')
                    .then(() => {
                        window.location.reload();
                    });
            })
            .catch(mostrarErrorFacturaVenta);
    });
});
</script>