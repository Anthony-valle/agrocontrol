
@extends('layouts.main')
@section('titulo', $titulo)
@section('contenido')
<main id="main" class="main">

    <div class="pagetitle">
        <h1>Lotes</h1>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">

                <div class="card shadow-sm border-0">
                    <div class="card-body">

                        <h5 class="card-title pb-0">Configuración de Lotes</h5>

                        <!--CONTROLES-->
                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 p-2 bg-light rounded shadow-sm gap-3">

                            <!-- Buscador + registros -->
                            <div class="d-flex align-items-center gap-3">
                                <div class="d-flex align-items-center gap-2">
                                    <select id="customPerPage" class="form-select form-select-sm" style="width:auto;">
                                        <option value="5">5</option>
                                        <option value="10" selected>10</option>
                                        <option value="20">20</option>
                                        <option value="50">50</option>
                                    </select>
                                    <small class="text-muted">registros</small>
                                </div>

                                <div class="input-group input-group-sm" style="max-width:250px;">
                                    <span class="input-group-text bg-white border-end-0">
                                        <i class="fa fa-search text-muted"></i>
                                    </span>
                                    <input type="text" id="inputBusqueda" class="form-control border-start-0" placeholder="Buscar lote...">
                                </div>

                            </div>

                            <!-- BOTÓN -->
                            <button type="button" class="btn btn-primary btn-sm" id="btnAbrirModal">
                                <i class="fa fa-plus me-1"></i> Nuevo Lote
                            </button>

                        </div>

                        <!-- TABLA -->
                        <div class="table-responsive border rounded shadow-sm">
                            <table class="table table-hover table-sm align-middle mb-0" style="min-width:800px;" id="tablaLotes">
                                <thead class="table-success text-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Código</th>
                                        <th>Nombre Lote</th>
                                        <th>Área</th>
                                        <th>Estado</th>
                                        <th>Creado por</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($lotes as $item)
                                    <tr>
                                        <td>{{ $item->id }}</td>
                                        <td>{{ $item->codigo }}</td>
                                        <td>{{ $item->nombre }}</td>
                                        <td class="fw-bold">{{ $item->area }}</td>
                                        <td>
                                            <span class="badge {{ $item->estado ? 'bg-success' : 'bg-danger' }}">
                                                {{ $item->estado_texto }}
                                            </span>
                                        </td>
                                        <td>{{ $item->creador->usuario ?? 'Sistema' }}</td>
                                        <td class="text-center text-nowrap">
                                            <button class="btn btn-warning btn-sm btnEditarLotes" data-id="{{ $item->id }}">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                            <button class="btn btn-danger btn-sm btnEliminarLotes" data-id="{{ $item->id }}">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if($lotes->isEmpty())
                            <div class="text-center mt-3">No hay lotes registrados.</div>
                        @endif

                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- MODAL CREAR -->
    <div class="modal fade" id="modalLotes" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable lotes-modal-dialog lotes-create-modal-dialog">
            <div class="modal-content" id="modalContent"></div>
        </div>
    </div>

    <!-- MODAL EDITAR -->
    <div class="modal fade" id="modalLotesEdit" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable lotes-modal-dialog">
            <div class="modal-content" id="modalContentEdit"></div>
        </div>
    </div>

</main>

<style>
.lotes-modal-dialog {
    max-width: 960px;
}

.lotes-create-modal-dialog {
    width: min(92vw, 920px);
    max-width: 920px;
}

.lotes-create-modal-dialog .modal-content {
    max-height: calc(100vh - 2rem);
    border: 0;
    overflow: hidden;
}

#tablaLotes thead th {
    background: #0f5a43;
    color: #fff;
    border-color: #0f5a43;
    font-weight: 600;
}

#tablaLotes tbody tr:hover {
    background: #f5fbf8;
}
</style>

<script>
let map;
let poligonoActivo = null;
let marcadorUsuario = null;
let marcadoresVertices = [];
let drawingManager = null;
let trackingActivo = false;
let trackingWatchId = null;
let ubicacionTiempoRealActiva = false;
let ubicacionWatchId = null;
let ultimoPuntoTracking = null;
let trackingErrorMostrado = false;
let estiloPoligonoActual = null;
let activeMapProvider = null;
let leafletPolygonPoints = [];
const TRACKING_MIN_DISTANCE_METERS = 1.5;
const googleMapsApiKey = @json(config('services.google_maps.api_key'));
const DEFAULT_MAP_CENTER = { lat: 14.0723, lng: -87.1921 };
const LEAFLET_SATELLITE_TILES = 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}';

function isGoogleMapsLoaded() {
    return typeof google !== 'undefined' && google.maps?.geometry && google.maps?.drawing;
}

function isLeafletLoaded() {
    return typeof L !== 'undefined';
}

function usesGoogleMaps() {
    return activeMapProvider === 'google';
}

function usesLeafletMaps() {
    return activeMapProvider === 'leaflet';
}

function getCurrentPolygonContext() {
    const modal = document.querySelector('.modal.show');
    const estadoSelect = modal?.querySelector('select[name="estado"]');

    return {
        isEditMode: modal?.id === 'modalLotesEdit',
        estadoLote: estadoSelect ? String(estadoSelect.value) : null,
    };
}

function getPointCoords(point) {
    if (!point) {
        return null;
    }

    if (typeof point.lat === 'function' && typeof point.lng === 'function') {
        return { lat: Number(point.lat()), lng: Number(point.lng()) };
    }

    if (Number.isFinite(point.lat) && Number.isFinite(point.lng)) {
        return { lat: Number(point.lat), lng: Number(point.lng) };
    }

    return parsePunto(point);
}

function createPoint(lat, lng) {
    const normalizedLat = Number(lat);
    const normalizedLng = Number(lng);

    if (!Number.isFinite(normalizedLat) || !Number.isFinite(normalizedLng)) {
        return null;
    }

    if (usesGoogleMaps() && typeof google !== 'undefined' && google.maps) {
        return new google.maps.LatLng(normalizedLat, normalizedLng);
    }

    if (isLeafletLoaded()) {
        return L.latLng(normalizedLat, normalizedLng);
    }

    return { lat: normalizedLat, lng: normalizedLng };
}

