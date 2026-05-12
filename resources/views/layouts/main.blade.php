<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="theme-color" content="#198754">
  <link rel="manifest" href="{{ asset('manifest.webmanifest') }}?v=20260511-2">

  <title>AgroControl</title>
  <meta content="" name="description">
  <meta content="" name="keywords">
  <meta name="application-name" content="AgroControl">
  <!-- Favicons -->
<link rel="shortcut icon" href="{{ asset('NiceAdmin/assets/img/agrocontrol.png') }}?v=20260508-2">
<link rel="icon" type="image/png" sizes="512x512" href="{{ asset('NiceAdmin/assets/img/agrocontrol.png') }}?v=20260508-2">
<link rel="apple-touch-icon" href="{{ asset('NiceAdmin/assets/img/agrocontrol.png') }}?v=20260508-2">
<script>
  document.addEventListener('DOMContentLoaded', function () {
    document.title = 'AgroControl';
  });
</script>

  <!-- Google Fonts -->
  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito|Poppins" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="{{ asset('NiceAdmin/assets/vendor/bootstrap/css/bootstrap.min.css')}}" rel="stylesheet">
  <link href="{{ asset('NiceAdmin/assets/vendor/bootstrap-icons/bootstrap-icons.css')}}" rel="stylesheet">
  <link href="{{ asset('NiceAdmin/assets/vendor/boxicons/css/boxicons.min.css')}}" rel="stylesheet">
  <link href="{{ asset('NiceAdmin/assets/vendor/quill/quill.snow.css')}}" rel="stylesheet">
  <link href="{{ asset('NiceAdmin/assets/vendor/quill/quill.bubble.css')}}" rel="stylesheet">
  <link href="{{ asset('NiceAdmin/assets/vendor/remixicon/remixicon.css')}}" rel="stylesheet">
  <link href="{{ asset('NiceAdmin/assets/vendor/simple-datatables/style.css')}}" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <!-- Template Main CSS File -->
  <link href="{{ asset('NiceAdmin/assets/css/style.css')}}" rel="stylesheet">
  <link href="{{ asset('NiceAdmin/assets/css/agro-theme.css')}}" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>

  <style>
    body {
      background:
        radial-gradient(circle at top left, rgba(190, 232, 205, 0.6), transparent 28%),
        radial-gradient(circle at bottom right, rgba(255, 224, 178, 0.35), transparent 24%),
        #f5f8f4;
    }

    #main.main {
      min-height: calc(100vh - 120px);
      background: transparent;
    }

    #main.main .card {
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(15, 90, 67, 0.08);
    }

    #main.main .table thead.table-light th,
    #main.main .table thead.table-success th {
      background: #0f5a43;
      color: #fff;
      border-color: #0f5a43;
      font-weight: 600;
    }

    #main.main .table-hover tbody tr:hover {
      background: #f5fbf8;
    }
  </style>
</head>
  

