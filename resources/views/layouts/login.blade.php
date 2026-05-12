<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
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
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="{{ asset('NiceAdmin/assets/vendor/bootstrap/css/bootstrap.min.css')}}" rel="stylesheet">
  <link href="{{ asset('NiceAdmin/assets/vendor/bootstrap-icons/bootstrap-icons.css')}}" rel="stylesheet">
  <link href="{{ asset('NiceAdmin/assets/vendor/boxicons/css/boxicons.min.css')}}" rel="stylesheet">
  <link href="{{ asset('NiceAdmin/assets/vendor/quill/quill.snow.css')}}" rel="stylesheet">
  <link href="{{ asset('NiceAdmin/assets/vendor/quill/quill.bubble.css')}}" rel="stylesheet">
  <link href="{{ asset('NiceAdmin/assets/vendor/remixicon/remixicon.css')}}" rel="stylesheet">
  <link href="{{ asset('NiceAdmin/assets/vendor/simple-datatables/style.css')}}" rel="stylesheet">

  <!-- Template Main CSS File -->
  <link href="{{ asset('NiceAdmin/assets/css/style.css')}}" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

  <!-- =======================================================
  * Template Name: NiceAdmin
  * Template URL: https://bootstrapmade.com/nice-admin-bootstrap-admin-html-template/
  * Updated: Apr 20 2024 with Bootstrap v5.3.3
  * Author: BootstrapMade.com
  * License: https://bootstrapmade.com/license/
  ======================================================== -->
</head>

<body>
@yield('contenido')
  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Vendor JS Files -->
  <script src="{{ asset('NiceAdmin/assets/vendor/apexcharts/apexcharts.min.js')}}"></script>
  <script src="{{ asset('NiceAdmin/assets/vendor/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
  <script src="{{ asset('NiceAdmin/assets/vendor/chart.js/chart.umd.js')}}"></script>
  <script src="{{ asset('NiceAdmin/assets/vendor/echarts/echarts.min.js')}}"></script>
  <script src="{{ asset('NiceAdmin/assets/vendor/quill/quill.js')}}"></script>
  <script src="{{ asset('NiceAdmin/assets/vendor/simple-datatables/simple-datatables.js')}}"></script>
  <script src="{{ asset('NiceAdmin/assets/vendor/tinymce/tinymce.min.js')}}"></script>
  <script src="{{ asset('NiceAdmin/assets/vendor/php-email-form/validate.js')}}"></script>

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

</body>

</html>