function haversineDistanceMeters(start, end) {
    const earthRadius = 6378137;
    const toRadians = (value) => (value * Math.PI) / 180;
    const dLat = toRadians(end.lat - start.lat);
    const dLng = toRadians(end.lng - start.lng);
    const lat1 = toRadians(start.lat);
    const lat2 = toRadians(end.lat);
    const a =
        Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(lat1) * Math.cos(lat2) * Math.sin(dLng / 2) * Math.sin(dLng / 2);

    return 2 * earthRadius * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

function computeDistanceMeters(start, end) {
    const startCoords = getPointCoords(start);
    const endCoords = getPointCoords(end);

    if (!startCoords || !endCoords) {
        return 0;
    }

    if (usesGoogleMaps() && typeof google !== 'undefined' && google.maps?.geometry) {
        return google.maps.geometry.spherical.computeDistanceBetween(
            new google.maps.LatLng(startCoords.lat, startCoords.lng),
            new google.maps.LatLng(endCoords.lat, endCoords.lng)
        );
    }

    if (isLeafletLoaded()) {
        return L.latLng(startCoords.lat, startCoords.lng).distanceTo(L.latLng(endCoords.lat, endCoords.lng));
    }

    return haversineDistanceMeters(startCoords, endCoords);
}

function computePerimeterMeters(points) {
    const coords = points.map(getPointCoords).filter(Boolean);

    if (coords.length < 2) {
        return 0;
    }

    let total = 0;
    for (let index = 1; index < coords.length; index += 1) {
        total += computeDistanceMeters(coords[index - 1], coords[index]);
    }

    if (coords.length >= 3) {
        total += computeDistanceMeters(coords[coords.length - 1], coords[0]);
    }

    return total;
}

function computeGeodesicAreaMeters(points) {
    const coords = points.map(getPointCoords).filter(Boolean);

    if (coords.length < 3) {
        return 0;
    }

    const earthRadius = 6378137;
    const d2r = Math.PI / 180;
    let area = 0;

    for (let index = 0; index < coords.length; index += 1) {
        const current = coords[index];
        const next = coords[(index + 1) % coords.length];
        area += (next.lng - current.lng) * d2r * (2 + Math.sin(current.lat * d2r) + Math.sin(next.lat * d2r));
    }

    return Math.abs((area * earthRadius * earthRadius) / 2);
}

function getPolygonPoints() {
    if (usesGoogleMaps() && poligonoActivo) {
        return poligonoActivo.getPath().getArray().map(getPointCoords).filter(Boolean);
    }

    if (usesLeafletMaps()) {
        return leafletPolygonPoints.map(getPointCoords).filter(Boolean);
    }

    return [];
}

function removePolygonLayer() {
    if (!poligonoActivo) {
        return;
    }

    if (usesGoogleMaps()) {
        poligonoActivo.setMap(null);
    } else if (usesLeafletMaps() && map?.removeLayer) {
        map.removeLayer(poligonoActivo);
    }

    poligonoActivo = null;
}

function createLeafletVertexIcon(index, fillColor) {
    return L.divIcon({
        className: 'leaflet-vertex-label',
        html: `<div style="width:22px;height:22px;border-radius:50%;background:${fillColor || '#00c853'};border:2px solid #000;color:#000;font-size:10px;font-weight:700;display:flex;align-items:center;justify-content:center;">${index + 1}</div>`,
        iconSize: [22, 22],
        iconAnchor: [11, 11],
    });
}

function renderLeafletPolygon(isEditMode = null, estadoLote = null) {
    if (!usesLeafletMaps() || !map) {
        return;
    }

    const context = getCurrentPolygonContext();
    const resolvedIsEditMode = isEditMode ?? context.isEditMode;
    const resolvedEstadoLote = estadoLote ?? context.estadoLote;
    const coords = leafletPolygonPoints.map(getPointCoords).filter(Boolean);

    removePolygonLayer();

    if (!coords.length) {
        limpiarMarcadoresVertices();
        actualizarAreaTotal();
        return;
    }

    const estiloPoligono = obtenerEstiloPoligono(resolvedIsEditMode, resolvedEstadoLote);
    poligonoActivo = L.polygon(coords.map((punto) => [punto.lat, punto.lng]), {
        color: estiloPoligono.strokeColor,
        weight: estiloPoligono.strokeWeight,
        opacity: estiloPoligono.strokeOpacity,
        fillColor: estiloPoligono.fillColor,
        fillOpacity: estiloPoligono.fillOpacity,
        interactive: true,
    }).addTo(map);

    estiloPoligonoActual = {
        fillColor: estiloPoligono.fillColor,
    };

    syncMarcadoresVertices();
    actualizarAreaTotal();
}

function getGeoErrorMessage(error) {
    switch (error?.code) {
        case error.PERMISSION_DENIED:
            return 'Permiso de ubicación denegado. Habilita la ubicación para este sitio en tu navegador.';
        case error.POSITION_UNAVAILABLE:
            return 'No se pudo determinar tu posición. Activa GPS o mejora la señal e inténtalo de nuevo.';
        case error.TIMEOUT:
            return 'La ubicación tardó demasiado en responder. Intenta nuevamente en un lugar con mejor señal.';
        default:
            return 'No se pudo leer tu ubicación para mapear caminando.';
    }
}

function actualizarMarcadorUsuario(punto) {
    if (usesLeafletMaps()) {
        const coords = getPointCoords(punto);
        if (!coords || !map) {
            return;
        }

        if (!marcadorUsuario) {
            marcadorUsuario = L.circleMarker([coords.lat, coords.lng], {
                radius: 7,
                color: '#0d6efd',
                fillColor: '#0d6efd',
                fillOpacity: 1,
                weight: 2,
            }).addTo(map);
        } else {
            marcadorUsuario.setLatLng([coords.lat, coords.lng]);
        }

        map.setView([coords.lat, coords.lng], Math.max(map.getZoom() || 18, 18));
        return;
    }

    if (!marcadorUsuario) {
        marcadorUsuario = new google.maps.Marker({
            position: punto,
            map,
            title: 'Mi ubicación',
            icon: 'https://maps.google.com/mapfiles/ms/icons/blue-dot.png',
        });
    } else {
        marcadorUsuario.setPosition(punto);
    }

    map.setCenter(punto);
}

function iniciarWatchTracking(buttonEl = null, opciones = null, permiteFallback = true) {
    if (!navigator.geolocation || !trackingActivo) {
        return;
    }

    if (trackingWatchId !== null) {
        navigator.geolocation.clearWatch(trackingWatchId);
        trackingWatchId = null;
    }

    const opcionesWatch =
        opciones ||
        {
            enableHighAccuracy: true,
            timeout: 15000,
            maximumAge: 1000,
        };

    trackingWatchId = navigator.geolocation.watchPosition(
        (posicion) => {
            const punto = createPoint(posicion.coords.latitude, posicion.coords.longitude);
            actualizarMarcadorUsuario(punto);

            if (!ultimoPuntoTracking) {
                agregarPunto(punto);
                ultimoPuntoTracking = punto;
                return;
            }

            const distancia = computeDistanceMeters(ultimoPuntoTracking, punto);
            if (distancia >= TRACKING_MIN_DISTANCE_METERS) {
                agregarPunto(punto);
                ultimoPuntoTracking = punto;
            }
        },
        (error) => {
            if (!trackingActivo) {
                return;
            }

            if (
                permiteFallback &&
                (error?.code === error.POSITION_UNAVAILABLE || error?.code === error.TIMEOUT)
            ) {
                iniciarWatchTracking(
                    buttonEl,
                    {
                        enableHighAccuracy: false,
                        timeout: 20000,
                        maximumAge: 5000,
                    },
                    false
                );

                if (!trackingErrorMostrado) {
                    trackingErrorMostrado = true;
                    Swal.fire(
                        'Señal GPS inestable',
                        'Se activó modo de ubicación aproximada para que puedas seguir mapeando.',
                        'info'
                    );
                }
                return;
            }

            if (error?.code === error.PERMISSION_DENIED) {
                detenerTracking(buttonEl);
                Swal.fire(
                    'Permiso requerido',
                    'Debes permitir la ubicación para usar Caminar lote. Revisa permisos del sitio en tu navegador.',
                    'warning'
                );
                return;
            }

            if (!trackingErrorMostrado) {
                trackingErrorMostrado = true;
                Swal.fire('GPS no disponible', getGeoErrorMessage(error), 'warning');
            }
        },
        opcionesWatch
    );
}

function resolveTrackingButton(buttonEl = null) {
    if (buttonEl) {
        return buttonEl;
    }

    return document.querySelector('.modal.show button[onclick^="iniciarTracking"]');
}

function resolveUbicacionButton(buttonEl = null) {
    if (buttonEl) {
        return buttonEl;
    }

    return document.querySelector('.modal.show button[onclick^="mostrarUbicacion"]');
}

function setUbicacionButtonState(isActive, buttonEl = null) {
    const button = resolveUbicacionButton(buttonEl);
    if (!button) {
        return;
    }

    if (isActive) {
        button.classList.remove('btn-primary');
        button.classList.add('btn-warning');
        button.textContent = '⏹ Detener ubicación';
    } else {
        button.classList.remove('btn-warning');
        button.classList.add('btn-primary');
        button.textContent = '📍 Mi ubicación';
    }
}

function setTrackingButtonState(isActive, buttonEl = null) {
    const button = resolveTrackingButton(buttonEl);
    if (!button) {
        return;
    }

    if (isActive) {
        button.classList.remove('btn-info');
        button.classList.add('btn-warning');
        button.textContent = '✅ Terminar recorrido';
    } else {
        button.classList.remove('btn-warning');
        button.classList.add('btn-info');
        button.textContent = '🚶 Caminar lote';
    }
}

function obtenerMetricasPoligonoActual() {
    const puntos = getPolygonPoints();
    if (!puntos.length) {
        return null;
    }

    let areaTotal = 0;
    let perimetroTotal = 0;

    if (puntos.length >= 3) {
        areaTotal = usesGoogleMaps() && poligonoActivo
            ? google.maps.geometry.spherical.computeArea(poligonoActivo.getPath())
            : computeGeodesicAreaMeters(puntos);
    }

    if (puntos.length >= 2) {
        perimetroTotal = usesGoogleMaps() && poligonoActivo
            ? google.maps.geometry.spherical.computeLength(poligonoActivo.getPath())
            : computePerimeterMeters(puntos);
    }

    return {
        puntos,
        totalPuntos: puntos.length,
        areaHa: areaTotal / 10000,
        perimetroM: perimetroTotal,
    };
}

function finalizarRecorridoConResumen() {
    const metricas = obtenerMetricasPoligonoActual();
    actualizarAreaTotal();

    if (metricas?.puntos?.length) {
        ajustarVista(metricas.puntos);
    }

    if (!metricas || metricas.totalPuntos < 3) {
        Swal.fire(
            'Recorrido finalizado',
            `Se capturaron ${metricas?.totalPuntos || 0} puntos. Debes capturar al menos 3 para calcular el área.`,
            'info'
        );
        return;
    }

    Swal.fire({
        title: 'Lote mapeado',
        icon: 'success',
        html: `Área: <b>${metricas.areaHa.toFixed(2)} Ha</b><br>Perímetro: <b>${metricas.perimetroM.toFixed(2)} m</b><br>Puntos: <b>${metricas.totalPuntos}</b>`,
    });
}

function detenerTracking(buttonEl = null, notify = false, mostrarResumen = false) {
    if (trackingWatchId !== null && navigator.geolocation) {
        navigator.geolocation.clearWatch(trackingWatchId);
    }

    trackingWatchId = null;
    trackingActivo = false;
    ultimoPuntoTracking = null;
    trackingErrorMostrado = false;
    setTrackingButtonState(false, buttonEl);

    if (mostrarResumen) {
        finalizarRecorridoConResumen();
        return;
    }

    if (notify) {
        Swal.fire('Tracking detenido', 'Se detuvo el mapeo caminando.', 'info');
    }
}

function detenerUbicacionTiempoReal(buttonEl = null, notify = false) {
    if (ubicacionWatchId !== null && navigator.geolocation) {
        navigator.geolocation.clearWatch(ubicacionWatchId);
    }

    ubicacionWatchId = null;
    ubicacionTiempoRealActiva = false;
    setUbicacionButtonState(false, buttonEl);

    if (notify) {
        Swal.fire('Ubicación detenida', 'Se detuvo la actualización en tiempo real.', 'info');
    }
}

async function ensureGoogleMapsReady(maxWaitMs = 10000) {
    if (isGoogleMapsLoaded()) {
        return true;
    }

    if (!googleMapsApiKey) {
        return false;
    }

    const startedAt = Date.now();
    while (Date.now() - startedAt < maxWaitMs) {
        if (isGoogleMapsLoaded()) {
            return true;
        }

        await new Promise((resolve) => setTimeout(resolve, 150));
    }

    return isGoogleMapsLoaded();
}

async function ensureMapProviderReady(maxWaitMs = 10000) {
    const googleReady = await ensureGoogleMapsReady(maxWaitMs);
    if (googleReady) {
        activeMapProvider = 'google';
        return 'google';
    }

    if (googleMapsApiKey) {
        activeMapProvider = null;
        return null;
    }

    if (isLeafletLoaded()) {
        activeMapProvider = 'leaflet';
        return 'leaflet';
    }

    activeMapProvider = null;
    return null;
}

function setMapAvailabilityState(modalElement, mapsReady) {
    if (!modalElement) {
        return;
    }

    const mapAlert = modalElement.querySelector('.map-unavailable-alert');
    const mapElement = modalElement.querySelector('#map');
    const actionButtons = modalElement.querySelectorAll('.map-action-btn');
    const areaInput = modalElement.querySelector('#area');
    const areaHint = modalElement.querySelector('.map-manual-hint');

    if (mapAlert) {
        const fallbackMessage = !googleMapsApiKey
            ? 'Google Maps no está disponible porque GOOGLE_MAPS_API_KEY está vacía en el archivo .env.'
            : 'Google Maps no pudo cargarse. Verifica la API key, Maps JavaScript API y las restricciones de tu clave en Google Cloud.';

        mapAlert.textContent = mapsReady ? '' : fallbackMessage;
        mapAlert.classList.toggle('d-none', mapsReady);
    }

    if (mapElement) {
        mapElement.classList.toggle('d-none', !mapsReady);
    }

    actionButtons.forEach((button) => {
        button.disabled = !mapsReady;
    });

    if (areaInput) {
        areaInput.readOnly = mapsReady;
    }

    if (areaHint) {
        areaHint.classList.toggle('d-none', mapsReady);
    }
}

function parsePunto(raw) {
    if (!raw) {
        return null;
    }

    // Soporta formato GeoJSON simple [lng, lat].
    if (Array.isArray(raw) && raw.length >= 2) {
        const lng = Number(raw[0]);
        const lat = Number(raw[1]);

        if (Number.isFinite(lat) && Number.isFinite(lng)) {
            return { lat, lng };
        }

        return null;
    }

    if (typeof raw !== 'object') {
        return null;
    }

    const latRaw = raw.lat ?? raw.latitude ?? raw.latitud;
    const lngRaw = raw.lng ?? raw.lon ?? raw.longitud ?? raw.longitude;

    const lat = Number(typeof latRaw === 'function' ? latRaw() : latRaw);
    const lng = Number(typeof lngRaw === 'function' ? lngRaw() : lngRaw);

    if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
        return null;
    }

    return { lat, lng };
}

