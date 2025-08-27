<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ env('APP_NAME','Simply‑ERP') }}</title>

    {{-- Fonts & SB‑Admin 2 --}}
    <link rel="stylesheet" href="{{ asset('assets/vendor/fontawesome-free/css/all.min.css') }}">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900">
    <link rel="stylesheet" href="{{ asset('assets/css/sb-admin-2.min.css') }}">

    {{-- Bootstrap 5 + DataTables 2.x (+Buttons, Responsive) --}}
    <link rel="stylesheet"
          href="https://cdn.datatables.net/v/bs5/dt-2.3.2/b-2.4.2/r-2.4.1/datatables.min.css"/>

    {{-- Select2 --}}
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css"/>

    {{-- Custom tweaks --}}
    <style>
        .sidebar{position:fixed;top:0;bottom:0;left:0;height:100vh;overflow-y:auto;z-index:970}
        #content-wrapper{margin-left:22%}
    </style>
    @stack('styles')
</head>
<body id="page-top">
<div id="wrapper">
    @include('partials.sidebar')

    <div id="content-wrapper" class="d-flex flex-column mt-4 mr-5 pt-5">
        @yield('content')

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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<!-- Bootstrap bundle (Popper inside) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- JSZip for Excel export -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js" integrity="sha384-+mbV2IY1Zk/X1p/nWllGySJSUN8uMs+gUAN10Or95UBH0fpj6GfKgPmgC5EXieXG" crossorigin="anonymous"></script>

<!-- *** ONE *** DataTables bundle – core 1.13.8 + Buttons 2.4.2 (+html5) + Responsive 2.5.0  -->
<script src="https://cdn.datatables.net/v/bs5/dt-1.13.8/b-2.4.2/b-html5-2.4.2/r-2.5.0/datatables.min.js"></script>

<!-- other jQuery‑based libs (e.g. Select2) after DataTables -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios@1/dist/axios.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
@stack('scripts')
</body>
</html>
