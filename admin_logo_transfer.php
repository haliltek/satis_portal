<?php
// admin_logo_transfer.php
include "fonk.php";
oturumkontrol();

if ($_SESSION['user_type'] === 'Bayi') {
    header('Location: anasayfa.php');
    exit;
}

// Fetch orders ready for transfer (Pending)
$conn = $db;
$statusToFind = 'Sipariş Onaylandı / Logoya Aktarım Bekliyor';
$offersSql = "SELECT o.id, o.teklifkodu, o.musteriadi, o.tekliftarihi, o.toplamtutar, o.geneltoplam, o.currency, y.adsoyad as hazirlayan
              FROM ogteklif2 o
              LEFT JOIN yonetici y ON o.hazirlayanid = y.yonetici_id
              WHERE o.durum = ? 
              AND (o.logo_transfer_status IS NULL OR o.logo_transfer_status != 'Aktarıldı')
              ORDER BY o.tekliftarihi DESC";
$stmt = $conn->prepare($offersSql);
$stmt->bind_param("s", $statusToFind);
$stmt->execute();
$offersResult = $stmt->get_result();
$pendingOffers = $offersResult->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Filtering logic for Transferred Orders
$f_start_date = $_GET['start_date'] ?? '';
$f_end_date   = $_GET['end_date'] ?? '';
$f_musteri    = $_GET['musteri'] ?? '';
$f_hazirlayan = $_GET['hazirlayan'] ?? '';

// Fetch only managers for filter dropdown
$managers = $conn->query("SELECT yonetici_id, adsoyad FROM yonetici ORDER BY adsoyad ASC")->fetch_all(MYSQLI_ASSOC);

$whereClauses = ["o.logo_transfer_status = 'Aktarıldı'"];
$params = [];
$types = "";

if (!empty($f_start_date)) {
    $whereClauses[] = "o.logo_transfer_date >= ?";
    $params[] = $f_start_date . " 00:00:00";
    $types .= "s";
}
if (!empty($f_end_date)) {
    $whereClauses[] = "o.logo_transfer_date <= ?";
    $params[] = $f_end_date . " 23:59:59";
    $types .= "s";
}
if (!empty($f_musteri)) {
    $whereClauses[] = "o.musteriadi LIKE ?";
    $params[] = "%$f_musteri%";
    $types .= "s";
}
if (!empty($f_hazirlayan)) {
    $whereClauses[] = "o.hazirlayanid = ?";
    $params[] = (int)$f_hazirlayan;
    $types .= "i";
}

$whereSql = implode(" AND ", $whereClauses);
$transferredSql = "SELECT o.id, o.teklifkodu, o.musteriadi, o.tekliftarihi, o.toplamtutar, o.geneltoplam, o.currency, o.number as logo_no, o.logo_transfer_date, y.adsoyad as hazirlayan
                   FROM ogteklif2 o
                   LEFT JOIN yonetici y ON o.hazirlayanid = y.yonetici_id
                   WHERE $whereSql
                   ORDER BY o.logo_transfer_date DESC";

