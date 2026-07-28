{{-- inventory/returns/suppliers/partials/modal.blade.php --}}
<div class="modal fade" id="returnModal" tabindex="-1"
     aria-labelledby="returnModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
     <form id="returnForm" class="modal-content">
        @csrf
        <input type="hidden" id="returnId" name="id">
        <input type="hidden" id="request_uuid" name="request_uuid" value="{{ $request_uuid }}">

        <div class="modal-header bg-success text-white">
           <h5 class="modal-title" id="returnModalLabel">New Supplier Return</h5>
           <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
           {{-- header fields --}}
           <div class="row g-3 mb-3">
              <div class="col-md-4">
                 <label class="form-label">Return From Store *</label>
                 <select id="store_id" name="store_id" class="form-select form-control" required>
                   <option value="">-- select --</option>
                 </select>
                 <small class="text-muted d-block mt-1">Select store first (qty will be capped by stock in that store)</small>
              </div>

              <div class="col-md-4">
                 <label class="form-label">Supplier *</label>
                 <select id="supplier_id" name="supplier_id" class="form-select form-control" required></select>
              </div>

              <div class="col-md-4">
                 <label class="form-label">Reference / RMA</label>
                 <input type="text" id="reference" name="reference" class="form-control">
              </div>

              <div class="col-md-4">
                 <label class="form-label">Issue Date</label>
                 <input type="date" id="issue_date" name="issue_date"
                        value="{{ now()->toDateString() }}" class="form-control">
              </div>

              <div class="col-md-12">
                 <label class="form-label">Reason / Notes</label>
                 <input type="text" id="reason" name="reason" class="form-control">
              </div>
           </div>

           {{-- lines table --}}
           <div class="table-responsive">
             <table class="table table-sm table-bordered align-middle" id="returnLineTbl">
                <thead class="table-light">
                   <tr>
                     <th style="width:45%">Variant *</th>
                     <th style="width:20%" class="text-end">Qty *</th>
                     <th style="width:20%" class="text-end">Unit Cost *</th>
                     <th style="width:15%" class="text-center">
                         <button type="button" class="btn btn-sm btn-success" id="addReturnLineBtn">
                             <i class="fas fa-plus"></i>
                         </button>
                     </th>
                   </tr>
                </thead>
                <tbody id="returnLinesBody"></tbody>
             </table>
           </div>
        </div>

        <div class="modal-footer">
           <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
           <button type="submit" class="btn btn-success">Save Draft</button>
        </div>
     </form>
  </div>
</div>

@push('scripts')
<script>
$.ajaxSetup({headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}});

const RETURN_MODAL = $('#returnModal');
const LINES_BODY   = $('#returnLinesBody');

const STORE_URL    = "{{ route('admin.inventory.api.stores') }}";
const SUPPLIER_URL = "{{ route('admin.suppliers.select2') }}";
const VARIANT_URL  = "{{ route('admin.inventory.stock_issues.fetch_variants') }}";
const AVAIL_URL    = "{{ route('admin.location_stores.stock.available') }}";

/* ---------- helpers ---------- */
function newUUID(){
  return (window.crypto?.randomUUID) ? crypto.randomUUID() :
    'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
      const r = Math.random() * 16 | 0, v = c === 'x' ? r : (r & 0x3 | 0x8);
      return v.toString(16);
    });
}

function setSelect2Option($select, id, text){
  if(!id) return;
  // IMPORTANT: clear old options so it doesn't show Supplier #id
  $select.empty();
  const opt = new Option(text || String(id), id, true, true);
  $select.append(opt).trigger('change');
}

function initStoreSelect2() {
  const $store = $('#store_id');
  if ($store.data('select2')) $store.select2('destroy');

  $store.select2({
    dropdownParent: RETURN_MODAL,
    width: '100%',
    placeholder: '-- select --',
    allowClear: true,
    minimumInputLength: 0,
    ajax: {
      url: STORE_URL,
      dataType: 'json',
      delay: 250,
      data: params => ({ q: params.term || '' }),
      processResults: data => ({ results: data }),
      cache: true
    }
  });
}