function parsePoligonoGuardado(rawValue) {
    if (!rawValue) {
        return [];
    }

    let parsed = rawValue;
    while (typeof parsed === 'string') {
        try {
            parsed = JSON.parse(parsed);
        } catch (_error) {
            return [];
        }
    }

    // Soporte GeoJSON { type: 'Polygon', coordinates: [[[lng,lat],...]] }
    if (parsed && parsed.type === 'Polygon' && Array.isArray(parsed.coordinates)) {
        const ring = parsed.coordinates[0] || [];
        const coords = ring
            .map((pair) => (Array.isArray(pair) && pair.length >= 2 ? { lat: Number(pair[1]), lng: Number(pair[0]) } : null))
            .filter((point) => point && Number.isFinite(point.lat) && Number.isFinite(point.lng));

        return coords.length ? [coords] : [];
    }

    if (!Array.isArray(parsed) || parsed.length === 0) {
        return [];
    }

    // Formato [[[lng,lat],...], ...] o [[{lat,lng},...], ...]
    if (Array.isArray(parsed[0])) {
        return parsed
            .map((seccion) => (Array.isArray(seccion) ? seccion.map(parsePunto).filter(Boolean) : []))
            .filter((seccion) => seccion.length > 0);
    }

    // Formato [{lat,lng}, ...]
    if (typeof parsed[0] === 'object' && parsed[0] !== null) {
        const coords = parsed.map(parsePunto).filter(Boolean);
        return coords.length ? [coords] : [];
    }

    return [];
}

