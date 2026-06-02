<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="theme-color" content="#198754">
  <link rel="manifest" href="{{ asset('manifest.webmanifest') }}?v=20260602-1">

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

    #main.main .card,
    #main.main .card-body,
    #main.main .modal-content,
    #main.main .row > [class*='col-'] {
      min-width: 0;
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

    #main.main .agro-table-card {
      border: 0;
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(15, 90, 67, 0.08);
    }

    #main.main .agro-table-toolbar {
      display: flex;
      flex-wrap: nowrap;
      justify-content: space-between;
      align-items: center;
      gap: 0.75rem;
      margin-bottom: 1rem;
      padding: 0.75rem;
      background: #f8faf9;
      border: 1px solid rgba(15, 90, 67, 0.08);
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(15, 90, 67, 0.06);
    }

    #main.main .agro-table-toolbar > * {
      min-width: 0;
    }

    #main.main .agro-table-toolbar-group {
      display: flex;
      flex-wrap: nowrap;
      align-items: center;
      flex: 1 1 auto;
      min-width: 0;
      gap: 0.75rem;
    }

    #main.main .agro-table-toolbar-group > * {
      min-width: 0;
    }

    #main.main .agro-toolbar-inline-form {
      display: flex;
      flex-wrap: nowrap;
      align-items: center;
      flex: 1 1 auto;
      min-width: 0;
      gap: 0.75rem;
      margin: 0;
    }

    #main.main .agro-toolbar-inline-form > * {
      min-width: 0;
    }

    #main.main .agro-toolbar-records {
      display: flex;
      align-items: center;
      flex-wrap: nowrap;
      gap: 0.5rem;
    }

    #main.main .agro-toolbar-records .form-select.form-select-sm,
    #main.main .agro-toolbar-records .agro-toolbar-select {
      width: auto;
      min-width: 72px;
      max-width: none;
      flex: 0 0 auto;
    }

    #main.main .agro-toolbar-records small {
      display: inline-block;
      width: auto;
      margin: 0;
      color: #6c757d;
      white-space: nowrap;
    }

    #main.main .agro-toolbar-select {
      width: auto;
      min-width: 88px;
    }

    #main.main .agro-toolbar-search {
      flex: 0 1 320px;
      min-width: 220px;
      width: min(320px, 100%);
      max-width: 320px;
    }

    #main.main .agro-table-toolbar-group > .form-control.form-control-sm,
    #main.main .agro-toolbar-inline-form > .form-control.form-control-sm {
      flex: 0 1 320px;
      min-width: 220px;
      width: min(320px, 100%);
    }

    #main.main .agro-toolbar-actions {
      display: flex;
      flex-wrap: nowrap;
      align-items: center;
      flex: 0 0 auto;
      gap: 0.5rem;
      margin-left: auto;
    }

    #main.main .agro-table-shell {
      border: 1px solid rgba(15, 90, 67, 0.14);
      border-radius: 12px;
      overflow: hidden;
      background: #fff;
    }

    #main.main .agro-table {
      --bs-table-bg: transparent;
    }

    #main.main .agro-table thead th {
      white-space: nowrap;
    }

    #main.main .agro-table tbody td,
    #main.main .agro-table tfoot th,
    #main.main .agro-table tfoot td {
      padding: 0.62rem 0.7rem;
      border-color: #d7e3dc;
      vertical-align: middle;
    }

    #main.main .agro-table .btn.btn-sm {
      box-shadow: none;
    }

    #main.main .agro-table-meta {
      display: block;
      color: #6c757d;
      font-size: 0.82rem;
      line-height: 1.35;
      margin-top: 0.15rem;
    }

    #main.main .agro-table-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 0.35rem;
      align-items: center;
    }

    #main.main .table tbody td .btn.btn-sm,
    #main.main .table tbody td .btn-group-sm > .btn,
    #main.main .table tbody td .agro-table-actions .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.35rem;
      min-width: 38px;
      min-height: 38px;
      padding: 0.45rem 0.8rem;
      border-radius: 999px;
      font-weight: 700;
      border-width: 0;
      box-shadow: 0 4px 10px rgba(15, 23, 42, 0.14);
      text-decoration: none;
      transition: transform 0.15s ease, box-shadow 0.15s ease, filter 0.15s ease;
    }

    #main.main .table tbody td .btn.btn-sm:hover,
    #main.main .table tbody td .btn-group-sm > .btn:hover,
    #main.main .table tbody td .agro-table-actions .btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 8px 16px rgba(15, 23, 42, 0.18);
      filter: saturate(1.05);
    }

    #main.main .table tbody td .btn.btn-sm i,
    #main.main .table tbody td .btn.btn-sm svg,
    #main.main .table tbody td .btn-group-sm > .btn i,
    #main.main .table tbody td .agro-table-actions .btn i {
      font-size: 0.95rem;
      line-height: 1;
    }

    #main.main .table tbody td .btn.btn-warning,
    #main.main .table tbody td .btn-warning.btn-sm {
      background: linear-gradient(180deg, #ffca2c 0%, #ffb703 100%);
      color: #212529;
    }

    #main.main .table tbody td .btn.btn-danger,
    #main.main .table tbody td .btn-danger.btn-sm {
      background: linear-gradient(180deg, #f15b6c 0%, #dc3545 100%);
      color: #fff;
    }

    #main.main .table tbody td .btn.btn-primary,
    #main.main .table tbody td .btn-primary.btn-sm,
    #main.main .table tbody td .btn.btn-outline-primary,
    #main.main .table tbody td .btn-outline-primary.btn-sm {
      background: linear-gradient(180deg, #3da5ff 0%, #1f6feb 100%);
      color: #fff;
      border-color: transparent;
    }

    #main.main .table tbody td .btn.btn-success,
    #main.main .table tbody td .btn-success.btn-sm {
      background: linear-gradient(180deg, #28a745 0%, #198754 100%);
      color: #fff;
    }

    #main.main .table tbody td .btn.btn-dark,
    #main.main .table tbody td .btn-dark.btn-sm,
    #main.main .table tbody td .btn.btn-secondary,
    #main.main .table tbody td .btn-secondary.btn-sm,
    #main.main .table tbody td .btn.btn-outline-secondary,
    #main.main .table tbody td .btn-outline-secondary.btn-sm {
      background: linear-gradient(180deg, #55637a 0%, #344054 100%);
      color: #fff;
      border-color: transparent;
    }

    #main.main .table tbody td .btn.btn-info,
    #main.main .table tbody td .btn-info.btn-sm {
      background: linear-gradient(180deg, #3bc9db 0%, #0ea5b7 100%);
      color: #fff;
    }

    #main.main .table tbody td .btn.btn-light,
    #main.main .table tbody td .btn-light.btn-sm {
      background: linear-gradient(180deg, #ffffff 0%, #e9eef5 100%);
      color: #344054;
      border: 1px solid #d0d5dd;
      box-shadow: 0 4px 10px rgba(15, 23, 42, 0.08);
    }

    #main.main .table tbody td.text-center.text-nowrap,
    #main.main .table tbody td .agro-table-actions {
      white-space: nowrap;
    }

    #main.main .table tbody .badge,
    #main.main .table tbody .categoria-estado-badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.35rem;
      padding: 0.42rem 0.9rem;
      border-radius: 999px;
      font-size: 0.82rem;
      font-weight: 700;
      line-height: 1;
      border: 1px solid transparent;
      box-shadow: none;
    }

    #main.main .table tbody .badge.bg-success,
    #main.main .table tbody .badge.text-bg-success,
    #main.main .table tbody .categoria-estado-badge.activo {
      background: #dff8e7 !important;
      color: #0b6b4b !important;
      border-color: #c7efd6 !important;
    }

    #main.main .table tbody .badge.bg-danger,
    #main.main .table tbody .badge.text-bg-danger,
    #main.main .table tbody .categoria-estado-badge.inactivo {
      background: #fde6e7 !important;
      color: #b42318 !important;
      border-color: #f6c8cb !important;
    }

    #main.main .table tbody .badge.bg-warning,
    #main.main .table tbody .badge.text-bg-warning,
    #main.main .table tbody .badge.bg-warning.text-dark {
      background: #fff1cc !important;
      color: #9a6700 !important;
      border-color: #ffe29a !important;
    }

    #main.main .table tbody .badge.bg-info,
    #main.main .table tbody .badge.text-bg-info,
    #main.main .table tbody .badge.bg-info.text-dark,
    #main.main .table tbody .badge.bg-primary,
    #main.main .table tbody .badge.text-bg-primary {
      background: #e1f0ff !important;
      color: #175cd3 !important;
      border-color: #c8defc !important;
    }

    #main.main .table tbody .badge.bg-secondary,
    #main.main .table tbody .badge.text-bg-secondary,
    #main.main .table tbody .badge.bg-dark,
    #main.main .table tbody .badge.text-bg-dark,
    #main.main .table tbody .badge.bg-light,
    #main.main .table tbody .badge.text-bg-light,
    #main.main .table tbody .badge.border.text-dark,
    #main.main .table tbody .badge.bg-light.text-dark.border,
    #main.main .table tbody .badge.bg-dark-subtle,
    #main.main .table tbody .badge.bg-danger-subtle,
    #main.main .table tbody .badge.text-bg-light.border {
      background: #eef2f6 !important;
      color: #475467 !important;
      border-color: #d5dde6 !important;
    }

    #main.main .table-responsive {
      max-width: 100%;
      overflow-x: auto;
      overflow-y: hidden;
      scrollbar-gutter: stable both-edges;
      scrollbar-width: thin;
      scrollbar-color: rgba(15, 90, 67, 0.45) rgba(15, 90, 67, 0.08);
    }

    #main.main .table-responsive > .table,
    #main.main .table-responsive > table {
      width: max-content;
      min-width: 100%;
      margin-bottom: 0;
    }

    #main.main .table-responsive.border.rounded,
    #main.main .table-responsive.border.rounded.shadow-sm,
    #main.main .table-responsive.border.rounded.bg-white,
    #main.main .table-responsive.border.rounded.shadow-sm.bg-white {
      border-color: rgba(15, 90, 67, 0.14) !important;
      border-radius: 14px !important;
      background: #fff;
      overflow-x: auto;
      overflow-y: hidden;
      box-shadow: 0 6px 18px rgba(15, 90, 67, 0.05);
    }

    #main.main .table-responsive.border.rounded > .table thead th:first-child,
    #main.main .table-responsive.border.rounded.shadow-sm > .table thead th:first-child,
    #main.main .table-responsive.border.rounded.bg-white > .table thead th:first-child,
    #main.main .table-responsive.border.rounded.shadow-sm.bg-white > .table thead th:first-child {
      border-top-left-radius: 12px;
    }

    #main.main .table-responsive.border.rounded > .table thead th:last-child,
    #main.main .table-responsive.border.rounded.shadow-sm > .table thead th:last-child,
    #main.main .table-responsive.border.rounded.bg-white > .table thead th:last-child,
    #main.main .table-responsive.border.rounded.shadow-sm.bg-white > .table thead th:last-child {
      border-top-right-radius: 12px;
    }

    #main.main .table-responsive::-webkit-scrollbar {
      height: 11px;
    }

    #main.main .table-responsive::-webkit-scrollbar-track {
      background: rgba(15, 90, 67, 0.08);
      border-radius: 999px;
    }

    #main.main .table-responsive::-webkit-scrollbar-thumb {
      background: rgba(15, 90, 67, 0.45);
      border-radius: 999px;
    }

    #main.main .table-responsive::-webkit-scrollbar-thumb:hover {
      background: rgba(15, 90, 67, 0.62);
    }

    #main.main .agro-auto-table-wrap,
    .modal .agro-auto-table-wrap,
    #main.main .datatable-wrapper .datatable-container,
    .modal .datatable-wrapper .datatable-container {
      max-width: 100%;
      overflow-x: auto;
      overflow-y: hidden;
      scrollbar-gutter: stable both-edges;
    }

    #main.main .agro-auto-table-wrap > .table,
    .modal .agro-auto-table-wrap > .table,
    #main.main .datatable-wrapper .datatable-table,
    .modal .datatable-wrapper .datatable-table {
      width: max-content;
      min-width: 100%;
      margin-bottom: 0;
    }

    #main.main .datatable-wrapper,
    .modal .datatable-wrapper {
      max-width: 100%;
    }

    #main.main .datatable-wrapper .datatable-top,
    #main.main .datatable-wrapper .datatable-bottom,
    .modal .datatable-wrapper .datatable-top,
    .modal .datatable-wrapper .datatable-bottom {
      display: flex;
      flex-wrap: wrap;
      gap: 0.75rem;
      align-items: center;
      justify-content: space-between;
    }

    img,
    svg,
    canvas,
    video {
      max-width: 100%;
      height: auto;
    }

    .header {
      gap: 0.75rem;
    }

    .header .logo {
      max-width: min(280px, 60vw);
    }

    .header .logo span {
      white-space: nowrap;
    }

    .header-nav,
    .header-nav > ul {
      min-width: 0;
    }

    .header-nav .nav-item.dropdown,
    .header-nav .nav-profile {
      min-width: 0;
    }

    .sidebar {
      overscroll-behavior: contain;
    }

    .sidebar-nav .nav-link,
    .sidebar-nav .nav-content a {
      min-width: 0;
      white-space: normal;
      word-break: break-word;
    }

    .sidebar-nav .nav-link span,
    .sidebar-nav .nav-content a span {
      min-width: 0;
      white-space: normal;
    }

    .pagetitle,
    .pagetitle nav,
    .breadcrumb {
      overflow-wrap: anywhere;
    }

    .modal-dialog {
      max-width: min(var(--bs-modal-width, 500px), calc(100vw - 1.5rem));
      margin: 0.75rem auto;
    }

    .modal-content {
      border-radius: 18px;
    }

    .modal-body {
      overflow-wrap: anywhere;
    }

    .table-responsive,
    .overflow-auto {
      -webkit-overflow-scrolling: touch;
    }

    .table td,
    .table th {
      overflow-wrap: anywhere;
    }

    .btn,
    .form-control,
    .form-select,
    .input-group {
      max-width: 100%;
    }

    .input-group > .form-control,
    .input-group > .form-select {
      min-width: 0;
    }

    .card-body,
    .card-header,
    .card-footer {
      overflow-wrap: anywhere;
    }

    .alert {
      overflow-wrap: anywhere;
      word-break: break-word;
      border-radius: 14px;
    }

    .alert > *:last-child {
      margin-bottom: 0;
    }

    .alert .btn,
    .alert .btn-close,
    .alert .alert-link,
    .alert a {
      max-width: 100%;
    }

    .alert.alert-dismissible {
      padding-right: 3rem;
    }

    .alert .d-flex,
    .alert .row {
      min-width: 0;
    }

    #footer.footer {
      padding-inline: 1rem;
      text-align: center;
    }

    .agro-swal-scroll-popup .swal2-html-container {
      margin-top: 0.75rem;
      overflow: hidden;
    }

    .agro-swal-scroll-box {
      max-height: 300px;
      overflow-y: auto;
      padding-right: 0.5rem;
      text-align: left;
      line-height: 1.5;
      white-space: pre-wrap;
      word-break: break-word;
      scrollbar-width: thin;
      scrollbar-color: rgba(15, 90, 67, 0.45) rgba(15, 90, 67, 0.08);
    }

    .agro-swal-scroll-box::-webkit-scrollbar {
      width: 10px;
    }

    .agro-swal-scroll-box::-webkit-scrollbar-track {
      background: rgba(15, 90, 67, 0.08);
      border-radius: 999px;
    }

    .agro-swal-scroll-box::-webkit-scrollbar-thumb {
      background: rgba(15, 90, 67, 0.45);
      border-radius: 999px;
    }

    #main.main .tabla-paginada-footer {
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      align-items: center;
      gap: 0.75rem;
      margin-top: 1rem;
      padding: 0.85rem 1rem;
      border: 1px solid rgba(15, 90, 67, 0.12);
      border-radius: 14px;
      background: linear-gradient(180deg, #ffffff 0%, #f8fbf9 100%);
      box-shadow: 0 4px 12px rgba(15, 90, 67, 0.05);
    }

    #main.main .tabla-paginada-footer-info {
      color: #6c757d;
      font-size: 0.92rem;
      margin: 0;
      overflow-wrap: break-word;
    }

    #main.main .tabla-paginada-footer nav {
      max-width: 100%;
      overflow-x: auto;
      overflow-y: hidden;
      -webkit-overflow-scrolling: touch;
      scrollbar-width: thin;
      padding-bottom: 0.1rem;
    }

    #main.main .tabla-paginada-footer .pagination {
      flex-wrap: nowrap;
      width: max-content;
    }

    #main.main .tabla-paginada-footer .page-item {
      flex: 0 0 auto;
    }

    #main.main .tabla-paginada-footer .page-link {
      white-space: nowrap;
      word-break: normal;
      overflow-wrap: normal;
      min-width: 2.2rem;
      text-align: center;
      border-radius: 10px;
      border-color: #d7e3dc;
      color: #175c43;
    }

    #main.main .tabla-paginada-footer .page-item.active .page-link {
      background: #17684b;
      border-color: #17684b;
      color: #fff;
    }

    @media (max-width: 767.98px) {
      body {
        font-size: 0.95rem;
      }

      .header {
        height: auto;
        min-height: 60px;
        padding: 0.55rem 0.9rem;
      }

      .header .logo {
        max-width: calc(100vw - 165px);
      }

      .header .logo img {
        max-height: 44px !important;
        height: 44px !important;
      }

      .header .toggle-sidebar-btn {
        font-size: 1.7rem;
        padding-left: 0.35rem;
      }

      .header-nav .nav-icon {
        margin-right: 0.75rem;
        font-size: 1.15rem;
      }

      .header-nav .profile {
        min-width: min(240px, calc(100vw - 1.5rem));
      }

      .sidebar {
        width: 300px;
        max-width: calc(100vw - 1rem);
        padding: 1rem 0.85rem 1.25rem;
      }

      body.toggle-sidebar::before {
        content: '';
        position: fixed;
        inset: 60px 0 0;
        background: rgba(15, 23, 42, 0.38);
        z-index: 995;
      }

      #main,
      #main.main {
        padding: 1rem 0.85rem 1.25rem;
        margin-top: 72px;
      }

      #main.main .card {
        border-radius: 14px;
      }

      #main.main .agro-table-toolbar,
      .d-flex.flex-wrap.justify-content-between.align-items-center.mb-3.p-2.bg-light.rounded.shadow-sm.gap-3,
      .d-flex.flex-wrap.justify-content-between.align-items-center.gap-2.mt-3 {
        flex-wrap: wrap !important;
        align-items: stretch !important;
      }

      #main.main .agro-table-toolbar {
        gap: 0.85rem;
      }

      #main.main .agro-table-toolbar > .btn,
      #main.main .agro-table-toolbar > a.btn,
      #main.main .agro-table-toolbar > button,
      #main.main .agro-toolbar-actions > .btn,
      #main.main .agro-toolbar-actions > a.btn {
        width: 100%;
      }

      .d-flex.flex-wrap.justify-content-between.align-items-center.mb-3.p-2.bg-light.rounded.shadow-sm.gap-3 > .d-flex.align-items-center.gap-3,
      .d-flex.flex-wrap.justify-content-between.align-items-center.mb-3.p-2.bg-light.rounded.shadow-sm.gap-3 > .d-flex.align-items-center.gap-2,
      .d-flex.flex-wrap.justify-content-between.align-items-center.mb-3.p-2.bg-light.rounded.shadow-sm.gap-3 > .d-flex.align-items-center.gap-2.flex-wrap,
      .d-flex.flex-wrap.justify-content-between.align-items-center.mb-3.p-2.bg-light.rounded.shadow-sm.gap-3 > .d-flex.align-items-center.gap-3.flex-wrap {
        width: 100%;
        flex-wrap: wrap !important;
        align-items: stretch !important;
      }

      #main.main .agro-table-toolbar-group,
      #main.main .agro-toolbar-inline-form,
      #main.main .agro-toolbar-actions {
        width: 100%;
        flex-wrap: wrap;
      }

      #main.main .agro-toolbar-actions,
      .reporteria-actions,
      .modal-footer {
        justify-content: stretch;
      }

      #main.main .agro-toolbar-actions > *,
      .reporteria-actions > *,
      .modal-footer > .btn,
      .d-flex.flex-wrap.justify-content-between.align-items-center.mb-3.p-2.bg-light.rounded.shadow-sm.gap-3 > .d-flex.align-items-center.gap-3 > *,
      .d-flex.flex-wrap.justify-content-between.align-items-center.mb-3.p-2.bg-light.rounded.shadow-sm.gap-3 > .d-flex.align-items-center.gap-2 > *,
      .d-flex.align-items-center.gap-2.flex-wrap > .btn,
      .d-flex.align-items-center.gap-2.flex-wrap > a.btn {
        width: 100%;
      }

      .d-flex.flex-wrap.justify-content-between.align-items-center.mb-3.p-2.bg-light.rounded.shadow-sm.gap-3 > .d-flex.align-items-center.gap-3 > .d-flex.align-items-center.gap-2,
      .d-flex.flex-wrap.justify-content-between.align-items-center.mb-3.p-2.bg-light.rounded.shadow-sm.gap-3 > .d-flex.align-items-center.gap-2 > .d-flex.align-items-center.gap-2 {
        width: auto;
        flex: 0 0 auto;
        flex-wrap: nowrap !important;
        align-items: center !important;
      }

      .d-flex.flex-wrap.justify-content-between.align-items-center.mb-3.p-2.bg-light.rounded.shadow-sm.gap-3 > .d-flex.align-items-center.gap-3 > .d-flex.align-items-center.gap-2 > .form-select.form-select-sm,
      .d-flex.flex-wrap.justify-content-between.align-items-center.mb-3.p-2.bg-light.rounded.shadow-sm.gap-3 > .d-flex.align-items-center.gap-2 > .d-flex.align-items-center.gap-2 > .form-select.form-select-sm {
        width: auto !important;
        min-width: 72px;
        max-width: none !important;
      }

      .d-flex.flex-wrap.justify-content-between.align-items-center.mb-3.p-2.bg-light.rounded.shadow-sm.gap-3 > .d-flex.align-items-center.gap-3 > .d-flex.align-items-center.gap-2 > small.text-muted,
      .d-flex.flex-wrap.justify-content-between.align-items-center.mb-3.p-2.bg-light.rounded.shadow-sm.gap-3 > .d-flex.align-items-center.gap-2 > .d-flex.align-items-center.gap-2 > small.text-muted {
        display: inline-block;
        width: auto;
        margin: 0;
        white-space: nowrap;
      }

      #main.main .agro-toolbar-select,
      #main.main .agro-toolbar-search,
      #main.main .agro-toolbar-records,
      .input-group.input-group-sm,
      .form-select.form-select-sm,
      .form-control.form-control-sm {
        width: 100% !important;
        max-width: 100% !important;
      }

      .d-flex.flex-wrap.justify-content-between.align-items-center.mb-3.p-2.bg-light.rounded.shadow-sm.gap-3 small.text-muted {
        display: inline-block;
        width: auto;
        text-align: left;
        white-space: nowrap;
      }

      #main.main .agro-toolbar-records .form-select.form-select-sm,
      #main.main .agro-toolbar-records .agro-toolbar-select {
        width: auto !important;
        max-width: none !important;
        flex: 0 0 auto;
      }

      #main.main .agro-toolbar-records small {
        width: auto !important;
      }

      #main.main .agro-table-toolbar-group > .form-control.form-control-sm,
      #main.main .agro-table-toolbar-group > .form-select.form-select-sm,
      #main.main .agro-toolbar-inline-form > .form-control.form-control-sm,
      #main.main .agro-toolbar-inline-form > .form-select.form-select-sm {
        flex: 1 1 100%;
      }

      .row {
        --bs-gutter-x: 1rem;
      }

      .table {
        font-size: 0.9rem;
      }

      .table td,
      .table th {
        white-space: nowrap;
      }

      .alert {
        padding: 0.85rem 1rem;
        font-size: 0.94rem;
      }

      .alert .d-flex {
        flex-wrap: wrap;
        gap: 0.75rem;
        align-items: flex-start !important;
      }

      .alert .btn,
      .alert .btn-close {
        flex: 0 0 auto;
      }

      .alert.alert-dismissible {
        padding-right: 2.75rem;
      }

      .modal-dialog {
        max-width: calc(100vw - 0.75rem);
        margin: 0.375rem auto;
      }

      .modal-body {
        padding-inline: 1rem !important;
      }

      #footer.footer {
        padding-bottom: 1rem;
      }

      #main.main .tabla-paginada-footer {
        align-items: stretch;
      }

      #main.main .tabla-paginada-footer-info {
        width: 100%;
        white-space: normal;
      }

      #main.main .tabla-paginada-footer nav {
        width: 100%;
        padding-bottom: 0.2rem;
      }

      #main.main .tabla-paginada-footer .pagination {
        margin-inline: 0;
      }

      #main.main .tabla-paginada-footer .page-link {
        padding: 0.38rem 0.62rem;
        font-size: 0.88rem;
      }

      #main.main .agro-table-toolbar {
        align-items: stretch;
      }

      #main.main .agro-table-toolbar-group {
        width: 100%;
      }

      #main.main .agro-toolbar-inline-form,
      #main.main .agro-toolbar-actions {
        width: 100%;
        margin-left: 0;
      }
    }

    @media (min-width: 768px) and (max-width: 991.98px) {
      #main.main .agro-table-toolbar,
      #main.main .agro-table-toolbar-group,
      #main.main .agro-toolbar-inline-form {
        flex-wrap: wrap;
        align-items: stretch;
      }

      #main.main .agro-table-toolbar-group,
      #main.main .agro-toolbar-inline-form,
      #main.main .agro-toolbar-actions {
        width: 100%;
      }

      #main.main .agro-toolbar-actions {
        justify-content: stretch;
      }

      #main.main .agro-toolbar-actions > *,
      #main.main .agro-table-toolbar-group > .form-select.form-select-sm,
      #main.main .agro-table-toolbar-group > .form-control.form-control-sm,
      #main.main .agro-toolbar-inline-form > .form-select.form-select-sm,
      #main.main .agro-toolbar-inline-form > .form-control.form-control-sm,
      #main.main .agro-toolbar-search {
        width: 100% !important;
        max-width: 100% !important;
      }

      .header {
        padding-inline: 1rem;
      }

      .header .logo {
        max-width: 240px;
      }

      #main,
      #main.main {
        padding: 1.15rem 1rem 1.5rem;
      }

      .sidebar {
        width: 300px;
        max-width: calc(100vw - 1rem);
      }

      .modal-dialog {
        max-width: calc(100vw - 2rem);
      }

      .d-flex.flex-wrap.justify-content-between.align-items-center.mb-3.p-2.bg-light.rounded.shadow-sm.gap-3 > .d-flex.align-items-center.gap-3,
      .d-flex.flex-wrap.justify-content-between.align-items-center.mb-3.p-2.bg-light.rounded.shadow-sm.gap-3 > .d-flex.align-items-center.gap-2,
      .d-flex.flex-wrap.justify-content-between.align-items-center.mb-3.p-2.bg-light.rounded.shadow-sm.gap-3 > .d-flex.align-items-center.gap-2.flex-wrap,
      .d-flex.flex-wrap.justify-content-between.align-items-center.mb-3.p-2.bg-light.rounded.shadow-sm.gap-3 > .d-flex.align-items-center.gap-3.flex-wrap {
        width: 100%;
        flex-wrap: wrap !important;
        align-items: center !important;
      }

      #main.main .agro-toolbar-actions {
        width: 100%;
        margin-left: 0;
      }

      #main.main .agro-toolbar-search {
        max-width: 100%;
      }
    }

    @media (max-width: 575.98px) {
      .header .logo span,
      .header-nav .nav-profile span {
        display: none !important;
      }

      .notifications-dropdown-menu,
      .header-nav .notifications,
      .header-nav .profile {
        width: min(92vw, 340px);
        min-width: min(92vw, 340px);
      }

      .pagetitle h1 {
        font-size: 1.35rem;
      }

      .card-title {
        font-size: 1rem;
      }

      .table td,
      .table th {
        padding: 0.65rem 0.55rem;
      }

      .breadcrumb {
        font-size: 0.82rem;
      }
    }

    @media (min-width: 992px) and (max-width: 1199.98px) {
      #main,
      #main.main {
        padding-inline: 1.25rem;
      }

      .sidebar {
        width: 300px;
      }

      .modal-dialog.modal-xl {
        max-width: calc(100vw - 2rem);
      }
    }
  </style>
