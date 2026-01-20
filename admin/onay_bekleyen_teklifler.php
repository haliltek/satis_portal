<?php
// Bu sayfa artık ana teklif listesinin filtrelenmiş halini kullanmaktadır.
// Eski sayfadaki eksik veri/görünüm sorunlarını gidermek için yönlendirme eklenmiştir.
header("Location: ../teklifsiparisler.php?status=" . urlencode('Yönetici Onayı Bekleniyor'));
exit;
?>
