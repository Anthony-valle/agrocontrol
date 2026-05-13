@extends('layouts.main')

@section('titulo', 'Historial de Notificaciones')

@section('contenido')
<main id="main" class="main">
    <style>
        .notification-summary-card {
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            padding: 0.9rem 1rem;
            min-height: 100%;
        }

        .notification-summary-label {
            color: #64748b;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .notification-summary-value {
            color: #1e293b;
            font-weight: 600;
            font-size: 1.1rem;
            margin-top: 0.35rem;
        }

        .notification-summary-date {
            color: #64748b;
            font-size: 0.8rem;
            margin-top: 0.35rem;
        }

        .notification-detail-text {
            min-width: 380px;
            white-space: normal;
            color: #0f172a;
            line-height: 1.45;
        }

        .notification-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .notification-meta span {
            display: inline-flex;
            align-items: center;
            padding: 0.2rem 0.55rem;
            border-radius: 999px;
            background: #ffffff;
            border: 1px solid #dbe4ee;
            color: #475569;
            font-size: 0.78rem;
            font-weight: 600;
        }
    </style>
    <div class="pagetitle">
        <h1>Historial de Notificaciones</h1>
    </div>

    <section class="section">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="card-title pb-0">Registro de Notificaciones</h5>

                <form method="GET" action="{{ route('notificaciones.index') }}" class="d-flex flex-wrap flex-md-nowrap align-items-center mb-3 p-2 bg-light rounded shadow-sm gap-3">
                    <div class="d-flex align-items-center gap-3 flex-wrap flex-md-nowrap grow">
                        <div class="d-flex align-items-center gap-2">
                            <select name="per_page" id="notificacionesPerPage" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                                <option value="5" {{ (int) $perPage === 5 ? 'selected' : '' }}>5</option>
                                <option value="10" {{ (int) $perPage === 10 ? 'selected' : '' }}>10</option>
                                <option value="15" {{ (int) $perPage === 15 ? 'selected' : '' }}>15</option>
                                <option value="20" {{ (int) $perPage === 20 ? 'selected' : '' }}>20</option>
                                <option value="50" {{ (int) $perPage === 50 ? 'selected' : '' }}>50</option>
                            </select>
                            <small class="text-muted text-nowrap">registros</small>
                        </div>

                        <div class="input-group input-group-sm grow" style="max-width: 280px;">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fa-solid fa-magnifying-glass text-muted"></i>
                            </span>
                            <input type="text" name="search" id="notificacionesBusqueda" class="form-control border-start-0" value="{{ $search }}" placeholder="Buscar notificación, tipo o usuario...">
                        </div>
                    </div>

                    <div class="d-flex gap-2 shrink-0 ms-md-auto">
                        <button type="submit" class="btn btn-primary btn-sm shadow-sm">
                            <i class="fa-solid fa-filter me-1"></i> Filtrar
                        </button>
                        @if($search !== '' || (int) $perPage !== 15)
                            <a href="{{ route('notificaciones.index') }}" class="btn btn-outline-secondary btn-sm shadow-sm">
                                <i class="fa-solid fa-eraser me-1"></i> Limpiar
                            </a>
                        @endif
                    </div>
                </form>

                <div class="table-responsive border rounded">
                    <table class="table table-hover w-100 mb-0" id="tablaNotificaciones">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Fecha de Notificación</th>
                                <th>Categoría</th>
                                <th>Estado</th>
                                <th>Cultivo</th>
                                <th>Detalle</th>
                                <th>Destinatario</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($notificaciones as $n)
                                @php
                                    $mensajeCompleto = trim((string) $n->mensaje);
                                    $mensajeMostrar = preg_replace('/\s*\[[^\]]+\]\s*$/', '', $mensajeCompleto) ?? $mensajeCompleto;
                                    $tipo = strtolower((string) $n->tipo);
                                    $badgeClass = match ($tipo) {
                                        'consumo' => 'bg-success',
                                        'cosecha' => 'bg-info text-dark',
                                        'mecanizacion' => 'bg-warning text-dark',
                                        'auditoria' => 'bg-secondary',
                                        default => 'bg-dark',
                                    };
                                    $tipoLabel = match ($tipo) {
                                        'mecanizacion' => 'Mecanización',
                                        'auditoria' => 'Auditoría',
                                        default => ucfirst((string) $n->tipo),
                                    };
                                @endphp
                                <tr>
                                    <td>{{ ($notificaciones->firstItem() ?? 0) + $loop->index }}</td>
                                    <td class="text-secondary small text-nowrap">{{ $n->created_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                    <td>
                                        <span class="badge {{ $badgeClass }}">{{ $tipoLabel }}</span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $n->leido ? 'bg-light text-dark border' : 'bg-primary' }}">
                                            {{ $n->leido ? 'Leida' : 'Pendiente' }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($n->cultivo)
                                            <div class="fw-semibold">{{ $n->cultivo->nombre }}</div>
                                            <div class="small text-muted">Codigo: {{ $n->cultivo->codigo ?? 'N/A' }}</div>
                                        @else
                                            <span class="text-muted">Sin cultivo asociado</span>
                                        @endif
                                    </td>
                                    <td class="notification-detail-text">
                                        <div class="fw-semibold mb-1">{{ $mensajeCompleto !== '' ? $mensajeCompleto : $mensajeMostrar }}</div>
                                        <div class="notification-meta">
                                            <span>Fecha: {{ $n->created_at?->format('d/m/Y H:i') ?? '-' }}</span>
                                            @if($n->cultivo)
                                                <span>Cultivo #{{ $n->cultivo->id }}</span>
                                            @endif
                                            <span>Categoria: {{ $tipoLabel }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $n->usuario?->usuario ?? $n->usuario?->name ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No hay notificaciones para los filtros seleccionados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($notificaciones->hasPages())
                    <div class="mt-3">
                        {{ $notificaciones->onEachSide(1)->links('vendor.pagination.bootstrap-5-notext') }}
                    </div>
                @endif
            </div>
        </div>
    </section>
</main>
@endsection
