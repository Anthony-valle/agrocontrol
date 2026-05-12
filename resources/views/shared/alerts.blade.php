@if(isset($insumosVencer) && count($insumosVencer) || isset($insumosBajoStock) && count($insumosBajoStock))
<div class="alert alert-warning alert-dismissible fade show d-flex align-items-center" role="alert" id="alerta-insumos">
    <i class="bi bi-bell-fill me-2"></i>
    <div>
        @if(isset($insumosVencer) && count($insumosVencer))
            <strong>¡Atención!</strong> Hay insumos próximos a vencer.<br>
        @endif
        @if(isset($insumosBajoStock) && count($insumosBajoStock))
            <strong>¡Atención!</strong> Hay insumos con bajo stock.
        @endif
    </div>
    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close" onclick="marcarAlertasLeidas()"></button>
</div>
@endif
<script>
function marcarAlertasLeidas() {
    fetch('/alertas/leidas', {method: 'POST', headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'}})
        .then(() => {
            const alerta = document.getElementById('alerta-insumos');
            if(alerta) alerta.remove();
        });
}
</script>
