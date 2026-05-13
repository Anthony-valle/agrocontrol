@extends('layouts.main')

@section('titulo', 'Vista de Factura de Entrada')

@section('contenido')
<main id="main" class="main">
    <style>
        .factura-preview-shell {
            border-radius: 1.25rem;
            overflow: hidden;
        }

        .factura-preview-meta {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.9rem;
        }

        .factura-preview-chip {
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 1rem;
            padding: 0.95rem 1rem;
            background: #f8fafc;
        }

        .factura-preview-chip small {
            display: block;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.04em;
            color: #64748b;
            margin-bottom: 0.3rem;
        }

        .factura-preview-frame {
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 1rem;
            background: #f8fafc;
            min-height: 72vh;
            overflow: hidden;
            position: relative;
        }

        .factura-preview-frame iframe,
        .factura-preview-frame img {
            width: 100%;
            height: 72vh;
            border: 0;
            display: block;
            background: #fff;
        }

        .factura-preview-toolbar {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .factura-preview-toolbar-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .factura-preview-note {
            color: #64748b;
            font-size: 0.9rem;
            margin: 0;
        }

        .factura-preview-image-stage {
            position: relative;
            width: 100%;
            height: 72vh;
            overflow: hidden;
            background:
                linear-gradient(45deg, #eef2f7 25%, transparent 25%),
                linear-gradient(-45deg, #eef2f7 25%, transparent 25%),
                linear-gradient(45deg, transparent 75%, #eef2f7 75%),
                linear-gradient(-45deg, transparent 75%, #eef2f7 75%);
            background-size: 22px 22px;
            background-position: 0 0, 0 11px, 11px -11px, -11px 0;
            cursor: grab;
            touch-action: none;
        }

        .factura-preview-image-stage.is-dragging {
            cursor: grabbing;
        }

        .factura-preview-image {
            width: 100%;
            height: 100%;
            object-fit: contain;
            object-position: center;
            transform-origin: center center;
            user-select: none;
            -webkit-user-drag: none;
            transition: transform 0.12s ease-out;
        }

        .factura-preview-empty {
            min-height: 72vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 2rem;
        }

        @media (max-width: 991.98px) {
            .factura-preview-meta {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 575.98px) {
            .factura-preview-meta {
                grid-template-columns: 1fr;
            }

            .factura-preview-frame,
            .factura-preview-image-stage,
            .factura-preview-frame iframe,
            .factura-preview-frame img,
            .factura-preview-empty {
                min-height: 60vh;
                height: 60vh;
            }
        }
    </style>

    <section class="section">
        <div class="card shadow-sm border-0 factura-preview-shell">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h5 class="card-title mb-1 fw-bold">
                        <i class="fa-solid fa-file-invoice me-2 text-primary"></i>
                        Vista de Factura
                    </h5>
                    <p class="text-muted mb-0">{{ $facturaNombre }}</p>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('reporteria.facturas_entradas') }}" class="btn btn-outline-secondary btn-sm">Volver</a>
                    <a href="{{ $facturaUrl }}" class="btn btn-primary btn-sm">Abrir archivo</a>
                </div>
            </div>

            <div class="card-body p-4">
                <div class="factura-preview-meta mb-4">
                    <div class="factura-preview-chip">
                        <small>Fecha</small>
                        <div>{{ $entrada->fecha_ingreso ? \Carbon\Carbon::parse($entrada->fecha_ingreso)->format('d/m/Y') : '-' }}</div>
                    </div>
                    <div class="factura-preview-chip">
                        <small>Insumo</small>
                        <div>{{ $entrada->insumo->nombre ?? 'Insumo eliminado' }}</div>
                    </div>
                    <div class="factura-preview-chip">
                        <small>Bodega</small>
                        <div>{{ $entrada->bodega->nombre ?? '-' }}</div>
                    </div>
                    <div class="factura-preview-chip">
                        <small>Proveedor</small>
                        <div>{{ $entrada->proveedor ?: '-' }}</div>
                    </div>
                </div>

                @if($esImagen)
                    <div class="factura-preview-toolbar">
                        <p class="factura-preview-note">La imagen se ajusta sin deformarse. Usa los controles o la rueda del mouse para hacer zoom.</p>
                        <div class="factura-preview-toolbar-actions">
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-zoom-action="out">-</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-zoom-action="reset">Ajustar</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-zoom-action="in">+</button>
                        </div>
                    </div>
                @endif

                <div class="factura-preview-frame">
                    @if($esPdf)
                        <iframe src="{{ $facturaUrl }}" title="Vista previa de factura PDF"></iframe>
                    @elseif($esImagen)
                        <div class="factura-preview-image-stage" id="facturaPreviewStage">
                            <img src="{{ $facturaUrl }}" alt="Vista previa de factura" class="factura-preview-image" id="facturaPreviewImage">
                        </div>
                    @else
                        <div class="factura-preview-empty">
                            <div>
                                <h6 class="fw-bold mb-2">No se puede previsualizar este archivo en pantalla</h6>
                                <p class="text-muted mb-3">Tipo detectado: {{ $mimeType !== '' ? $mimeType : 'desconocido' }}</p>
                                <a href="{{ $facturaUrl }}" class="btn btn-primary btn-sm">Abrir archivo</a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const stage = document.getElementById('facturaPreviewStage');
    const image = document.getElementById('facturaPreviewImage');

    if (!stage || !image) {
        return;
    }

    let scale = 1;
    let translateX = 0;
    let translateY = 0;
    let dragging = false;
    let startX = 0;
    let startY = 0;

    function clampScale(nextScale) {
        return Math.min(4, Math.max(1, nextScale));
    }

    function applyTransform() {
        image.style.transform = `translate(${translateX}px, ${translateY}px) scale(${scale})`;
        stage.classList.toggle('is-dragging', dragging);
    }

    function resetView() {
        scale = 1;
        translateX = 0;
        translateY = 0;
        applyTransform();
    }

    function zoom(delta) {
        const nextScale = clampScale(scale + delta);

        if (nextScale === 1) {
            translateX = 0;
            translateY = 0;
        }

        scale = nextScale;
        applyTransform();
    }

    stage.addEventListener('wheel', function (event) {
        event.preventDefault();
        zoom(event.deltaY < 0 ? 0.2 : -0.2);
    }, { passive: false });

    stage.addEventListener('pointerdown', function (event) {
        if (scale <= 1) {
            return;
        }

        dragging = true;
        startX = event.clientX - translateX;
        startY = event.clientY - translateY;
        stage.setPointerCapture(event.pointerId);
        applyTransform();
    });

    stage.addEventListener('pointermove', function (event) {
        if (!dragging) {
            return;
        }

        translateX = event.clientX - startX;
        translateY = event.clientY - startY;
        applyTransform();
    });

    function stopDragging(event) {
        if (!dragging) {
            return;
        }

        dragging = false;

        if (event && typeof stage.releasePointerCapture === 'function') {
            try {
                stage.releasePointerCapture(event.pointerId);
            } catch (error) {
            }
        }

        applyTransform();
    }

    stage.addEventListener('pointerup', stopDragging);
    stage.addEventListener('pointerleave', stopDragging);
    stage.addEventListener('pointercancel', stopDragging);

    document.querySelectorAll('[data-zoom-action]').forEach(function (button) {
        button.addEventListener('click', function () {
            const action = button.getAttribute('data-zoom-action');

            if (action === 'in') {
                zoom(0.2);
                return;
            }

            if (action === 'out') {
                zoom(-0.2);
                return;
            }

            resetView();
        });
    });

    image.addEventListener('dragstart', function (event) {
        event.preventDefault();
    });

    resetView();
});
</script>
@endsection