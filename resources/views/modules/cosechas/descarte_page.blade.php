@extends('layouts.main')

@section('titulo', $titulo ?? 'Baja por descarte')

@section('contenido')
<main id="main" class="main">
    <div class="pagetitle d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h1>Baja por Descarte</h1>
            <p class="mb-0 text-muted">Registra merma o descarte sin mezclarlo con ventas facturadas.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('cosecha.facturas', $cosecha) }}" class="btn btn-outline-success btn-sm">
                <i class="fa fa-file-invoice-dollar me-1"></i> Ir a facturas
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
                                    <i class="fa-solid fa-triangle-exclamation"></i>
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
                            <span class="factura-metric-label">Descarte acumulado</span>
                            <strong class="text-warning">{{ agro_number($cosecha->cantidad_descarte ?? $cosecha->descarte ?? 0, 2) }} {{ $cosecha->unidad_medida }}</strong>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm factura-form-card">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                            <div>
                                <h6 class="mb-1 fw-bold">Registrar Baja por Descarte</h6>
                                <small class="text-muted">Esta acción reduce el saldo disponible y aumenta el descarte acumulado.</small>
                            </div>
                            <span class="badge text-bg-warning border">Disponible para baja: {{ agro_number($cosecha->cantidad_disponible, 2) }} {{ $cosecha->unidad_medida }}</span>
                        </div>

                        <form action="{{ route('cosecha.descarte.store', $cosecha) }}" method="POST">
                            @csrf

                            <div class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">Cantidad a descartar</label>
                                    <input type="number" step="0.01" min="0.01" max="{{ $cosecha->cantidad_disponible }}"
                                           name="cantidad_descarte" class="form-control" value="{{ old('cantidad_descarte') }}" required>
                                </div>

                                <div class="col-md-7">
                                    <label class="form-label small fw-bold">Motivo del descarte</label>
                                    <input type="text" name="motivo_descarte" class="form-control"
                                           placeholder="Ejemplo: daño, pudrición, merma, descarte de calidad" value="{{ old('motivo_descarte') }}" required>
                                </div>

                                <div class="col-md-2 d-grid">
                                    <button type="submit" class="btn btn-outline-warning">
                                        <i class="fa-solid fa-triangle-exclamation me-1"></i> Dar de baja
                                    </button>
                                </div>
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
    color: #b7791f;
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
@endsection