function initSupplierSelect2() {
  const $supplier = $('#supplier_id');
  if ($supplier.data('select2')) $supplier.select2('destroy');

  $supplier.select2({
    dropdownParent: RETURN_MODAL,
    width: '100%',
    placeholder: '-- search supplier --',
    minimumInputLength: 2,
    ajax:{
      url: SUPPLIER_URL,
      dataType:'json',
      delay:250,
      data:params=>({q:params.term || ''}),
      processResults:data=>({results:data})
    }
  });
}

function mustHaveStore(){
  const storeId = $('#store_id').val();
  if(!storeId){
    Swal.fire('Select Store First','Choose a store before selecting variants.','info');
    return false;
  }
  return true;
}

function fetchAvailable(storeId, variantId){
  return $.get(AVAIL_URL, {
    location_store_id: storeId,
    product_variant_id: variantId
  });
}

/* -------- line row builder -------- */
function newReturnLine(prefill = {}) {
  if(!mustHaveStore()) return;

  const idx = Date.now();

  const $tr = $(`
    <tr data-key="${idx}">
      <td>
        <select name="lines[${idx}][product_variant_id]" class="form-select variant-select" required></select>
      </td>
      <td>
        <input name="lines[${idx}][qty]" type="number"
               class="form-control text-end qty-input" min="0.001" step="0.001" required>
        <div class="small text-muted mt-1">
          Available: <span class="avail-text">—</span>
        </div>
      </td>
      <td>
        <input name="lines[${idx}][unit_cost]" type="number"
               class="form-control text-end" min="0" step="0.01" required>
      </td>
      <td class="text-center">
        <button class="btn btn-sm btn-danger remReturnLineBtn" type="button">
          <i class="fas fa-trash"></i>
        </button>
      </td>
    </tr>
  `);

  LINES_BODY.append($tr);

  $tr.find('.variant-select').select2({
    dropdownParent: RETURN_MODAL,
    ajax:{
      url: VARIANT_URL,
      dataType:'json',
      delay:250,
      data: params => ({ q: params.term || '' }),
      processResults: data => ({ results:data })
    },
    placeholder:'-- choose variant --',
    minimumInputLength:2,
    width:'100%'
  });

  $tr.on('select2:select', '.variant-select', function(e){
    const storeId   = $('#store_id').val();
    const variantId = e.params?.data?.id;
    if(!storeId || !variantId) return;

    fetchAvailable(storeId, variantId)
      .done(res => {
        const available = Number(res.available || 0);
        $tr.find('.avail-text').text(available.toFixed(4));

        const $qty = $tr.find('.qty-input');
        $qty.attr('max', available);

        const cur = Number($qty.val() || 0);
        if(available > 0 && cur > available) $qty.val(available);

        if(available <= 0){
          Swal.fire('No Stock', 'This variant has no available stock in the selected store.', 'warning');
        }
      })
      .fail(xhr => Swal.fire('Error', xhr.responseJSON?.message || 'Failed to fetch availability', 'error'));
  });

  $tr.on('input', '.qty-input', function(){
    const max = Number($(this).attr('max') || 0);
    const val = Number($(this).val() || 0);
    if(max > 0 && val > max) $(this).val(max);
  });

  // Prefill
  if (prefill.variant_id) {
    const opt = new Option(prefill.text || ('Variant #' + prefill.variant_id),
                          prefill.variant_id, true, true);
    $tr.find('.variant-select').append(opt).trigger('change');

    $tr.find('[name$="[qty]"]').val(prefill.qty || '');
    $tr.find('[name$="[unit_cost]"]').val(prefill.unit_cost || '');

    const storeId = $('#store_id').val();
    if(storeId){
      fetchAvailable(storeId, prefill.variant_id)
        .done(res => {
          const available = Number(res.available || 0);
          $tr.find('.avail-text').text(available.toFixed(4));
          $tr.find('.qty-input').attr('max', available);
          const cur = Number($tr.find('.qty-input').val() || 0);
          if(available > 0 && cur > available) $tr.find('.qty-input').val(available);
        });
    }
  }
}

