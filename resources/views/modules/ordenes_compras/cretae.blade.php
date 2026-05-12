@extends('layouts.main')

@section('contenido')

<main id="main" class="main">
    <div class="pagetitle d-flex align-items-center justify-content-between">
        <h1 class="mb-0"><i class="bi bi-bell-fill text-primary me-2"></i>Historial de Notificaciones</h1>
        <span class="badge bg-primary px-3 py-2 rounded-pill shadow-sm">MÓDULO: NOTIFICACIONES</span>
    </div>
    <div class="card shadow-sm border-0 mt-3">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th style="width: 160px;">Fecha</th>
                            <th style="width: 120px;">Tipo</th>
                            <th>Mensaje</th>
                            <th style="width: 160px;">Usuario</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($notificaciones as $n)
                            @php
                                $mensajeMostrar = preg_replace('/\s*\[[^\]]+\]\s*$/', '', (string) $n->mensaje) ?? (string) $n->mensaje;
                            @endphp
                            <tr>
                                <td class="text-secondary small">{{ $n->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <span class="badge {{ $n->tipo == 'consumo' ? 'bg-success' : ($n->tipo == 'cosecha' ? 'bg-info text-dark' : 'bg-secondary') }}">
                                        {{ ucfirst($n->tipo) }}
                                    </span>
                                </td>
                                <td>{{ $mensajeMostrar }}</td>
                                <td>{{ $n->usuario->usuario ?? 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">No hay notificaciones.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
@endsection
