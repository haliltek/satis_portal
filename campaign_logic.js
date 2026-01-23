$(document).ready(function () {
    // Global special price state management
    if (!window.specialPriceItems) {
        window.specialPriceItems = new Set();
    }
    // === KAMPANYA SİSTEMİ ENTEGRASYONU v2 ===

    var $btn = $('#applyCampaignsBtn');

    // --- 0. KORUMALAR (VISIBILITY & LEGACY CLEANUP) ---
    $btn.show().css('display', 'inline-flex');

    var protectionInterval = setInterval(function () {
        var $b = $('#applyCampaignsBtn');
        if ($b.css('display') === 'none') {
            $b.show().css('display', 'inline-flex');
        }
        if ($b.attr('onclick')) {
            $b.removeAttr('onclick');
        }
        $b.off('click'); // Direct handler temizliği
    }, 200);

    setTimeout(function () {
        clearInterval(protectionInterval);
        setInterval(function () {
            $('#applyCampaignsBtn').show().css('display', 'inline-flex');
            $('#applyCampaignsBtn').removeAttr('onclick');
        }, 2000);
    }, 10000);

    $(document).on('select2:select change', '#musteri', function () {
        setTimeout(function () { $('#applyCampaignsBtn').show().css('display', 'inline-flex'); }, 50);
        setTimeout(function () { $('#applyCampaignsBtn').off('click'); }, 100);
    });

    // --- 1. KOŞUL KONTROLÜ ---
    function checkCampaignConditions() {
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
                .html('<i class="bi bi-percent me-1"></i> Kampanya Uygula')
                .data('campaigns', null);
            return;
        }

        $.ajax({
            url: 'api/kampanya/check_conditions.php',
            type: 'POST',
            data: {
                cart: JSON.stringify(cart),
                customer_id: $('#musteri').val() || 0,
                customer_name: $('#musteri option:selected').text() || ''
            },
            dataType: 'json',
            success: function (response) {
                if (response.eligible) {
                    $button.addClass('campaign-blink')
                        .html('<i class="bi bi-gift-fill me-1"></i> FİLTRE ÖZEL FİYAT')
                        .removeClass('btn-secondary').addClass('btn-warning')
                        .data('campaigns', response.campaigns);
                } else {
                    $button.removeClass('campaign-blink')
                        .html('<i class="bi bi-percent me-1"></i> Kampanya Uygula')
                        .data('campaigns', null);
                }
                $button.show().css('display', 'inline-flex');
            }
        });
    }

    $(document).on('input change', '.quantity-input, .editable-product-code', function () {
        // Miktar değiştiğinde özel fiyat uygulanmışsa kaldır
        if ($(this).hasClass('quantity-input')) {
            var $row = $(this).closest('tr');
            var $discountInput = $row.find('.discount-input');
            var $priceInput = $row.find('input[name^="fiyatsi"]');

            // Özel fiyat uygulanmış mı kontrol et (readonly ve placeholder="Özel Fiyat")
            if ($discountInput.prop('readonly') && $discountInput.attr('placeholder') === 'Özel Fiyat') {

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
                // Ana Bayi Ek İskonto için farklı stil
                var isExtra = camp.is_extra_discount || false;
                var cardBorder = isExtra ? 'border-warning' : 'border-success';
                var cardHeader = isExtra ? 'bg-warning text-dark' : 'bg-success text-white';
                var btnClass = isExtra ? 'btn-warning' : 'btn-primary';
                var btnText = isExtra ? 'Ek İskonto Uygula' : 'Bu Gruba Uygula';

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
                html += '<p class="mb-1"><strong>Koşul:</strong> ' + camp.condition + '</p>';
                html += '<p class="mb-1"><strong>Kategori:</strong> ' + camp.category + '</p>';
                html += '<p class="mb-0"><strong>Uygulanacak Ürünler:</strong> ' + camp.products.length + ' adet</p>';
                html += '</div>';

                // Ek İskonto butonu için başlangıç kontrolü
                var isDisabled = '';
                var tooltip = '';

                if (isExtra) {
                    var hasSpecialPrice = $('.row-has-special-price').length > 0;
                    if (!hasSpecialPrice) {
                        isDisabled = 'disabled';
                        tooltip = 'title="Lütfen önce yukarıdaki Özel Fiyat kampanyasını uygulayınız!" data-bs-toggle="tooltip"';
                        btnText = 'Önce Özel Fiyat!';
                        btnClass = 'btn-secondary'; // Gri renk
                    }
                }

                // Tekil Uygulama Butonu
                html += '<button type="button" class="' + btnClass + ' apply-single-campaign-btn" ' +
                    'data-products=\'' + JSON.stringify(camp.products) + '\' ' +
                    'data-campaign-name="' + camp.name + '" ' +
                    'data-is-extra="' + isExtra + '" ' +
                    'data-discount-rate="' + (camp.discount_rate || 0) + '" ' +
                    'data-min-amount="' + (camp.campaign_meta ? camp.campaign_meta.min_amount : 0) + '" ' +
                    isDisabled + ' ' + tooltip + '>' +
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
        var isExtra = $btn.data('is-extra') === 'true' || $btn.data('is-extra') === true;
        var discountRate = $btn.data('discount-rate') || 0;

        if (!products || products.length === 0) return;

        // Butonu yükleniyor yap
        var originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Uygulanıyor...');

        if (isExtra) {
            // KONTROL: Önce özel fiyat uygulanmış mı?
            var hasSpecialPrice = $('.row-has-special-price').length > 0;

            if (!hasSpecialPrice) {
                // Özel fiyat yoksa uygulama ve uyar
                $btn.prop('disabled', false).html(originalHtml); // Butonu eski haline getir

                if (typeof toastr !== 'undefined') {
                    toastr.error('Lütfen önce "Özel Fiyat" kampanyasını uygulayınız!', 'Sıralama Hatası');
                } else {
                    alert('Lütfen önce "Özel Fiyat" kampanyasını uygulayınız!');
                }
                return; // İşlemi durdur
            }

            // Ana Bayi Ek İskonto: İskonto alanına yaz
            applyExtraDiscountToTable(products, discountRate);
            $btn.removeClass('btn-warning').addClass('btn-success')
                .html('<i class="bi bi-check-circle-fill"></i> Uygulandı');
        } else {
            // Normal özel fiyat kampanyası
            $.ajax({
                url: 'api/kampanya/get_special_prices.php',
                type: 'POST',
                data: { codes: JSON.stringify(products) },
                dataType: 'json',
                success: function (prices) {
                    applyPricesToTable(prices);
                    $btn.removeClass('btn-primary').addClass('btn-success')
                        .html('<i class="bi bi-check-circle-fill"></i> Uygulandı');

                    // --- DİNAMİK GÜNCELLEME ---
                    // Eğer bu bir Özel Fiyat uygulamasıysa, pasif durumdaki Ek İskonto butonlarını aç
                    var $modal = $btn.closest('.modal-content');
                    var $disabledExtraBtns = $modal.find('.apply-single-campaign-btn[disabled][data-is-extra="true"]');

                    if ($disabledExtraBtns.length > 0) {
                        $disabledExtraBtns.each(function () {
                            var $extraBtn = $(this);
                            $extraBtn.prop('disabled', false)
                                .removeClass('btn-secondary').addClass('btn-warning')
                                .html('<i class="bi bi-check2-circle"></i> Ek İskonto Uygula')
                                .removeAttr('title')
                                .removeAttr('data-bs-toggle')
                                .tooltip('dispose'); // Varsa tooltip'i yok et

                            // Animasyon efekti
                            $extraBtn.fadeOut(100).fadeIn(300);
                        });
                    }
                },
                error: function () {
                    $btn.prop('disabled', false).html(originalHtml);
                    alert('Fiyatlar çekilirken hata oluştu.');
                }
            });
        }
    });

    // --- 4b. ANA BAYİ EK İSKONTO UYGULAMA ---
    function applyExtraDiscountToTable(products, discountRate) {
        var appliedCount = 0;

        $('.editable-product-code').each(function () {
            var $input = $(this);
            var code = $input.val().trim();
            var $row = $input.closest('tr');

            if (products.includes(code)) {
                var $discountInput = $row.find('.discount-input');

                // Sadece özel fiyat uygulanmış satırlara iskonto ekle
                // (iskonto alanı readonly ise özel fiyat uygulanmış demektir)
                if ($discountInput.prop('readonly')) {
                    var formattedRate = parseFloat(discountRate).toFixed(2).replace('.', ',');

                    // İskonto alanını unlock et ve %5,00 yaz
                    $discountInput.prop('readonly', false)
                        .attr('placeholder', '')
                        .val(formattedRate);

                    // Log to console for debugging
                    console.log('Ek iskonto uygulandı:', formattedRate);

                    // Yeşil renk KORUNUR (özel fiyat hala geçerli)
                    // Hesaplamayı tetikle
                    $row.find('.quantity-input').trigger('input');

                    appliedCount++;
                }
            }
        });

        if (appliedCount > 0) {
            if (typeof toastr !== 'undefined') {
                toastr.success('Ana Bayi ek iskontosu (%' + discountRate + ') ' + appliedCount + ' ürüne uygulandı!');
            }
        } else {
            if (typeof toastr !== 'undefined') {
                toastr.warning('Ana Bayi ek iskontosu uygulanamadı! Önce özel fiyat kampanyasını uygulayın.');
            }
        }
    }

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
        var totalSpecialAmount = 0;

        // Tablodaki özel fiyatlı ürünleri topla
        $('.editable-product-code').each(function () {
            var $row = $(this).closest('tr');
            if ($row.hasClass('row-has-special-price')) {
                // Fiyatı al (TR formatını parse et)
                var priceStr = $row.find('input[name^="fiyatsi"]').val();
                if (priceStr) {
                    var price = parseFloat(priceStr.replace('.', '').replace(',', '.')) || 0; // 1.000,50 -> 1000.50
                    var qty = parseFloat($row.find('.quantity-input').val()) || 0;
                    totalSpecialAmount += (price * qty);
                }
            }
        });

        console.log('Toplam Özel Fiyat Tutarı:', totalSpecialAmount);

        var $dealerBtn = $('#applyDealerDiscountBtn');
        var $dealerStatus = $('#dealerDiscountStatus');
        var $dealerInfo = $('#dealerDiscountInfo');

        if ($dealerBtn.length > 0) {
            // Hedef tutar (Şimdilik sabit veya API'den sonra çekilebilir)
            var targetAmount = 50000;

            if (totalSpecialAmount >= targetAmount) {
                // KOŞUL SAĞLANDI
                $dealerBtn.prop('disabled', false).removeClass('btn-secondary').addClass('btn-primary');
                $dealerStatus.removeClass('bg-secondary text-white').addClass('bg-success text-white').text('Koşul Sağlandı');
                $dealerInfo.html('<span class="text-success fw-bold">Toplam: ' +
                    totalSpecialAmount.toLocaleString('tr-TR', { minimumFractionDigits: 2 }) +
                    ' €</span> (Min: ' + targetAmount.toLocaleString('tr-TR') + ' €)');
            } else {
                // KOŞUL SAĞLANMADI
                $dealerBtn.prop('disabled', true).addClass('btn-secondary').removeClass('btn-primary');
                $dealerStatus.removeClass('bg-success text-white').addClass('bg-secondary text-white').text('Min. Tutar Bekleniyor');

                var remaining = targetAmount - totalSpecialAmount;
                $dealerInfo.html('<span class="text-danger">Mevcut: ' +
                    totalSpecialAmount.toLocaleString('tr-TR', { minimumFractionDigits: 2 }) +
                    ' €</span> <br> Kalan: ' +
                    remaining.toLocaleString('tr-TR', { minimumFractionDigits: 2 }) + ' €');
            }
        }

        // Ana butonu güncelle (görsel olarak)
        $('#applyCampaignsBtn')
            .removeClass('campaign-blink')
            .removeClass('btn-warning').addClass('btn-success')
            .html('<i class="bi bi-check-circle-fill"></i> Kısmi Uygulandı');
    }
});
