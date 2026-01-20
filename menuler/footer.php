<footer class="footer">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <script>
                    document.write(new Date().getFullYear())
                </script> © Gemaş Ar-Ge Yazılım
            </div>
            <div class="col-sm-6">
                <div class="text-sm-end d-none d-sm-block">
                    Tüm Hakları Saklıdır </a>
                </div>
            </div>
        </div>
    </div>
</footer>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Note: jQuery is assumed to be loaded in the main layout (e.g., anasayfa.php loads it). If not, it might fail. -->
<script>
    // Global Notification System
    (function(){
        let lastStatus = null;
        let lastId = null;

        function checkGlobalStatus() {
            // Ensure jQuery is loaded
            if (typeof $ === 'undefined') return;

            $.get('api/teklif/check_last_offer_status.php?t=' + new Date().getTime(), function(data) {
                if (data && data.found) {
                    const currentStatus = data.durum;
                    const approval = data.approval_status;
                    const id = data.id;

                    // Init on first load
                    if (lastStatus === null) {
                        lastStatus = currentStatus;
                        lastId = id;
                        return;
                    }

                    // If ID changed (new offer), just update reference
                    if (lastId !== id) {
                        lastId = id;
                        lastStatus = currentStatus;
                        return;
                    }

                    // If Status Changed for same ID
                    if (lastStatus !== currentStatus) {
                        lastStatus = currentStatus;

                        // Dispatch Global Event for other scripts to listen
                        document.dispatchEvent(new CustomEvent('offerStatusUpdate', { 
                            detail: { 
                                id: id, 
                                status: currentStatus,
                                approval: approval 
                            } 
                        }));

                        if (approval === 'approved' || currentStatus.includes('Onayladı')) {
                            let title = 'Teklif Onaylandı!';
                            let msg   = 'Teklif #' + id + ' onaylandı.';

                            if (currentStatus.includes('Yönetici')) {
                                title = 'Yönetici Onayladı';
                                msg   = 'Teklif #' + id + ' yönetici tarafından onaylandı. Müşteriye gönderilebilir.';
                            } else if (currentStatus.includes('Sipariş Onaylandı')) {
                                title = 'Müşteri Onayladı';
                                msg   = 'Teklif #' + id + ' müşteri tarafından onaylandı!';
                            }

                            showGlobalToast('success', title, msg);
                        } else if (approval === 'rejected' || currentStatus.includes('Red')) {
                            showGlobalToast('error', 'Teklif Reddedildi', 'Teklif #' + id + ' yönetici tarafından reddedildi.');
                        } else {
                            // Optional: Don't show generic status changes to avoid spam
                            // showGlobalToast('info', 'Durum Değişti', 'Teklif #' + id + ' durumu: ' + currentStatus);
                        }
                    }
                }
            });
        }

        function showGlobalToast(icon, title, text) {
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

        // Poll every 5 seconds
        setInterval(checkGlobalStatus, 5000);
    })();
</script>