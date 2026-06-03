@extends('layouts.main')

@section('titulo', $titulo)

@section('contenido')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>{{ $titulo }}</h1>
        <p class="text-muted mb-0">Selecciona un usuario y define los accesos individuales que tendra dentro del sistema.</p>
    </div>

    <section class="section">
        <div class="row g-4">
            <div class="col-12">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if($selectedUser && in_array('labores', $selectedUser->access_permissions ?? [], true))
                    <div class="alert alert-info border-0 shadow-sm" role="alert">
                        Este usuario tiene el permiso legado Labores. Mientras exista, seguira viendo Mano de Obra, Preparación de Suelo, Mecanización y Reporte de Mano de Obra aunque no esten marcados por separado.
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger" role="alert">
                        <strong>No se pudieron guardar los accesos.</strong>
                        <ul class="mb-0 mt-2 ps-3">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            @php($selectedPermissions = collect(old('access_permissions', $selectedUser->access_permissions ?? []))
                ->when($selectedUser && in_array('labores', $selectedUser->access_permissions ?? [], true), fn ($permissions) => $permissions->merge(['mano_obra', 'preparacion_suelo', 'mecanizacion', 'reporte_mano_obra']))
                ->unique()
                ->values()
                ->all())
            @php($totalPermissions = collect($accessCatalog)->sum(fn ($group) => count($group['items'])))
            @php($activeTabIndex = (int) old('active_tab', 0))

            <div class="col-12">
                <div class="card border-0 shadow-sm access-desktop-card">
                    <div class="access-desktop-toolbar">
                        <div class="access-toolbar-title">Seguridad de Usuarios</div>
                        <div class="access-toolbar-actions">
                            <span class="access-toolbar-chip">{{ $selectedUser ? count($selectedPermissions) : 0 }}/{{ $totalPermissions }} accesos</span>
                            <span class="access-toolbar-chip">{{ collect($accessCatalog)->count() }} menús</span>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        @if($users->isEmpty())
                            <div class="p-4">
                                <div class="alert alert-warning mb-0">No hay usuarios disponibles para administrar accesos.</div>
                            </div>
                        @else
                            <form method="POST" action="{{ $selectedUser ? route('usuarios.access.update', $selectedUser->id) : '#' }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="active_tab" id="active_tab" value="{{ $activeTabIndex }}">

                                <div class="access-sheet-header">
                                    <div class="row g-3 align-items-end">
                                        <div class="col-lg-4 col-md-6">
                                            <label for="user_id" class="access-field-label">Usuario</label>
                                            <div class="access-input-shell">
                                                <select name="user_picker" id="user_id" class="form-select" onchange="window.location=this.options[this.selectedIndex].dataset.url">
                                                    @foreach($users as $userOption)
                                                        <option
                                                            value="{{ $userOption->id }}"
                                                            data-url="{{ route('usuarios.access.index', ['user_id' => $userOption->id]) }}"
                                                            {{ (int) ($selectedUser->id ?? 0) === (int) $userOption->id ? 'selected' : '' }}
                                                        >
                                                            {{ $userOption->nombre_completo }} - {{ $userOption->usuario }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-lg-2 col-md-3 col-6">
                                            <label class="access-field-label">Código</label>
                                            <div class="access-static-field">{{ $selectedUser->id ?? '---' }}</div>
                                        </div>
                                        <div class="col-lg-3 col-md-3 col-6">
                                            <label class="access-field-label">Registro</label>
                                            <div class="access-static-field">{{ optional($selectedUser?->created_at)->format('d/m/y') ?? '---' }}</div>
                                        </div>
                                        <div class="col-lg-3 col-md-6">
                                            <label class="access-field-label">Estado</label>
                                            <div class="access-static-field access-state-field {{ ($selectedUser?->estado ?? false) ? 'is-active' : 'is-inactive' }}">{{ ($selectedUser?->estado ?? false) ? 'Activo' : 'Inactivo' }}</div>
                                        </div>
                                        <div class="col-lg-5 col-md-6">
                                            <label class="access-field-label">Nombre</label>
                                            <div class="access-static-field">{{ $selectedUser->nombre_completo ?? 'Seleccione un usuario' }}</div>
                                        </div>
                                        <div class="col-lg-3 col-md-6">
                                            <label class="access-field-label">Perfil</label>
                                            <div class="access-static-field">{{ $selectedUser->rol->nombre ?? 'Sin rol' }}</div>
                                        </div>
                                        <div class="col-lg-4 col-md-6">
                                            <label class="access-field-label">Sucursal</label>
                                            <div class="access-static-field">{{ $selectedUser->sucursal->nombre ?? 'Sin sucursal' }}</div>
                                        </div>
                                        <div class="col-lg-8">
                                            <label for="bodega_id_consumo" class="access-field-label">Almacén o bodega de consumo</label>
                                            <div class="access-bodega-row">
                                                <div class="grow">
                                                    <select name="bodega_id_consumo" id="bodega_id_consumo" class="form-select @error('bodega_id_consumo') is-invalid @enderror" {{ $selectedUser ? '' : 'disabled' }}>
                                                        <option value="">Sin asignar</option>
                                                        @foreach(($bodegas ?? collect()) as $bodega)
                                                            <option value="{{ $bodega->id }}" {{ (string) old('bodega_id_consumo', $selectedUser->bodega_id_consumo ?? '') === (string) $bodega->id ? 'selected' : '' }}>
                                                                {{ $bodega->nombre }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    @error('bodega_id_consumo')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                                <div class="access-bodega-help">
                                                    {{ $selectedUser?->requiresAssignedConsumptionWarehouse() ? 'Obligatoria' : 'Opcional' }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                @if($selectedUser)
                                    <div class="access-security-bar">Seguridad</div>

                                    <div class="access-tabs-wrap">
                                        <ul class="nav nav-tabs access-classic-tabs" id="accessMenuTabs" role="tablist">
                                            @foreach($accessCatalog as $groupIndex => $group)
                                                @php($assignedInGroup = collect($group['items'])->filter(fn ($item) => ($item['editable'] ?? true) && in_array($item['permission'], $selectedPermissions ?? [], true))->count())
                                                <li class="nav-item" role="presentation">
                                                    <button
                                                        class="nav-link {{ $groupIndex === $activeTabIndex ? 'active' : '' }}"
                                                        id="access-tab-{{ $groupIndex }}"
                                                        data-bs-toggle="tab"
                                                        data-bs-target="#access-pane-{{ $groupIndex }}"
                                                        type="button"
                                                        role="tab"
                                                        aria-controls="access-pane-{{ $groupIndex }}"
                                                        aria-selected="{{ $groupIndex === $activeTabIndex ? 'true' : 'false' }}"
                                                    >
                                                        {{ $groupIndex + 1 }}. {{ $group['title'] }}
                                                        <span class="access-tab-mini-count">{{ $assignedInGroup }}</span>
                                                    </button>
                                                </li>
                                            @endforeach
                                        </ul>

                                        <div class="tab-content access-classic-content">
                                            @foreach($accessCatalog as $groupIndex => $group)
                                                @php($assignedInGroup = collect($group['items'])->filter(fn ($item) => ($item['editable'] ?? true) && in_array($item['permission'], $selectedPermissions ?? [], true))->count())
                                                <div
                                                    class="tab-pane fade {{ $groupIndex === $activeTabIndex ? 'show active' : '' }}"
                                                    id="access-pane-{{ $groupIndex }}"
                                                    role="tabpanel"
                                                    aria-labelledby="access-tab-{{ $groupIndex }}"
                                                >
                                                    <div class="access-pane-header">
                                                        <div>
                                                            <div class="access-pane-title">{{ $group['title'] }}</div>
                                                            <div class="access-pane-subtitle">{{ $group['description'] }}</div>
                                                        </div>
                                                        <div class="access-pane-counter">{{ $assignedInGroup }}/{{ count($group['items']) }} asignados</div>
                                                    </div>

                                                    <div class="table-responsive access-classic-table-wrap">
                                                        <table class="table access-classic-table mb-0">
                                                            <thead>
                                                                <tr>
                                                                    <th style="width: 54px;">#</th>
                                                                    <th>Clave / Menú</th>
                                                                    <th>Formulario / Función</th>
                                                                    <th>Usará para</th>
                                                                    <th style="width: 92px; text-align: center;">Acceso</th>
                                                                    <th style="width: 170px;">Almacén</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach($group['items'] as $itemIndex => $item)
                                                                    @php($isAssigned = ($item['editable'] ?? true) && in_array($item['permission'], $selectedPermissions ?? [], true))
                                                                    <tr class="{{ $isAssigned ? 'is-selected' : '' }} {{ !($item['editable'] ?? true) ? 'is-informative' : '' }}">
                                                                        <td class="text-center">{{ $itemIndex + 1 }}</td>
                                                                        <td>
                                                                            <div class="access-grid-title">{{ $item['label'] }}</div>
                                                                            <div class="access-grid-key">{{ $item['permission'] ?? 'informativo' }}</div>
                                                                        </td>
                                                                        <td>
                                                                            <div class="access-grid-functions">
                                                                                @foreach($item['functions'] as $function)
                                                                                    <div>{{ $function }}</div>
                                                                                @endforeach
                                                                            </div>
                                                                        </td>
                                                                        <td>
                                                                            <div class="access-grid-summary">{{ $item['summary'] }}</div>
                                                                        </td>
                                                                        <td class="text-center">
                                                                            @if($item['editable'] ?? true)
                                                                                <input
                                                                                    class="form-check-input access-grid-check"
                                                                                    type="checkbox"
                                                                                    name="access_permissions[]"
                                                                                    value="{{ $item['permission'] }}"
                                                                                    {{ $isAssigned ? 'checked' : '' }}
                                                                                >
                                                                            @else
                                                                                <span class="access-grid-info-pill">Informativo</span>
                                                                            @endif
                                                                        </td>
                                                                        <td>
                                                                            @if($item['uses_assigned_warehouse'])
                                                                                <span class="access-grid-warehouse is-linked">{{ $selectedUser->bodegaConsumo->nombre ?? 'Sin asignar' }}</span>
                                                                            @else
                                                                                <span class="access-grid-warehouse">No aplica</span>
                                                                            @endif
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="access-footer-actions">
                                        <a href="{{ route('usuarios.index') }}" class="btn btn-light border">Volver a usuarios</a>
                                        <button type="submit" class="btn btn-primary">Guardar accesos</button>
                                    </div>
                                @else
                                    <div class="p-4">
                                        <div class="alert alert-secondary mb-0">Selecciona un usuario para ver y modificar sus accesos.</div>
                                    </div>
                                @endif
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<style>
    .access-desktop-card {
        --access-green-900: #1f5d3b;
        --access-green-800: #2d7a52;
        --access-green-700: #3f8f64;
        --access-green-200: #d7ebde;
        --access-green-100: #edf7f0;
        --access-green-050: #f7fcf8;
        --access-ink: #2f4a3c;
        --access-muted: #6c8275;
        --access-line: #d7e4da;
        border: 1px solid #cfe1d5;
        background: #f4faf6;
    }

    .access-desktop-card {
        border: 1px solid #cfe1d5;
        background: #f4faf6;
    }

    .access-desktop-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        padding: 0.8rem 1rem;
        background: linear-gradient(180deg, #fbfefc 0%, #e7f4ea 100%);
        border-bottom: 1px solid var(--access-line);
    }

    .access-toolbar-title {
        font-weight: 700;
        color: var(--access-green-900);
    }

    .access-toolbar-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .access-toolbar-chip {
        padding: 0.22rem 0.65rem;
        border-radius: 999px;
        background: #fff;
        border: 1px solid var(--access-line);
        font-size: 0.76rem;
        font-weight: 600;
        color: var(--access-ink);
    }

    .access-sheet-header {
        padding: 1rem;
        background: var(--access-green-050);
        border-bottom: 1px solid var(--access-line);
    }

    .access-field-label {
        display: block;
        margin-bottom: 0.28rem;
        font-size: 0.78rem;
        font-weight: 700;
        color: var(--access-green-900);
    }

    .access-input-shell,
    .access-static-field {
        min-height: 38px;
        padding: 0.45rem 0.65rem;
        background: #fff;
        border: 1px solid #c7dbce;
        border-radius: 0.2rem;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7);
        color: var(--access-ink);
    }

    .access-static-field {
        display: flex;
        align-items: center;
    }

    .access-state-field.is-active {
        color: var(--access-green-800);
        font-weight: 700;
    }

    .access-state-field.is-inactive {
        color: #a44b4b;
        font-weight: 700;
    }

    .access-bodega-row {
        display: flex;
        gap: 0.75rem;
        align-items: stretch;
    }

    .access-bodega-help {
        min-width: 96px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0.45rem 0.7rem;
        background: #eef8f1;
        border: 1px solid #cfe3d5;
        border-radius: 0.2rem;
        font-size: 0.78rem;
        font-weight: 700;
        color: var(--access-green-900);
    }

    .access-security-bar {
        padding: 0.38rem 1rem;
        background: linear-gradient(180deg, var(--access-green-800) 0%, var(--access-green-900) 100%);
        color: #fff;
        font-size: 0.82rem;
        font-weight: 700;
        letter-spacing: 0.02em;
    }

    .access-tabs-wrap {
        padding: 0 1rem 1rem;
        background: #fff;
    }

    .access-classic-tabs {
        display: flex;
        flex-wrap: nowrap;
        overflow-x: auto;
        overflow-y: hidden;
        scrollbar-width: thin;
        border-bottom: 1px solid var(--access-line);
        padding-top: 0.65rem;
        gap: 0.15rem;
    }

    .access-classic-tabs .nav-item {
        flex: 0 0 auto;
    }

    .access-classic-tabs .nav-link {
        border: 1px solid var(--access-line);
        border-bottom: 0;
        border-radius: 0.4rem 0.4rem 0 0;
        background: #f5faf6;
        color: var(--access-muted);
        font-size: 0.8rem;
        padding: 0.55rem 0.8rem;
        white-space: nowrap;
        min-width: max-content;
    }

    .access-tab-mini-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 1.2rem;
        height: 1.2rem;
        margin-left: 0.45rem;
        padding: 0 0.28rem;
        border-radius: 999px;
        background: rgba(31, 93, 59, 0.08);
        font-size: 0.7rem;
        font-weight: 700;
    }

    .access-classic-tabs .nav-link.active {
        background: #fff;
        color: var(--access-green-900);
        font-weight: 700;
        box-shadow: inset 0 3px 0 var(--access-green-700);
    }

    .access-classic-content {
        border: 1px solid var(--access-line);
        border-top: 0;
        background: #fff;
    }

    .access-pane-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        padding: 0.9rem 1rem;
        border-bottom: 1px solid var(--access-line);
        background: #ffffff;
    }

    .access-pane-title {
        font-weight: 700;
        color: var(--access-green-900);
    }

    .access-pane-subtitle {
        font-size: 0.84rem;
        color: var(--access-muted);
    }

    .access-pane-counter {
        padding: 0.24rem 0.55rem;
        border: 1px solid var(--access-line);
        border-radius: 999px;
        background: #ffffff;
        font-size: 0.78rem;
        font-weight: 700;
        white-space: nowrap;
        color: var(--access-green-900);
    }

    .access-classic-table-wrap {
        padding: 0;
    }

    .access-classic-table {
        min-width: 980px;
    }

    .access-classic-table thead th {
        background: #0f5d3d;
        color: #ffffff;
        font-size: 0.77rem;
        font-weight: 700;
        text-transform: none;
        border-bottom: 1px solid var(--access-line);
    }

    .access-classic-table td,
    .access-classic-table th {
        border-color: #e0ebe3;
        vertical-align: top;
    }

    .access-classic-table tbody tr.is-selected td {
        background: #f7fcf8;
    }

    .access-classic-table tbody tr.is-informative td {
        background: #fbfcfb;
    }

    .access-classic-table tbody tr:hover td {
        background: #fbfdfb;
    }

    .access-grid-title {
        font-weight: 700;
        color: var(--access-green-900);
        margin-bottom: 0.1rem;
    }

    .access-grid-key {
        font-size: 0.76rem;
        color: var(--access-muted);
    }

    .access-grid-functions {
        display: grid;
        gap: 0.16rem;
        color: var(--access-ink);
        font-size: 0.86rem;
    }

    .access-grid-summary {
        color: #556b5f;
        font-size: 0.84rem;
        line-height: 1.45;
    }

    .access-grid-check {
        width: 1.05rem;
        height: 1.05rem;
    }

    .access-grid-warehouse {
        display: inline-block;
        padding: 0.2rem 0.45rem;
        border-radius: 0.2rem;
        background: #f5faf6;
        border: 1px solid var(--access-line);
        color: var(--access-muted);
        font-size: 0.78rem;
    }

    .access-grid-warehouse.is-linked {
        background: #e7f5eb;
        border-color: #c5ddcc;
        color: var(--access-green-900);
        font-weight: 600;
    }

    .access-grid-info-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.2rem 0.45rem;
        border-radius: 999px;
        background: #f4f6f4;
        border: 1px solid #dbe4dd;
        color: #6d7d73;
        font-size: 0.72rem;
        font-weight: 700;
    }

    .access-footer-actions {
        display: flex;
        justify-content: end;
        gap: 0.75rem;
        padding: 1rem;
        border-top: 1px solid var(--access-line);
        background: var(--access-green-050);
    }

    @media (max-width: 767.98px) {
        .access-desktop-toolbar,
        .access-pane-header,
        .access-footer-actions,
        .access-bodega-row {
            flex-direction: column;
            align-items: stretch;
        }

        .access-classic-tabs {
            padding-top: 0.75rem;
            padding-bottom: 0.2rem;
            gap: 0.35rem;
        }

        .access-classic-tabs .nav-link {
            padding: 0.6rem 0.9rem;
            font-size: 0.78rem;
        }

        .access-classic-table {
            min-width: 760px;
        }

        .access-sheet-header {
            padding: 0.85rem;
        }

        .access-tabs-wrap {
            padding: 0 0.85rem 0.85rem;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const activeTabInput = document.getElementById('active_tab');
        if (!activeTabInput) {
            return;
        }

        document.querySelectorAll('#accessMenuTabs [data-bs-toggle="tab"]').forEach(function (tabButton, index) {
            tabButton.addEventListener('shown.bs.tab', function () {
                activeTabInput.value = index;
            });
        });
    });
</script>
@endsection