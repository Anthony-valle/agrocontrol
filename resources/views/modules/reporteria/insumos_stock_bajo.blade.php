@extends('layouts.main')

@section('contenido')
<main id="main" class="main">
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 fw-bold">
                <i class="fa-solid fa-box-open text-warning me-2"></i>Insumos con stock bajo
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Código</th>
                            <th>Insumo</th>
                            <th>Lote</th>
                            <th>Stock actual</th>
                            <th>Stock mínimo</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($bajos as $i)
                        <tr>
                            <td>{{ $i->insumo_codigo }}</td>
                            <td>{{ $i->insumo_nombre }}</td>
                            <td>{{ $i->lote_codigo }}</td>
                            <td>{{ $i->stock_actual }}</td>
                            <td>{{ $i->stock_minimo }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">Sin insumos con stock bajo</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
@endsection
