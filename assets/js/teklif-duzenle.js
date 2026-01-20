// assets/js/teklif-duzenle.js
(function(){
    // PHP’den gelen değerler
    const TEKLIF_ID    = window.TEKLIF_ID;
    const MAX_DISCOUNT = window.ISKONTO_MAX;
  
    // Ortak fonksiyonlar
    function showLogoStatus(type, message) {
      const $alert = $('#logoTransferAlert');
      $alert
        .removeClass('d-none alert-info alert-success alert-danger')
        .addClass('alert-' + type)
        .text(message);
    }
  
    function showError(message) {
      $('<div class="alert alert-danger m-3">')
        .text(message)
        .prependTo('#dataModal .modal-content')
        .delay(4000)
        .fadeOut();
    }
  
    function showDataInModal(title, data, type) {
      $('#dataModalLabel').text(title);
      const $table = $('#diffTable').empty();
  
      if (type === 'header') {
        $table.append(`
          <thead class="table-light">
            <tr><th>Alan</th><th>Yerel Değer</th><th>Logo’daki Değer</th></tr>
          </thead><tbody></tbody>
        `);
        const $tbody = $table.find('tbody');
        Object.entries(data).forEach(([field, diff]) => {
          $tbody.append(`
            <tr>
              <td><code>${field}</code></td>
              <td>${diff.old  ?? '<i class="text-muted">yok</i>'}</td>
              <td>${diff.new  ?? '<i class="text-muted">yok</i>'}</td>
            </tr>
          `);
        });
      }
      else if (type === 'items') {
        $table.append(`
          <thead class="table-light">
            <tr>
              <th>Internal Ref</th>
              <th>Alan</th>
              <th>Yerel Değer</th>
              <th>Logo’daki Değer</th>
            </tr>
          </thead><tbody></tbody>
        `);
        const $tbody = $table.find('tbody');
        Object.entries(data).forEach(([ref, diffs]) => {
          const fields = Object.keys(diffs);
          fields.forEach((field, idx) => {
            const { old, new: neu } = diffs[field];
            const refCell = idx === 0
              ? `<td rowspan="${fields.length}" class="align-middle"><strong>${ref}</strong></td>`
              : '';
            $tbody.append(`
              <tr>
                ${refCell}
                <td><code>${field}</code></td>
                <td>${old ?? '<i class="text-muted">yok</i>'}</td>
                <td>${neu ?? '<i class="text-muted">yok</i>'}</td>
              </tr>
            `);
          });
        });
      }
  
      new bootstrap.Modal($('#dataModal')[0]).show();
    }
  
    // Compare butonları
    function bindCompareButtons() {
      $('#btnCompareHeader, #btnCompareItems').on('click', function(e){
        e.preventDefault();
        const isHeader = this.id === 'btnCompareHeader';
        const action   = isHeader ? 'compareHeader' : 'compareItems';
        const title    = isHeader ? 'Başlık Farkları' : 'Kalem Farkları';
        const ref      = $('#internal_reference').val().trim();
        if (!ref) return showError('Internal Reference boş!');
  
        $.post('', {
          action,
          internal_reference: ref,
          teklifid: TEKLIF_ID
        }, function(res){
          if (res.status) {
            showDataInModal(title, isHeader ? res.diff : res.diffItems, isHeader ? 'header' : 'items');
          } else {
            showError(res.message || 'Karşılaştırma başarısız.');
          }
        }, 'json');
      });
    }
  
    // DataTable init
    function initDataTable() {
      $('#examples').DataTable({
        processing: true,
        serverSide: true,
        ajax: `uruncek.php?teklifid=${TEKLIF_ID}`,
        language: { url: '//cdn.datatables.net/plug-ins/2.2.2/i18n/tr.json' }
      });
    }
  
    // Logo’ya gönderme
    function performLogoTransfer() {
      showLogoStatus('info', "Logo’ya aktarım başlatılıyor…");
      $('#sendToLogoBtn').prop('disabled', true);
      $('#sendToLogoSpinner').removeClass('d-none');
  
      $.post('', {
        logoyaAktar: 1,
        icerikid: TEKLIF_ID
      }, function(response){
        if (response.status) {
          showLogoStatus('success', response.message || "Logo’ya aktarma başarılı.");
          if (response.internal_reference) {
            $('#internal_reference').val(response.internal_reference);
          }
        } else {
          showLogoStatus('danger', response.message || "Logo’ya aktarma sırasında hata oluştu.");
          $('#sendToLogoBtn').prop('disabled', false);
        }
      }, 'json')
      .fail((xhr,status,err)=>{
        console.error('Logo transfer AJAX error:', err, xhr);
        const msg = xhr.responseText || err;
        showLogoStatus('danger', "Sunucu hatası: " + msg);
        $('#sendToLogoBtn').prop('disabled', false);
      })
      .always(()=>{
        $('#sendToLogoSpinner').addClass('d-none');
      });
    }
  
    function bindLogoActions() {
      // Manuel Logo bilgisi kaydet
      $('#updateLogoInfoBtn').on('click', ()=>{
        const formData = {
          updateLogoInfo: 1,
          teklifid: TEKLIF_ID,
          vatexcept_code: $('#vatexcept_code').val(),
          vatexcept_reason: $('#vatexcept_reason').val(),
          auxil_code: $('#auxil_code').val(),
          auth_code: $('#auth_code').val(),
          order_note: $('#order_note').val(),
          division: $('#division').val(),
          department: $('#department').val(),
          source_wh: $('#source_wh').val(),
          source_costgrp: $('#source_costgrp').val(),
          factory: $('#factory').val(),
          salesman_code: $('input[name="salesman_code"]').val(),
          salesmanref: $('#salesmanref').val(),
          trading_grp: $('#trading_grp').val(),
          payment_code: $('#payment_code').val(),
          paydefref: $('#paydefref').val(),
          doc_number: $('#doc_number').val()
        };
        $.post('', formData, function(txt){
          let json;
          try {
            json = JSON.parse(txt);
          } catch (e) {
            console.error('Invalid JSON:', txt);
            return alert("Gelen ham çıktı JSON değil:\n"+txt);
          }
          console.log('Update response:', json);
          alert(json.message);
          if (json.status) location.reload();
        }).fail(xhr=>{
          console.error('AJAX Error:', xhr);
          alert("Sunucu hatası:\n"+xhr.responseText);
        });
      });
  
      // Siparişi Logo’ya gönder
      $('#sendToLogoBtn').on('click', function(){
        if ($(this).prop('disabled') || $('#internal_reference').val()!=='') {
          return showLogoStatus('info', "Bu sipariş zaten Logo’ya aktarılmıştır.");
        }
        new bootstrap.Modal($('#confirmLogoTransferModal')[0]).show();
      });
      $('#confirmTransferBtn').on('click', ()=>{
        $('#confirmLogoTransferModal').modal('hide');
        performLogoTransfer();
      });
    }
  
    // İscontolu fiyat modal & recalc
    function bindPriceModal() {
      let $currentRow, listPrice;
      $(document).on('click','button[data-bs-target="#priceModal"]', function(){
        $currentRow = $(this).closest('tr');
        listPrice   = parseFloat($(this).data('list-price'))||0;
        $('#priceModalInput')
          .val($currentRow.find('.final-price-hidden').val())
          .removeClass('is-invalid');
        $('#priceModalError').text('');
      });
  
      $('#priceModalSave').on('click', ()=>{
        let newVal = parseFloat($('#priceModalInput').val());
        if (isNaN(newVal)||newVal<0) {
          $('#priceModalInput').addClass('is-invalid');
          $('#priceModalError').text('Lütfen geçerli bir sayı girin.');
          return;
        }
        let discPct = listPrice>0 ? (1-newVal/listPrice)*100 : 0;
        discPct = Math.min(Math.max(discPct,0), MAX_DISCOUNT);
        $('#priceModal').modal('hide');
        $currentRow.find('.net-price-display').val(newVal.toFixed(2));
        $currentRow.find('.final-price-hidden').val(newVal.toFixed(2));
        $currentRow.find('.discount-input').val(discPct.toFixed(2));
        recalcOfferRow($currentRow);
      });
    }
  
    function recalcOfferRow($row) {
      const qty      = parseFloat($row.find('.qty-input').val())    || 0;
      const discPct  = Math.min(Math.max(parseFloat($row.find('.discount-input').val())||0,0), MAX_DISCOUNT);
      const listPrice= parseFloat($row.find('.list-price').val())  || 0;
      const unit     = listPrice*(1-discPct/100);
      const total    = unit*qty;
      $row.find('.final-price-hidden').val(unit.toFixed(2));
      $row.find('.net-price-display').val(unit.toFixed(2));
      $row.find('.total-price').text(total.toFixed(2).replace('.',','));
    }
  
    function bindRecalc() {
      $(document).on('input','.qty-input, .discount-input', function(){
        recalcOfferRow($(this).closest('tr'));
      });
    }
  
    // Satır güncelle
    function bindUpdateRow() {
      $(document).on('click','.update-row', function(){
        const rowId = $(this).data('row-id');
        const $row  = $(`tr[data-row-id="${rowId}"]`);
        $.post('', {
          action: 'updateRow',
          id: rowId,
          miktar: $row.find('.qty-input').val(),
          birim:  $row.find('.unit-input').val(),
          iskonto:$row.find('.discount-input').val()
        }, function(res){
          if (res.status) {
            alert(res.message);
            $row.find('.net-price-display').val(res.netFiyat);
            $row.find('.total-price').text(res.toplam.replace('.',','));
          } else {
            alert("Hata: "+res.message);
          }
        }, 'json').fail((xhr,st,err)=>{
          console.error('AJAX Error:', err, xhr);
          const msg = xhr.responseText || err;
          alert("Sunucu hatası: "+msg);
        });
      });
    }
  
    // Seçim kontrolleri
    function bindSelectors() {
      $('#source_wh').on('change', function(){
        const $o = $(this).find('option:selected');
        $('#source_wh_nr').val($o.data('nr'));
        $('#source_costgrp').val($o.data('costgrp'));
      }).trigger('change');
  
      $('#salesmanref').on('change', function(){
        $('input[name="salesman_code"]').val(
          $(this).find('option:selected').data('code')||''
        );
      }).trigger('change');
  
      $('#paydefref').on('change', function(){
        $('#payment_code').val(
          $(this).find('option:selected').data('code')||''
        );
      }).trigger('change');
    }
  
    // Diğer kod/sebep input toggle
    function bindExemptToggles() {
      $('#vatexcept_code').on('change', function(){
        $('#vatexcept_code_other').toggle(this.value==='other').val(this.value===''?'':'');
      });
      $('#vatexcept_reason').on('change', function(){
        $('#vatexcept_reason_other').toggle(this.value==='other').val(this.value===''?'':'');
      });
      if ($('#vatexcept_code').val()==='other')   $('#vatexcept_code_other').show();
      if ($('#vatexcept_reason').val()==='other') $('#vatexcept_reason_other').show();
    }
  
    // Sync refs
    function bindSyncRefsBtn() {
      $('#syncRefsBtn').on('click', ()=>{
        $.post('', { action:'syncRef', firmNr: window.FIRM_NR||997 }, function(res){
          let msg = "✅ Synced: "+res.success.join(', ');
          if (Object.keys(res.failed||{}).length) {
            msg += "\n❌ Failed: "+JSON.stringify(res.failed);
          }
          alert(msg);
        }, 'json').fail((xhr,st,err)=>{
          console.error(xhr.responseText);
          alert("Sync error: "+err+"\n\n"+xhr.responseText);
        });
      });
    }
  
    // Başlat
    document.addEventListener('DOMContentLoaded', function(){
      initDataTable();
      bindCompareButtons();
      bindLogoActions();
      bindPriceModal();
      bindRecalc();
      bindUpdateRow();
      bindSelectors();
      bindExemptToggles();
      bindSyncRefsBtn();
    });
  })();
  