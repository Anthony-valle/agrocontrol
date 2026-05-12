@extends('layouts.main')

@section('titulo', 'Verificar Preparación de Suelo')

@section('contenido')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Verificar Preparación de Suelo</h1>
    </div>

    <section class="section">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-lg-3 col-md-6">
                        <small class="text-muted d-block">Registro</small>
                        <strong>#{{ $consumo->id }}</strong>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <small class="text-muted d-block">Fecha</small>
                        <strong>{{ \Carbon\Carbon::parse($consumo->fecha_consumo)->format('d/m/Y') }}</strong>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <small class="text-muted d-block">Estado</small>
                        <strong>{{ $consumo->estado }}</strong>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <small class="text-muted d-block">Registrado por</small>
                        <strong>{{ $consumo->creador->usuario ?? $consumo->creador->name ?? 'Sistema' }}</strong>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <small class="text-muted d-block">Lote</small>
                        <strong>{{ $consumo->cultivo->lote->nombre ?? '-' }}</strong>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <small class="text-muted d-block">Cultivo</small>
                        <strong>{{ $consumo->cultivo->nombre ?? '-' }}</strong>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <small class="text-muted d-block">Hectáreas aplicadas</small>
                        <strong>{{ agro_number((float) $detalle->cantidad, 2) }} ha</strong>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <small class="text-muted d-block">Actividad</small>
                        <strong>{{ $actividad }}</strong>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <small class="text-muted d-block">Unidad</small>
                        <strong>{{ $detalle->unidad_medida }}</strong>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <small class="text-muted d-block">Costo unitario</small>
                        <strong>{{ agro_number((float) $detalle->costo_unitario, 2) }} Lps</strong>
                    </div>

                    <div class="col-lg-12">
                        <small class="text-muted d-block">Observación</small>
                        <strong>{{ $observacion !== '' ? $observacion : '-' }}</strong>
                    </div>

                    <div class="col-lg-12">
                        <small class="text-muted d-block">Costo total</small>
                        <h4 class="text-primary fw-bold mb-0">{{ agro_number((float) $consumo->total, 2) }} Lps</h4>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-4">
                    <a href="{{ route('preparacion-suelo.index') }}" class="btn btn-outline-secondary">Volver</a>
                    @if(strtoupper((string) $consumo->estado) !== 'ANULADO')
                        <a href="{{ route('preparacion-suelo.edit', $consumo->id) }}" class="btn btn-primary">
                            <i class="fa-solid fa-pen-to-square me-1"></i> Editar registro
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </section>
</main>
@endsection