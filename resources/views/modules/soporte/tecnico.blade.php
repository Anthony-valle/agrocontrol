@extends('layouts.main')

@section('titulo', $titulo)

@section('contenido')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>{{ $titulo }}</h1>
        <p class="text-muted mb-0">Solicita ayuda tecnica y da seguimiento a tus casos.</p>
    </div>

    <section class="section">
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h5 class="card-title mb-3">Nueva solicitud</h5>

                        <form method="POST" action="{{ route('soporte.tecnico.store') }}" class="row g-3">
                            @csrf
                            <div class="col-12">
                                <label class="form-label">Asunto</label>
                                <input type="text" name="asunto" class="form-control" maxlength="120" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Prioridad</label>
                                <select name="prioridad" class="form-select" required>
                                    <option value="media" selected>Media</option>
                                    <option value="baja">Baja</option>
                                    <option value="alta">Alta</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Descripcion del problema</label>
                                <textarea name="descripcion" class="form-control" rows="5" maxlength="3000" required></textarea>
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-send me-1"></i>Enviar solicitud
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h5 class="card-title mb-3">Historial de soporte</h5>

                        <div class="table-responsive">
                            <table class="table align-middle table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        @if(auth()->user()?->isSuperUser())
                                            <th>Usuario</th>
                                        @endif
                                        <th>Asunto</th>
                                        <th>Prioridad</th>
                                        <th>Estado</th>
                                        <th>Respuesta</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($tickets as $ticket)
                                        <tr>
                                            <td>{{ $ticket->id }}</td>
                                            @if(auth()->user()?->isSuperUser())
                                                <td>{{ $ticket->usuario->usuario ?? 'N/A' }}</td>
                                            @endif
                                            <td>
                                                <div class="fw-semibold">{{ $ticket->asunto }}</div>
                                                <small class="text-muted">{{ $ticket->created_at?->format('d/m/Y H:i') }}</small>
                                                <div class="small mt-1">{{ $ticket->descripcion }}</div>
                                            </td>
                                            <td class="text-capitalize">{{ str_replace('_', ' ', $ticket->prioridad) }}</td>
                                            <td>
                                                <span class="badge bg-{{ $ticket->estado === 'resuelto' ? 'success' : ($ticket->estado === 'en_proceso' ? 'warning text-dark' : ($ticket->estado === 'cerrado' ? 'secondary' : 'primary')) }} text-capitalize">
                                                    {{ str_replace('_', ' ', $ticket->estado) }}
                                                </span>
                                            </td>
                                            <td>
                                                @if(auth()->user()?->isSuperUser())
                                                    <form method="POST" action="{{ route('soporte.tecnico.update', $ticket->id) }}" class="d-grid gap-2" style="min-width: 230px;">
                                                        @csrf
                                                        @method('PATCH')
                                                        <select name="estado" class="form-select form-select-sm">
                                                            <option value="pendiente" @selected($ticket->estado === 'pendiente')>Pendiente</option>
                                                            <option value="en_proceso" @selected($ticket->estado === 'en_proceso')>En proceso</option>
                                                            <option value="resuelto" @selected($ticket->estado === 'resuelto')>Resuelto</option>
                                                            <option value="cerrado" @selected($ticket->estado === 'cerrado')>Cerrado</option>
                                                        </select>
                                                        <textarea name="respuesta" class="form-control form-control-sm" rows="2" placeholder="Respuesta al usuario">{{ $ticket->respuesta }}</textarea>
                                                        <button type="submit" class="btn btn-sm btn-outline-primary">Guardar</button>
                                                    </form>
                                                @else
                                                    <div>{{ $ticket->respuesta ?: 'Sin respuesta aun' }}</div>
                                                    @if($ticket->atendidoPor)
                                                        <small class="text-muted">Atendido por {{ $ticket->atendidoPor->usuario }} {{ $ticket->atendido_en ? 'el ' . $ticket->atendido_en->format('d/m/Y H:i') : '' }}</small>
                                                    @endif
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ auth()->user()?->isSuperUser() ? 6 : 5 }}" class="text-center text-muted py-4">Aun no hay solicitudes de soporte.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
@endsection
