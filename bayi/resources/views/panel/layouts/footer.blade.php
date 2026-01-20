
<footer class="footer">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <p><script>document.write(new Date().getFullYear())</script> © {{baslik()}}</p>
            </div>
            <div class="col-sm-6">
                <div class="text-sm-right d-none d-sm-block">
                    B2B Satış Sistemi 
                </div>
            </div>
        </div>
    </div>
</footer>

<x-rightbar />

<!-- Right bar overlay-->
<div class="rightbar-overlay"></div>



<!-- JAVASCRIPT -->


<!--
<script src="{{ asset('assets/panel/libs/jquery/jquery.min.js') }}"></script>
<script src="//cdn.datatables.net/1.10.7/js/jquery.dataTables.min.js"></script>
@jquery
-->
@jquery
@toastr_js
@toastr_render

<!-- cdnjs -->
<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/jquery.lazy/1.7.9/jquery.lazy.min.js"></script>
<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/jquery.lazy/1.7.9/jquery.lazy.plugins.min.js"></script>
<script src="/public/assets/panel/js/sistem.js"></script>
<script src="//cdn.datatables.net/1.10.7/js/jquery.dataTables.min.js"></script>
<script src="/public/assets/panel/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/public/assets/panel/libs/metismenu/metisMenu.min.js"></script>
<script src="/public/assets/panel/libs/simplebar/simplebar.min.js"></script>
<script src="/public/assets/panel/libs/node-waves/waves.min.js"></script>

<!-- apexcharts -->
<script src="/public/assets/panel/libs/apexcharts/apexcharts.min.js"></script>

<!-- jquery.vectormap map -->
<script src="/public/assets/panel/libs/admin-resources/jquery.vectormap/jquery-jvectormap-1.2.2.min.js"></script>
<script src="/public/assets/panel/libs/admin-resources/jquery.vectormap/maps/jquery-jvectormap-us-merc-en.js"></script>

<script src="/public/assets/panel/js/pages/dashboard.init.js"></script>

<!-- form wizard -->
<script src="/public/assets/panel/libs/jquery-steps/build/jquery.steps.min.js"></script>

<!-- form wizard init -->
<script src="/public/assets/panel/js/pages/form-wizard.init.js"></script>

<script src="/public/assets/panel/js/app.js"></script>
<script src="/public/assets/panel/js/report.js"></script>

<script src="/public/assets/panel/js/main.js"></script>
<script src="/public/assets/panel/js/batch-processing.js"></script>
<script src="/public/assets/panel/js/mobile.js"></script>
<!-- DataTables -->

<!-- Required datatable js -->
<script src="/public/assets/panel/libs/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="/public/assets/panel/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
<!-- Buttons examples -->
<script src="/public/assets/panel/libs/datatables.net-buttons/js/dataTables.buttons.min.js"></script>
<script src="/public/assets/panel/libs/datatables.net-buttons-bs4/js/buttons.bootstrap4.min.js"></script>
<script src="/public/assets/panel/libs/jszip/jszip.min.js"></script>
<script src="/public/public/assets/panel/libs/pdfmake/build/pdfmake.min.js"></script>
<script src="/public/assets/panel/libs/pdfmake/build/vfs_fonts.js"></script>
<script src="/public/assets/panel/libs/datatables.net-buttons/js/buttons.html5.min.js"></script>
<script src="/public/assets/panel/libs/datatables.net-buttons/js/buttons.print.min.js"></script>
<script src="/public/assets/panel/libs/datatables.net-buttons/js/buttons.colVis.min.js"></script>
<!-- Responsive examples -->
<script src="/public/assets/panel/libs/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
<script src="/public/assets/panel/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js"></script>

<!-- Datatable init js -->
<script src="/public/assets/panel/js/pages/datatables.init.js"></script>

<script src="/public/assets/front/assets/libs/sweetalert2/sweetalert2.min.js"></script>
<script src="/public/assets/front/assets/js/pages/sweet-alerts.init.js"></script>

<!-- Bootstrap JavaScript -->
<!-- App scripts -->
<script src="/public/assets/panel/libs/tinymce/tinymce.min.js"></script>
<script src="/public/assets/panel/js/pages/form-editor.init.js"></script>
@stack('scripts')