function normalizarPoligono(poligonoGuardado) {
    return parsePoligonoGuardado(poligonoGuardado);
}

function toLatLng(raw) {
    if (!raw) {
        return null;
    }

    const latRaw = raw.lat ?? raw.latitude ?? raw.latitud;
    const lngRaw = raw.lng ?? raw.lon ?? raw.longitud ?? raw.longitude;
    const lat = Number(typeof latRaw === 'function' ? latRaw() : latRaw);
    const lng = Number(typeof lngRaw === 'function' ? lngRaw() : lngRaw);

    if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
        return null;
    }

    return createPoint(lat, lng);
}

function obtenerEstiloPoligono(isEditMode = false, estadoLote = null) {
    if (!isEditMode) {
        return {
            strokeColor: '#00c853',
            strokeOpacity: 1,
            strokeWeight: 3,
            fillColor: '#00c853',
            fillOpacity: 0.35,
        };
    }

    return estadoLote === '0'
        ? {
              strokeColor: '#f44336',
              strokeOpacity: 1,
              strokeWeight: 3,
              fillColor: '#f44336',
              fillOpacity: 0.5,
          }
        : {
              strokeColor: '#ffeb3b',
              strokeOpacity: 1,
              strokeWeight: 3,
              fillColor: '#ffeb3b',
              fillOpacity: 0.5,
          };
}

