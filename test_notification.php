<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bildirim Testi</title>
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- Bootstrap CSS (Opsiyonel, güzel görünmesi için) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>

    <div class="card text-center shadow p-5">
        <h3 class="mb-4">Bildirim Test Paneli</h3>
        <p class="text-muted">En son oluşturulan teklifin durumunu dinliyor...</p>
        <div id="statusResult" class="alert alert-secondary">Bekleniyor...</div>
        <!-- <button id="notifyBtn" class="btn btn-primary btn-lg">🔔 Manuel Test</button> -->
    </div>

    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        let lastStatus = null;
        let lastId = null;

        function checkStatus() {
            $.get('api/teklif/check_last_offer_status.php?t=' + new Date().getTime(), function(data) {
                if (data.found) {
                    const currentStatus = data.durum;
                    const approval = data.approval_status;
                    const id = data.id;

                    $('#statusResult').text('#' + id + ' - ' + currentStatus);

                    // İlk yüklemede sadece durumu kaydet, bildirim gösterme
                    if (lastStatus === null) {
                        lastStatus = currentStatus;
                        lastId = id;
                        return;
                    }

                    // ID değiştiyse (yeni teklif oluşturuldu)
                    if (lastId !== id) {
                        lastId = id;
                        lastStatus = currentStatus; // Yeni teklifin durumunu al
                        // Yeni teklif bildirimi? İstenirse eklenebilir.
                        return;
                    }

                    // Durum değiştiyse BİLDİRİM GÖNDER
                    if (lastStatus !== currentStatus) {
                        lastStatus = currentStatus; // Durumu güncelle

                        if (approval === 'approved' || currentStatus.includes('Onayladı')) {
                            showToast('success', 'Teklif Onaylandı!', 'Teklif #' + id + ' yönetici tarafından onaylandı.');
                        } else if (approval === 'rejected' || currentStatus.includes('Red')) {
                            showToast('error', 'Teklif Reddedildi', 'Teklif #' + id + ' yönetici tarafından reddedildi.');
                        } else {
                            showToast('info', 'Durum Değişti', 'Teklif #' + id + ' durumu: ' + currentStatus);
                        }
                    }
                }
            });
        }

        function showToast(icon, title, text) {
             const Toast = Swal.mixin({
                toast: true,
                position: 'bottom-end',
                showConfirmButton: false,
                timer: 8000, 
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                    toast.addEventListener('click', Swal.close)
                    toast.style.cursor = 'pointer';
                }
            });

            Toast.fire({
                icon: icon,
                title: title,
                text: text
            });
        }

        // Her 4 saniyede bir kontrol et
        setInterval(checkStatus, 4000);
        checkStatus(); // Sayfa açılınca hemen bir kontrol et
    </script>

</body>
</html>