/* add / remove line */
$('#addReturnLineBtn').on('click', ()=> newReturnLine());
LINES_BODY.on('click', '.remReturnLineBtn', function(){
  $(this).closest('tr').remove();
});

/* store change resets lines */
$(document).on('change', '#store_id', function(){
  LINES_BODY.empty();
  if($('#store_id').val()) newReturnLine();
});

/* init select2 when modal opens */
RETURN_MODAL.on('shown.bs.modal', function () {
  initStoreSelect2();
  initSupplierSelect2();
});

/* open modal (new/edit) */
window.openReturnModal = function (payload){
  const isNew = !(payload?.id);

  $('#returnModalLabel').text(isNew ? 'New Supplier Return' : 'Edit Supplier Return');
  $('#returnId').val(payload?.id || '');

  $('#request_uuid').val(isNew ? newUUID() : (payload.request_uuid || newUUID()));

  // init select2
  initStoreSelect2();
  initSupplierSelect2();

  // IMPORTANT: set these AFTER init, using real names from payload
  if (payload?.store_id) {
    setSelect2Option($('#store_id'), payload.store_id, payload.store_name || payload.store?.name || payload.store?.text);
  } else {
    $('#store_id').val(null).trigger('change');
  }

  if (payload?.supplier_id) {
    // ✅ will show supplier name in the select2 input
    setSelect2Option($('#supplier_id'), payload.supplier_id, payload.supplier_name || payload.supplier?.name || payload.supplier?.text);
  } else {
    $('#supplier_id').val(null).trigger('change');
  }

  $('#reference').val(payload?.reference || '');
  $('#reason').val(payload?.reason || '');
  $('#issue_date').val(payload?.issue_date || "{{ now()->toDateString() }}");

  // lines
  LINES_BODY.empty();
  (payload?.lines || []).forEach(l => {
    newReturnLine({
      variant_id : l.product_variant_id,
      text       : l.sku || l.variant?.sku || ('Variant #' + l.product_variant_id),
      qty        : l.qty,
      unit_cost  : l.unit_cost
    });
  });

  if (!(payload?.lines || []).length && $('#store_id').val()) newReturnLine();

  RETURN_MODAL.modal('show');
};

/* submit */
$('#returnForm').on('submit', function(e){
  e.preventDefault();

  if(!$('#store_id').val()) return Swal.fire('Error','Select a store','error');
  if(!$('#supplier_id').val()) return Swal.fire('Error','Select a supplier','error');
  if(LINES_BODY.find('tr').length < 1) return Swal.fire('Error','Add at least one line','error');

  let bad = null;
  LINES_BODY.find('tr').each(function(){
    const $tr = $(this);
    const variantId = $tr.find('.variant-select').val();
    const qty = Number($tr.find('.qty-input').val() || 0);
    const max = Number($tr.find('.qty-input').attr('max') || 0);

    if(!variantId) { bad = 'Select variant for all lines.'; return false; }
    if(qty <= 0) { bad = 'Qty must be greater than 0.'; return false; }
    if(max > 0 && qty > max) { bad = `Qty cannot exceed available (${max}).`; return false; }
  });
  if(bad) return Swal.fire('Error', bad, 'error');

  const id  = $('#returnId').val();
  const url = id ? `/admin/inventory/returns/supplier/${id}` : "{{ route('admin.inventory.returns.supplier.store') }}";
  const data = $(this).serialize() + (id ? '&_method=PUT' : '');

  $.post(url, data)
    .done(r=>{
      RETURN_MODAL.modal('hide');
      if (window.resetAndReloadReturnsTable) window.resetAndReloadReturnsTable();
      Swal.fire('Saved', r.message ?? 'Saved successfully', 'success');
    })
    .fail(xhr=>{
      let msg='Save failed';
      if (xhr.status===422 && xhr.responseJSON?.errors){
        msg = Object.values(xhr.responseJSON.errors)[0][0];
      } else if (xhr.responseJSON?.message){
        msg = xhr.responseJSON.message;
      }
      Swal.fire('Error', msg, 'error');
    });
});
</script>
@endpush
