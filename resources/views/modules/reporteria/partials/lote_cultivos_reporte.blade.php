<style>
    .lote-rpt-shell {
        border: 1px solid #dbe3ef;
        border-radius: 1rem;
        background: #f8fafc;
    }

    .lote-rpt-head {
        border-bottom: 1px solid #e8edf4;
        background: #ffffff;
        border-radius: 1rem 1rem 0 0;
    }

    .lote-rpt-metric {
        border: 1px solid #e8edf4;
        border-radius: 0.85rem;
        background: #ffffff;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.04);
    }

    .lote-rpt-title {
        font-size: 0.78rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .lote-rpt-map {
        height: 360px;
        width: 100%;
        border-radius: 0.8rem;
        border: 1px solid #dbe3ef;
    }
</style>

<div class="card shadow-sm border-0 lote-rpt-shell">
    <div class="card-header lote-rpt-head p-3 p-lg-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h5 class="card-title mb-0 fw-bold text-dark">
            <i class="fa-solid fa-map-location-dot me-2 text-success"></i>
            Lote: {{ $lote->nombre }}
            <span class="text-muted fw-semibold">({{ $lote->codigo }})</span>
        </h5>
        <span class="badge rounded-pill bg-success-subtle text-success-emphasis px-3 py-2">Resumen del lote</span>
    </div>

    <div class="card-body p-3 p-lg-4">
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-4">
                <div class="lote-rpt-metric p-3 h-100">
                    <div class="lote-rpt-title fw-semibold mb-1">Area total</div>
                    <div class="fs-4 fw-bold text-dark">{{ agro_number($areaTotal,2) }} <small class="fs-6 text-muted">ha</small></div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="lote-rpt-metric p-3 h-100">
                    <div class="lote-rpt-title fw-semibold mb-1">Area ocupada</div>
                    <div class="fs-4 fw-bold text-primary">{{ agro_number($areaOcupada,2) }} <small class="fs-6 text-muted">ha</small></div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="lote-rpt-metric p-3 h-100">
                    <div class="lote-rpt-title fw-semibold mb-1">Area disponible</div>
                    <div class="fs-4 fw-bold text-success">{{ agro_number($areaDisponible,2) }} <small class="fs-6 text-muted">ha</small></div>
                </div>
            </div>
        </div>

        @if(count($cultivosData))
            <div class="row g-3 mb-3">
                <div class="col-12 col-lg-7">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-0 pb-0">
                            <h6 class="mb-0 fw-bold text-secondary">Distribucion de cultivos activos</h6>
                        </div>
                        <div class="card-body pt-3">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Cultivo</th>
                                            <th>Variedad</th>
                                            <th>Area (ha)</th>
                                            <th>% del lote</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($cultivosData as $c)
                                            <tr>
                                                <td class="fw-semibold">{{ $c['nombre'] }}</td>
                                                <td>{{ $c['variedad'] }}</td>
                                                <td>{{ agro_number($c['hectareas'],2) }}</td>
                                                <td><span class="badge bg-light text-dark border">{{ $c['porcentaje'] }}%</span></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-5">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-0 pb-0">
                            <h6 class="mb-0 fw-bold text-secondary">Grafico de participacion</h6>
                        </div>
                        <div class="card-body d-flex flex-column align-items-center justify-content-center">
                            <canvas id="graficoCultivosLote" height="220"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="alert alert-info border-0 shadow-sm mb-3">
                No hay cultivos activos ocupando area en este lote.
            </div>
        @endif

        @if(count($cultivosCerradosData ?? []))
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white border-0 pb-0 d-flex justify-content-between align-items-center gap-2">
                    <h6 class="mb-0 fw-bold text-secondary">Cultivos cerrados</h6>
                    <span class="badge bg-light text-dark border">Ya no ocupan area del lote</span>
                </div>
                <div class="card-body pt-3">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Cultivo</th>
                                    <th>Variedad</th>
                                    <th>Area liberada (ha)</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(($cultivosCerradosData ?? []) as $cultivoCerrado)
                                    <tr>
                                        <td class="fw-semibold">{{ $cultivoCerrado['nombre'] }}</td>
                                        <td>{{ $cultivoCerrado['variedad'] ?: '-' }}</td>
                                        <td>{{ agro_number($cultivoCerrado['hectareas'], 2) }}</td>
                                        <td><span class="badge bg-secondary">{{ $cultivoCerrado['estado'] }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        @php
            $lat = 0;
            $lng = 0;
            if (is_array($poligono) && count($poligono) && is_array($poligono[0]) && count($poligono[0]) >= 2) {
                $lng = is_scalar($poligono[0][0]) ? $poligono[0][0] : 0;
                $lat = is_scalar($poligono[0][1]) ? $poligono[0][1] : 0;
            }
        @endphp

        @if(is_array($poligono) && count($poligono) && is_array($poligono[0]) && count($poligono[0]) >= 2 && is_scalar($poligono[0][0]) && is_scalar($poligono[0][1]))
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header bg-white border-0 pb-0">
                    <h6 class="mb-0 fw-bold text-secondary">Mapa del lote</h6>
                </div>
                <div class="card-body pt-3">
                    <div id="mapaLote" class="lote-rpt-map"></div>
                </div>
            </div>
        @endif
    </div>
</div>

<script>
    function initLoteCultivosPartial() {
        @if(count($cultivosData))
            const ctx = document.getElementById('graficoCultivosLote');
            if (ctx && typeof Chart !== 'undefined') {
                const labels = [
                    @foreach($cultivosData as $c) '{{ $c['nombre'] }}', @endforeach
                    'Disponible'
                ];

                const data = [
                    @foreach($cultivosData as $c) {{ $c['hectareas'] }}, @endforeach
                    {{ $areaDisponible }}
                ];

                const bgColors = [
                    '#4e79a7','#f28e2b','#e15759','#76b7b2','#59a14f','#edc949','#af7aa1','#ff9da7','#9c755f','#bab0ab','#b07aa1','#7a9cb0','#b0b07a','#7ab07a','#b07a7a','#7a7ab0','#d37295','#fabfd2','#b2df8a','#a6cee3','#1f78b4','#b2b2b2','#33a02c','#fb9a99','#e31a1c','#fdbf6f','#ff7f00','#cab2d6','#6a3d9a','#ffff99','#b15928','#393b79','#5254a3','#6b6ecf','#9c9ede','#637939','#8ca252','#b5cf6b','#cedb9c','#8c6d31','#bd9e39','#e7ba52','#e7cb94','#843c39','#ad494a','#d6616b','#e7969c','#7b4173','#a55194','#ce6dbd','#de9ed6'
                ];

                ctx.height = 220;
                new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: data,
                            backgroundColor: bgColors.slice(0, labels.length),
                            borderWidth: 1,
                        }],
                    },
                    options: {
                        plugins: {
                            legend: { position: 'bottom' },
                        },
                    },
                });
            }
        @endif

        @if(is_array($poligono) && count($poligono) && is_array($poligono[0]) && count($poligono[0]) >= 2 && is_scalar($poligono[0][0]) && is_scalar($poligono[0][1]))
            if (typeof L !== 'undefined') {
                const mapContainer = document.getElementById('mapaLote');
                if (mapContainer && !mapContainer.dataset.initialized) {
                    mapContainer.dataset.initialized = 'true';

                    const map = L.map('mapaLote').setView([{{ $lat }}, {{ $lng }}], 17);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 20,
                    }).addTo(map);

                    const coords = @json($poligono);
                    const baseCoords = Array.isArray(coords) && Array.isArray(coords[0]) && Array.isArray(coords[0][0]) ? coords[0] : coords;
                    const latlngs = Array.isArray(baseCoords)
                        ? baseCoords
                              .filter((c) => Array.isArray(c) && c.length >= 2)
                              .map((c) => [c[1], c[0]])
                        : [];

                    if (latlngs.length > 0) {
                        L.polygon(latlngs, {
                            color: '#1d4ed8',
                            fillColor: '#4e79a7',
                            fillOpacity: 0.35,
                            weight: 3,
                            dashArray: '6 4',
                        }).addTo(map);
                        map.fitBounds(latlngs);
                    }
                }
            }
        @endif
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLoteCultivosPartial);
    } else {
        initLoteCultivosPartial();
    }
</script>
