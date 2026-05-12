@extends('layouts.main')

@section('contenido')
<main id="main" class="main">

<div class="card shadow-sm border-0 mb-4">
	<div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
		<div>
			<h5 class="mb-1 fw-bold">
				<i class="fa-solid fa-boxes-stacked me-2 text-success"></i>
				Entradas de Inventario
			</h5>
			<small class="text-muted">Panel rápido para registrar entradas manuales o por carga masiva.</small>
		</div>
		<div class="d-flex gap-2">
			<a href="{{ route('movimientos.entrada.importar.template') }}" class="btn btn-outline-success btn-sm">
				<i class="fa-solid fa-download me-2"></i> Plantilla Excel
			</a>
			<a href="{{ route('movimientos.entrada') }}" class="btn btn-success btn-sm">
				<i class="fa-solid fa-plus me-2"></i> Nueva entrada
			</a>
		</div>
	</div>
	<div class="card-body">
		<div class="row g-3 mb-4">
			<div class="col-md-4">
				<div class="border rounded p-3 h-100 bg-light">
					<small class="text-muted d-block">Entradas hoy</small>
					<div class="fs-4 fw-bold text-success">{{ agro_number($totalEntradasHoy) }}</div>
				</div>
			</div>
			<div class="col-md-4">
				<div class="border rounded p-3 h-100 bg-light">
					<small class="text-muted d-block">Bodegas disponibles</small>
					<div class="fs-4 fw-bold text-primary">{{ agro_number($totalBodegas) }}</div>
				</div>
			</div>
			<div class="col-md-4">
				<div class="border rounded p-3 h-100 bg-light">
					<small class="text-muted d-block">Insumos registrados</small>
					<div class="fs-4 fw-bold text-dark">{{ agro_number($totalInsumos) }}</div>
				</div>
			</div>
		</div>

		<div class="row g-3 mb-4">
			<div class="col-lg-6">
				<div class="border rounded p-4 h-100 bg-white shadow-sm">
					<h6 class="fw-bold mb-2">Entrada manual</h6>
					<p class="text-muted small mb-3">Registra varias líneas con lote, vencimiento, proveedor y archivo de factura.</p>
					<a href="{{ route('movimientos.entrada') }}" class="btn btn-primary btn-sm">
						Abrir formulario manual
					</a>
				</div>
			</div>
			<div class="col-lg-6">
				<div class="border rounded p-4 h-100 bg-white shadow-sm">
					<h6 class="fw-bold mb-2">Carga masiva</h6>
					<p class="text-muted small mb-3">Usa la plantilla Excel de 11 columnas para crear y actualizar inventario inicial por lote.</p>
					<div class="d-flex gap-2 flex-wrap">
						<a href="{{ route('movimientos.entrada') }}" class="btn btn-success btn-sm">
							Ir a importar Excel
						</a>
						<a href="{{ route('movimientos.entrada.importar.template') }}" class="btn btn-outline-success btn-sm">
							Descargar plantilla
						</a>
					</div>
				</div>
			</div>
		</div>

		<div class="table-responsive border rounded shadow-sm bg-white">
			<table class="table table-hover align-middle mb-0">
				<thead class="table-light">
					<tr>
						<th>Fecha</th>
						<th>Codigo</th>
						<th>Insumo</th>
						<th>Bodega destino</th>
						<th>Lote</th>
						<th class="text-end">Cantidad</th>
						<th class="text-end">Costo</th>
					</tr>
				</thead>
				<tbody>
					@forelse($entradasRecientes as $movimiento)
						<tr>
							<td>{{ optional($movimiento->created_at)->format('d/m/Y H:i') }}</td>
							<td>{{ $movimiento->insumo->codigo ?? '-' }}</td>
							<td>{{ $movimiento->insumo->nombre ?? '-' }}</td>
							<td>{{ $movimiento->bodegaDestino->nombre ?? '-' }}</td>
							<td>{{ $movimiento->numero_lote ?: 'SIN LOTE' }}</td>
							<td class="text-end fw-bold text-success">{{ agro_number((float) $movimiento->cantidad, 2) }}</td>
							<td class="text-end">{{ agro_number((float) ($movimiento->costo_unitario ?? $movimiento->precio_unitario ?? 0), 2) }}</td>
						</tr>
					@empty
						<tr>
							<td colspan="7" class="text-center text-muted py-4">Todavia no hay entradas registradas.</td>
						</tr>
					@endforelse
				</tbody>
			</table>
		</div>
	</div>
</div>

</main>
@endsection
