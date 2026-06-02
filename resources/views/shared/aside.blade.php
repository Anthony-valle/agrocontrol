<aside id="sidebar" class="sidebar">

    <ul class="sidebar-nav" id="sidebar-nav">

        <li class="nav-item">
            <a class="nav-link" href="{{ route('home') }}">
                <i class="bi bi-bar-chart-line"></i>
                <span>Dashboard</span>
            </a>
        </li><!-- End Dashboard Nav -->

        <!--Configuración Empresa-->
        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-target="#empresa-nav" data-bs-toggle="collapse" href="#">
                <i class="bi bi-gear-wide-connected"></i><span>Configuración General</span><i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="empresa-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                @if(auth()->user()->hasAccess('empresas'))
                <li>
                    <a href="{{ route('empresas.index') }}">
                        <i class="bi bi-building fs-6 me-2"></i><span>Datos de Empresa</span>
                    </a>
                </li>
                @endif
                @if(auth()->user()->hasAccess('sucursales'))
                <li>    
                    <a href="{{ route('sucursal.index') }}">
                        <i class="bi bi-geo-alt fs-6 me-2"></i><span>Gestión de Sucursales</span>
                    </a>
                </li>
                @endif
                @if(auth()->user()->hasAccess('bodegas'))
                <li>
                    <a href="{{ route('bodegas.index') }}">
                        <i class="bi bi-door-open fs-6 me-2"></i><span>Gestión de Almacenes</span>
                    </a>
                </li>
                @endif
                @if(auth()->user()->hasAccess('lotes'))
                <li>
                    <a href="{{ route('lotes.index') }}">
                        <i class="bi bi-layers fs-6 me-2"></i><span>Gestión de Lotes</span>
                    </a>
                </li>
                @endif
            </ul>
        </li>

        <!--Cultivos-->
        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-target="#cultivos-nav" data-bs-toggle="collapse" href="#">
                <i class="bi bi-tree"></i><span>Módulo de Cultivos</span><i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="cultivos-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                @if(auth()->user()->hasAccess('cultivos'))
                <li>
                    <a href="{{route('cultivo.index')}}">
                        <i class="bi bi-flower1 fs-6 me-2"></i><span>Crear Cultivo</span>
                    </a>
                </li>
                @endif
                @if(auth()->user()->hasAccess('labores'))
                <li>
                    <a href="{{ route('labores.index') }}">
                        <i class="bi bi-person-workspace fs-6 me-2"></i><span>Mano de Obra</span>
                    </a>
                </li>
                @endif
                @if(auth()->user()->hasAccess('labores'))
                <li>
                    <a href="{{ route('preparacion-suelo-actividades.index') }}">
                        <i class="bi bi-truck fs-6 me-2"></i><span>Actividades de Preparación de Suelo</span>
                    </a>
                </li>
                @endif
                @if(auth()->user()->hasAccess('planes'))
                <li>
                    <a href="{{ route('planes.index')}}">
                        <i class="bi bi-file-earmark-medical fs-6 me-2"></i><span>Recetas y Planes</span>
                    </a>
                </li>
                @endif
                @if(auth()->user()->hasAccess('consumo') || auth()->user()->hasAccess('cosecha'))
                <li>
                    <a href="{{ route('cultivo.index', ['estado' => 'Cerrado']) }}">
                        <i class="bi bi-activity fs-6 me-2"></i><span>Cultivos Cerrados</span>
                    </a>
                </li>
                @endif
            </ul>
        </li>

        <!--Almacen/Insumos-->
        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-target="#insumos-nav" data-bs-toggle="collapse" href="#">
                <i class="bi bi-box-seam"></i><span>Control de Inventario</span><i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="insumos-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                @if(auth()->user()->hasAccess('insumos'))
                <li>
                    <a href="{{ route('categorias.index')}}">
                        <i class="bi bi-tags fs-6 me-2"></i><span>Categorías de Insumos</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('insumos.index')}}">
                        <i class="bi bi-plus-circle fs-6 me-2"></i><span>Catálogo de Insumos</span>
                    </a>
                </li>
                @endif
                @if(auth()->user()->hasAccess('entrada'))
                <li>
                    <a href="{{ route('movimientos.entrada')}}">
                        <i class="bi bi-box-arrow-in-right fs-6 me-2"></i><span>Registrar Entrada</span>
                    </a>
                </li>
                @endif
                @if(auth()->user()->hasAccess('traslado'))
                <li>
                    <a href="{{ route('movimientos.traslado')}}">
                        <i class="bi bi-arrow-left-right fs-6 me-2"></i><span>Traslado entre Almacen</span>
                    </a>
                </li>
                @endif
                @if(auth()->user()->hasAccess('ajuste'))
                <li>
                    <a href="{{ route('movimientos.ajuste')}}">
                        <i class="bi bi-tools fs-6 me-2"></i><span>Ajustes Almacen</span>
                    </a>
                </li>
                @endif
                @if(auth()->user()->hasAccess('inventarios'))
                <li>
                    <a href="{{ route('inventarios.index')}}">
                        <i class="bi bi-clipboard-data fs-6 me-2"></i><span>Stock Actual</span>
                    </a>
                </li>
                @endif
                @if(auth()->user()->hasAccess('inventarios'))
                <li>
                    <a href="{{ route('movimientos.index')}}">
                        <i class="bi bi-clock-history fs-6 me-2"></i><span>Kardex de Movimientos</span>
                    </a>
                </li>
                @endif
            </ul>
        </li>

        <!-- REPORTERÍA -->
        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-target="#reporteria-nav" data-bs-toggle="collapse" href="#">
                <i class="bi bi-bar-chart-line"></i>
                <span>Reportería</span>
                <i class="bi bi-chevron-down ms-auto"></i>
            </a>

            <ul id="reporteria-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">

                <!-- Reporte de Lote y Cultivos -->
                @if(auth()->user()->hasAccess('lotes'))
                <li>
                    <a href="{{ route('reporteria.lote_cultivos') }}">
                        <i class="bi bi-geo fs-6 me-2"></i>
                        <span>Reporte de Lote y Cultivos</span>
                    </a>
                </li>
                @endif


                <!-- Reporte de Cultivos -->
                @if(auth()->user()->hasAccess('cultivos'))
                <li>
                    <a href="{{ route('reporteria.cultivos') }}">
                        <i class="bi bi-tree fs-6 me-2"></i>
                        <span>Reporte de Cultivos</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('reporteria.cultivos.consumos-general') }}">
                        <i class="bi bi-grid-3x3-gap fs-6 me-2"></i>
                        <span>Reporte General de Consumos</span>
                    </a>
                </li>
                @endif

                <!-- Reporte de Consumos -->
                @if(auth()->user()->hasAccess('consumo'))
                <li>
                    <a href="{{ route('reporteria.consumos') }}">
                        <i class="bi bi-receipt fs-6 me-2"></i>
                        <span>Reporte de Consumos</span>
                    </a>
                </li>
                @endif

                <!-- Reporte de Cosechas -->
                @if(auth()->user()->hasAccess('cosecha'))
                <li>
                    <a href="{{ route('reporteria.cosechas') }}">
                        <i class="bi bi-basket fs-6 me-2"></i>
                        <span>Reporte de Cosechas</span>
                    </a>
                </li>
                @endif

                @if(auth()->user()->hasAccess('inventarios'))
                <li>
                    <a href="{{ route('reporteria.inventario') }}">
                        <i class="bi bi-box-seam fs-6 me-2"></i>
                        <span>Reporte de Inventario</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('reporteria.facturas_entradas') }}">
                        <i class="bi bi-paperclip fs-6 me-2"></i>
                        <span>Facturas de Entradas</span>
                    </a>
                </li>
                @endif

                @if(auth()->user()->hasAccess('labores'))
                <li>
                    <a href="{{ route('reporteria.mano_obra') }}">
                        <i class="bi bi-person-workspace fs-6 me-2"></i>
                        <span>Reporte de Mano de Obra</span>
                    </a>
                </li>
                @endif

                @if(auth()->user()->hasAccess('insumos'))
                <li>
                    <a href="{{ route('reporteria.insumos_categoria') }}">
                        <i class="bi bi-tags fs-6 me-2"></i>
                        <span>Insumos por Categoría</span>
                    </a>
                </li>
                @endif

                @if(auth()->user()->hasAccess('cultivos'))
                <li>
                    <a href="{{ route('reporteria.rentabilidad') }}">
                        <i class="bi bi-cash-coin fs-6 me-2"></i>
                        <span>Rentabilidad</span>
                    </a>
                </li>
                @endif

                @if(auth()->user()?->isSuperUser())
                <li>
                    <a href="{{ route('reporteria.alertas') }}">
                        <i class="bi bi-bell fs-6 me-2"></i>
                        <span>Alertas y Notificaciones</span>
                    </a>
                </li>
                @endif
            </ul>
        </li>

        @if(auth()->user()?->hasAccess('compras') || auth()->user()?->hasRole('compra') || auth()->user()?->isSuperUser())
        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-target="#compras-modulo-nav" data-bs-toggle="collapse" href="#">
                <i class="bi bi-cart4"></i><span>Módulo de Compras</span><i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="compras-modulo-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                <li>
                    <a href="{{ route('compras.solicitudes.index') }}">
                        <i class="bi bi-card-checklist fs-6 me-2"></i><span>Indice documental</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('compras.ordenes.validation.index') }}">
                        <i class="bi bi-clipboard-check fs-6 me-2"></i><span>Validar llegada O.C.</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('compras.ordenes.report') }}">
                        <i class="bi bi-journal-text fs-6 me-2"></i><span>Reporte O.C.</span>
                    </a>
                </li>
            </ul>
        </li>
        @endif


        <!--Notificaciones-->
        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-target="#compra-nav" data-bs-toggle="collapse" href="#">
                <i class="bi bi-bell"></i><span>Notificación</span><i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="compra-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                @if(auth()->user()->hasAccess('consumo'))
                <li>
                    <a href="{{ route('consumo.index')}}">
                        <i class="bi bi-droplet-half fs-6 me-2"></i><span>Consumo Cultivo</span>
                    </a>
                </li>
                @endif
                @if(auth()->user()->hasAccess('cosecha'))
                <li>
                    <a href="{{ route('cosecha.index')}}">
                        <i class="bi bi-basket2 fs-6 me-2"></i><span>Gestión de Cosechas</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('cosecha.facturadas.index') }}">
                        <i class="bi bi-receipt-cutoff fs-6 me-2"></i><span>Facturar Cosechas</span>
                    </a>
                </li>
                @endif
                @if(auth()->user()->hasAccess('labores'))
                <li>
                    <a href="{{ route('preparacion-suelo.index')}}">
                        <i class="bi bi-truck fs-6 me-2"></i><span>Mecanización</span>
                    </a>
                </li>
                @endif
            </ul>
        </li>
        

        <!--Usuarios-->
        @if(auth()->user()?->hasRole('admin') || auth()->user()?->hasRole('programador') || auth()->user()?->hasRole('propietario'))
        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-target="#usuarios-nav" data-bs-toggle="collapse" href="#">
                <i class="bi bi-people-fill"></i><span>Seguridad y Acceso</span><i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="usuarios-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                <li>
                    <a href="{{ route('usuarios.index')}}">
                        <i class="bi bi-person-lines-fill fs-6 me-2"></i><span>Lista de Usuarios</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('rol.index')}}">
                        <i class="bi bi-shield-lock fs-6 me-2"></i><span>Roles y Permisos</span>
                    </a>
                </li>
            </ul>
        </li>

        <!--Sincronizar-->
        <li class="nav-item">
            <a class="nav-link" href="#" onclick="if(window.AgroOfflineSync){window.AgroOfflineSync.openTray();} return false;">
                <i class="bi bi-arrow-repeat"></i>
                <span>Sincronización Offline</span>
            </a>
        </li>

        @endif

        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-target="#soporte-nav" data-bs-toggle="collapse" href="#">
                <i class="bi bi-life-preserver"></i><span>Soporte</span><i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="soporte-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                <li>
                    <a href="{{ route('soporte.tecnico.index') }}">
                        <i class="bi bi-headset fs-6 me-2"></i><span>Soporte Técnico</span>
                    </a>
                </li>
                @if(auth()->user()?->isSuperUser())
                <li>
                    <a href="{{ route('soporte.index') }}">
                        <i class="bi bi-shield-check fs-6 me-2"></i><span>Backup del Sistema</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('soporte.recuperar.index') }}">
                        <i class="bi bi-trash3-fill fs-6 me-2"></i><span>Recuperar Eliminados</span>
                    </a>
                </li>
                @endif
            </ul>
        </li>

    </ul>

</aside>