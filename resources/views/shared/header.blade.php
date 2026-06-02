<style>
  .notifications-dropdown-menu {
    width: min(380px, 92vw);
  }

  .notifications-scroll {
    max-height: 420px;
    overflow-y: auto;
    overflow-x: hidden;
  }

  .notifications-scroll::-webkit-scrollbar {
    width: 8px;
  }

  .notifications-scroll::-webkit-scrollbar-thumb {
    background: rgba(16, 94, 74, 0.28);
    border-radius: 999px;
  }

  .notifications-scroll::-webkit-scrollbar-track {
    background: rgba(16, 94, 74, 0.08);
  }

  .notifications-scroll .notification-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 14px 18px;
    white-space: normal;
  }

  .notifications-scroll .notification-item i {
    font-size: 1.4rem;
    line-height: 1;
    margin-top: 4px;
  }

  .notifications-scroll .notification-item h4 {
    margin-bottom: 4px;
  }

  .notifications-scroll .notification-item p {
    margin-bottom: 4px;
  }

  .notifications-scroll .dropdown-divider {
    margin: 0;
  }
  
  @media (max-width: 576px) {
    .notifications-scroll {
      max-height: 320px;
    }
  }
</style>

<header id="header" class="header fixed-top d-flex align-items-center" style="background-color: #dbf5e1;">

  <!-- LOGO y toggle sidebar -->
  <div class="d-flex align-items-center justify-content-between">
    <a href="{{ route('home')}}" class="logo d-flex align-items-center">
      <img src="{{ asset('NiceAdmin/assets/img/agrocontrol.png') }}" alt="agrocontrol"
           style="height: 60px; width: auto; max-height: 80px; object-fit: contain;">
      <span class="d-none d-lg-block">AgroControl</span>
    </a>
    <i class="bi bi-list toggle-sidebar-btn"></i>
  </div>

  <!-- NAV -->
  <nav class="header-nav ms-auto">
    <ul class="d-flex align-items-center">

      @if(auth()->user()?->isSuperUser() || auth()->user()?->hasRole('compra'))
      <!-- NOTIFICACIONES -->
      <li class="nav-item dropdown">
        @php
            $notificacionesCampana = $notificacionesCampana ?? collect();
            $notiNoLeidas = $notificacionesCampana->where('leido', false);
        @endphp

        <a class="nav-link nav-icon" href="#" data-bs-toggle="dropdown" id="notiDropdown" onclick="marcarNotificacionesLeidas()">
          <i class="bi bi-bell"></i>
          @if($notiNoLeidas->count() > 0)
              <span class="badge bg-danger badge-number" id="badgeNoti">
                  {{ $notiNoLeidas->count() }}
              </span>
          @endif
        </a>

        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow notifications notifications-dropdown-menu">
          <li class="dropdown-header" id="notiHeaderText">
            Tienes {{ $notificacionesCampana->count() ?? 0 }} notificaciones
          </li>

          <li><hr class="dropdown-divider"></li>

          <li class="p-0">
            <div class="notifications-scroll">
              @forelse($notificacionesCampana as $n)
                @php
                    $tipo = strtolower((string) $n->tipo);
                    $iconClass = match ($tipo) {
                      'compra' => 'bi bi-cart-check text-primary',
                        'consumo' => 'bi bi-droplet-half text-success',
                        'cosecha' => 'bi bi-basket2 text-info',
                        'mecanizacion' => 'bi bi-truck text-warning',
                        'auditoria' => 'bi bi-shield-check text-secondary',
                        default => 'bi bi-box-seam text-primary',
                    };
                    $titulo = match ($tipo) {
                      'compra' => 'Compras',
                        'consumo' => 'Consumo',
                        'cosecha' => 'Cosecha',
                        'mecanizacion' => 'Mecanización',
                        'auditoria' => 'Auditoría',
                        default => ucfirst((string) ($n->tipo ?? 'Notificación')),
                    };
                @endphp
                <div class="notification-item {{ !$n->leido ? 'noti-noleida' : '' }}" data-created-at="{{ optional($n->created_at)->toIso8601String() }}">
                  <i class="{{ $iconClass }}"></i>
                  <div>
                    <h4>{{ $n->titulo ?? $titulo }}</h4>
                    <p>{{ $n->mensaje }}</p>
                    <p>{{ $n->created_at->diffForHumans() }}</p>
                  </div>
                </div>
                <hr class="dropdown-divider">
              @empty
                <div class="text-center p-3">
                  Sin notificaciones
                </div>
              @endforelse
            </div>
          </li>

          <li class="dropdown-footer">
            <a href="{{ route('notificaciones.index') }}">Ver todas</a>
          </li>
        </ul>
      </li>
      @endif

      <!-- PERFIL -->
      <li class="nav-item dropdown pe-3">
        <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
          @auth
            <img src="{{ auth()->user()->imagen_usuario_url }}" alt="Profile" class="rounded-circle" style="width: 35px; height: 35px; object-fit: cover;" onerror="this.onerror=null;this.src='{{ asset('NiceAdmin/assets/img/default-user-avatar.svg') }}';">
            <span class="d-none d-md-block dropdown-toggle ps-2">{{ auth()->user()->usuario }}</span>
          @else
            <img src="{{ asset('NiceAdmin/assets/img/default-user-avatar.svg') }}" alt="Invitado" class="rounded-circle" style="width: 35px; height: 35px; object-fit: cover;">
            <span class="d-none d-md-block dropdown-toggle ps-2">Invitado</span>
          @endauth
        </a>
        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
          <li class="dropdown-header">
            @auth
              <div class="mb-2">
                <img src="{{ auth()->user()->imagen_usuario_url }}" alt="Profile" class="rounded-circle" style="width: 60px; height: 60px; object-fit: cover;" onerror="this.onerror=null;this.src='{{ asset('NiceAdmin/assets/img/default-user-avatar.svg') }}';">
              </div>
              <h6>{{ auth()->user()->usuario }}</h6>
              <span>{{ auth()->user()->roles->nombre ?? 'Usuario' }}</span>
            @else
              <h6>Invitado</h6>
            @endauth
          </li>

          <li><hr class="dropdown-divider"></li>

          <li>
            @auth
              <a class="dropdown-item d-flex align-items-center" href="{{ route('logout') }}">
                <i class="bi bi-box-arrow-right"></i>
                <span>Cerrar Sesión</span>
              </a>
            @else
              <a class="dropdown-item d-flex align-items-center" href="{{ route('login') }}">
                <i class="bi bi-box-arrow-right"></i>
                <span>Iniciar Sesión</span>
              </a>
            @endauth
          </li>
        </ul>
      </li>

      <!-- FIN MODO OSCURO -->

    </ul>
  </nav>

