<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Historial de Consumo</title>

<style>
body {
    font-family: Arial, sans-serif;
    margin: 10px;
    background: #f4f6f9;
    color: #233;
}

/* HEADER */
.header {
    margin-bottom: 15px;
    border-left: 5px solid #2c7be5;
    padding-left: 10px;
}

.header-title {
    font-size: 20px;
    font-weight: bold;
    color: #2c7be5;
}

.header-subtitle {
    font-size: 13px;
    color: #555;
}

/* CARD */
.card {
    background: #fff;
    border-radius: 10px;
    margin-bottom: 15px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    overflow: hidden;
}

.card-header {
    background: #2c7be5;
    color: white;
    padding: 10px;
    font-size: 14px;
    font-weight: bold;
}

.card-body {
    padding: 10px;
}

/* GRID RESPONSIVE */
.row {
    display: flex;
    flex-wrap: wrap;
}

.col-6 {
    width: 50%;
    padding: 5px;
}

@media (max-width: 600px) {
    .col-6 {
        width: 100%;
    }
}

/* CAMPOS */
.field {
    margin-bottom: 6px;
    font-size: 13px;
}

.field span {
    font-weight: bold;
}

/* TABLAS */
.table-container {
    width: 100%;
    overflow-x: auto; /* 🔥 CLAVE PARA CELULAR */
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
    min-width: 700px; /* 🔥 evita que se aplaste */
}

th, td {
    border: 1px solid #ddd;
    padding: 6px;
}

th {
    background: #eef2f7;
}

.text-end {
    text-align: right;
    white-space: nowrap;
}

.text-center {
    text-align: center;
}

.descripcion {
    max-width: 200px;
    word-wrap: break-word;
    white-space: normal;
}

/* DESTACADOS */
.consumo-id {
    background: #e9f2ff;
    font-weight: bold;
}

.total-consumo {
    color: #2c7be5;
    font-weight: bold;
}

/* SEPARADOR */
.consumo-separador td {
    border: none;
    height: 10px;
}

/* MOBILE EXTRA */
@media (max-width: 500px) {
    .header-title {
        font-size: 16px;
    }

    table {
        font-size: 11px;
    }
}
</style>
</head>

<body>

<!-- HEADER -->
<div class="header">
    <div class="header-title">Historial de Consumo</div>
    <div class="header-subtitle">
        {{ $cultivo->nombre }} | {{ $cultivo->codigo }}
    </div>
</div>

<!-- INFO -->
<div class="card">
    <div class="card-header">Información del Cultivo</div>
    <div class="card-body">
        <div class="row">
            <div class="col-6">
                <div class="field"><span>Nombre:</span> {{ $cultivo->nombre }}</div>
                <div class="field"><span>Código:</span> {{ $cultivo->codigo }}</div>
                <div class="field"><span>Estado:</span> {{ $cultivo->estado }}</div>
            </div>
            <div class="col-6">
                <div class="field"><span>Unidad:</span> {{ $cultivo->unidad_medida ?? 'N/A' }}</div>
                <div class="field"><span>Consumos:</span> {{ $totalConsumos }}</div>
                <div class="field">
                    <span>Costo:</span>
                    <strong>L {{ agro_number($totalConsumo,2) }}</strong>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- RESUMEN -->
<div class="card">
    <div class="card-header">Resumen</div>
    <div class="card-body">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Categoría</th>
                        <th class="text-end">Cantidad</th>
                        <th class="text-end">Costo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($categoryTotals as $categoria => $totales)
                    <tr>
                        <td>{{ $categoria }}</td>
                        <td class="text-end">{{ agro_number($totales['cantidad'],2) }}</td>
                        <td class="text-end">L {{ agro_number($totales['subtotal'],2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- DETALLE -->
<div class="card">
    <div class="card-header">Detalle</div>
    <div class="card-body">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Sem</th>
                        <th>Fecha</th>
                        <th>Total</th>
                        <th>Código</th>
                        <th>Cat</th>
                        <th>Cant</th>
                        <th>UM</th>
                        <th>P. Unitario</th>
                        <th>Sub</th>
                    </tr>
                </thead>
                <tbody>

                @foreach($consumos as $consumo)
                    @php $detalles = $consumo->detalles; @endphp

                    @foreach($detalles as $index => $item)
                    <tr>

                        @if($index == 0)
                        <td rowspan="{{ $detalles->count() }}" class="text-center consumo-id">
                            #{{ $consumo->id }}
                        </td>

                        <td rowspan="{{ $detalles->count() }}" class="text-center">
                            {{ \Carbon\Carbon::parse($consumo->fecha_consumo)->weekOfYear }}
                        </td>

                        <td rowspan="{{ $detalles->count() }}" class="text-center">
                            {{ \Carbon\Carbon::parse($consumo->fecha_consumo)->format('d/m/Y') }}
                        </td>

                        <td rowspan="{{ $detalles->count() }}" class="text-end total-consumo">
                            L {{ agro_number($consumo->total,2) }}
                        </td>
                        @endif

                        <td>{{ $item->insumo->codigo ?? '-' }}</td>

                        <td>{{ $item->categoria }}</td>

                        <td class="text-end">{{ agro_number($item->cantidad,2) }}</td>
                        <td class="text-center">{{ $item->unidad_medida }}</td>
                        <td class="text-end">L {{ agro_number($item->costo_unitario,2) }}</td>
                        <td class="text-end">L {{ agro_number($item->subtotal,2) }}</td>

                    </tr>
                    @endforeach

                    <tr class="consumo-separador">
                        <td colspan="10"></td>
                    </tr>

                @endforeach

                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>