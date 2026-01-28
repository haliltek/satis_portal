<!-- Kampanya Bilgi Modal - Diğer Müşteriler İçin -->
<div class="modal fade" id="kampanyaModalRegular" tabindex="-1" aria-labelledby="kampanyaModalRegularLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title text-white" id="kampanyaModalRegularLabel">
                    <i class="bi bi-gift me-2"></i>Kampanya Bilgileri
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Yurtiçi Müşteriler</strong> için mevcut özel fiyatlar:
                </div>
                
                <div class="row g-3" id="kampanyaListesiRegular">
                    <?php
                    // DB bağlantısı (Karakter seti sorunu için özel bağlantı)
                    $config2 = require dirname(__DIR__) . '/config/config.php';
                    $dbConfig2 = $config2['db'];
                    $campDb2 = new mysqli($dbConfig2['host'], $dbConfig2['user'], $dbConfig2['pass'], $dbConfig2['name'], $dbConfig2['port']);
                    $campDb2->set_charset("utf8mb4");
                    
                    // Aktif kampanyaları çek
                    $sql2 = "SELECT * FROM custom_campaigns ORDER BY id ASC";
                    $result2 = $campDb2->query($sql2);
                    
                    // Duplicate önleme için görülen ID'leri takip et
                    $seenIds2 = [];

                    if ($result2 && $result2->num_rows > 0) {
                        while ($camp2 = $result2->fetch_assoc()) {
                            $campId2 = $camp2['id'];
                            $categoryName2 = $camp2['category_name'] ?? $camp2['name'];
                            
                            // Duplicate kontrolü
                            if (in_array($categoryName2, $seenIds2)) {
                                continue;
                            }
                            $seenIds2[] = $categoryName2;
                            
                            // Kampanya kurallarını çek
                            $rulesResult2 = $campDb2->query("SELECT * FROM custom_campaign_rules WHERE campaign_id = $campId2 ORDER BY priority ASC");
                            $rules2 = [];
                            while($rule2 = $rulesResult2->fetch_assoc()) {
                                // Ana Bayi ek iskonto kurallarını filtrele (10,000 EUR+ gibi)
                                // Sadece peşin ödeme kuralını göster
                                if ($rule2['rule_type'] === 'payment_based') {
                                    $rules2[] = $rule2;
                                }
                            }
                            
                            // Eğer sadece Ana Bayi ek iskonto kuralları varsa bu kampanyayı gösterme
                            if (count($rules2) === 0) {
                                continue;
                            }

                            // Kampanya ürünlerini çek
                            $prodResult2 = $campDb2->query("SELECT count(*) as total FROM custom_campaign_products WHERE campaign_id = $campId2");
                            $prodCount2 = $prodResult2->fetch_assoc()['total'];

                            // Tarih formatı
                            $start2 = '01.01.2026';
                            $end2 = '31.12.2026';
                            $validity2 = "$start2 - $end2";
                            
                            // Renk seçimi
                            $colors2 = ['primary', 'success', 'warning', 'info'];
                            $color2 = $colors2[$campId2 % count($colors2)];

                            // İsim düzeltmesi - "Ana Bayi" veya "Ertek" kelimelerini kaldır
                            $displayName2 = str_replace(['Ertek', 'Ana Bayi', 'ERTEK', 'ANA BAYİ'], '', $camp2['name']);
                            $displayName2 = trim($displayName2);
                            
                            // Kampanyaya göre özel birimler
                            $isMediaCampaign2 = (stripos($categoryName2, 'MEDYA') !== false);
                            $isKenarCampaign2 = (stripos($categoryName2, 'KENAR') !== false);
                            
                            if ($isMediaCampaign2) {
                                $quantityUnit2 = 'KG';
                                $amountUnit2 = 'KG';
                            } elseif ($isKenarCampaign2) {
                                $quantityUnit2 = 'Metre';
                                $amountUnit2 = ($camp2['currency'] ?? 'EUR');
                            } else {
                                $quantityUnit2 = 'Adet';
                                $amountUnit2 = ($camp2['currency'] ?? 'EUR');
                            }
                    ?>
                    <div class="col-md-6">
                        <div class="card border-<?= $color2 ?> h-100">
                            <div class="card-header bg-<?= $color2 ?> text-white">
                                <h6 class="mb-0"><i class="bi bi-gift-fill me-2"></i><?= htmlspecialchars($displayName2) ?></h6>
                            </div>
                            <div class="card-body">
                                <p class="card-text"><strong>Geçerlilik:</strong> <?= $validity2 ?></p>
                                <p class="card-text"><strong>Kapsam:</strong> <?= $prodCount2 > 0 ? $prodCount2 . " Adet Ürün" : "Tüm Ürünler" ?></p>
                                
                                <?php if($camp2['min_quantity'] > 0): ?>
                                <small class="text-muted d-block">Min. Sipariş: <?= number_format($camp2['min_quantity'],0) ?> <?= $quantityUnit2 ?></small>
                                <?php endif; ?>
                                
                                <?php if($camp2['min_total_amount'] > 0): ?>
                                <small class="text-muted d-block">Min. Tutar: <?= number_format($camp2['min_total_amount'],2,',','.') ?> <?= $amountUnit2 ?></small>
                                <?php endif; ?>

                                <?php if(count($rules2) > 0): ?>
                                <hr class="my-2">
                                <ul class="list-unstyled mb-0 small">
                                    <?php foreach($rules2 as $rule2): 
                                        $desc2 = match($rule2['rule_type']) {
                                            'payment_based'  => "Peşin Ödeme",
                                            default          => "Genel"
                                        };
                                    ?>
                                    <li>
                                        <i class="bi bi-check-circle-fill text-<?= $color2 ?> me-1"></i>
                                        <?php 
                                        $displayRuleName2 = $rule2['rule_name'];
                                        if ($isMediaCampaign2) {
                                            $displayRuleName2 = str_replace('€', 'KG', $displayRuleName2);
                                        }
                                        ?>
                                        <strong><?= htmlspecialchars($displayRuleName2) ?>:</strong> +%<?= number_format($rule2['discount_rate'], 2) ?>
                                        <span class="text-muted">(<?= $desc2 ?>)</span>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php 
                        }
                    } else { 
                    ?>
                    <div class="col-12">
                        <div class="alert alert-warning text-center">
                            <i class="bi bi-exclamation-triangle me-2"></i> Aktif kampanya bulunmamaktadır.
                        </div>
                    </div>
                    <?php } 
                    // Bağlantıyı kapat
                    if(isset($campDb2)) $campDb2->close();
                    ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>