function iniciarMapa(poligonoGuardado = null, isEditMode = false, estadoLote = null) {
    const mapElement = document.getElementById('map');
    if (!mapElement) {
        return;
    }

    if (usesLeafletMaps()) {
        if (map && typeof map.remove === 'function') {
            map.remove();
        }

        map = L.map(mapElement, {
            zoomControl: true,
            attributionControl: true,
            doubleClickZoom: false,
        }).setView([DEFAULT_MAP_CENTER.lat, DEFAULT_MAP_CENTER.lng], 18);

        L.tileLayer(LEAFLET_SATELLITE_TILES, {
            maxZoom: 20,
            attribution: '&copy; Esri',
        }).addTo(map);

        poligonoActivo = null;
        marcadorUsuario = null;
        leafletPolygonPoints = [];
        limpiarMarcadoresVertices();
        detenerTracking();

        const handleMapPointerLeaflet = (event) => {
            const punto = toLatLng(event?.latlng || event);
            if (punto) {
                agregarPunto(punto);
            }
        };

        map.on('click', handleMapPointerLeaflet);
        map.on('contextmenu', handleMapPointerLeaflet);

        const seccionesLeaflet = normalizarPoligono(poligonoGuardado);
        if (seccionesLeaflet.length > 0 && seccionesLeaflet[0].length > 0) {
            crearPoligonoDesdePuntos(seccionesLeaflet[0], isEditMode, estadoLote);
            ajustarVista(seccionesLeaflet[0]);
        }

        setTimeout(() => map.invalidateSize(), 50);
        actualizarAreaTotal();
        return;
    }

    if (typeof google === 'undefined' || !google.maps) {
        return;
    }

    map = new google.maps.Map(mapElement, {
        center: DEFAULT_MAP_CENTER,
        zoom: 19,
        mapTypeId: 'satellite',
        mapTypeControl: false,
        fullscreenControl: true,
        streetViewControl: false,
        draggableCursor: 'crosshair',
        disableDoubleClickZoom: true,
        gestureHandling: 'greedy',
    });

    poligonoActivo = null;
    marcadorUsuario = null;
    limpiarMarcadoresVertices();
    detenerTracking();

    initDrawingManager();

    const handleMapPointer = (event) => {
        const punto = toLatLng(event?.latLng);
        if (punto) {
            agregarPunto(punto);
        }
    };

    map.addListener('click', handleMapPointer);
    map.addListener('dblclick', handleMapPointer);
    map.addListener('rightclick', handleMapPointer);

    const secciones = normalizarPoligono(poligonoGuardado);
    if (secciones.length > 0 && secciones[0].length > 0) {
        crearPoligonoDesdePuntos(secciones[0], isEditMode, estadoLote);
        ajustarVista(secciones[0]);
    }

    actualizarAreaTotal();
}

function crearNuevoPoligono() {
    if (!map) {
        return;
    }

    if (usesLeafletMaps()) {
        leafletPolygonPoints = [];
        removePolygonLayer();
        limpiarMarcadoresVertices();
        actualizarAreaTotal();
        return;
    }

    if (poligonoActivo) {
        poligonoActivo.setMap(null);
    }

    poligonoActivo = new google.maps.Polygon({
        paths: [],
        strokeColor: '#00FF00',
        strokeOpacity: 1,
        strokeWeight: 2,
        fillColor: '#00FF00',
        fillOpacity: 0.4,
        editable: true,
        draggable: true,
        clickable: false,
        map,
    });
    estiloPoligonoActual = {
        fillColor: '#00FF00',
    };

    activarEventosEdicion();
    syncMarcadoresVertices();
}

function crearPoligonoDesdePuntos(puntos, isEditMode = false, estadoLote = null) {
    const coords = puntos
        .map((punto) => parsePunto(punto))
        .filter(Boolean);

    if (!coords.length) {
        return;
    }

    if (usesLeafletMaps()) {
        leafletPolygonPoints = coords.map((punto) => createPoint(punto.lat, punto.lng)).filter(Boolean);
        renderLeafletPolygon(isEditMode, estadoLote);
        return;
    }

    const estiloPoligono = obtenerEstiloPoligono(isEditMode, estadoLote);
    removePolygonLayer();

    // Crear el poligono directamente con los puntos guardados evita estados intermedios sin render.
    poligonoActivo = new google.maps.Polygon({
        paths: coords,
        editable: true,
        draggable: true,
        clickable: true,
        map,
        ...estiloPoligono,
    });

    estiloPoligonoActual = {
        fillColor: estiloPoligono.fillColor,
    };
    activarEventosEdicion();
    syncMarcadoresVertices();
    actualizarAreaTotal();
}

function initDrawingManager() {
    if (!map || typeof google === 'undefined' || !google.maps?.drawing) {
        return;
    }

    if (drawingManager) {
        drawingManager.setMap(null);
    }

    drawingManager = new google.maps.drawing.DrawingManager({
        drawingMode: null,
        drawingControl: false,
        polygonOptions: {
            strokeColor: '#00FF00',
            strokeOpacity: 1,
            strokeWeight: 2,
            fillColor: '#00FF00',
            fillOpacity: 0.4,
            editable: true,
            draggable: true,
            clickable: true,
        },
    });

    drawingManager.setMap(map);

    google.maps.event.addListener(drawingManager, 'overlaycomplete', (event) => {
        if (event.type !== google.maps.drawing.OverlayType.POLYGON) {
            return;
        }

        if (poligonoActivo) {
            poligonoActivo.setMap(null);
        }

        poligonoActivo = event.overlay;
        poligonoActivo.setOptions({ editable: true, draggable: true, clickable: true });
        estiloPoligonoActual = {
            fillColor: '#00FF00',
        };
        drawingManager.setDrawingMode(null);
        activarEventosEdicion();
        syncMarcadoresVertices();
        actualizarAreaTotal();
    });
}

function limpiarMarcadoresVertices() {
    marcadoresVertices.forEach((marker) => {
        if (marker && typeof marker.setMap === 'function') {
            marker.setMap(null);
            return;
        }

        if (map?.removeLayer) {
            map.removeLayer(marker);
            return;
        }

        if (marker && typeof marker.remove === 'function') {
            marker.remove();
        }
    });
    marcadoresVertices = [];
}

