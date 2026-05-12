@extends('layouts.main')

@section('titulo', 'Soporte del Sistema')

@section('contenido')
<main id="main" class="main">
    @if(session('backup_file'))
        @php
            $backupDownloadUrl = route('soporte.backup.download', session('backup_file'));
            $backupFileName = session('backup_file');
            $backupGeneratedAt = session('backup_generated_at');
        @endphp
    @endif

    <div class="pagetitle">
        <h1>Soporte del Sistema</h1>
        <p class="text-muted mb-0">Respaldo técnico y recuperación del sistema AgroControl.</p>
    </div>

    <section class="section">
        @if(session('backup_file'))
            <div class="alert alert-success d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                <div>
                    <strong>Backup generado.</strong>
                    @if(!empty($backupGeneratedAt))
                        <span class="d-block small text-muted">Fecha del respaldo: {{ $backupGeneratedAt }}</span>
                    @endif
                    <span id="backup-download-status">Preparando descarga automatica...</span>
                </div>
                <a id="manual-backup-download" href="{{ $backupDownloadUrl }}" class="btn btn-success btn-sm">
                    <i class="bi bi-download me-1"></i>Descargar ahora
                </a>
            </div>
        @endif

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h5 class="card-title mb-3">¿Cómo funciona el backup?</h5>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="p-3 rounded border h-100 bg-light">
                            <span class="badge bg-primary mb-2">Paso 1</span>
                            <h6 class="mb-2">Generar respaldo</h6>
                            <p class="text-muted mb-0">Presiona <strong>Generar backup ahora</strong>. El sistema crea un archivo ZIP con base de datos y archivos del proyecto.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 rounded border h-100 bg-light">
                            <span class="badge bg-success mb-2">Paso 2</span>
                            <h6 class="mb-2">Descargar y guardar</h6>
                            <p class="text-muted mb-0">Desde el historial, descarga el ZIP y guárdalo en un lugar seguro (idealmente en otra máquina o nube).</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 rounded border h-100 bg-light">
                            <span class="badge bg-dark mb-2">Paso 3</span>
                            <h6 class="mb-2">Restaurar</h6>
                            <p class="text-muted mb-0">Copia los archivos del ZIP, instala dependencias con <strong>composer install</strong> e importa <strong>database_backup.sql</strong> en MySQL.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h5 class="card-title mb-3">Backup completo</h5>
                        <p class="text-muted mb-3">
                            Genera un archivo ZIP con copia de la base de datos (SQL) y los archivos del proyecto,
                            listo para restauración o migración.
                        </p>
                        <div class="alert alert-secondary mb-3" role="alert">
                            El respaldo se guarda en <strong>storage/app/backups</strong>, se descarga automaticamente en tu navegador e incluye una carpeta <strong>reporteria_snapshot</strong> con archivos CSV listos para abrir en Excel de cultivos, insumos, inventario, movimientos y otros modulos clave.
                        </div>
                        <form method="POST" action="{{ route('soporte.backup.create') }}">
                            @csrf
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-cloud-arrow-down me-2"></i>Generar backup ahora
                            </button>
                        </form>
                        <div class="alert alert-info mt-3 mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            Recomendación: ejecutar el backup antes de cambios mayores o despliegues.
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h5 class="card-title mb-3">Historial de respaldos</h5>

                        <div class="table-responsive">
                            <table class="table align-middle table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Archivo</th>
                                        <th>Tamaño</th>
                                        <th>Fecha</th>
                                        <th class="text-end">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($files as $backup)
                                        <tr>
                                            <td class="fw-semibold">{{ $backup['file'] }}</td>
                                            <td>{{ agro_number($backup['size'] / 1048576, 2) }} MB</td>
                                            <td>{{ \Carbon\Carbon::createFromTimestamp($backup['last_modified'])->format('d/m/Y H:i') }}</td>
                                            <td class="text-end">
                                                <a href="{{ route('soporte.backup.download', $backup['file']) }}" class="btn btn-sm btn-outline-success">
                                                    <i class="bi bi-download me-1"></i>Descargar
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">Todavía no hay respaldos generados.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

@if(session('backup_file'))
    <script>
        window.addEventListener('load', function () {
            var backupUrl = @json($backupDownloadUrl);
            var backupFileName = @json($backupFileName);
            var statusNode = document.getElementById('backup-download-status');

            if (!backupUrl) {
                if (statusNode) {
                    statusNode.textContent = 'No se encontro el archivo para descarga automatica.';
                }
                return;
            }

            fetch(backupUrl, {
                method: 'GET',
                credentials: 'same-origin'
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }
                    return response.blob();
                })
                .then(function (blob) {
                    var objectUrl = URL.createObjectURL(blob);
                    var anchor = document.createElement('a');
                    anchor.href = objectUrl;
                    anchor.download = backupFileName || 'agrocontrol_backup.zip';
                    document.body.appendChild(anchor);
                    anchor.click();
                    anchor.remove();
                    URL.revokeObjectURL(objectUrl);

                    if (statusNode) {
                        statusNode.textContent = 'Descarga iniciada correctamente.';
                    }
                })
                .catch(function (error) {
                    if (statusNode) {
                        statusNode.textContent = 'No se pudo iniciar la descarga automatica (' + error.message + '). Usa el boton Descargar ahora.';
                    }
                });
        });
    </script>
@endif
@endsection
