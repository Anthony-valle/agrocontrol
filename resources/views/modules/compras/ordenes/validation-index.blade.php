@extends('layouts.main')

@section('titulo', $titulo)

@section('contenido')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>{{ $titulo }}</h1>
        <p class="text-muted mb-0">Pantalla separada para revisar y validar lo recibido en cada orden.</p>
    </div>

    <section class="section">
        <div class="card agro-table-card">
            <div class="card-body pt-3">
                <div class="agro-table-toolbar">
                    <h5 class="card-title mb-0">Órdenes para validación</h5>
                    <div class="agro-table-toolbar-group">
                        <form method="GET" action="{{ route('compras.ordenes.validation.index') }}" class="agro-toolbar-inline-form">
                            <div class="agro-toolbar-records">
                                <select name="per_page" class="form-select form-select-sm agro-toolbar-select" onchange="this.form.submit()">
                                    <option value="10" @selected($perPage === 10)>10</option>
                                    <option value="20" @selected($perPage === 20)>20</option>
                                    <option value="50" @selected($perPage === 50)>50</option>
                                    <option value="100" @selected($perPage === 100)>100</option>
                                </select>
                                <small>registros</small>
                            </div>
                            <select name="recepcion_estado" class="form-select form-select-sm agro-toolbar-select" onchange="this.form.submit()">
                                @foreach($estadosRecepcion as $estadoItem)
                                    <option value="{{ $estadoItem }}" @selected($recepcionEstado === $estadoItem)>
                                        {{ ucfirst(str_replace('_', ' ', $estadoItem)) }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                    <div class="agro-toolbar-actions">
                        <a href="{{ route('compras.ordenes.report') }}" class="btn btn-primary btn-sm"><i class="fa fa-filter me-1"></i> Reporte O.C.</a>
                    </div>
                </div>

                <div class="table-responsive agro-table-shell">
                    <table class="table table-hover table-sm align-middle agro-table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>O.C.</th>
                                <th>Recepción</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($ordenes as $orden)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $orden->codigo }}</div>
                                        <span class="agro-table-meta">{{ $orden->proveedor }}</span>
                                        <span class="agro-table-meta">{{ $orden->fecha_emision?->format('d/m/Y') }}</span>
                                        <span class="agro-table-meta">{{ $orden->solicitudCompra->solicitante->usuario ?? 'N/D' }}</span>
                                    </td>
                                    <td>
                                        <span class="fw-semibold">{{ $orden->recepcion_estado_label }}</span>
                                        <span class="agro-table-meta">Solicitud {{ $orden->solicitudCompra->codigo ?? 'N/D' }}</span>
                                    </td>
                                    <td>
                                        <a href="{{ route('compras.ordenes.validation.show', $orden) }}" class="btn btn-sm btn-dark px-2 py-1">
                                            {{ filled($orden->recepcion_estado) ? 'Ver' : 'Validar' }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">No hay órdenes para ese filtro.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($ordenes->hasPages())
                    <div class="mt-3">
                        {{ $ordenes->onEachSide(1)->links('vendor.pagination.bootstrap-5-notext') }}
                    </div>
                @endif
            </div>
        </div>
    </section>
</main>
@endsection