function syncMarcadoresVertices() {
    limpiarMarcadoresVertices();

    if (!map || !poligonoActivo) {
        return;
    }

    if (usesLeafletMaps()) {
        const puntosLeaflet = leafletPolygonPoints.map(getPointCoords).filter(Boolean);
        const fillColor = estiloPoligonoActual?.fillColor || '#00FF00';

        puntosLeaflet.forEach((punto, index) => {
            const marker = L.marker([punto.lat, punto.lng], {
                icon: createLeafletVertexIcon(index, fillColor),
                keyboard: false,
                interactive: false,
            }).addTo(map);

            marcadoresVertices.push(marker);
        });

        return;
    }

    const puntos = poligonoActivo.getPath().getArray();
    const fillColor = estiloPoligonoActual?.fillColor || '#00FF00';
    puntos.forEach((punto, index) => {
        const marker = new google.maps.Marker({
            map,
            position: punto,
            clickable: false,
            draggable: false,
            icon: {
                path: google.maps.SymbolPath.CIRCLE,
                scale: 6,
                fillColor,
                fillOpacity: 1,
                strokeColor: '#000000',
                strokeWeight: 2,
            },
            label: {
                text: String(index + 1),
                color: '#000000',
                fontSize: '10px',
                fontWeight: '700',
            },
            zIndex: 999,
        });

        marcadoresVertices.push(marker);
    });
}

function activarEventosEdicion() {
    if (usesLeafletMaps()) {
        return;
    }

    if (!poligonoActivo) {
        return;
    }

    const path = poligonoActivo.getPath();
    ['set_at', 'insert_at', 'remove_at'].forEach((evento) => {
        path.addListener(evento, () => {
            syncMarcadoresVertices();
            actualizarAreaTotal();
        });
    });

    google.maps.event.addListener(poligonoActivo, 'dragend', () => {
        syncMarcadoresVertices();
        actualizarAreaTotal();
    });

    google.maps.event.addListener(poligonoActivo, 'click', (event) => {
        const punto = toLatLng(event?.latLng);
        if (punto) {
            agregarPunto(punto);
        }
    });
}

function agregarPunto(latLng) {
    if (usesLeafletMaps()) {
        const puntoLeaflet = toLatLng(latLng);
        if (!puntoLeaflet) {
            return;
        }

        leafletPolygonPoints.push(puntoLeaflet);
        renderLeafletPolygon();
        return;
    }

    if (!poligonoActivo) {
        crearNuevoPoligono();
    }

    const punto = toLatLng(latLng);
    if (!punto) {
        return;
    }

    poligonoActivo.getPath().push(punto);
    syncMarcadoresVertices();
    actualizarAreaTotal();
}

function deshacer() {
    if (usesLeafletMaps()) {
        if (!leafletPolygonPoints.length) {
            return;
        }

        leafletPolygonPoints.pop();
        renderLeafletPolygon();
        return;
    }

    if (!poligonoActivo || poligonoActivo.getPath().getLength() === 0) {
        return;
    }

    poligonoActivo.getPath().pop();
    syncMarcadoresVertices();
    actualizarAreaTotal();
}

function activarDibujo() {
    if (usesLeafletMaps()) {
        if (!leafletPolygonPoints.length) {
            crearNuevoPoligono();
        }
        return;
    }

    if (drawingManager && typeof google !== 'undefined' && google.maps?.drawing) {
        drawingManager.setDrawingMode(google.maps.drawing.OverlayType.POLYGON);
        if (map) {
            map.setOptions({ draggableCursor: 'crosshair' });
        }
        return;
    }

    if (!poligonoActivo) {
        crearNuevoPoligono();
    }
}

function nuevoPoligonoManual() {
    resetMapa();
    activarDibujo();
}

function iniciarTracking(buttonEl = null) {
    if (!map) {
        Swal.fire('Mapa no disponible', 'Abre el formulario y espera a que cargue el mapa.', 'warning');
        return;
    }

    if (!navigator.geolocation) {
        Swal.fire('No compatible', 'Tu navegador no soporta geolocalización.', 'error');
        return;
    }

    if (!window.isSecureContext && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
        Swal.fire(
            'Contexto inseguro',
            'La geolocalización requiere HTTPS o localhost. Abre el sistema por localhost para usar Caminar lote.',
            'warning'
        );
        return;
    }

    if (trackingActivo) {
        detenerTracking(buttonEl, false, true);
        return;
    }

    // Evitar dos watchers de geolocalización simultáneos en el mismo mapa.
    detenerUbicacionTiempoReal();

    if (drawingManager && typeof google !== 'undefined' && google.maps?.drawing) {
        drawingManager.setDrawingMode(null);
    }

    if (!poligonoActivo) {
        crearNuevoPoligono();
    }

    trackingActivo = true;
    ultimoPuntoTracking = null;
    trackingErrorMostrado = false;
    setTrackingButtonState(true, buttonEl);

    const iniciarSeguimiento = () => {
        iniciarWatchTracking(buttonEl);
    };

    const solicitarConPrecisionBaja = () => {
        navigator.geolocation.getCurrentPosition(
            (posicion) => {
                const punto = createPoint(posicion.coords.latitude, posicion.coords.longitude);
                actualizarMarcadorUsuario(punto);
                agregarPunto(punto);
                ultimoPuntoTracking = punto;
                iniciarSeguimiento();
            },
            (error) => {
                detenerTracking(buttonEl);
                Swal.fire('GPS no disponible', getGeoErrorMessage(error), 'error');
            },
            {
                enableHighAccuracy: false,
                timeout: 20000,
                maximumAge: 5000,
            }
        );
    };

    if (navigator.permissions?.query) {
        navigator.permissions
            .query({ name: 'geolocation' })
            .then((estado) => {
                if (estado.state === 'denied') {
                    detenerTracking(buttonEl);
                    Swal.fire(
                        'Permiso bloqueado',
                        'La ubicación está bloqueada para este sitio. Habilítala en el navegador y vuelve a intentar.',
                        'warning'
                    );
                    return;
                }

                navigator.geolocation.getCurrentPosition(
                    (posicion) => {
                        const punto = createPoint(posicion.coords.latitude, posicion.coords.longitude);
                        actualizarMarcadorUsuario(punto);
                        agregarPunto(punto);
                        ultimoPuntoTracking = punto;
                        iniciarSeguimiento();
                    },
                    (error) => {
                        if (error?.code === error.POSITION_UNAVAILABLE || error?.code === error.TIMEOUT) {
                            solicitarConPrecisionBaja();
                            return;
                        }

                        detenerTracking(buttonEl);
                        Swal.fire('GPS no disponible', getGeoErrorMessage(error), 'error');
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 15000,
                        maximumAge: 0,
                    }
                );
            })
            .catch(() => {
                solicitarConPrecisionBaja();
            });
        return;
    }

    solicitarConPrecisionBaja();
}

