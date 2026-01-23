<?php
require_once 'fonk.php';
oturumkontrol();

global $sistemayar, $db;
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8" />
    <title><?php echo $sistemayar["title"]; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" />
    <link href="assets/css/icons.min.css" rel="stylesheet" />
    <link href="assets/css/app.min.css" rel="stylesheet" />
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body data-layout="horizontal" data-topbar="colored">
    <div id="layout-wrapper">
        <header id="page-topbar">
            <?php include "menuler/ustmenu.php"; ?>
            <?php include "menuler/solmenu.php"; ?>
        </header>
        
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <!-- Header -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <h4 class="mb-0">🎯 Kampanya Özel Fiyatlar</h4>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addProductModal">
                                        <i class="bi bi-plus-circle me-1"></i> Yeni Ürün Ekle
                                    </button>
                                    <a href="import_kampanya_fiyatlar.php" class="btn btn-info">
                                        <i class="bi bi-upload me-1"></i> Data Import
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card shadow-sm">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="kampanyaTable" class="table table-striped table-bordered dt-responsive nowrap" style="width:100%;">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Stok Kodu</th>
                                                    <th>Stok Adı</th>
                                                    <th>Yurtiçi Fiyat</th>
                                                    <th>İhracat Fiyatı</th>
                                                    <th>Özel Fiyat</th>
                                                    <th>Kategori</th>
                                                    <th>İşlemler</th>
                                                </tr>
                                            </thead>
                                            <tfoot class="table-light">
                                                <tr>
                                                    <th>Stok Kodu</th>
                                                    <th>Stok Adı</th>
                                                    <th>Yurtiçi Fiyat</th>
                                                    <th>İhracat Fiyatı</th>
                                                    <th>Özel Fiyat</th>
                                                    <th>Kategori</th>
                                                    <th>İşlemler</th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include "menuler/footer.php"; ?>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Yeni Ürün Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addProductForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Stok Kodu <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="stok_kodu" id="new_stok_kodu" required>
                            <small class="text-muted">Logo'dan otomatik doldurulacak</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Özel Fiyat (€) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" name="ozel_fiyat" id="new_ozel_fiyat" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kategori</label>
                            <input type="text" class="form-control" name="kategori" id="new_kategori">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-success">Ekle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/libs/metismenu/metisMenu.min.js"></script>
    <script src="assets/libs/simplebar/simplebar.min.js"></script>
    <script src="assets/libs/node-waves/waves.min.js"></script>
    <script src="assets/js/app.js"></script>
    <script src="assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
    <script src="assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
    <script src="assets/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            var table = $('#kampanyaTable').DataTable({
                "serverSide": true,
                "processing": true,
                "ajax": "kampanyalar_datatable.php",
                "pageLength": 25,
                "language": {
                    "url": "assets/libs/datatables.net/i18n/tr.json"
                },
                "order": [[0, 'asc']]
            });

            // Inline Special Price Edit
            $(document).on('change', '.special-price-input', function() {
                var $input = $(this);
                var id = $input.data('id');
                var newPrice = $input.val();
                
                $.ajax({
                    url: 'api/kampanya/update_special_price.php',
                    type: 'POST',
                    data: {
                        id: id,
                        ozel_fiyat: newPrice
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $input.addClass('is-valid');
                            setTimeout(() => $input.removeClass('is-valid'), 2000);
                        } else {
                            alert('Hata: ' + response.message);
                            table.ajax.reload(null, false);
                        }
                    },
                    error: function() {
                        alert('Sunucu hatası');
                        table.ajax.reload(null, false);
                    }
                });
            });

            // Delete Product
            $(document).on('click', '.delete-product-btn', function() {
                if (!confirm('Bu ürünü kampanyadan kaldırmak istediğinizden emin misiniz?')) {
                    return;
                }
                
                var id = $(this).data('id');
                
                $.ajax({
                    url: 'api/kampanya/delete_product.php',
                    type: 'POST',
                    data: { id: id },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            table.ajax.reload();
                        } else {
                            alert('Hata: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Sunucu hatası');
                    }
                });
            });

            // Add Product Form
            $('#addProductForm').on('submit', function(e) {
                e.preventDefault();
                
                var formData = $(this).serialize();
                
                $.ajax({
                    url: 'api/kampanya/add_product.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#addProductModal').modal('hide');
                            $('#addProductForm')[0].reset();
                            table.ajax.reload();
                        } else {
                            alert('Hata: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Sunucu hatası');
                    }
                });
            });
        });
    </script>
</body>
</html>