<body>

  <script src="{{ asset('NiceAdmin/assets/js/offline-sync.js') }}?v=20260511-2"></script>

  @include('shared.header')
  @include('shared.aside')

  @yield('contenido')

  @include('shared.footer')

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center">
    <i class="bi bi-arrow-up-short"></i>
  </a>

  <!-- Vendor JS Files -->
  <script src="{{ asset('NiceAdmin/assets/vendor/apexcharts/apexcharts.min.js')}}"></script>
  <script src="{{ asset('NiceAdmin/assets/vendor/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
  <script src="{{ asset('NiceAdmin/assets/vendor/chart.js/chart.umd.js')}}"></script>
  <script src="{{ asset('NiceAdmin/assets/vendor/echarts/echarts.min.js')}}"></script>
  <script src="{{ asset('NiceAdmin/assets/vendor/quill/quill.js')}}"></script>
  <script src="{{ asset('NiceAdmin/assets/vendor/simple-datatables/simple-datatables.js')}}"></script>
  <script src="{{ asset('NiceAdmin/assets/vendor/tinymce/tinymce.min.js')}}"></script>
  <script src="{{ asset('NiceAdmin/assets/vendor/php-email-form/validate.js')}}"></script>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <!-- Template Main JS File -->
  <script src="{{ asset('NiceAdmin/assets/js/main.js')}}"></script>

  <script>
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', function () {
        navigator.serviceWorker.register('/sw.js?v=20260511-2').catch(function (error) {
          console.warn('No se pudo registrar service worker:', error);
        });
      });
    }
  </script>


  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    (function () {
      const originalFetch = window.fetch.bind(window);

      function isDeleteMethod(value) {
        return String(value || '').toUpperCase() === 'DELETE';
      }

      function getDeleteMethodFromForm(form) {
        if (!form) return false;
        const methodInput = form.querySelector('input[name="_method"]');
        return methodInput && isDeleteMethod(methodInput.value);
      }

      async function pedirJustificacionEliminacion() {
        const result = await Swal.fire({
          title: 'Justifica la eliminación',
          input: 'textarea',
          inputLabel: 'Motivo de eliminación',
          inputPlaceholder: 'Escribe por qué se elimina este registro...',
          inputAttributes: {
            'aria-label': 'Motivo de eliminación'
          },
          showCancelButton: true,
          confirmButtonText: 'Eliminar',
          cancelButtonText: 'Cancelar',
          confirmButtonColor: '#d33',
          inputValidator: (value) => {
            if (!String(value || '').trim()) {
              return 'La justificación es obligatoria';
            }
            return null;
          }
        });

        return result.isConfirmed ? String(result.value || '').trim() : null;
      }

      function appendDeleteReasonToForm(form, reason) {
        let input = form.querySelector('input[name="delete_reason"]');
        if (!input) {
          input = document.createElement('input');
          input.type = 'hidden';
          input.name = 'delete_reason';
          form.appendChild(input);
        }
        input.value = reason;
      }

      function bodyHasDeleteReason(body) {
        if (!body) return false;
        if (body instanceof FormData) return body.has('delete_reason');
        if (body instanceof URLSearchParams) return body.has('delete_reason');
        if (typeof body === 'string') return body.includes('delete_reason');
        return false;
      }

      function injectDeleteReasonIntoOptions(options, reason) {
        const nextOptions = { ...options };
        const headers = new Headers(nextOptions.headers || {});
        headers.set('X-Delete-Reason', reason);

        if (nextOptions.body instanceof FormData) {
          nextOptions.body.append('delete_reason', reason);
        } else if (nextOptions.body instanceof URLSearchParams) {
          nextOptions.body.set('delete_reason', reason);
        } else if (typeof nextOptions.body === 'string' && headers.get('Content-Type')?.includes('application/json')) {
          const parsed = JSON.parse(nextOptions.body || '{}');
          parsed.delete_reason = reason;
          nextOptions.body = JSON.stringify(parsed);
        } else if (typeof nextOptions.body === 'string' && nextOptions.body.trim() !== '') {
          const params = new URLSearchParams(nextOptions.body);
          params.set('delete_reason', reason);
          nextOptions.body = params;
        } else {
          headers.set('Content-Type', 'application/json');
          nextOptions.body = JSON.stringify({ delete_reason: reason });
        }

        nextOptions.headers = headers;
        return nextOptions;
      }

      document.addEventListener('submit', async function (event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !getDeleteMethodFromForm(form)) {
          return;
        }

        if (form.dataset.deleteJustified === '1') {
          return;
        }

        event.preventDefault();
        const reason = await pedirJustificacionEliminacion();
        if (!reason) {
          return;
        }

        appendDeleteReasonToForm(form, reason);
        form.dataset.deleteJustified = '1';
        form.requestSubmit();
      }, true);

      window.fetch = async function (input, options = {}) {
        const requestUrl = typeof input === 'string' ? input : (input?.url || '');
        const method = String(options.method || (typeof input !== 'string' ? input?.method : '') || 'GET').toUpperCase();

        if (method !== 'DELETE') {
          return originalFetch(input, options);
        }

        const existingReason = options.headers && new Headers(options.headers).get('X-Delete-Reason');
        if (existingReason || bodyHasDeleteReason(options.body)) {
          return originalFetch(input, options);
        }

        const reason = await pedirJustificacionEliminacion();
        if (!reason) {
          return Promise.reject(new Error('Eliminación cancelada por el usuario.'));
        }

        const nextOptions = injectDeleteReasonIntoOptions(options, reason);
        return originalFetch(input, nextOptions);
      };
    })();
  </script>
  @if(config('services.google_maps.api_key'))
  <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.api_key') }}&libraries=geometry,drawing&v=weekly" async defer></script>
  @endif




  @if(session('success'))
  <script>
    Swal.fire({
      icon: 'success',
      title: '¡Correcto!',
      text: @json(session('success')),
      timer: 2500,
      showConfirmButton: false
    });
  </script>
  @endif

  @if(session('error'))
  <script>
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: @json(session('error')),
      confirmButtonText: 'Aceptar'
    });
  </script>
  @endif

  @stack('scripts')


  
</body>
</html>