function resetMapa() {
    detenerTracking();
    detenerUbicacionTiempoReal();

    removePolygonLayer();
    leafletPolygonPoints = [];

    limpiarMarcadoresVertices();

    if (marcadorUsuario) {
        if (usesLeafletMaps() && map?.removeLayer) {
            map.removeLayer(marcadorUsuario);
        } else {
            marcadorUsuario.setMap(null);
        }
        marcadorUsuario = null;
    }

    const areaInput = document.getElementById('area');
    const areaLabel = document.getElementById('area-label');
    const perimetroLabel = document.getElementById('perimetro-label');
    const poligonoInput = document.getElementById('poligono');

    if (areaInput) areaInput.value = '';
    if (areaLabel) areaLabel.innerText = '0.00 Ha';
    if (perimetroLabel) perimetroLabel.innerText = 'Perímetro: 0.00 m';
    if (poligonoInput) poligonoInput.value = '';

    if (map) {
        crearNuevoPoligono();
    }
}

function actualizarAreaTotal() {
    let areaTotal = 0;
    let perimetroTotal = 0;
    let coordenadas = [];

    const puntosActuales = getPolygonPoints();

    if (puntosActuales.length >= 3) {
        areaTotal = usesGoogleMaps() && poligonoActivo
            ? google.maps.geometry.spherical.computeArea(poligonoActivo.getPath())
            : computeGeodesicAreaMeters(puntosActuales);
        coordenadas = [puntosActuales];
    }

    if (puntosActuales.length >= 2) {
        perimetroTotal = usesGoogleMaps() && poligonoActivo
            ? google.maps.geometry.spherical.computeLength(poligonoActivo.getPath())
            : computePerimeterMeters(puntosActuales);
    }

    const areaHa = areaTotal / 10000;
    const areaInput = document.getElementById('area');
    const areaLabel = document.getElementById('area-label');
    const perimetroLabel = document.getElementById('perimetro-label');
    const poligonoInput = document.getElementById('poligono');

    if (areaInput) areaInput.value = areaHa.toFixed(2);
    if (areaLabel) areaLabel.innerText = `${areaHa.toFixed(2)} Ha`;
    if (perimetroLabel) perimetroLabel.innerText = `Perímetro: ${perimetroTotal.toFixed(2)} m`;
    if (poligonoInput) poligonoInput.value = JSON.stringify(coordenadas);
}

function ajustarVista(coords) {
    if (!coords.length) {
        return;
    }

    if (usesLeafletMaps()) {
        const bounds = L.latLngBounds(coords.map((punto) => [punto.lat, punto.lng]));
        map.fitBounds(bounds, { padding: [20, 20] });
        return;
    }

    const bounds = new google.maps.LatLngBounds();
    coords.forEach((punto) => bounds.extend(new google.maps.LatLng(punto.lat, punto.lng)));
    map.fitBounds(bounds);
}

function mostrarUbicacion(buttonEl = null) {
    if (!navigator.geolocation || !map) {
        Swal.fire('Error', 'No fue posible obtener la ubicación actual.', 'error');
        return;
    }

    if (!window.isSecureContext && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
        Swal.fire(
            'Contexto inseguro',
            'La geolocalización en tiempo real requiere HTTPS o localhost.',
            'warning'
        );
        return;
    }

    const boton = resolveUbicacionButton(buttonEl);

    if (ubicacionTiempoRealActiva) {
        detenerUbicacionTiempoReal(boton, true);
        return;
    }

    ubicacionTiempoRealActiva = true;
    setUbicacionButtonState(true, boton);

    ubicacionWatchId = navigator.geolocation.watchPosition(
        (posicion) => {
            const latLng = createPoint(posicion.coords.latitude, posicion.coords.longitude);
            actualizarMarcadorUsuario(latLng);

            if (usesLeafletMaps()) {
                if ((map.getZoom() || 0) < 18) {
                    map.setZoom(18);
                }
                return;
            }

            if ((map.getZoom() || 0) < 18) {
                map.setZoom(18);
            }
        },
        (error) => {
            detenerUbicacionTiempoReal(boton);
            Swal.fire('GPS no disponible', getGeoErrorMessage(error), 'warning');
        },
        {
            enableHighAccuracy: true,
            timeout: 15000,
            maximumAge: 1000,
        }
    );
}