$stmt = $conn->prepare($transferredSql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$transferredOffers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8" />
    <title>Logo Aktarım Yönetimi | <?php echo $sistemayar["title"]; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="assets/images/favicon.ico">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" />
    <link href="assets/css/icons.min.css" rel="stylesheet" />
    <link href="assets/css/app.min.css" rel="stylesheet" />
    <!-- DataTables -->
    <link href="assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/custom.css" rel="stylesheet" />
    <style>
        .queue-status-pending { color: #f1b44c; }
        .queue-status-processing { color: #50a5f1; font-weight: bold; }
        .queue-status-success { color: #34c38f; }
        .queue-status-error { color: #f46a6a; }
        .animate-pulse {
            animation: pulse-animation 2s infinite;
        }
        @keyframes pulse-animation {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
    </style>
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
                    <!-- Başlık -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-flex align-items-center justify-content-between">
                                <h4 class="mb-0">Logo Aktarım Yönetimi (Kuyruk Sistemi)</h4>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Sipariş Listesi (Sekmeli) -->
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header bg-soft-primary d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">Sipariş Yönetimi</h5>
                                    <ul class="nav nav-pills card-header-pills" id="transferTabs" role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link active" id="pending-tab" data-bs-toggle="tab" href="#pending" role="tab">Sıradakiler <span class="badge bg-danger ms-1"><?= count($pendingOffers) ?></span></a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" id="transferred-tab" data-bs-toggle="tab" href="#transferred" role="tab">Aktarılanlar</a>
                                        </li>
                                    </ul>
                                </div>
                                <div class="card-body">
                                    <div class="tab-content">
                                        <!-- Aktarılacaklar Sekmesi -->
                                        <div class="tab-pane fade show active" id="pending" role="tabpanel">
                                            <div class="table-responsive">
                                                <table class="table table-centered table-nowrap mb-0">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Teklif No</th>
                                                            <th>Müşteri</th>
                                                            <th>Hazırlayan</th>
                                                            <th class="text-end">Tutar</th>
                                                            <th>İşlem</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php if (empty($pendingOffers)): ?>
                                                            <tr>
                                                                <td colspan="5" class="text-center p-4 text-muted">Aktarım bekleyen sipariş bulunamadı.</td>
                                                            </tr>
                                                        <?php else: ?>
                                                            <?php foreach ($pendingOffers as $off): ?>
                                                                <tr>
                                                                    <td><a href="offer_detail.php?te=<?= $off['id'] ?>" target="_blank">#<?= $off['teklifkodu'] ?></a></td>
                                                                    <td style="max-width: 150px; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($off['musteriadi']) ?></td>
                                                                    <td><?= htmlspecialchars($off['hazirlayan'] ?? 'Bilinmeyen') ?></td>
                                                                    <td class="text-end">
                                                                        <strong><?= number_format($off['geneltoplam'], 2) ?></strong> 
                                                                        <small><?= $off['currency'] ?: 'TL' ?></small>
                                                                    </td>
                                                                    <td>
                                                                        <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Yönetici'): ?>
                                                                            <button class="btn btn-primary btn-sm btn-transfer" data-id="<?= $off['id'] ?>">
                                                                                <i class="bx bx-send me-1"></i> Aktar
                                                                            </button>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <!-- Aktarılanlar Sekmesi -->
                                        <div class="tab-pane fade" id="transferred" role="tabpanel">
                                            <!-- Filtreleme Formu -->
                                            <div class="card mb-3 border shadows-none bg-light pt-3">
                                                <div class="card-body py-0">
                                                    <form method="GET" class="row g-2 align-items-end mb-3">
                                                        <div class="col-md-2">
                                                            <label class="form-label small mb-1">Başlangıç Tarihi</label>
                                                            <input type="date" name="start_date" class="form-control form-control-sm" value="<?= htmlspecialchars($f_start_date) ?>">
                                                        </div>
                                                        <div class="col-md-2">
                                                            <label class="form-label small mb-1">Bitiş Tarihi</label>
                                                            <input type="date" name="end_date" class="form-control form-control-sm" value="<?= htmlspecialchars($f_end_date) ?>">
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label class="form-label small mb-1">Müşteri/Cari Adı</label>
                                                            <input type="text" name="musteri" class="form-control form-control-sm" placeholder="Ara..." value="<?= htmlspecialchars($f_musteri) ?>">
                                                        </div>
                                                        <div class="col-md-2">
                                                            <label class="form-label small mb-1">Hazırlayan</label>
                                                            <select name="hazirlayan" class="form-select form-select-sm">
                                                                <option value="">Tümü</option>
                                                                <?php foreach($managers as $m): ?>
                                                                    <option value="<?= $m['yonetici_id'] ?>" <?= $f_hazirlayan == $m['yonetici_id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['adsoyad']) ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-3 pb-1">
                                                            <button type="submit" class="btn btn-primary btn-sm px-3">
                                                                <i class="bx bx-filter-alt me-1"></i> Filtrele
                                                            </button>
                                                            <a href="admin_logo_transfer.php" class="btn btn-light btn-sm px-2">Temizle</a>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>

                                            <div class="table-responsive">
                                                <table id="datatable-transferred" class="table table-centered table-nowrap mb-0 w-100">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Logo No</th>
                                                            <th>Müşteri</th>
                                                            <th>Tarih</th>
                                                            <th class="text-end">Tutar</th>
                                                            <th>Durum</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php if (empty($transferredOffers)): ?>
                                                            <tr>
                                                                <td colspan="5" class="text-center p-4 text-muted">Henüz aktarılan sipariş bulunmuyor.</td>
                                                            </tr>
                                                        <?php else: ?>
                                                            <?php foreach ($transferredOffers as $off): ?>
                                                                <tr>
                                                                    <td><span class="badge bg-soft-success text-success">#<?= $off['logo_no'] ?></span></td>
                                                                    <td style="max-width: 150px; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($off['musteriadi']) ?></td>
                                                                    <td><?= date('d.m.Y H:i', strtotime($off['logo_transfer_date'])) ?></td>
                                                                    <td class="text-end">
                                                                        <strong><?= number_format($off['geneltoplam'], 2) ?></strong> 
                                                                        <small><?= $off['currency'] ?: 'TL' ?></small>
                                                                    </td>
                                                                    <td>
                                                                        <a href="offer_detail.php?te=<?= $off['id'] ?>" target="_blank" class="btn btn-light btn-sm">
                                                                            <i class="bx bx-show"></i> Detay
                                                                        </a>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <!-- Kuyruk İzleme -->
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header bg-soft-info d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">Aktarım Kuyruğu İzleyici</h5>
                                    <span id="queue-indicator" class="badge bg-success d-none animate-pulse">İşleniyor...</span>
                                </div>
                                <div class="card-body">
                                    <div id="queue-monitor" style="max-height: 400px; overflow-y: auto;">

                                        <div class="text-center p-4">
                                            <div class="spinner-border text-primary" role="status"></div>
                                            <p class="mt-2 text-muted">Kuyruk durumu yükleniyor...</p>
                                        </div>
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

    <script src="assets/libs/jquery/jquery.min.js"></script>
    <script src="assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    
    <!-- Required datatable js -->
    <script src="assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
    <!-- Responsive examples -->
    <script src="assets/libs/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
    <script src="assets/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTables
            $('#datatable-transferred').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Turkish.json"
                },
                "order": [[2, "desc"]], // Sort by date column
                "pageLength": 10,
                "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "Tümü"]],
                "responsive": true
            });

            loadQueueStatus();
            
            // Poll queue status every 3 seconds
            setInterval(loadQueueStatus, 3000);

            // Transfer button click
            $(document).on('click', '.btn-transfer', function() {
                const btn = $(this);
                const offerId = btn.data('id');
                
                btn.prop('disabled', true).html('<i class="bx bx-loader bx-spin me-1"></i> Bekleyin...');
                
                $.post('api/add_to_queue.php', { offer_id: offerId }, function(res) {
                    if (res.status) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Başarılı',
                            text: res.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        loadQueueStatus();
                        // Trigger processing immediately
                        triggerQueueProcessor();
                    } else {
                        Swal.fire('Hata', res.message, 'error');
                    }
                    btn.prop('disabled', false).html('<i class="bx bx-send me-1"></i> Aktar');
                }, 'json').fail(function() {
                    Swal.fire('Hata', 'İstemci hatası oluştu.', 'error');
                    btn.prop('disabled', false).html('<i class="bx bx-send me-1"></i> Aktar');
                });

            });

            function loadQueueStatus() {
                $.get('api/get_queue_status.php', function(res) {
                    if (res.status && res.data) {
                        renderQueue(res.data);
                        checkIfProcessing(res.data);
                    }
                }, 'json');
            }

            function renderQueue(data) {
                if (data.length === 0) {
                    $('#queue-monitor').html('<p class="text-center text-muted p-4">Kuyrukta işlem bulunmuyor.</p>');
                    return;
                }

                let html = '<div class="list-group list-group-flush">';
                data.forEach(item => {
                    let statusClass = 'queue-status-' + item.status;
                    let icon = 'bx-time';
                    if (item.status === 'processing') icon = 'bx-sync bx-spin';
                    if (item.status === 'success') icon = 'bx-check-circle';
                    if (item.status === 'error') icon = 'bx-x-circle';

                    html += `
                        <div class="list-group-item px-0">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0 me-3">
                                    <div class="avatar-sm">
                                        <span class="avatar-title rounded-circle bg-light ${statusClass} fs-4">
                                            <i class="bx ${icon}"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="flex-grow-1 overflow-hidden">
                                    <h6 class="text-truncate mb-1">
                                        #${item.teklifkodu} - ${item.musteriadi || 'Bilinmeyen'}
                                    </h6>
                                    <p class="text-muted mb-0 small">
                                        Tutar: <strong>${parseFloat(item.geneltoplam || 0).toLocaleString('tr-TR', {minimumFractionDigits: 2})} ${item.currency || 'TL'}</strong> | 
                                        Hazırlayan: ${item.hazirlayan || 'Bilinmeyen'}
                                    </p>
                                    <p class="text-muted text-truncate mb-0 small">
                                        Status: <span class="${statusClass}">${item.status.toUpperCase()}</span> 
                                        ${item.logo_no ? ' | Logo No: ' + item.logo_no : ''}
                                    </p>
                                    ${item.message ? `<p class="mt-1 mb-0 small text-danger">${item.message}</p>` : ''}
                                </div>

                                <div class="flex-shrink-0 text-end ms-2">
                                    <span class="text-muted small">${formatTime(item.created_at)}</span>
                                </div>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                $('#queue-monitor').html(html);
            }

            function checkIfProcessing(data) {
                const isProcessing = data.some(item => item.status === 'processing');
                const hasPending = data.some(item => item.status === 'pending');
                
                if (isProcessing) {
                    $('#queue-indicator').removeClass('d-none').text('İşleniyor...');
                } else if (hasPending) {
                    $('#queue-indicator').removeClass('d-none').text('Bekleniyor...');
                    // Try to trigger processor if something is pending but not processing
                    triggerQueueProcessor();
                } else {
                    $('#queue-indicator').addClass('d-none');
                }
            }

            function triggerQueueProcessor() {
                // Background call to process_queue.php
                fetch('api/process_queue.php').then(() => {
                    loadQueueStatus();
                }).catch(() => {});
            }

            function formatTime(dateTimeStr) {
                const date = new Date(dateTimeStr);
                return date.getHours().toString().padStart(2, '0') + ':' + date.getMinutes().toString().padStart(2, '0');
            }
        });
    </script>
</body>
</html>
