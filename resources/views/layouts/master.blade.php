<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ env('APP_NAME','Simply-ERP') }}</title>

    {{-- Fonts & SB-Admin-2 --}}
    <link rel="stylesheet" href="{{ asset('assets/vendor/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900">
    <link rel="stylesheet" href="{{ asset('assets/css/sb-admin-2.min.css') }}">

    {{-- Bootstrap 5 + DataTables (BS5) --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/v/bs5/dt-2.3.2/b-2.4.2/r-2.4.1/datatables.min.css"/>

    {{-- Select2 --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css"/>

    {{-- Custom layout fixes (Mobile sidebar support) --}}
    
    <style>
        :root{
            --sidebar-width: 14rem;
            --sidebar-width-mobile: 16rem;
        }

        #wrapper{ min-height: 100vh; }
        
        /* Ensure topbar is ALWAYS above sidebar on mobile */
        .topbar{
            z-index: 1060;
        }
        
        /* Sidebar stays below topbar */
        .sidebar{
            z-index: 1050;
            width: var(--sidebar-width);
        }
        
        /* Overlay below topbar */
        .sidebar-overlay{
            z-index: 1040;
        }
        
        /* Push content below fixed topbar */
        #content{
        }


        /* Desktop: sidebar fixed, content shifted */
        @media (min-width: 768px){
            .sidebar{
                position: fixed;
                top: 0;
                left: 0;
                height: 100vh;
                overflow-y: auto;
                z-index: 1030;
            }
            #content-wrapper{
                margin-left: var(--sidebar-width);
                width: calc(100% - var(--sidebar-width));
            }
        }

        /* Mobile: off-canvas */
        @media (max-width: 767.98px){
            .sidebar{
                position: fixed;
                top: 0;
                left: 0;
                height: 100vh;
                overflow-y: auto;
                z-index: 1050;
                transform: translateX(-105%);
                transition: transform .25s ease-in-out;
                width: var(--sidebar-width-mobile);
            }

            #content-wrapper{
                margin-left: 0 !important;
                width: 100% !important;
            }

            body.sidebar-mobile-open .sidebar{
                transform: translateX(0);
            }

            .sidebar-overlay{
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,.35);
                z-index: 1040;
                display: none;
            }
            body.sidebar-mobile-open .sidebar-overlay{
                display: block;
            }
        }

        /* submenu icon + spacing helpers */
        .collapse-inner .collapse-item{
            display:flex;
            align-items:center;
            gap:.55rem;
        }
        .collapse-inner .collapse-item i{
            width:18px;
            text-align:center;
            opacity:.85;
        }

        /* Keep content spacing sane */
        #content-wrapper{ padding-top: .75rem; }
    
    
        /* Force Select2 to match SB-Admin-2 input styling (works even without bootstrap-5 theme css) */
        .select2-container { width: 100% !important; }
        
        .select2-container--bootstrap-5 .select2-selection--single,
        .select2-container--default .select2-selection--single {
            height: calc(1.5em + .75rem + 2px) !important;
            border: 1px solid #d1d3e2 !important;
            border-radius: .35rem !important;
            background-color: #fff !important;
            display: flex !important;
            align-items: center !important;
        }
        
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered,
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            padding-left: .75rem !important;
            padding-right: 2.25rem !important; /* space for arrow/clear */
            line-height: 1.5 !important;
            color: #6e707e !important;
            width: 100% !important;
        }
        
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow,
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 100% !important;
            right: .6rem !important;
        }
        
        .select2-container--bootstrap-5 .select2-selection--single .select2-selection__clear,
        .select2-container--default .select2-selection--single .select2-selection__clear {
            margin-right: .4rem !important;
        }
        
        .select2-container--bootstrap-5.select2-container--focus .select2-selection,
        .select2-container--bootstrap-5.select2-container--open .select2-selection,
        .select2-container--default.select2-container--focus .select2-selection,
        .select2-container--default.select2-container--open .select2-selection {
            border-color: #4e73df !important;
            box-shadow: 0 0 0 .2rem rgba(78,115,223,.25) !important;
        }
        
        /* SB-Admin-2 override: allow large scrollable modals to use more height */
        @media (min-width: 576px) {
          .modal-dialog.modal-dialog-scrollable {
            max-height: none !important;
            height: calc(100vh - 2rem) !important;   /* more room */
          }
        
          .modal-dialog.modal-dialog-scrollable .modal-content {
            max-height: calc(100vh - 2rem) !important;
          }
        
          .modal-dialog.modal-dialog-scrollable .modal-body {
            overflow-y: auto !important;
            max-height: calc(100vh - 11rem) !important; /* header+footer space */
          }
        }
        
        .modal {
            z-index: 2000 !important;
        }
        
        .modal-backdrop {
            z-index: 1990 !important;
        }
        
        .select2-container {
            z-index: 2100 !important;
        }

        </style>

    @stack('styles')
</head>

<body id="page-top">
<div id="wrapper">

    {{-- Sidebar --}}
    @include('partials.sidebar')

    {{-- Mobile overlay --}}
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    {{-- Content Wrapper --}}
    <div id="content-wrapper" class="d-flex flex-column">

        {{-- Main Content --}}
        <div id="content">
            {{-- Topbar --}}
            @include('partials.topbar')

            {{-- Page Content --}}
            <div class="container-fluid">
                @yield('content')
            </div>
        </div>

        <footer class="sticky-footer bg-white">
            <div class="container my-auto text-center">
                &copy; {{ env('APP_NAME') }} {{ date('Y') }}
            </div>
        </footer>
    </div>
</div>

<a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>
@include('partials.dashboard.logout-modal')

<!-- jQuery first -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>
  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<!-- Bootstrap 5 bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables -->
<script src="https://cdn.datatables.net/v/bs5/dt-1.13.8/b-2.4.2/b-html5-2.4.2/r-2.5.0/datatables.min.js"></script>

<!-- Select2 -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios@1/dist/axios.min.js"></script>

<!-- JSZip + pdfmake -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

<script>
  // ✅ Mobile sidebar toggle (Bootstrap 5 safe)
  (function () {
    const btn = document.getElementById('sidebarToggleTop'); // from topbar
    const overlay = document.getElementById('sidebarOverlay');

    function closeSidebar(){
      document.body.classList.remove('sidebar-mobile-open');
    }

    function toggleSidebar(){
      document.body.classList.toggle('sidebar-mobile-open');
    }

    if (btn){
      btn.addEventListener('click', function(e){
        e.preventDefault();
        toggleSidebar();
      });
    }

    if (overlay){
      overlay.addEventListener('click', function(){
        closeSidebar();
      });
    }

    // Close after clicking a real link inside sidebar (mobile only)
    document.addEventListener('click', function(e){
      const isMobile = window.matchMedia('(max-width: 767.98px)').matches;
      if (!isMobile) return;

      const link = e.target.closest('.sidebar a');
      if (link && link.getAttribute('href') && link.getAttribute('href') !== '#'){
        closeSidebar();
      }
    });
  })();

  // SweetAlert flash
  document.addEventListener('DOMContentLoaded', function () {
    @if(session('ok'))
      Swal.fire({
        icon: 'success',
        title: 'Success',
        text: @json(session('ok')),
        timer: 2500,
        showConfirmButton: false
      });
    @endif

    @if(session('error'))
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: @json(session('error'))
      });
    @endif
  });
</script>

@stack('scripts')
</body>
</html>