document.addEventListener('DOMContentLoaded', () => {
    const tabla = document.getElementById('tablaLotes');
    const filas = Array.from(tabla.tBodies[0].rows);
    const inputBusqueda = document.getElementById('inputBusqueda');
    const perPageSelect = document.getElementById('customPerPage');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const modalCrear = document.getElementById('modalLotes');
    const modalEditar = document.getElementById('modalLotesEdit');

    function mostrarErrores(error) {
        if (error && error.errors) {
            let mensajes = '<ul style="text-align:left; margin:0; padding-left:18px;">';
            Object.values(error.errors).flat().forEach((msg) => {
                mensajes += `<li>${msg}</li>`;
            });
            mensajes += '</ul>';
            Swal.fire({ title: 'Error de validación', html: mensajes, icon: 'error' });
            return;
        }

        Swal.fire('Error', error?.message || 'No se pudo procesar la solicitud.', 'error');
    }

    function bindAjaxForm(modalId, contentId, successMessage) {
        const modalElement = document.getElementById(modalId);
        const container = document.getElementById(contentId);
        const form = container.querySelector('form');
        if (!form || form.dataset.ajaxBound === 'true') {
            return;
        }

        form.dataset.ajaxBound = 'true';
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(form);
            formData.set('_token', csrfToken);

            const requestUrl = new URL(form.action, window.location.origin);
            const relativeAction = `${requestUrl.pathname}${requestUrl.search}`;

            fetch(relativeAction, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
            })
                .then(async (response) => {
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok) throw data;
                    return data;
                })
                .then((data) => {
                    bootstrap.Modal.getInstance(modalElement)?.hide();
                    Swal.fire('Éxito', data.success || successMessage, 'success').then(() => location.reload());
                })
                .catch(mostrarErrores);
        });
    }

    function mostrarFilas(filasVisibles) {
        filas.forEach((fila) => {
            fila.style.display = 'none';
        });
        filasVisibles.forEach((fila) => {
            fila.style.display = '';
        });
    }

    function filtrarTabla() {
        const texto = inputBusqueda.value.toLowerCase();
        const filtradas = filas.filter((fila) =>
            Array.from(fila.cells).some((celda) => celda.textContent.toLowerCase().includes(texto))
        );
        mostrarFilas(filtradas.slice(0, parseInt(perPageSelect.value, 10)));
    }

    function limpiarModal(modalElement, contentId) {
        detenerTracking();
        detenerUbicacionTiempoReal();

        document.getElementById(contentId).innerHTML = '';
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach((backdrop) => backdrop.remove());
        document.body.classList.remove('modal-open');
        document.body.style.paddingRight = '';
        document.body.style.overflow = '';

        removePolygonLayer();

        if (usesLeafletMaps() && map && typeof map.remove === 'function') {
            map.remove();
        }

        limpiarMarcadoresVertices();
        map = null;
        poligonoActivo = null;
        marcadorUsuario = null;
        activeMapProvider = null;
        leafletPolygonPoints = [];
    }

    function prepararMapaEnModal(modalElement) {
        modalElement.addEventListener(
            'shown.bs.modal',
            async () => {
                const isEditMode = modalElement.id === 'modalLotesEdit';
                const estadoSelect = modalElement.querySelector('select[name="estado"]');
                const estadoLote = estadoSelect ? String(estadoSelect.value) : null;
                const poligonoInput = modalElement.querySelector('#poligono');

                // Preferir data-poligono (valor PHP sin htmlentities) sobre value.
                let rawPoligono = poligonoInput?.dataset?.poligono || poligonoInput?.value || '';
                const poligonoGuardado = normalizarPoligono(rawPoligono);

                const provider = await ensureMapProviderReady();
                if (!provider) {
                    setMapAvailabilityState(modalElement, false);
                    return;
                }

                setMapAvailabilityState(modalElement, true);

                setTimeout(() => {
                    iniciarMapa(poligonoGuardado, isEditMode, estadoLote);

                    if (usesGoogleMaps() && map) {
                        google.maps.event.trigger(map, 'resize');
                    } else if (usesLeafletMaps() && map?.invalidateSize) {
                        map.invalidateSize();
                    }

                    // Si hay polígono guardado, centrar y ajustar vista automáticamente.
                    if (poligonoGuardado.length > 0 && poligonoGuardado[0].length > 0 && map) {
                        setTimeout(() => {
                            const puntos = poligonoGuardado[0].map(parsePunto).filter(Boolean);
                            if (puntos.length) {
                                ajustarVista(puntos);
                            }
                        }, 150);
                    }

                    if (isEditMode && estadoSelect) {
                        estadoSelect.addEventListener('change', () => {
                            if (usesLeafletMaps()) {
                                renderLeafletPolygon(true, String(estadoSelect.value));
                                return;
                            }

                            if (!poligonoActivo) {
                                return;
                            }
                            const nuevoEstilo = obtenerEstiloPoligono(true, String(estadoSelect.value));
                            poligonoActivo.setOptions(nuevoEstilo);
                            estiloPoligonoActual = {
                                fillColor: nuevoEstilo.fillColor,
                            };
                            syncMarcadoresVertices();
                        });
                    }
                }, 300);
            },
            { once: true }
        );
    }

    function sanitizeCreateModalHtml(rawHtml) {
        const wrapper = document.createElement('div');
        wrapper.innerHTML = rawHtml;

        // Si accidentalmente devolvieron un modal completo, tomar solo su contenido.
        const nestedModalContent = wrapper.querySelector('#modalLotes .modal-content');
        if (nestedModalContent) {
            return nestedModalContent.innerHTML;
        }

        // Evitar scripts inline/externos que rompen el flujo AJAX del modal principal.
        wrapper.querySelectorAll('script').forEach((node) => node.remove());

        // Mantener encabezado, formulario y estilos del parcial cargado.
        // El edit de lotes define el encabezado fuera del <form>, así que no se puede recortar solo al form.

        return wrapper.innerHTML;
    }

    inputBusqueda.addEventListener('input', filtrarTabla);
    perPageSelect.addEventListener('change', filtrarTabla);
    mostrarFilas(filas.slice(0, parseInt(perPageSelect.value, 10)));

    modalCrear.addEventListener('hidden.bs.modal', () => limpiarModal(modalCrear, 'modalContent'));
    modalEditar.addEventListener('hidden.bs.modal', () => limpiarModal(modalEditar, 'modalContentEdit'));

    document.getElementById('btnAbrirModal').addEventListener('click', () => {
        fetch("{{ route('lotes.create') }}")
            .then((res) => res.text())
            .then((html) => {
                const cleanHtml = sanitizeCreateModalHtml(html);
                document.getElementById('modalContent').innerHTML = cleanHtml;
                bindAjaxForm('modalLotes', 'modalContent', 'Lote registrado correctamente');
                prepararMapaEnModal(modalCrear);
                new bootstrap.Modal(modalCrear).show();
            });
    });

    document.addEventListener('click', (e) => {
        const editarBtn = e.target.closest('.btnEditarLotes');
        if (editarBtn) {
            e.preventDefault();
            const id = editarBtn.dataset.id;
            fetch(`/lotes/${id}/edit`)
                .then((res) => res.text())
                .then((html) => {
                    const cleanHtml = sanitizeCreateModalHtml(html);
                    document.getElementById('modalContentEdit').innerHTML = cleanHtml;
                    bindAjaxForm('modalLotesEdit', 'modalContentEdit', 'Lote actualizado correctamente');
                    prepararMapaEnModal(modalEditar);
                    new bootstrap.Modal(modalEditar).show();
                });
            return;
        }

        const eliminarBtn = e.target.closest('.btnEliminarLotes');
        if (!eliminarBtn) {
            return;
        }

        const id = eliminarBtn.dataset.id;
        Swal.fire({
            title: '¿Estás seguro?',
            text: 'Esta acción no se puede deshacer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
        }).then((result) => {
            if (!result.isConfirmed) {
                return;
            }

            fetch(`/lotes/${id}`, {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
            })
                .then(async (response) => {
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok) throw data;
                    return data;
                })
                .then((data) => {
                    Swal.fire('Éxito', data.success || 'Lote eliminado correctamente', 'success').then(() => location.reload());
                })
                .catch(mostrarErrores);
        });
    });
});
</script>
@endsection