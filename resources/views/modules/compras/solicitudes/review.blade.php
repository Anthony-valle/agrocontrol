@extends('layouts.main')

@section('titulo', $titulo)

@section('contenido')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>{{ $titulo }}</h1>
        <p class="text-muted mb-0">Listado resumido de solicitudes registradas.</p>
    </div>

    <section class="section">
        <div class="card agro-table-card">
            <div class="card-body pt-3">
                <div class="agro-table-toolbar">
                    <h5 class="card-title mb-0">Solicitudes</h5>
                    <div class="agro-table-toolbar-group">
                        <form method="GET" action="{{ route('compras.solicitudes.index') }}" class="agro-toolbar-inline-form">
                            <div class="agro-toolbar-records">
                                <select name="per_page" class="form-select form-select-sm agro-toolbar-select" onchange="this.form.submit()">
                                    <option value="10" @selected($perPage === 10)>10</option>
                                    <option value="20" @selected($perPage === 20)>20</option>
                                    <option value="50" @selected($perPage === 50)>50</option>
                                    <option value="100" @selected($perPage === 100)>100</option>
                                </select>
                                <small>registros</small>
                            </div>
                            <select name="estado" class="form-select form-select-sm agro-toolbar-select" onchange="this.form.submit()">
                                <option value="">Todos los estados</option>
                                @foreach($estados as $estadoOption)
                                    <option value="{{ $estadoOption }}" @selected($estado === $estadoOption)>
                                        {{ ucfirst(str_replace('_', ' ', $estadoOption)) }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                    <div class="agro-toolbar-actions">
                        @if(auth()->user()?->hasRole('compra') || auth()->user()?->isSuperUser())
                            <a href="{{ route('compras.ordenes.report') }}" class="btn btn-outline-dark btn-sm">Reporte O.C.</a>
                        @endif
                        <a href="{{ route('compras.solicitudes.create') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus me-1"></i> Nueva solicitud</a>
                    </div>
                </div>

                <div class="table-responsive agro-table-shell">
                    <table class="table table-hover table-sm align-middle agro-table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Solicitud</th>
                                @if(auth()->user()?->isSuperUser() || auth()->user()?->hasRole('compra'))
                                    <th>Solicitante</th>
                                @endif
                                <th>Resumen</th>
                                <th>Estado</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($solicitudes as $solicitud)
                                @php
                                    $estadoVisual = $solicitud->estado;
                                    $estadoLabel = str_replace('_', ' ', $solicitud->estado);

                                    if ($solicitud->ordenCompra?->recepcion_estado === 'con_diferencias') {
                                        $estadoVisual = 'recibida_con_diferencias';
                                        $estadoLabel = 'recibida con diferencias';
                                    } elseif ($solicitud->ordenCompra?->recepcion_estado === 'diferencias_aprobadas') {
                                        $estadoVisual = 'diferencias_aprobadas';
                                        $estadoLabel = 'diferencias aprobadas';
                                    } elseif ($solicitud->ordenCompra?->recepcion_estado === 'completa') {
                                        $estadoVisual = 'recibida';
                                        $estadoLabel = 'recibida';
                                    }

                                    $badgeClass = match ($estadoVisual) {
                                        'aprobada' => 'bg-success',
                                        'en_proceso' => 'bg-warning text-dark',
                                        'rechazada' => 'bg-danger',
                                        'recibida' => 'bg-info text-dark',
                                        'recibida_con_diferencias' => 'bg-warning text-dark',
                                        'diferencias_aprobadas' => 'bg-success',
                                        default => 'bg-primary',
                                    };
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $solicitud->codigo ?: 'SC-' . $solicitud->id }}</div>
                                        <span class="agro-table-meta">{{ $solicitud->fecha_requerida?->format('d/m/Y') ?? 'Sin fecha requerida' }}</span>
                                    </td>
                                    @if(auth()->user()?->isSuperUser() || auth()->user()?->hasRole('compra'))
                                        <td>
                                            <div class="fw-semibold">{{ $solicitud->solicitante->usuario ?? 'N/A' }}</div>
                                        </td>
                                    @endif
                                    <td>
                                        <div class="fw-semibold">{{ $solicitud->asunto }}</div>
                                        <span class="agro-table-meta">{{ $solicitud->departamento ?: 'General' }}</span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $badgeClass }} text-capitalize">
                                            {{ $estadoLabel }}
                                        </span>
                                        @if($solicitud->ordenCompra)
                                            <span class="agro-table-meta fw-semibold">{{ $solicitud->ordenCompra->codigo }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="agro-table-actions" style="min-width: 180px;">
                                            <a href="{{ route('compras.solicitudes.show', $solicitud) }}" class="btn btn-primary btn-sm px-2 py-1" title="Ver detalle completo de la solicitud">Ver</a>
                                            @if($solicitud->ordenCompra)
                                                <a href="{{ route('compras.ordenes.show', $solicitud->ordenCompra) }}" class="btn btn-outline-dark btn-sm px-2 py-1" title="Ver la orden de compra relacionada">O.C.</a>
                                            @endif
                                            @if(auth()->user()?->isSuperUser() && $solicitud->estado === 'pendiente_aprobacion')
                                                <form method="POST" action="{{ route('compras.solicitudes.approve', $solicitud) }}" class="agro-table-actions">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="text" name="observaciones_compra" class="form-control form-control-sm" style="max-width: 120px;" placeholder="Obs." title="Escribe una observación para la aprobación">
                                                    <button type="submit" class="btn btn-outline-success btn-sm px-2 py-1" title="Aprobar esta solicitud">Ok</button>
                                                </form>
                                                <form method="POST" action="{{ route('compras.solicitudes.reject', $solicitud) }}" class="agro-table-actions">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="text" name="motivo_rechazo" class="form-control form-control-sm" style="max-width: 120px;" placeholder="Motivo" title="Escribe el motivo del rechazo" required>
                                                    <button type="submit" class="btn btn-outline-danger btn-sm px-2 py-1" title="Rechazar esta solicitud">No</button>
                                                </form>
                                            @elseif((auth()->user()?->hasRole('compra') || auth()->user()?->isSuperUser()) && in_array($solicitud->estado, ['aprobada', 'en_proceso'], true))
                                                @if(!$solicitud->ordenCompra)
                                                    <a href="{{ route('compras.solicitudes.order.create', $solicitud) }}" class="btn btn-outline-dark btn-sm px-2 py-1" title="Generar una orden de compra para esta solicitud">Generar</a>
                                                @endif
                                                <form method="POST" action="{{ route('compras.solicitudes.progress', $solicitud) }}" class="agro-table-actions">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="text" name="observaciones_compra" class="form-control form-control-sm" style="max-width: 120px;" placeholder="Obs." value="{{ $solicitud->observaciones_compra }}" title="Actualizar observación del proceso de compra">
                                                    <button type="submit" class="btn btn-outline-primary btn-sm px-2 py-1" title="Actualizar el estado u observación del proceso de compra">
                                                        {{ $solicitud->estado === 'en_proceso' ? 'Act.' : 'Proc.' }}
                                                    </button>
                                                </form>
                                            @else
                                                <span class="small text-muted">Sin acción</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ auth()->user()?->isSuperUser() || auth()->user()?->hasRole('compra') ? 5 : 4 }}" class="text-center text-muted py-4">
                                        Aun no hay solicitudes de compra registradas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($solicitudes->hasPages())
                    <div class="mt-3">
                        {{ $solicitudes->onEachSide(1)->links('vendor.pagination.bootstrap-5-notext') }}
                    </div>
                @endif
            </div>
        </div>
    </section>
</main>
@endsection
