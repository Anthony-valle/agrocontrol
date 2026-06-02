@php
    $categorias = $consumo->detalles
        ->pluck('categoria')
        ->map(fn ($categoria) => trim((string) $categoria))
        ->filter()
        ->unique()
        ->sort()
        ->values();
@endphp

<div class="historial-detalle-consumo">
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="small text-uppercase text-muted fw-bold">Fecha consumo</div>
            <div class="fs-4 fw-semibold">{{ \Carbon\Carbon::parse($consumo->fecha_consumo)->format('d/m/Y') }}</div>
        </div>
        <div class="col-md-4">
            <div class="small text-uppercase text-muted fw-bold">Total del consumo</div>
            <div class="fs-4 fw-semibold">{{ agro_number($consumo->total, 2) }} Lps</div>
        </div>
        <div class="col-md-4">
            <div class="small text-uppercase text-muted fw-bold">Items registrados</div>
            <div class="fs-4 fw-semibold">{{ $consumo->detalles->count() }}</div>
        </div>
    </div>

    <div class="row g-3 mb-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label fw-bold mb-1">Categoria</label>
            <select class="form-select" data-detalle-categoria>
                <option value="">Todas</option>
                @foreach($categorias as $categoria)
                    <option value="{{ $categoria }}">{{ $categoria }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-5">
            <label class="form-label fw-bold mb-1">Buscar en detalle</label>
            <input type="text" class="form-control" data-detalle-busqueda placeholder="Buscar por insumo o descripcion...">
        </div>
        <div class="col-md-3">
            <div class="small text-uppercase text-muted fw-bold">Resumen visible</div>
            <div class="fw-semibold" data-detalle-resumen>Cantidad 0.00 | Total 0.00 Lps</div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-bordered mb-0">
            <thead class="table-light">
                <tr>
                    <th>Insumo</th>
                    <th>Categoría</th>
                    <th class="text-end">Cantidad</th>
                    <th class="text-center">U.M.</th>
                    <th class="text-end">Precio unitario</th>
                    <th class="text-end">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($consumo->detalles as $item)
                    @php
                        $insumoTexto = $item->insumo ? (($item->insumo->codigo ?? '-') . ' - ' . ($item->insumo->nombre ?? '-')) : '-';
                    @endphp
                    <tr
                        data-detalle-row
                        data-categoria="{{ trim((string) $item->categoria) }}"
                        data-search="{{ strtolower(trim($insumoTexto . ' ' . ($item->descripcion ?? ''))) }}"
                        data-cantidad="{{ (float) $item->cantidad }}"
                        data-subtotal="{{ (float) $item->subtotal }}"
                    >
                        <td>{{ $insumoTexto }}</td>
                        <td>{{ $item->categoria }}</td>
                        <td class="text-end">{{ agro_number($item->cantidad, 2) }}</td>
                        <td class="text-center">{{ $item->unidad_medida }}</td>
                        <td class="text-end">{{ agro_number($item->costo_unitario, 2) }}</td>
                        <td class="text-end">{{ agro_number($item->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
