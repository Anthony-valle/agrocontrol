@extends('layouts.main')

@section('titulo', $titulo ?? 'Editar factura de cosecha')

@section('contenido')
<main id="main" class="main">
    <div class="pagetitle d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h1>Editar Factura de Cosecha</h1>
            <p class="mb-0 text-muted">Ajusta datos de la venta sin perder el control del saldo disponible.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('cosecha.facturas', $cosecha) }}" class="btn btn-outline-success btn-sm">
                <i class="fa fa-file-invoice-dollar me-1"></i> Volver a facturas
            </a>
            <a href="{{ route('cosecha.facturadas.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-arrow-left me-1"></i> Volver a facturadas
            </a>
        </div>
    </div>

    <section class="section">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card mb-4">
            <div class="card-body pt-4">
                <div class="factura-hero rounded-4 p-3 p-md-4 mb-4">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center gap-3">
                                <div class="factura-hero-badge">
                                    <i class="fa-solid fa-file-pen"></i>
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
                    <div class="col-md-4">
                        <div class="factura-metric h-100">
                            <span class="factura-metric-label">Factura actual</span>
                            <strong>{{ $factura->numero_factura }}</strong>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="factura-metric h-100">
                            <span class="factura-metric-label">Cantidad editable</span>
                            <strong class="text-success">{{ agro_number($cantidadDisponibleEditable, 2) }} {{ $cosecha->unidad_medida }}</strong>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="factura-metric h-100">
                            <span class="factura-metric-label">Total actual</span>
                            <strong class="text-primary">{{ agro_number($factura->total, 2) }} Lps</strong>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm factura-form-card">
                    <div class="card-body p-4">
                        <form action="{{ route('cosecha.facturas.update', $factura) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">N° Factura</label>
                                    <input type="text" name="numero_factura" class="form-control" value="{{ old('numero_factura', $factura->numero_factura) }}" required>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">Cliente</label>
                                    <input type="text" name="cliente" class="form-control" value="{{ old('cliente', $factura->cliente) }}">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">Fecha Venta</label>
                                    <input type="date" name="fecha_factura" class="form-control" value="{{ old('fecha_factura', optional($factura->fecha_factura)->format('Y-m-d') ?? $factura->fecha_factura) }}" required>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">Cantidad Vendida</label>
                                    <input type="number" step="0.01" min="0.01" max="{{ $cantidadDisponibleEditable }}" name="cantidad_vendida" id="cantidad_vendida" class="form-control" value="{{ old('cantidad_vendida', $factura->cantidad_vendida) }}" required>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">Precio Unitario</label>
                                    <input type="number" step="0.01" min="0" name="precio_unitario" id="precio_unitario" class="form-control" value="{{ old('precio_unitario', $factura->precio_unitario) }}" required>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">Total</label>
                                    <input type="text" id="total_factura" class="form-control bg-light" value="{{ agro_number($factura->total, 2) }}" readonly>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">Reemplazar archivo</label>
                                    <input type="file" name="archivo" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">Observaciones</label>
                                    <input type="text" name="observaciones" class="form-control" value="{{ old('observaciones', $factura->observaciones) }}">
                                </div>
                            </div>

                            <div class="mt-3 d-flex justify-content-end gap-2">
                                <a href="{{ route('cosecha.facturas', $cosecha) }}" class="btn btn-outline-secondary">Cancelar</a>
                                <button type="submit" class="btn btn-warning text-dark">
                                    <i class="fa fa-pen me-1"></i> Guardar cambios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

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
    background: #fff4db;
    color: #9a6700;
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
</style>

<script>
function formatearNumero(valor) {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(valor);
}

function calcularTotal() {
    const cantidadInput = document.getElementById('cantidad_vendida');
    const precioInput = document.getElementById('precio_unitario');
    const totalInput = document.getElementById('total_factura');

    if (!cantidadInput || !precioInput || !totalInput) {
        return;
    }

    const cantidad = parseFloat(cantidadInput.value) || 0;
    const precio = parseFloat(precioInput.value) || 0;

    totalInput.value = formatearNumero(cantidad * precio);
}

document.getElementById('cantidad_vendida')?.addEventListener('input', calcularTotal);
document.getElementById('precio_unitario')?.addEventListener('input', calcularTotal);
calcularTotal();
</script>
@endsection