@extends('layouts.main')

@section('titulo', $titulo)

@section('contenido')
<main id="main" class="main">

    <div class="pagetitle">
        <h1>Órdenes de Compra</h1>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">

                <div class="card">
                    <div class="card-body">

                        <h5 class="card-title">Administrar Órdenes de Compra</h5>

                        <!-- BOTÓN NUEVA ORDEN -->
                        <div class="text-end mb-3">
                            <a href="{{ route('ordenes.create') }}" class="btn btn-primary">
                                <i class="fa-solid fa-circle-plus me-2"></i> Nueva Orden
                            </a>
                        </div>

                        @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        <div class="table-responsive">
                            <table class="table datatable">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Proveedor</th>
                                        <th>Fecha</th>
                                        <th>Total</th>
                                        <th>Estado</th>
                                        <th width="220">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($orden_compra as $orden)
                                        <tr>
                                            <td>{{ $orden->id }}</td>
                                            <td>{{ $orden->proveedor->nombre }}</td>
                                            <td>{{ $orden->fecha_orden }}</td>
                                            <td>L {{ agro_number($orden->total, 2) }}</td>
                                            <td>
                                                <span class="badge
                                                    @if($orden->estado == 'BORRADOR') bg-secondary
                                                    @elseif($orden->estado == 'APROBADA') bg-warning
                                                    @elseif($orden->estado == 'RECIBIDA') bg-success
                                                    @else bg-danger @endif">
                                                    {{ $orden->estado }}
                                                </span>
                                            </td>
                                            <td>

                                                <!-- APROBAR -->
                                                @if($orden->estado == 'BORRADOR')
                                                    <form action="{{ route('ordenes.aprobar', $orden->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button class="btn btn-warning btn-sm">
                                                            <i class="fa-solid fa-check"></i>
                                                        </button>
                                                    </form>
                                                @endif

                                                <!-- RECIBIR -->
                                                @if($orden->estado == 'APROBADA')
                                                    <form action="{{ route('ordenes.recibir', $orden->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button class="btn btn-success btn-sm">
                                                            <i class="fa-solid fa-box"></i>
                                                        </button>
                                                    </form>
                                                @endif

                                                <!-- VER DETALLE (opcional) -->
                                                <a href="{{ route('ordenes.show', $orden->id) }}"
                                                   class="btn btn-info btn-sm">
                                                    <i class="fa-solid fa-eye"></i>
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
@endsection
