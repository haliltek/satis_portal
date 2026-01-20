$(document).ready(function() {
    // Görsel yükleme hatalarını durdur - global error handler
    $(document).on('error', 'img.pr-image', function(e) {
        var $img = $(this);
        var defaultImage = window.BASE_URL ? window.BASE_URL + '/assets/front/assets/images/unnamed.png' : '/assets/front/assets/images/unnamed.png';
        
        // Eğer zaten varsayılan görsel değilse, varsayılan görsele geç
        if ($img.attr('src') !== defaultImage && !$img.hasClass('error-handled')) {
            $img.addClass('error-handled');
            $img.attr('src', defaultImage);
            // Tekrar yüklenmeyi engelle
            $img.off('error');
        }
        // Event'i durdur
        e.preventDefault();
        e.stopPropagation();
        return false;
    });
    
    // Sayfa yüklendiğinde tab1 aktifse DataTables'ı başlat
    // Ancak sadece bir kez başlatılmasını sağlamak için flag kullan
    var tab1Initialized = false;
    setTimeout(function() {
        if (($('.tab[v-tab="tab1"]').hasClass('active') || $('#tab1').is(':visible')) && !tab1Initialized) {
            var baseUrl = window.BASE_URL || '';
            var table= baseUrl + '/yeniurun';
            
            // Önceki DataTable instance'ını temizle
            if ($.fn.DataTable.isDataTable('.yeniurun')) {
                $('.yeniurun').DataTable().destroy();
            }
            
            $('.yeniurun').DataTable( {
                paging: true,
                "lengthChange": true,
                "pageLength": 10,
                "pagingType": "full_numbers",
                "processing": true,
                "serverSide": false,
                "ajax": {
                    "url": table,
                    "type": "GET",
                    "dataSrc": function(json) {
                        console.log('AJAX Response:', json);
                        if (json.error) {
                            console.error('Server Error:', json.error);
                            return [];
                        }
                        if (!json || !json.data) {
                            console.error('Invalid response format:', json);
                            return [];
                        }
                        return json.data;
                    },
                    "error": function(xhr, error, thrown) {
                        console.error('AJAX Error:', error);
                        console.error('Status:', xhr.status);
                        console.error('Response:', xhr.responseText);
                        // Hata durumunda boş array döndür
                        return [];
                    }
                },
                "columns": [
                    { "data": 0 },
                    { "data": 1 },
                    { 
                        "data": 2,
                        "render": function(data, type, row) {
                            // Fiyat kolonu - Renklendirme
                            if (type === 'display') {
                                var content = data || '';
                                
                                // HTML içeriğinden metni çıkar
                                if (content && typeof content === 'string') {
                                    // Eğer HTML içeriyorsa (örn: <div class="align-right">363,00 €</div>)
                                    if (content.indexOf('<div') !== -1) {
                                        // HTML'den metni çıkar
                                        var tempDiv = document.createElement('div');
                                        tempDiv.innerHTML = content;
                                        var textContent = tempDiv.textContent || tempDiv.innerText || '';
                                        
                                        // "Fiyat Yok" kontrolü
                                        if (textContent.indexOf('Fiyat Yok') !== -1 || textContent.trim() === 'Fiyat Yok') {
                                            return '<div class="align-right"><span style="color: #dc3545; font-weight: bold;">Fiyat Yok</span></div>';
                                        } else {
                                            // Fiyat varsa mavi göster
                                            return '<div class="align-right"><span style="color: #0056b3; font-weight: bold;">' + textContent.trim() + '</span></div>';
                                        }
                                    } else {
                                        // Düz metin
                                        if (content.indexOf('Fiyat Yok') !== -1 || content.trim() === 'Fiyat Yok' || content === '' || content === '0' || content === '0,00') {
                                            return '<div class="align-right"><span style="color: #dc3545; font-weight: bold;">Fiyat Yok</span></div>';
                                        } else {
                                            // Fiyat varsa mavi göster
                                            return '<div class="align-right"><span style="color: #0056b3; font-weight: bold;">' + content + '</span></div>';
                                        }
                                    }
                                }
                                
                                // Geçersiz veya boş ise
                                return '<div class="align-right"><span style="color: #dc3545; font-weight: bold;">Fiyat Yok</span></div>';
                            }
                            // Sorting ve filtering için ham değeri döndür
                            if (type === 'type' || type === 'sort') {
                                // HTML'den sayısal değeri çıkar
                                if (data && typeof data === 'string') {
                                    var match = data.match(/[\d,]+/);
                                    if (match) {
                                        return parseFloat(match[0].replace(',', '.'));
                                    }
                                }
                                return parseFloat(data) || 0;
                            }
                            return data || '';
                        }
                },
                { 
                    "data": 3,
                    "render": function(data, type, row) {
                        // Kampanya kolonu - HTML içeriğini direkt döndür
                        if (type === 'display') {
                            return data || '<span style="color: #95a5a6; font-size: 12px;">-</span>';
                        }
                        return data || '';
                    }
                },
                { "data": 4 },
                { "data": 5 }
            ],
            "error": function(xhr, error, thrown) {
                console.error('DataTables Error:', error);
                console.error('Status:', xhr.status);
                console.error('Response:', xhr.responseText);
            },
            "initComplete": function(settings, json) {
                console.log('DataTables initialized successfully');
                console.log('Data count:', json ? json.data.length : 0);
                tab1Initialized = true;
                // Kampanya animasyonu için CSS ekle
                if (!$('#kampanya-animation-style').length) {
                    $('head').append('<style id="kampanya-animation-style">@keyframes kampanyaPulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.02); } }</style>');
                }
            }
        });
        }
    }, 500);

   $(".tab").click(function (){
   var vtab = $(this).attr('v-tab');
   if(vtab=='tab1') {
        var baseUrl = window.BASE_URL || '';
        var table= baseUrl + '/yeniurun';
        
        // Önceki DataTable instance'ını temizle
        if ($.fn.DataTable.isDataTable('.yeniurun')) {
            $('.yeniurun').DataTable().destroy();
            tab1Initialized = false; // Flag'i sıfırla
        }
        
        // Eğer zaten başlatılmışsa tekrar başlatma
        if (tab1Initialized && $.fn.DataTable.isDataTable('.yeniurun')) {
            return; // Zaten başlatılmış, tekrar başlatma
        }

        $('.yeniurun').DataTable( {
            paging: true,
            "lengthChange": true,
            "pageLength":10,
            "pagingType": "full_numbers",
            "processing": true,
            "serverSide": false,
            "ajax": {
                "url": table,
                "type": "GET",
                "dataSrc": function(json) {
                    console.log('AJAX Response:', json);
                    if (json.error) {
                        console.error('Server Error:', json.error);
                        return [];
                    }
                    if (!json || !json.data) {
                        console.error('Invalid response format:', json);
                        return [];
                    }
                    return json.data;
                },
                "error": function(xhr, error, thrown) {
                    console.error('AJAX Error:', error);
                    console.error('Status:', xhr.status);
                    console.error('Response:', xhr.responseText);
                    return [];
                }
            },
            "columns": [
                { "data": 0 },
                { "data": 1 },
                { 
                    "data": 2,
                    "render": function(data, type, row) {
                        // Fiyat kolonu - Renklendirme
                        if (type === 'display') {
                            var content = data || '';
                            
                            // HTML içeriğinden metni çıkar
                            if (content && typeof content === 'string') {
                                // Eğer HTML içeriyorsa (örn: <div class="align-right">363,00 €</div>)
                                if (content.indexOf('<div') !== -1) {
                                    // HTML'den metni çıkar
                                    var tempDiv = document.createElement('div');
                                    tempDiv.innerHTML = content;
                                    var textContent = tempDiv.textContent || tempDiv.innerText || '';
                                    
                                    // "Fiyat Yok" kontrolü
                                    if (textContent.indexOf('Fiyat Yok') !== -1 || textContent.trim() === 'Fiyat Yok') {
                                        return '<div class="align-right"><span style="color: #dc3545; font-weight: bold;">Fiyat Yok</span></div>';
                                    } else {
                                        // Fiyat varsa mavi göster
                                        return '<div class="align-right"><span style="color: #0056b3; font-weight: bold;">' + textContent.trim() + '</span></div>';
                                    }
                                } else {
                                    // Düz metin
                                    if (content.indexOf('Fiyat Yok') !== -1 || content.trim() === 'Fiyat Yok' || content === '' || content === '0' || content === '0,00') {
                                        return '<div class="align-right"><span style="color: #dc3545; font-weight: bold;">Fiyat Yok</span></div>';
                                    } else {
                                        // Fiyat varsa mavi göster
                                        return '<div class="align-right"><span style="color: #0056b3; font-weight: bold;">' + content + '</span></div>';
                                    }
                                }
                            }
                            
                            // Geçersiz veya boş ise
                            return '<div class="align-right"><span style="color: #dc3545; font-weight: bold;">Fiyat Yok</span></div>';
                        }
                        // Sorting ve filtering için ham değeri döndür
                        if (type === 'type' || type === 'sort') {
                            // HTML'den sayısal değeri çıkar
                            if (data && typeof data === 'string') {
                                var match = data.match(/[\d,]+/);
                                if (match) {
                                    return parseFloat(match[0].replace(',', '.'));
                                }
                            }
                            return parseFloat(data) || 0;
                        }
                        return data || '';
                    }
                },
                { 
                    "data": 3,
                    "render": function(data, type, row) {
                        // Kampanya kolonu - HTML içeriğini direkt döndür
                        if (type === 'display') {
                            return data || '<span style="color: #95a5a6; font-size: 12px;">-</span>';
                        }
                        return data || '';
                    }
                },
                { "data": 4 },
                { "data": 5 }
            ],
            "error": function(xhr, error, thrown) {
                console.error('DataTables Error:', error);
                console.error('Status:', xhr.status);
                console.error('Response:', xhr.responseText);
            },
            "initComplete": function(settings, json) {
                console.log('DataTables initialized successfully');
                console.log('Data count:', json ? json.data.length : 0);
                tab1Initialized = true; // Flag'i set et
                // Kampanya animasyonu için CSS ekle
                if (!$('#kampanya-animation-style').length) {
                    $('head').append('<style id="kampanya-animation-style">@keyframes kampanyaPulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.02); } }</style>');
                }
            }
        });
   }
   else if(vtab=='tab3') {
       var baseUrl = window.BASE_URL || '';
       var table= baseUrl + '/kampanya';
       
       // Önceki DataTable instance'ını temizle
       if ($.fn.DataTable.isDataTable('.kampanya')) {
           $('.kampanya').DataTable().destroy();
       }

       $('.kampanya').DataTable( {
           paging: true,
           "lengthChange": true,
           "pageLength":10,
           "destroy": true,
           "pagingType": "full_numbers",
           "ajax": table,
           "columns": [
               { "data": 0 },
               { "data": 1 },
               { 
                   "data": 2,
                   "render": function(data, type, row) {
                       // Fiyat kolonu - HTML içeriğini direkt döndür (eski fiyat ve yeni fiyat için)
                       if (type === 'display') {
                           // Eğer data HTML içeriyorsa direkt döndür (eski fiyat ve yeni fiyat zaten HTML formatında)
                           if (data && typeof data === 'string' && data.indexOf('<div') !== -1) {
                               return data;
                           }
                           // Eğer "Fiyat Yok" içeriyorsa
                           if (data && typeof data === 'string' && data.indexOf('Fiyat Yok') !== -1) {
                               return '<div style="text-align:right;"><span style="color: #dc3545; font-weight: bold;">Fiyat Yok</span></div>';
                           }
                           // Geçersiz veya boş ise
                           return data || '<div style="text-align:right;"><span style="color: #dc3545; font-weight: bold;">Fiyat Yok</span></div>';
                       }
                       // Sorting ve filtering için ham değeri döndür
                       if (type === 'type' || type === 'sort') {
                           // HTML'den sayısal değeri çıkar (ilk fiyat değerini al)
                           if (data && typeof data === 'string') {
                               // İlk sayısal değeri bul (yeni fiyat)
                               var match = data.match(/[\d,]+/);
                               if (match) {
                                   return parseFloat(match[0].replace(',', '.'));
                               }
                           }
                           return parseFloat(data) || 0;
                       }
                       return data || '';
                   }
               },
               { 
                   "data": 3,
                   "render": function(data, type, row) {
                       // Kampanya kolonu - HTML içeriğini direkt döndür
                       if (type === 'display') {
                           return data || '<span style="color: #95a5a6; font-size: 12px;">-</span>';
                       }
                       return data || '';
                   }
               },
               { "data": 4 },
               { "data": 5 }
           ],
           "initComplete": function(settings, json) {
               // Kampanya animasyonu için CSS ekle
               if (!$('#kampanya-animation-style').length) {
                   $('head').append('<style id="kampanya-animation-style">@keyframes kampanyaPulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.02); } }</style>');
               }
           }
       });
   }


   else if(vtab=='tab4') {
       var baseUrl = window.BASE_URL || '';
       var table= baseUrl + '/kampanyapaket';
       
       // Önceki DataTable instance'ını temizle
       if ($.fn.DataTable.isDataTable('.esdeger')) {
           $('.esdeger').DataTable().destroy();
       }

       $('.esdeger').DataTable( {
           paging: true,
           "lengthChange": true,
           "pageLength":10,
           "destroy": true,
           "pagingType": "full_numbers",
           "processing": true,
           "serverSide": false,
           "ajax": {
               "url": table,
               "type": "GET",
               "dataSrc": function(json) {
                   console.log('=== Kampanya Paket AJAX Response ===');
                   console.log('Full Response:', json);
                   console.log('Response Type:', typeof json);
                   console.log('Has data property:', json && json.hasOwnProperty('data'));
                   console.log('Data length:', json && json.data ? json.data.length : 0);
                   
                   if (!json) {
                       console.error('Response is null or undefined');
                       return [];
                   }
                   
                   if (json.error) {
                       console.error('Server Error:', json.error);
                       alert('Kampanya paket yüklenirken hata oluştu: ' + json.error);
                       return [];
                   }
                   
                   if (!json.hasOwnProperty('data')) {
                       console.error('Invalid response format - no data property:', json);
                       console.error('Available properties:', Object.keys(json));
                       return [];
                   }
                   
                   if (!Array.isArray(json.data)) {
                       console.error('Data is not an array:', json.data);
                       console.error('Data type:', typeof json.data);
                       return [];
                   }
                   
                   console.log('Returning data array with', json.data.length, 'items');
                   if (json.data.length > 0) {
                       console.log('First item:', json.data[0]);
                   }
                   return json.data;
               },
               "error": function(xhr, error, thrown) {
                   console.error('=== Kampanya Paket AJAX Error ===');
                   console.error('Error:', error);
                   console.error('Status:', xhr.status);
                   console.error('Status Text:', xhr.statusText);
                   console.error('Response Text:', xhr.responseText);
                   console.error('Thrown:', thrown);
                   console.error('URL:', table);
                   
                   // Hata mesajını göster
                   var errorMsg = 'Kampanya paket yüklenirken bir hata oluştu. ';
                   if (xhr.status === 0) {
                       errorMsg += 'Sunucuya bağlanılamadı.';
                   } else if (xhr.status === 404) {
                       errorMsg += 'Sayfa bulunamadı (404).';
                   } else if (xhr.status === 500) {
                       errorMsg += 'Sunucu hatası (500).';
                   } else {
                       errorMsg += 'Hata kodu: ' + xhr.status;
                   }
                   
                   alert(errorMsg + '\n\nLütfen sayfayı yenileyin veya yöneticiye bildirin.');
               }
           },
           "columns": [
               { "data": 0 },
               { "data": 1 },
               { "data": 2 },
               { "data": 3 },
               { "data": 4 },
               { "data": 5 }
           ],
           "error": function(xhr, error, thrown) {
               console.error('DataTables Error:', error);
               console.error('Status:', xhr.status);
               console.error('Response:', xhr.responseText);
           }
       });
   }
   else if(vtab=='tab5') {
       var baseUrl = window.BASE_URL || '';
       var table= baseUrl + '/satinaldiklarim';
       
       // Önceki DataTable instance'ını temizle
       if ($.fn.DataTable.isDataTable('.satinaldiklarim')) {
           $('.satinaldiklarim').DataTable().destroy();
       }

       $('.satinaldiklarim').DataTable( {
           paging: true,
           "lengthChange": true,
           "pageLength":10,
           "destroy": true,
           "pagingType": "full_numbers",
           "ajax": table,

       });
   }
    });

   $('.esdeger-button').click(function(){
       var id = $(this).attr('id');
       var table = 'esdeger';
       $('.tab-c').hide();
       $('#tab4').fadeIn();
       $('.tab').attr('class','tab');
       $('.tab[v-tab="tab4"]').attr('class','tab active');
       $('.esdeger').DataTable( {
           paging: true,
           "lengthChange": true,
           "pageLength":10,
           "destroy": true,
           "pagingType": "full_numbers",
           "ajax": table+'/'+id

       });
       $('.product-detailmodal').fadeOut();

   });
    $('.filtrele').click(function (){
        var marka = $("#marka").val();
        var model = $("#model").val();
        var kat = $("#kat").val();
        var oem = $('#oem').val();
        var acar_no = $('#acar_no').val();
        var garama = $('#garama').val();
        var table = "filtrele";
        if(oem != '') {
            $('.tab-c').hide();
            $('#tab2').fadeIn();
            $('.tab').attr('class','tab');
            $('.tab[v-tab="tab2"]').attr('class','tab active');
            $('#filtrele').DataTable( {
                "destroy": true,
                "ajax" : table+'?oem='+oem
            });
        }
        else if(acar_no != '') {
            $('.tab-c').hide();
            $('#tab2').fadeIn();
            $('.tab').attr('class','tab');
            $('.tab[v-tab="tab2"]').attr('class','tab active');
            $('#filtrele').DataTable( {
                "destroy": true,
                "ajax" : table+'?acar_no='+acar_no
            });

        }
        else if(garama != '') {
            $('.tab-c').hide();
            $('#tab2').fadeIn();
            $('.tab').attr('class','tab');
            $('.tab[v-tab="tab2"]').attr('class','tab active');
            $('#filtrele').DataTable( {
                "destroy": true,
                "ajax" : table+'?genel='+garama
            });

        }

        //$('#filtrele').DataTable().row.add(data);
        else {
            $('.tab-c').hide();
            $('#tab2').fadeIn();
            $('.tab').attr('class','tab');
            $('.tab[v-tab="tab2"]').attr('class','tab active');
                $('#filtrele').DataTable( {
                    pageLength: 25,
                    paging: true,
                    "lengthChange": true,
                    "pagingType": "full_numbers",
                    "destroy": true,
                    "ajax" : table+'?marka='+marka+'&model='+model+'&kat='+kat
                });

        }




    });


});
function productdetail(id) {
    $('.image-modal').remove();
    $('.product-detailmodal').fadeIn();
    
    // Base URL'i kontrol et ve düzelt
    var baseUrl = window.BASE_URL || '';
    console.log('window.BASE_URL:', window.BASE_URL);
    
    // Eğer baseUrl boşsa veya sadece / ise, mevcut sayfanın base URL'ini kullan
    if (!baseUrl || baseUrl === '' || baseUrl === '/') {
        // Mevcut sayfanın origin ve path'ini al
        var origin = window.location.origin;
        var pathname = window.location.pathname;
        
        // Path'ten son segment'i kaldır (örn: /home -> /)
        var pathParts = pathname.split('/').filter(function(p) { return p; });
        if (pathParts.length > 0 && pathParts[pathParts.length - 1] === 'home') {
            pathParts.pop();
        }
        
        // Base path'i oluştur
        var basePath = pathParts.length > 0 ? '/' + pathParts.join('/') : '';
        baseUrl = origin + basePath;
        
        console.log('Calculated baseUrl from location:', baseUrl);
    }
    
    // Eğer baseUrl sonunda / varsa kaldır
    if (baseUrl.endsWith('/')) {
        baseUrl = baseUrl.slice(0, -1);
    }
    
    var route = baseUrl + '/productdetail/'+id;
    
    console.log('Product detail requested for ID:', id);
    console.log('Base URL:', baseUrl);
    console.log('Route:', route);
    
    $.getJSON(route,function(data){
        console.log('Product detail response:', data);
        
        // data bir obje ise direkt kullan, array ise ilk elemanı al
        var val = Array.isArray(data) ? data[0] : data;
        
        // Eğer error mesajı varsa göster
        if (val && val.error) {
            console.error('API Error:', val.error);
            alert('Ürün detayı alınamadı: ' + val.error);
            return;
        }
        
        // urun_adi veya stokadi kontrolü yap
        if (val && (val.urun_adi || val.stokadi)) {
            var urunAdi = val.urun_adi || val.stokadi || 'Ürün Adı Yok';
            var urunKodu = val.urun_kodu || val.stokkodu || '';
            var stok = val.stok || val.miktar || '0';
            
            // Fiyat işleme - Basit ve kesin çözüm
            var fiyat = val.fiyat;
            var fiyatGoster = 'Fiyat Yok';
            
            // Gelen veri kontrolü
            if (fiyat && String(fiyat).trim() !== '' && String(fiyat) !== '0' && String(fiyat) !== '0,00') {
                var fStr = String(fiyat).trim();
                if (fStr.indexOf('€') === -1 && fStr.indexOf('₺') === -1) {
                    fiyatGoster = fStr + ' €';
                } else {
                    fiyatGoster = fStr.replace('₺', '€');
                }
            }
            
            console.log('Product detail - Final fiyatGoster:', fiyatGoster);
            
            var tanimadi = val.tanimadi || val.marka || '';
            var tanimDeger = val.tanim_deger || val.kat1 || '';
            var aciklama = val.aciklama || 'Açıklama bulunamadı.';
            
            console.log('Product detail - fiyat:', fiyat, 'type:', typeof fiyat, 'val.fiyat:', val.fiyat, 'fiyatGoster:', fiyatGoster);
            
            $('.product-detailmodal h5').text(urunAdi);
            $('.product-detailmodal #d-kod').text(urunKodu);
            $('.product-detailmodal #d-stok').text(stok);
            // Fiyatı € ile göster
            if (fiyatGoster !== 'Fiyat Yok') {
                $('.product-detailmodal #d-fiyat').html('<strong style="color:#0056b3;">' + fiyatGoster + '</strong>');
            } else {
                $('.product-detailmodal #d-fiyat').text('Fiyat Yok');
            }
            $('.product-detailmodal #d-tanimad').text(tanimadi);
            $('.product-detailmodal #d-tanimdeger').text(tanimDeger);
            $('.product-detailmodal #d-aciklama').html(aciklama);
            
            // Resim varsa göster, yoksa hiç gösterme
            var imageUrl = '';
            if (val.resim && val.resim !== '') {
                // Eğer resim tam URL ise direkt kullan, değilse baseUrl ekle
                if (val.resim.startsWith('http://') || val.resim.startsWith('https://')) {
                    imageUrl = val.resim;
                } else {
                    imageUrl = baseUrl + '/' + val.resim;
                }
            } else {
                // Resim yoksa stok kodundan dinamik URL oluştur
                if (urunKodu && urunKodu !== '') {
                    var ilkIkiKarakter = urunKodu.substring(0, 2);
                    imageUrl = 'https://gemas.com.tr/public/uploads/images/malzeme/' + ilkIkiKarakter + '/' + urunKodu + '.jpg';
                }
                // Görsel yoksa boş bırak - hiç gösterme
            }
            
            if (imageUrl && imageUrl !== '') {
                $('.product-detailmodal #d-resim').attr('src', imageUrl).on('error', function(e) {
                    console.log('Image load error, hiding image');
                    var $img = $(this);
                    // Tekrar yüklenmeyi engelle ve görseli gizle
                    $img.off('error');
                    $img.hide();
                    // Event'i durdur
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }).show();
            } else {
                // Görsel yoksa gizle
                $('.product-detailmodal #d-resim').hide();
            }
            
            $('.product-detailmodal .btn-success').attr('onclick','sepet2('+id+')');
            $('.product-detailmodal .btn-success').attr('uid',id);
            $('.product-detailmodal .d-input').attr('id','b'+id);
            $('.product-detailmodal .esdeger-button').attr('id',id);
            
            console.log('Product detail loaded successfully');
        } else {
            console.error('Ürün detayı alınamadı - Geçersiz veri:', data);
            console.error('val:', val);
            alert('Ürün detayı yüklenemedi. Lütfen konsolu kontrol edin.');
        }
    }).fail(function(jqXHR, textStatus, errorThrown) {
        console.error('AJAX Error:', textStatus, errorThrown);
        console.error('Response:', jqXHR.responseText);
        alert('Ürün detayı yüklenirken bir hata oluştu: ' + textStatus);
    });
    
    // baseUrl'i tekrar kontrol et ve düzelt
    var baseUrlForOem = window.BASE_URL || '';
    if (baseUrlForOem.endsWith('/')) {
        baseUrlForOem = baseUrlForOem.slice(0, -1);
    }
    
    $.getJSON(baseUrlForOem + '/urunoem/'+id,function(data){
        console.log('urunoem response:', data);
        $('.product-detailmodal #d-oem').text('');
        if (Array.isArray(data) && data.length > 0) {
            $.each(data,function(index,val){
                if (val.oem) {
                    $('.product-detailmodal #d-oem').append(val.oem + ' ');
                }
            });
        }
    }).fail(function(jqXHR, textStatus, errorThrown) {
        console.error('urunoem AJAX Error:', textStatus, errorThrown);
        console.error('Response:', jqXHR.responseText);
        console.error('URL:', baseUrlForOem + '/urunoem/'+id);
    });
    $.getJSON(baseUrlForOem + '/markamodellist/'+id,function(data){
        $('.product-detailmodal #d-marka').text('');
        $.each(data,function(index,val){
            $('.product-detailmodal #d-marka').append(val.marka_adi + ' - ' + val.model_adi);
        }) ;
    });
    // Close butonu için event listener (sadece bir kez ekle)
    $(document).off('click', '.close-detailmodal').on('click', '.close-detailmodal', function(){
        $('.product-detailmodal').fadeOut();
    });
    
    // Modal dışına tıklama ile kapanma - event propagation'i kontrol et
    // Bu event listener'ı sadece bir kez ekle (fonksiyon dışında)
    if (!window.modalClickHandlerAdded) {
        $(document).on('click', function(e) {
            // Eğer modal açıksa ve tıklama modal dışındaysa kapat
            if ($('.product-detailmodal').is(':visible')) {
                // Modal içindeki elementlere tıklanmışsa kapatma
                if ($(e.target).closest('.product-detailmodal').length > 0) {
                    return;
                }
                
                // Close butonuna tıklanmışsa zaten kapatılacak
                if ($(e.target).hasClass('close-detailmodal') || $(e.target).closest('.close-detailmodal').length > 0) {
                    return;
                }
                
                // Link'lere, pr-image'lere veya form-button2'lere tıklanmışsa kapatma
                if ($(e.target).hasClass('link') || $(e.target).closest('.link').length > 0 ||
                    $(e.target).hasClass('pr-image') || $(e.target).closest('.pr-image').length > 0 ||
                    $(e.target).hasClass('form-button2') || $(e.target).closest('.form-button2').length > 0) {
                    return;
                }
                
                // Diğer durumlarda modal'ı kapat
                $('.product-detailmodal').fadeOut();
            }
        });
        
        // Modal içindeki tıklamaların dışarıya yayılmasını engelle
        $(document).on('click', '.product-detailmodal', function(e) {
            e.stopPropagation();
        });
        
        window.modalClickHandlerAdded = true;
    }
}