</head>
  

<body>

  <script src="{{ asset('NiceAdmin/assets/js/offline-sync.js') }}?v=20260602-1"></script>

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
    window.AgroPermissions = {
      canManageSensitiveActions: @json(auth()->user()?->canManageSensitiveActions() ?? false)
    };

    (function () {
      if (window.AgroPermissions.canManageSensitiveActions) {
        return;
      }

      const deleteSelectors = [
        '[class*="btnEliminar"]',
        '.btnEliminarFacturaVenta',
        'form[data-sensitive-delete="1"]',
        'button[data-sensitive-delete="1"]',
        'a[data-sensitive-delete="1"]'
      ];

      function mostrarAccesoDenegado() {
        Swal.fire({
          icon: 'warning',
          title: 'Acceso denegado',
          text: 'No tienes acceso para eliminar registros.',
          confirmButtonText: 'Aceptar'
        });
      }

      document.addEventListener('click', function (event) {
        const deleteButton = event.target.closest(deleteSelectors.join(','));
        if (!deleteButton) {
          return;
        }

        event.preventDefault();
        event.stopPropagation();
        mostrarAccesoDenegado();
      }, true);

      document.addEventListener('submit', function (event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) {
          return;
        }

        const methodInput = form.querySelector('input[name="_method"]');
        const isDelete = methodInput && String(methodInput.value || '').toUpperCase() === 'DELETE';
        const markedDelete = form.matches('form[data-sensitive-delete="1"]');

        if (!isDelete && !markedDelete) {
          return;
        }

        event.preventDefault();
        event.stopPropagation();
        mostrarAccesoDenegado();
      });
    })();

    if ('serviceWorker' in navigator) {
      window.addEventListener('load', function () {
        navigator.serviceWorker.register('/sw.js?v=20260602-1').catch(function (error) {
          console.warn('No se pudo registrar service worker:', error);
        });
      });
    }
  </script>
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    (function () {
      window.agroScrollableSwalHtml = function (message) {
        const safeMessage = String(message || '')
          .replace(/&/g, '&amp;')
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;')
          .replace(/"/g, '&quot;')
          .replace(/'/g, '&#039;');

        return '<div class="agro-swal-scroll-box">' + safeMessage + '</div>';
      };

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

      function shouldAutoWrapTable(table) {
        if (!(table instanceof HTMLTableElement)) {
          return false;
        }

        if (!table.classList.contains('table')) {
          return false;
        }

        if (table.classList.contains('datatable')) {
          return false;
        }

        if (table.closest('.table-responsive, .agro-auto-table-wrap, .datatable-wrapper, .datatable-container, .dataTable-wrapper, .dataTable-container')) {
          return false;
        }

        if (table.closest('[data-no-auto-table-wrap="1"]')) {
          return false;
        }

        return !!table.closest('#main.main, .modal');
      }

      function autoWrapTables(root) {
        const scope = root instanceof Element || root instanceof Document ? root : document;
        const tables = scope.querySelectorAll('table');

        tables.forEach(function (table) {
          if (!shouldAutoWrapTable(table) || !table.parentNode) {
            return;
          }

          const wrapper = document.createElement('div');
          wrapper.className = 'table-responsive agro-auto-table-wrap';
          wrapper.setAttribute('data-auto-table-wrap', '1');
          table.parentNode.insertBefore(wrapper, table);
          wrapper.appendChild(table);
        });
      }

      autoWrapTables(document);

      const autoWrapObserver = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
          mutation.addedNodes.forEach(function (node) {
            if (!(node instanceof Element)) {
              return;
            }

            if (node.matches('table')) {
              autoWrapTables(node.parentElement || document);
              return;
            }

            if (node.querySelector('table')) {
              autoWrapTables(node);
            }
          });
        });
      });

      autoWrapObserver.observe(document.body, {
        childList: true,
        subtree: true,
      });

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
      html: window.agroScrollableSwalHtml(@json(session('error'))),
      customClass: {
        popup: 'agro-swal-scroll-popup'
      },
      confirmButtonText: 'Aceptar'
    });
  </script>
  @endif

  @stack('scripts')


  
</body>
</html>
