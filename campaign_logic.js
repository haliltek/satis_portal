$(document).ready(function () {
    // Global special price state management
    if (!window.specialPriceItems) {
        window.specialPriceItems = new Set();
    }
    // === KAMPANYA SİSTEMİ ENTEGRASYONU v2 ===

    var $btn = $('#applyCampaignsBtn');
    var $kampanyaBtn = $('#kampanyaBtn');

    // Pazar tipi kontrolü için yardımcı fonksiyon
    function isPazarYurtdisi() {
        var checked = document.querySelector('input[name="pazar_tipi"]:checked');
        return checked && checked.value === 'yurtdisi';
    }

    // --- 0. KORUMALAR (VISIBILITY & LEGACY CLEANUP) ---
    // Sadece yurtiçi modunda göster
    if (!isPazarYurtdisi()) {
        $btn.show().css('display', 'inline-flex');
        $kampanyaBtn.show().css('display', 'inline-flex');
    }

    var protectionInterval = setInterval(function () {
        // Yurtdışı modunda butonları gösterme
        if (isPazarYurtdisi()) {
            return;
        }

        var $b = $('#applyCampaignsBtn');
        if ($b.css('display') === 'none') {
            $b.show().css('display', 'inline-flex');
        }
        if ($b.attr('onclick')) {
            $b.removeAttr('onclick');
        }
        $b.off('click'); // Direct handler temizliği

        // Kampanya Bilgi butonu koruması
        var $kb = $('#kampanyaBtn');
        if ($kb.css('display') === 'none') {
            $kb.show().css('display', 'inline-flex');
        }
    }, 200); 50

    setTimeout(function () {
        clearInterval(protectionInterval);
        setInterval(function () {
            // Yurtdışı modunda butonları gösterme
            if (isPazarYurtdisi()) {
                return;
            }

            $('#applyCampaignsBtn').show().css('display', 'inline-flex');
            $('#applyCampaignsBtn').removeAttr('onclick');
            // Kampanya Bilgi butonu koruması
            $('#kampanyaBtn').show().css('display', 'inline-flex');
        }, 2000);
    }, 10000);

    $(document).on('select2:select change', '#musteri', function () {
        // Yurtdışı modunda butonları gösterme
        if (isPazarYurtdisi()) {
            return;
        }

        setTimeout(function () { $('#applyCampaignsBtn').show().css('display', 'inline-flex'); }, 50);
        setTimeout(function () { $('#applyCampaignsBtn').off('click'); }, 100);
        // Kampanya Bilgi butonu koruması
        setTimeout(function () { $('#kampanyaBtn').show().css('display', 'inline-flex'); }, 50);
    });

    // --- 1. KOŞUL KONTROLÜ ---
    function checkCampaignConditions() {
        // Yurtdışı modunda kampanya kontrolü yapma
        if (isPazarYurtdisi()) {
            console.log('Yurtdışı modu - Kampanya kontrolü atlandı');
            return;
        }

        var cart = [];
        $('.editable-product-code').each(function () {
            var $input = $(this);
            var $row = $input.closest('tr');
            var code = $input.val().trim();
            var qtyVal = $row.find('.quantity-input').val();
            var qty = parseFloat(qtyVal) || 0;

            if (code && qty > 0) {
                cart.push({ code: code, quantity: qty });
            }
        });

        var $button = $('#applyCampaignsBtn');

        if (cart.length === 0) {
            $button.removeClass('campaign-blink')
                .html('<i class="bi bi-percent me-1"></i> ÖZEL FİYAT !')
                .data('campaigns', null);
            return;
        }

        // DEBUG: Ödeme planı ve peşin ödeme checkbox kontrolü
        var paymentMethod = $('#payplan').val() || '';
        var isPesinChecked = $('#pesinOdeme').is(':checked');

        // Peşin ödeme checkbox işaretliyse, payment_method'a ekle
        if (isPesinChecked) {
            paymentMethod = 'PEŞİN - ' + (paymentMethod || 'PEŞİN');
        }

        console.log('🔍 Ödeme Planı:', paymentMethod, '| Peşin Checkbox:', isPesinChecked);

        $.ajax({
            url: 'api/kampanya/check_conditions.php',
            type: 'POST',
            data: {
                cart: JSON.stringify(cart),
                customer_id: $('#musteri').val() || 0,
                customer_name: $('#musteri option:selected').text() || '',
                payment_method: paymentMethod // Peşin ödeme kontrolü için
            },
            dataType: 'json',
            success: function (response) {
                console.log('📊 Kampanya Yanıtı:', response);

                if (response.eligible) {
                    $button.addClass('campaign-blink')
                        .html('<i class="bi bi-gift-fill me-1"></i> ÖZEL FİYAT !')
                        .removeClass('btn-secondary').addClass('btn-warning')
                        .data('campaigns', response.campaigns);
                } else {
                    $button.removeClass('campaign-blink')
                        .html('<i class="bi bi-percent me-1"></i> ÖZEL FİYAT !')
                        .data('campaigns', null);
                }
                // Sadece yurtiçi modunda butonu göster
                if (!isPazarYurtdisi()) {
                    $button.show().css('display', 'inline-flex');
                }
            }
        });
    }

    $(document).on('input change', '.quantity-input, .editable-product-code', function () {
        // Miktar değiştiğinde özel fiyat uygulanmışsa kaldır
        if ($(this).hasClass('quantity-input')) {
            var $row = $(this).closest('tr');
            var $discountInput = $row.find('.discount-input');
            var $priceInput = $row.find('input[name^="fiyatsi"]');

            // Özel fiyat uygulanmış mı kontrol et (readonly/placeholder VEYA data attribute)
            var hasSpecialPriceMark = $discountInput.attr('data-has-special-price') === '1';
            if (hasSpecialPriceMark || ($discountInput.prop('readonly') && $discountInput.attr('placeholder') === 'Özel Fiyat')) {

                // KORUMA: Eğer kampanya uygulanıyorsa (sistem tetiklediyse) silme!
                if (window.isApplyingCampaign) {
                    console.log('Sistem güncelliyor - Özel fiyat korunuyor.');
                } else {
                    // Miktar değişti, özel fiyatı kaldır
                    removeSpecialPriceFromRow($row);

                    if (typeof toastr !== 'undefined') {
                        toastr.warning('Miktar değiştiği için özel fiyat kaldırıldı.');
                    }
                }
            }
        }

        if (window.campaignCheckTimeout) clearTimeout(window.campaignCheckTimeout);
        window.campaignCheckTimeout = setTimeout(checkCampaignConditions, 500);
    });

    // Ödeme planı veya peşin ödeme checkbox değiştiğinde kampanya kontrolü yap
    $(document).on('change', '#payplan, #pesinOdeme', function () {
        if (window.campaignCheckTimeout) clearTimeout(window.campaignCheckTimeout);
        window.campaignCheckTimeout = setTimeout(checkCampaignConditions, 500);
    });

    // Özel fiyatı satırdan kaldıran yardımcı fonksiyon
    function removeSpecialPriceFromRow($row) {
        var $priceInput = $row.find('input[name^="fiyatsi"]');
        var $discountInput = $row.find('.discount-input');
        var $priceCell = $priceInput.closest('td');

        // Orijinal liste fiyatını data attribute'dan al
        var originalPrice = $priceInput.data('original-price');

        if (originalPrice) {
            // Liste fiyatını geri yükle (Formatlı ve Kalın)
            $priceInput.val(originalPrice.toString().replace('.', ','));

            // Hücre içeriğini güncelle: <b>401,00</b> € <input...>
            var formattedPrice = parseFloat(originalPrice).toFixed(2).replace('.', ',');
            $priceCell.html('<b>' + formattedPrice + '</b>'); // Kalın yap

            // Para birimi ekle
            var currencyIcon = ' €'; // Default
            $priceCell.append(' ' + currencyIcon.trim());
            $priceCell.append($priceInput);
        }

        // İskonto alanını unlock et ve temizle
        $discountInput.prop('readonly', false)
            .attr('placeholder', '')
            .removeAttr('data-has-special-price')  // Marker'ı kaldır
            .val('');

        // Ana Bayi default iskontosunu geri yükle (eğer Ana Bayi müşteri seçiliyse)
        var customerName = $('#musteri option:selected').text() || '';
        if (customerName.includes('ERTEK') || customerName.includes('Ana Bayi')) {
            // Ödeme şekline göre iskonto belirle
            var paymentType = $('#odemesekli').val() || '';
            if (paymentType.includes('Peşin') || paymentType.includes('peşin')) {
                $discountInput.val('50.5');
            } else {
                $discountInput.val('45');
            }
        }

        // Visual indicator'ları kaldır
        $priceInput.removeClass('special-price-applied');
        $row.removeClass('table-success');
        $row.removeClass('row-has-special-price');
        $priceCell.removeClass('special-price-applied');

        // DOM STATE: Özel fiyat işaretini kaldır
        var code = $row.find('.editable-product-code').val();
        var $table = $('#cartTable');
        var specialItems = $table.data('special-items') || [];

        if (code && specialItems.includes(code)) {
            specialItems = specialItems.filter(item => item !== code);
            $table.data('special-items', specialItems);
            console.log('Removed from DOM storage:', code, specialItems);
        }

        // Hesaplamayı tetikle
        $row.find('.quantity-input').trigger('input');

        // --- GRUP İPTALİ (CASCADE DELETE) ---
        // Eğer bu satır bir gruba dahilse, gruptaki diğer ürünleri de iptal et
        var batchId = $row.attr('data-campaign-batch-id');
        if (batchId) {
            console.log('Batch iptal ediliyor:', batchId);
            // Sonsuz döngüyü engellemek için önce bu satırın ID'sini siliyoruz
            $row.removeAttr('data-campaign-batch-id');

            // Aynı ID'ye sahip diğer satırları bul
            $('tr[data-campaign-batch-id="' + batchId + '"]').each(function () {
                console.log('Gruptaki diğer ürün iptal ediliyor...');
                removeSpecialPriceFromRow($(this));
            });
        }
    }

    $(document).on('click', '.remove-btn', function () {
        setTimeout(checkCampaignConditions, 500);
    });

    setTimeout(checkCampaignConditions, 1000);

    // --- 3. MODAL AÇMA ---
    $(document).off('click.myCampaign', '#applyCampaignsBtn');
    $(document).on('click.myCampaign', '#applyCampaignsBtn', function (e) {
        e.preventDefault();
        console.log('Kampanya Butonuna Tıklandı (Delegated)');

        var campaigns = $(this).data('campaigns');
        var html = '';
        var showGlobalApply = false;

        if (campaigns && campaigns.length > 0) {
            html += '<div class="alert alert-success">Tebrikler! Aşağıdaki kampanya koşullarını sağladınız. İlgili satırlara uygulamak için butonları kullanın:</div>';

            campaigns.forEach(function (camp, index) {
                // Tüm kampanyalar eşit şekilde gösterilecek
                var cardBorder = 'border-success';
                var cardHeader = 'bg-success text-white';
                var btnClass = 'btn-primary';
                var btnText = 'Bu Gruba Uygula';

                html += '<div class="card mb-3 ' + cardBorder + ' shadow-sm">';
                html += '<div class="card-header ' + cardHeader + ' d-flex justify-content-between align-items-center">';
                html += '<span>' + camp.name;
                if (camp.discount_rate) {
                    html += ' <strong>(%' + camp.discount_rate + ')</strong>';
                }
                html += '</span>';
                html += '</div>';

                html += '<div class="card-body">';
                html += '<div class="d-flex justify-content-between align-items-center">';
                html += '<div>';
                html += '<p class="mb-1"><strong>Kategori:</strong> ' + camp.category + '</p>';
                html += '<p class="mb-0"><strong>Uygulanacak Ürünler:</strong> ' + camp.products.length + ' adet</p>';

                // Ürün fiyat listesi ekle
                if (camp.product_details && Object.keys(camp.product_details).length > 0) {
                    html += '<div class="mt-2" style="max-height: 120px; overflow-y: auto; font-size: 0.65rem;">';
                    html += '<table class="table table-sm table-bordered mb-0" style="font-size: 0.65rem; line-height: 1.2;">';
                    html += '<thead style="position: sticky; top: 0; background: white; z-index: 1;"><tr>';
                    html += '<th style="padding: 2px 4px;">Kod</th>';
                    html += '<th style="padding: 2px 4px;">Ürün Adı</th>';
                    html += '<th style="padding: 2px 4px;">Liste</th>';
                    html += '<th style="padding: 2px 4px;">Özel</th>';
                    html += '<th style="padding: 2px 4px;">İndirim</th>';
                    html += '</tr></thead>';
                    html += '<tbody>';

                    for (var code in camp.product_details) {
                        var detail = camp.product_details[code];
                        var listPrice = parseFloat(detail.list_price);
                        var specialPrice = parseFloat(detail.special_price);
                        var productName = detail.product_name || '-';

                        // İndirim yüzdesini hesapla
                        var discountPercent = 0;
                        if (listPrice > 0) {
                            discountPercent = ((listPrice - specialPrice) / listPrice) * 100;
                        }

                        var listPriceStr = listPrice.toFixed(2).replace('.', ',');
                        var specialPriceStr = specialPrice.toFixed(2).replace('.', ',');
                        var discountStr = discountPercent.toFixed(0);

                        html += '<tr style="line-height: 1.1;">';
                        html += '<td style="padding: 2px 4px; font-size: 0.6rem;">' + code + '</td>';
                        html += '<td style="padding: 2px 4px; font-size: 0.6rem;">' + productName + '</td>';
                        html += '<td style="padding: 2px 4px; font-size: 0.6rem;">' + listPriceStr + '</td>';
                        html += '<td style="padding: 2px 4px;" class="text-success fw-bold">' + specialPriceStr + '</td>';
                        html += '<td style="padding: 2px 4px;" class="text-danger fw-bold">-%' + discountStr + '</td>';
                        html += '</tr>';
                    }

                    html += '</tbody></table>';
                    html += '</div>';
                }

                html += '</div>'

                // Tekil Uygulama Butonu
                html += '<button type="button" class="' + btnClass + ' apply-single-campaign-btn" ' +
                    'data-products=\'' + JSON.stringify(camp.products) + '\' ' +
                    'data-campaign-name="' + camp.name + '" ' +
                    'data-discount-rate="' + (camp.discount_rate || 0) + '">' +
                    '<i class="bi bi-check2-circle"></i> ' + btnText + '</button>';

                html += '</div></div></div>'; // card body, card
            });

            // html += '<p class="text-muted small">Not: Herhangi bir grubun kampanyasını uyguladığınızda, o gruptaki ürünlerin fiyatları güncellenecektir.</p>';

            // Genel uygula butonunu gizle (artık tekil butonlar var)
            $('#confirmCampaignApply').hide();

        } else {
            html += '<div class="alert alert-warning">';
            html += '<h5><i class="bi bi-exclamation-triangle"></i> Uygun Kampanya Bulunamadı</h5>';
            html += '<p>Şu anki sepetiniz için aktif bir kampanya koşulu sağlanmamaktadır.</p>';
            html += '<hr>';
            html += '<p class="mb-0"><strong>İpucu:</strong> Kampanya koşullarını (örn: Filtre grubundan 10 adet alım) sağladığınızda buton yanıp sönecektir.</p>';
            html += '</div>';

            $('#confirmCampaignApply').hide();
        }

        $('#campaignModalContent').html(html);
        $('#campaignApplyModal').modal('show');
    });

    // --- 4. TEKİL KAMPANYA UYGULAMA BUTONU ---
    // Modal içindeki card butonlarına delegate handler
    $(document).on('click', '.apply-single-campaign-btn', function () {
        var $btn = $(this);
        var products = $btn.data('products'); // Array of codes
        var campaignName = $btn.data('campaign-name');
        var discountRate = $btn.data('discount-rate') || 0;

        if (!products || products.length === 0) return;

        // Butonu yükleniyor yap
        var originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Uygulanıyor...');

        // Özel fiyat kampanyası uygula
        $.ajax({
            url: 'api/kampanya/get_special_prices.php',
            type: 'POST',
            data: { codes: JSON.stringify(products) },
            dataType: 'json',
            success: function (response) {
                // Yeni format: {prices: {...}, debug: {...}}
                var prices = response.prices || response; // Geriye uyumluluk
                var debug = response.debug || null;

                // DEBUG: Hangi ürünlerde özel fiyat bulunamadı?
                if (debug && debug.not_found && debug.not_found.length > 0) {
                    console.warn('⚠️ Özel fiyat bulunamayan ürünler:', debug.not_found);
                    console.log('📊 İstenen:', debug.requested_codes.length, 'Bulunan:', debug.found_count);
                }

                applyPricesToTable(prices);
                $btn.removeClass('btn-primary').addClass('btn-success')
                    .html('<i class="bi bi-check-circle-fill"></i> Uygulandı');
            },
            error: function () {
                $btn.prop('disabled', false).html(originalHtml);
                alert('Fiyatlar çekilirken hata oluştu.');
            }
        });
    });

    // Ana Bayi ek iskonto fonksiyonu kaldırıldı

    // --- YARDIMCI FONKSİYON: FİYAT UYGULAMA ---
    function applyPricesToTable(prices) {
        // Batch ID oluştur (Grup iptali için)
        var batchId = 'batch_' + new Date().getTime();

        $('.editable-product-code').each(function () {
            var $input = $(this);
            var code = $input.val().trim();
            var $row = $input.closest('tr');

            if (prices[code]) {
                var specialPrice = prices[code];
                var $priceInput = $row.find('input[name^="fiyatsi"]');
                var $discountInput = $row.find('.discount-input');

                // 0. Orijinal fiyatı kaydet (Geri dönüş için)
                if (!$priceInput.data('original-price')) {
                    var currentListPrice = parseFloat($priceInput.val().replace(',', '.')) || 0;
                    $priceInput.data('original-price', currentListPrice); // Kaydet
                    $priceInput.attr('data-original-price', currentListPrice);
                    console.log('Original price saved:', currentListPrice);
                }

                // 1. Liste fiyatını güncelle
                $priceInput.val(specialPrice.toFixed(2).replace('.', ','));

                var $td = $row.find('td').eq(4);
                var currentText = $td.text();
                var currencyIcon = '';
                if (currentText.includes('€')) currencyIcon = ' €';
                else if (currentText.includes('$')) currencyIcon = ' $';
                else if (currentText.includes('₺')) currencyIcon = ' ₺';

                $priceInput.detach();
                $td.html(specialPrice.toFixed(2).replace('.', ',') + currencyIcon);
                $td.append($priceInput);

                // 2. İskontoyu temizle ve kilitle (Ana Bayi %45 iskonto dahil)
                $discountInput.val('0,00')
                    .prop('readonly', true)
                    .attr('placeholder', 'Özel Fiyat')
                    .attr('data-has-special-price', '1');  // Marker attribute - must match check in teklif-olustur.php

                // 3. Görsel indikatör - Yeşil renk ekle
                $priceInput.addClass('special-price-applied').css('color', 'green');
                $row.addClass('table-success');
                $row.addClass('row-has-special-price');
                $row.attr('data-campaign-batch-id', batchId); // Batch ID ata
                $td.addClass('special-price-applied');

                // DOM STATE: Bu ürüne özel fiyat uygulandığını tabloya kaydet
                var $table = $('#cartTable');
                var specialItems = $table.data('special-items') || [];

                if (!specialItems.includes(code)) {
                    specialItems.push(code);
                    $table.data('special-items', specialItems);
                    console.log('Added to DOM storage:', code, specialItems);
                }

                // 4. Hesaplamayı tetikle
                // KORUMA BAŞLANGICI
                window.isApplyingCampaign = true;
                $row.find('.quantity-input').trigger('input');
                // Trigger senkron çalışırsa hemen false yapabiliriz, ama emin olmak için timeout
                // Trigger senkron çalışırsa hemen false yapabiliriz, ama emin olmak için timeout
                setTimeout(function () { window.isApplyingCampaign = false; }, 100);
            }
        });

        // --- ANA BAYİ BUTON KONTROLÜ (SMART LOGIC) ---
        // Miktar ve tutar şartı kaldırıldı - Buton her zaman aktif

        // Ana butonu güncelle (görsel olarak)
        $('#applyCampaignsBtn')
            .removeClass('campaign-blink')
            .removeClass('btn-warning').addClass('btn-success')
            .html('<i class="bi bi-check-circle-fill"></i> Kısmi Uygulandı');

        // ÖNEMLİ: Özel fiyat uygulandığında otomatik olarak "Özel Teklif" checkbox'ını işaretle
        var specialOfferCheckbox = $('#is_special_offer');
        if (specialOfferCheckbox.length && !specialOfferCheckbox.prop('checked')) {
            console.log('✅ Özel fiyat uygulandı - Otomatik olarak "Özel Teklif" işaretleniyor');
            specialOfferCheckbox.prop('checked', true).trigger('change');
        }
    }

    // === ÖZEL FİYAT ÇALIŞMASI ENTEGRASYONU (İHRACAT) ===

    // Değişkenler
    var specialPricingWork = null;

    // 1. Pazar tipi veya müşteri değiştiğinde kontrol et
    function checkSpecialWorkEligibility() {
        var musteriKodu = $('#musteri').val(); // Select2 value (Cari ID veya Kodu)
        console.log('🔍 Özel Fiyat Kontrolü Tetiklendi. Müşteri:', musteriKodu);

        // Müşteri seçili değilse işlem yapma
        if (!musteriKodu) {
            console.log('❌ Müşteri seçili değil, buton gizleniyor.');
            $('#ozelFiyatBtn').hide();
            return;
        }

        // Butonu oluştur (eğer yoksa)
        injectSpecialPricingButton();

        // API kontrolü (Cari kodunu/ID'sini gönder)
        console.log('📡 API isteği gönderiliyor: api/check_special_pricing.php?cari_kodu=' + musteriKodu);
        $.ajax({
            url: 'api/check_special_pricing.php',
            type: 'GET',
            data: { cari_kodu: musteriKodu },
            dataType: 'json',
            success: function (response) {
                console.log('✅ API Yanıtı:', response);
                if (response.success && response.has_work) {
                    console.log('🎉 Özel fiyat çalışması VAR! Buton gösteriliyor.');
                    specialPricingWork = response.work;

                    // Butonu göster - GÜÇLÜ GÖSTERİM (Force Show)
                    var $btn = $('#ozelFiyatBtn');
                    if ($btn.length === 0) {
                        console.error('😱 Buton DOM\'da bulunamadı! Tekrar inject ediliyor...');
                        injectSpecialPricingButton();
                        $btn = $('#ozelFiyatBtn');
                    }

                    $btn.show().css('display', 'inline-block').removeClass('d-none');
                    $btn.html('<i class="bi bi-tag-fill me-1"></i> Özel Fiyat Çalışması Var');

                    // Yanıp sönme efekti ekle
                    $btn.addClass('campaign-blink');

                    // Manuel girişte otomatik uygulama için flag set et
                    window.hasActiveSpecialWork = true;
                    window.activeSpecialWorkId = response.work.id;

                    // Ürünleri arka planda çekip cache'e at
                    cacheSpecialPrices(response.work.id);
                } else {
                    console.log('ℹ️ Özel fiyat çalışması YOK.');
                    specialPricingWork = null;
                    $('#ozelFiyatBtn').hide();
                    window.hasActiveSpecialWork = false;
                    window.activeSpecialWorkId = 0;
                    window.cachedSpecialPrices = {};
                }
            },
            error: function (err) {
                console.error('🔥 API Hatası:', err);
                // Hata durumunda gizle
                $('#ozelFiyatBtn').hide();
            }
        });
    }

    // Özel fiyatları çekip hafızaya al
    function cacheSpecialPrices(workId) {
        window.cachedSpecialPrices = {};
        $.ajax({
            url: 'api/get_pricing_products.php',
            data: { work_id: workId },
            dataType: 'json',
            success: function (response) {
                if (response.success && response.products.length > 0) {
                    response.products.forEach(function (prod) {
                        // Normalize key: Uppercase and Trim
                        var key = (prod.stok_kodu || '').toUpperCase().trim();
                        window.cachedSpecialPrices[key] = {
                            price: prod.ozel_fiyat,
                            currency: prod.doviz,
                            cost: prod.maliyet
                        };
                    });
                    console.log('Özel fiyatlar önbelleğe alındı:', Object.keys(window.cachedSpecialPrices).length + ' ürün');
                }
            }
        });
    }

    // MANUEL GİRİŞ TAKİBİ - Ürün kodu girildiğinde özel fiyatı uygula
    $(document).on('change', '#newProductCode, .editable-product-code, .new-product-code', function () {
        var $input = $(this);
        var rawCode = $input.val();
        var code = (rawCode || '').toUpperCase().trim();

        if (window.hasActiveSpecialWork && window.cachedSpecialPrices && window.cachedSpecialPrices[code]) {
            var data = window.cachedSpecialPrices[code];
            var price = parseFloat(data.price);

            if (price > 0) {
                console.log('⚡ Özel fiyat tespit edildi:', code, price);

                // Sistemin kendi fiyat getirme işleminin bitmesini bekle
                setTimeout(function () {
                    var $row = $input.closest('tr');

                    // 1. Liste fiyatı alanını güncelle (Yeşil ve kalın)
                    var $listPrice = $row.find('#newProductListPrice, .new-product-list-price, td:eq(4)');

                    if ($listPrice.length) {
                        var currencyIcon = (data.currency === 'EUR' ? '€' : (data.currency === 'USD' ? '$' : 'TL'));
                        // İçeriği değiştir ama classları bozma
                        $listPrice.html('<b style="color:green; background:#d1e7dd; padding:2px 4px; border-radius:3px;">' +
                            price.toFixed(2).replace('.', ',') + ' ' + currencyIcon + '</b>');
                        $listPrice.addClass('special-price-applied');
                    }

                    // 1b. GİZLİ LİSTE FİYATI INPUT'UNU GÜNCELLE (Hesaplama buradan yapılıyor)
                    var $hiddenListPriceRef = $row.find('input[name^="fiyatsi"]');
                    if ($hiddenListPriceRef.length) {
                        $hiddenListPriceRef.val(price.toFixed(2).replace('.', ','));
                        console.log('✅ Hidden list price updated:', price);
                    }

                    // 2. Final fiyat inputunu güncelle
                    var $priceInput = $row.find('.final-price-input, input[name*="final_price"]');
                    if ($priceInput.length) {
                        $priceInput.val(price.toFixed(2).replace('.', ','));
                        // $priceInput.trigger('input'); // Tutarı güncelle - YETERLİ DEĞİL
                    }

                    // 3. İskontoyu kilitle
                    var $discountInput = $row.find('.discount-input, input[name*="iskonto"]');
                    if ($discountInput.length) {
                        $discountInput.val('0,00').prop('readonly', true).attr('placeholder', 'Özel Fiyat');
                    }

                    // 4. Satırı renklendir ve maliyet bilgisini güncelle
                    $row.addClass('table-success row-has-special-price');
                    $row.attr('data-maliyet', data.cost || 0);

                    // 5. HESAPLAMAYI TETİKLE (Miktar değişmiş gibi davran)
                    var $qtyInput = $row.find('.quantity-input');
                    if ($qtyInput.length) {
                        $qtyInput.trigger('input');
                        console.log('🔄 Row calculation triggered via quantity input');
                    }

                    if (typeof toastr !== 'undefined') toastr.success('Özel fiyat çalışmasındaki fiyat uygulandı!');

                }, 1500); // 1.5 sn bekle (API yanıtından ve diğer işlemlerden sonra)
            }
        }
    });

    // Butonu sayfaya enjekte et
    // Butonu sayfaya enjekte et
    // Butonu sayfaya enjekte et
    function injectSpecialPricingButton() {
        if ($('#ozelFiyatBtn').length === 0) {

            var btnHtml = '<button type="button" id="ozelFiyatBtn" class="btn btn-info btn-sm" style="display:none; font-weight:bold; color:white; height: 20px; line-height: 1; padding: 2px 8px; font-size: 11px;">' +
                '<i class="bi bi-tag-fill me-1"></i> Özel Fiyat Çalışması Var</button>';

            // Yeni container'a ekle
            var $container = $('#ozelFiyatContainer');
            if ($container.length) {
                $container.html(btnHtml);
            } else {
                // Fallback (eğer container yoksa eski yöntem)
                var $currencyRadio = $('input[name="doviz_goster"]').first();
                var $currencyContainer = $currencyRadio.closest('div[style*="display: flex"]');
                $currencyContainer.append(btnHtml);
            }


        }
    }

    // Butona tıklama
    $(document).on('click', '#ozelFiyatBtn', function () {
        if (!specialPricingWork) return;

        // Modal başlıklarını güncelle
        $('#ozelFiyatBaslik').text(specialPricingWork.title);
        $('#ozelFiyatTarih').text(specialPricingWork.date);
        $('#currentSpecialWorkId').val(specialPricingWork.id);

        // Ürünleri çek
        loadSpecialWorkProducts(specialPricingWork.id);
    });

    function loadSpecialWorkProducts(workId) {
        var $tbody = $('#ozelFiyatListesi');
        $tbody.html('<tr><td colspan="9" class="text-center"><div class="spinner-border text-primary"></div> Yükleniyor...</td></tr>');
        $('#ozelFiyatModal').modal('show');

        $.ajax({
            url: 'api/get_pricing_products.php',
            data: { work_id: workId },
            dataType: 'json',
            success: function (response) {
                $tbody.empty();
                if (response.success && response.products.length > 0) {
                    response.products.forEach(function (prod) {
                        var margin = 0;
                        if (prod.ozel_fiyat > 0) {
                            margin = ((prod.ozel_fiyat - prod.maliyet) / prod.ozel_fiyat) * 100;
                        }

                        var marginColor = margin < 0 ? 'text-danger' : (margin < 10 ? 'text-warning' : 'text-success');

                        var tr = `
                            <tr>
                                <td>${prod.stok_kodu}</td>
                                <td>${prod.urun_adi}</td>
                                <td>${prod.olcubirimi}</td>
                                <td class="text-end">${parseFloat(prod.maliyet).toFixed(2)}</td>
                                <td class="text-end">${parseFloat(prod.guncel_liste_fiyati).toFixed(2)}</td>
                                <td class="text-end fw-bold text-success">${parseFloat(prod.ozel_fiyat).toFixed(2)}</td>
                                <td class="text-end ${marginColor}">${margin.toFixed(2)}%</td>
                                <td class="text-center">${prod.doviz}</td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-primary add-special-product-btn"
                                            data-product='${JSON.stringify(prod)}'>
                                        <i class="bi bi-plus-circle"></i> Ekle
                                    </button>
                                </td>
                            </tr>
                        `;
                        $tbody.append(tr);
                    });
                } else {
                    $tbody.html('<tr><td colspan="9" class="text-center">Bu çalışmada ürün bulunamadı.</td></tr>');
                }
            }
        });
    }

    // Modaldan ürün ekleme
    $(document).on('click', '.add-special-product-btn', function () {
        var prod = $(this).data('product');
        var $btn = $(this);

        $btn.prop('disabled', true).text('Ekleniyor...');

        // Teklif listesine ekle
        var productData = {
            code: prod.stok_kodu,
            name: prod.urun_adi,
            unit: prod.olcubirimi,
            unit_price: prod.ozel_fiyat,
            list_price: prod.ozel_fiyat,
            currency_icon: prod.doviz === 'EUR' ? '€' : (prod.doviz === 'USD' ? '$' : 'TL'),
            has_pending_request: false,
            discount_rate: 0
        };

        if (typeof addProductToCartFromNewRow === 'function') {
            window.isSpecialPriceAddition = true;
            addProductToCartFromNewRow('new', 1, productData, null);

            setTimeout(function () {
                $btn.prop('disabled', false).html('<i class="bi bi-check"></i> Eklendi');
                setTimeout(function () { $btn.html('<i class="bi bi-plus-circle"></i> Ekle'); }, 2000);
            }, 1000);
        } else {
            alert('Ürün ekleme fonksiyonu bulunamadı!');
            $btn.prop('disabled', false).text('Hata');
        }
    });

    // Event Listeners for Validation
    $(document).on('change', 'input[name="pazar_tipi"]', checkSpecialWorkEligibility);
    $(document).on('select2:select change', '#musteri', checkSpecialWorkEligibility);

    // Sayfa yüklendiğinde ve periyodik olarak kontrol
    // Bazı durumlarda DOM geç yüklendiği için setInterval ile takip ediyoruz
    var checkInterval = setInterval(function () {
        // Butonun varlığını ve müşteri seçimini kontrol et
        var musteriKodu = $('#musteri').val();
        if (musteriKodu) {
            // Eğer buton henüz eklenmediyse veya görünür olması gerekiyorsa kontrol et
            // Ancak sürekli API çağırmamak için, sadece buton yoksa kontrol et
            if ($('#ozelFiyatBtn').length === 0 || $('#ozelFiyatBtn').is(':hidden')) {
                // Buton gizliyse belki API'den olumlu yanıt gelmiştir ama DOM'da gösterilmemiştir?
                // Hayır, zaten success içinde show() yapıyoruz.
                // Sürekli API çağrısını engellemek lazım.
                // Sadece buton DOM'da YOKSA çağır.
                if ($('#ozelFiyatBtn').length === 0) {
                    checkSpecialWorkEligibility();
                }
            }
        }
    }, 3000);

    // İlk yüklemede çalıştır
    setTimeout(checkSpecialWorkEligibility, 1000);

});
