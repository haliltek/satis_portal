<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8" />
    <title> @yield('title')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Kontrol Merkezi" name="description" />
    <meta content=" " name="author" />
    <!-- App favicon -->

    <link rel="shortcut icon" href="/assets/panel/images/favicon.ico">

    <!-- jquery.vectormap css -->
    <link href="/public/assets/panel/libs/admin-resources/jquery.vectormap/jquery-jvectormap-1.2.2.css" rel="stylesheet" type="text/css" />

    <!-- Bootstrap Css -->
    <link href="/public/assets/panel/css/bootstrap.min.css" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <!-- Icons Css -->
    <link href="/public/assets/panel/css/icons.min.css" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="/public/assets/panel/css/app.min.css" id="app-style" rel="stylesheet" type="text/css" />
    <link href="/public/assets/panel/css/page.css" id="app-style" rel="stylesheet" type="text/css" />
    <link href="/public/assets/front/assets/libs/sweetalert2/sweetalert2.min.css" rel="stylesheet" type="text/css">
    @toastr_css

    <!-- DataTables -->
    <link href="/public/assets/panel/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="/public/assets/panel/libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />

    <!-- Responsive datatable examples -->
    <link href="/public/assets/panel/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />

</head>





<body data-layout="horizontal" data-topbar="dark">
@if(Auth::user()->seviye==1)
<div class="container-fluid">
    <!-- Begin page -->
    <div id="layout-wrapper">

@include('.panel.layouts.header')

        @yield('content')

@include('.panel.layouts.footer')

    </div>
</div>
@else
<script>window.location = "/";</script>
@endif
</body>

</html>
