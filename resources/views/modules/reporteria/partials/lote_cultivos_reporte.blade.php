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

    .lote-rpt-map--compact {
        height: 320px;
    }

    .lote-rpt-map-shell {
        position: relative;
        overflow: hidden;
        border-radius: 0.95rem;
    }

    .lote-rpt-map-overlay {
        position: absolute;
        top: 0.9rem;
        left: 0.9rem;
        z-index: 500;
        display: flex;
        flex-wrap: wrap;
        gap: 0.6rem;
        pointer-events: none;
    }

    .lote-rpt-map-chip {
        background: rgba(255, 255, 255, 0.94);
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 0.85rem;
        padding: 0.55rem 0.75rem;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.12);
        min-width: 120px;
    }

    .lote-rpt-map-chip small {
        display: block;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #64748b;
        margin-bottom: 0.2rem;
    }

    .lote-rpt-map-chip strong {
        font-size: 1rem;
        color: #0f172a;
    }

    .lote-rpt-map-note {
        font-size: 0.85rem;
        color: #64748b;
    }

    .lote-rpt-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 0.85rem;
    }

    .lote-rpt-legend-item {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.35rem 0.65rem;
        border-radius: 999px;
        background: #fff;
        border: 1px solid #dbe3ef;
        font-size: 0.8rem;
        color: #475569;
    }

    .lote-rpt-legend-color {
        width: 12px;
        height: 12px;
        border-radius: 999px;
        display: inline-block;
        border: 1px solid rgba(15, 23, 42, 0.2);
    }

    .lote-rpt-vertex-label {
        background: transparent;
        border: 0;
        box-shadow: none;
        color: #111827;
        font-size: 0.72rem;
        font-weight: 700;
    }

    @media (max-width: 767.98px) {
        .lote-rpt-map-overlay {
            position: static;
            margin-bottom: 0.75rem;
        }

        .lote-rpt-map {
            height: 300px;
        }
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
                            <h6 class="mb-0 fw-bold text-secondary">Lote mapeado</h6>
                        </div>
                        <div class="card-body pt-3">
                            @if(is_array($poligono) && count($poligono) && is_array($poligono[0]) && count($poligono[0]) >= 2 && is_scalar($poligono[0][0]) && is_scalar($poligono[0][1]))
                                <div class="lote-rpt-map-shell">
                                    <div class="lote-rpt-map-overlay">
                                        <div class="lote-rpt-map-chip">
                                            <small>Mapeado</small>
                                            <strong>{{ agro_number($areaOcupada, 2) }} Ha</strong>
                                        </div>
                                        <div class="lote-rpt-map-chip">
                                            <small>Libre</small>
                                            <strong>{{ agro_number($areaDisponible, 2) }} Ha</strong>
                                        </div>
                                    </div>
                                    <div id="mapaLoteResumen" class="lote-rpt-map lote-rpt-map--compact"></div>
                                </div>
                                <div class="lote-rpt-legend">
                                    <span class="lote-rpt-legend-item"><span class="lote-rpt-legend-color" style="background:#ffeb3b"></span> Poligono mapeado del lote</span>
                                    <span class="lote-rpt-legend-item"><span class="lote-rpt-legend-color" style="background:#16a34a"></span> Area ocupada: {{ agro_number($areaOcupada, 2) }} Ha</span>
                                    <span class="lote-rpt-legend-item"><span class="lote-rpt-legend-color" style="background:#cbd5e1"></span> Disponible: {{ agro_number($areaDisponible, 2) }} Ha</span>
                                </div>
                                <div class="lote-rpt-map-note mt-2">Se muestra el dibujo real del lote mapeado. Los cultivos activos se resumen por area porque no tienen poligonos individuales guardados.</div>
                            @else
                                <div class="d-flex flex-column align-items-center justify-content-center h-100 text-center py-4">
                                    <div class="text-muted mb-2">Este lote no tiene un poligono mapeado guardado.</div>
                                    <canvas id="graficoCultivosLote" height="220"></canvas>
                                </div>
                            @endif
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
                    <div class="lote-rpt-map-shell">
                        <div class="lote-rpt-map-overlay">
                            <div class="lote-rpt-map-chip">
                                <small>Area</small>
                                <strong id="mapaLoteArea">{{ agro_number($areaTotal, 2) }} Ha</strong>
                            </div>
                            <div class="lote-rpt-map-chip">
                                <small>Perimetro</small>
                                <strong id="mapaLotePerimetro">0.00 m</strong>
                            </div>
                            <div class="lote-rpt-map-chip">
                                <small>Puntos</small>
                                <strong id="mapaLotePuntos">0</strong>
                            </div>
                        </div>
                        <div id="mapaLote" class="lote-rpt-map"></div>
                    </div>
                    <div class="lote-rpt-map-note mt-2">El poligono del lote se carga automaticamente con medicion visual de area y perimetro.</div>
                </div>
            </div>
        @endif
    </div>
</div>

<script>
    function initLoteCultivosPartial() {
        function computeGeodesicAreaMeters(points) {
            if (!Array.isArray(points) || points.length < 3) {
                return 0;
            }

            const radius = 6378137;
            let area = 0;

            for (let index = 0; index < points.length; index++) {
                const current = points[index];
                const next = points[(index + 1) % points.length];
                const lon1 = current.lng * Math.PI / 180;
                const lon2 = next.lng * Math.PI / 180;
                const lat1 = current.lat * Math.PI / 180;
                const lat2 = next.lat * Math.PI / 180;

                area += (lon2 - lon1) * (2 + Math.sin(lat1) + Math.sin(lat2));
            }

            return Math.abs(area * radius * radius / 2);
        }

        function computePerimeterMeters(points) {
            if (!Array.isArray(points) || points.length < 2) {
                return 0;
            }

            const radius = 6371000;
            let total = 0;

            for (let index = 0; index < points.length; index++) {
                const current = points[index];
                const next = points[(index + 1) % points.length];
                const lat1 = current.lat * Math.PI / 180;
                const lat2 = next.lat * Math.PI / 180;
                const deltaLat = (next.lat - current.lat) * Math.PI / 180;
                const deltaLng = (next.lng - current.lng) * Math.PI / 180;
                const haversine = Math.sin(deltaLat / 2) * Math.sin(deltaLat / 2)
                    + Math.cos(lat1) * Math.cos(lat2) * Math.sin(deltaLng / 2) * Math.sin(deltaLng / 2);
                total += 2 * radius * Math.atan2(Math.sqrt(haversine), Math.sqrt(1 - haversine));
            }

            return total;
        }

        function actualizarMetricasMapa(points) {
            const areaNode = document.getElementById('mapaLoteArea');
            const perimetroNode = document.getElementById('mapaLotePerimetro');
            const puntosNode = document.getElementById('mapaLotePuntos');
            const totalPuntos = Array.isArray(points) ? points.length : 0;
            const areaHa = totalPuntos >= 3 ? computeGeodesicAreaMeters(points) / 10000 : 0;
            const perimetroM = totalPuntos >= 2 ? computePerimeterMeters(points) : 0;

            if (areaNode) {
                areaNode.textContent = `${areaHa.toFixed(2)} Ha`;
            }

            if (perimetroNode) {
                perimetroNode.textContent = `${perimetroM.toFixed(2)} m`;
            }

            if (puntosNode) {
                puntosNode.textContent = String(totalPuntos);
            }
        }

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
                const coords = @json($poligono);
                const baseCoords = Array.isArray(coords) && Array.isArray(coords[0]) && Array.isArray(coords[0][0]) ? coords[0] : coords;
                const puntosPoligono = Array.isArray(baseCoords)
                    ? baseCoords
                          .filter((c) => Array.isArray(c) && c.length >= 2)
                          .map((c) => ({ lat: Number(c[1]), lng: Number(c[0]) }))
                    : [];
                const latlngs = puntosPoligono.map((c) => [c.lat, c.lng]);

                function inicializarMapaLote(elementId, actualizarPanelMetricas = false) {
                    const mapContainer = document.getElementById(elementId);

                    if (!mapContainer || mapContainer.dataset.initialized || latlngs.length === 0) {
                        return;
                    }

                    mapContainer.dataset.initialized = 'true';

                    const map = L.map(elementId).setView([{{ $lat }}, {{ $lng }}], 17);
                    L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                        maxZoom: 20,
                        attribution: '&copy; Esri',
                    }).addTo(map);

                    const polygon = L.polygon(latlngs, {
                        color: '#ffeb3b',
                        fillColor: '#ffeb3b',
                        fillOpacity: 0.45,
                        weight: 3,
                    }).addTo(map);

                    latlngs.forEach((point, index) => {
                        L.circleMarker(point, {
                            radius: 7,
                            color: '#111827',
                            weight: 2,
                            fillColor: '#ffeb3b',
                            fillOpacity: 1,
                        }).addTo(map).bindTooltip(String(index + 1), {
                            permanent: true,
                            direction: 'center',
                            className: 'lote-rpt-vertex-label',
                            offset: [0, 0],
                        });
                    });

                    if (actualizarPanelMetricas) {
                        actualizarMetricasMapa(puntosPoligono);
                    }

                    const areaHa = (computeGeodesicAreaMeters(puntosPoligono) / 10000).toFixed(2);
                    const perimetroM = computePerimeterMeters(puntosPoligono).toFixed(2);
                    polygon.bindTooltip(`Area: ${areaHa} Ha | Perimetro: ${perimetroM} m`, {
                        sticky: true,
                        direction: 'top',
                    });
                    map.fitBounds(latlngs);
                }

                inicializarMapaLote('mapaLoteResumen');
                inicializarMapaLote('mapaLote', true);
            }
        @endif
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLoteCultivosPartial);
    } else {
        initLoteCultivosPartial();
    }
</script>
