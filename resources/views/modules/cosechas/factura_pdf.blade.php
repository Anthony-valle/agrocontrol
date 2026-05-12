<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Factura {{ $factura->numero_factura }}</title>

<style>
@page { margin: 30px 35px; }

body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 11px;
    color: #1f2a37;
}

/* HEADER */
.header {
    width: 100%;
    border-bottom: 2px solid #0f4c3a;
    padding-bottom: 10px;
    margin-bottom: 15px;
}

.logo {
    width: 90px;
    height: 90px;
    object-fit: contain;
}

.company-name {
    font-size: 22px;
    font-weight: bold;
    color: #0f4c3a;
}

.sub {
    font-size: 10px;
    color: #6b7280;
    text-transform: uppercase;
}

/* FACTURA BOX */
.invoice-box {
    text-align: right;
    background: #0f4c3a;
    color: #fff;
    padding: 12px;
    border-radius: 6px;
}

.invoice-number {
    font-size: 18px;
    font-weight: bold;
}

/* INFO */
.info-table {
    width: 100%;
    margin-top: 15px;
}

.info-box {
    border: 1px solid #e5e7eb;
    padding: 10px;
}

/* TABLE */
table {
    width: 100%;
    border-collapse: collapse;
}

th {
    background: #0f4c3a;
    color: white;
    padding: 8px;
    font-size: 10px;
    text-transform: uppercase;
}

td {
    border: 1px solid #e5e7eb;
    padding: 8px;
}

tr:nth-child(even) {
    background: #f9fafb;
}

.right {
    text-align: right;
}

/* TOTALS */
.totals {
    margin-top: 15px;
    width: 100%;
}

.total-box {
    border: 2px solid #0f4c3a;
    padding: 10px;
}

.total-row {
    display: flex;
    justify-content: space-between;
    padding: 5px 0;
}

.total-final {
    background: #0f4c3a;
    color: white;
    padding: 8px;
    font-weight: bold;
    font-size: 13px;
}

/* FOOTER */
.footer {
    margin-top: 25px;
    font-size: 10px;
    color: #6b7280;
    border-top: 1px solid #e5e7eb;
    padding-top: 10px;
}
</style>
</head>

<body>

<!-- HEADER -->
<table class="header">
<tr>
    <td style="width:120px;">
        @if($logoEmpresa)
            <img src="{{ $logoEmpresa }}" class="logo">
        @endif
    </td>

    <td>
        <div class="company-name">{{ $empresa->nombre ?? 'Empresa' }}</div>
        <div class="sub">Factura de venta agrícola</div>

        <div style="margin-top:8px; font-size:10px;">
            RTN: {{ $empresa->rtn ?? 'N/D' }} <br>
            Tel: {{ $empresa->telefono ?? 'N/D' }} <br>
            Dirección: {{ $empresa->direccion ?? 'N/D' }}
        </div>
    </td>

    <td style="width:200px;">
        <div class="invoice-box">
            FACTURA<br>
            <div class="invoice-number">{{ $factura->numero_factura }}</div>
            <div style="font-size:10px;">
                {{ \Carbon\Carbon::parse($factura->fecha_factura)->format('d/m/Y') }}
            </div>
        </div>
    </td>
</tr>
</table>

<!-- CLIENTE -->
<table class="info-table">
<tr>
    <td class="info-box">
        <strong>Cliente:</strong> {{ $factura->cliente ?: 'Consumidor final' }} <br>
        <strong>Producto:</strong> {{ $factura->cosecha->cultivo->nombre ?? '-' }}
    </td>

    <td class="info-box">
        <strong>Moneda:</strong> HNL <br>
        <strong>Unidad:</strong> {{ $factura->cosecha->unidad_medida ?? '-' }}
    </td>
</tr>
</table>

<br>

<!-- DETALLE -->
<table>
<thead>
<tr>
    <th>Descripción</th>
    <th class="right">Cantidad</th>
    <th class="right">Precio</th>
    <th class="right">Total</th>
</tr>
</thead>

<tbody>
<tr>
    <td>{{ $factura->cosecha->cultivo->nombre ?? 'Producto' }}</td>
    <td class="right">{{ agro_number($factura->cantidad_vendida,2) }}</td>
    <td class="right">{{ agro_number($factura->precio_unitario,2) }}</td>
    <td class="right">{{ agro_number($factura->total,2) }} Lps</td>
</tr>
</tbody>
</table>

<!-- TOTALES -->
<div class="totals">
    <div class="total-box">

        <div class="total-row">
            <span>Subtotal</span>
            <span>{{ agro_number($factura->total,2) }} Lps</span>
        </div>

        <div class="total-row">
            <span>Impuesto</span>
            <span>0.00 Lps</span>
        </div>

        <div class="total-final">
            TOTAL: {{ agro_number($factura->total,2) }} Lps
        </div>

    </div>
</div>

<!-- OBSERVACIONES -->
<div style="margin-top:15px;">
    <strong>Observaciones:</strong><br>
    {{ $factura->observaciones ?: 'Factura generada automáticamente por el sistema AgroControl.' }}
</div>

<!-- FOOTER -->
<div class="footer">
    Documento generado automáticamente por AgroControl | {{ now()->format('d/m/Y H:i') }}
</div>

</body>
</html>