</header>

<!-- SCRIPT para marcar notificaciones como leídas -->
<script>
function marcarNotificacionesLeidas() {
  fetch("{{ route('notificaciones.leer') }}", {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': '{{ csrf_token() }}',
      'Accept': 'application/json'
    }
  }).then(() => {
    document.querySelectorAll('.notification-item.noti-noleida').forEach(function(item) {
      item.classList.remove('noti-noleida');
    });
    actualizarEstadoVisualNotificaciones();
  });
}

function actualizarEstadoVisualNotificaciones() {
  const badge = document.getElementById('badgeNoti');
  const header = document.getElementById('notiHeaderText');
  const items = Array.from(document.querySelectorAll('.notification-item'));
  const visibles = items.filter((item) => item.style.display !== 'none');
  const noLeidasVisibles = visibles.filter((item) => item.classList.contains('noti-noleida'));

  if (badge) {
    if (noLeidasVisibles.length > 0) {
      badge.textContent = String(noLeidasVisibles.length);
      badge.style.display = '';
    } else {
      badge.style.display = 'none';
    }
  }

  if (header) {
    header.textContent = `Tienes ${visibles.length} notificaciones`;
  }
}

document.addEventListener('DOMContentLoaded', function () {
  actualizarEstadoVisualNotificaciones();
});
</script>