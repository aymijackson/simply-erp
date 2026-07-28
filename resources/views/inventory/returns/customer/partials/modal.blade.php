<div class="modal fade" id="customerReturnModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <form id="customerReturnForm" class="modal-content">
      @csrf
      <input type="hidden" id="returnId" name="id">

      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="customerReturnModalLabel">New Customer Return</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="row g-3 mb-3">
          <div class="col-md-4">
            <label class="form-label">Store *</label>
            <select id="store_id" name="store_id" class="form-select" required></select>
          </div>

          <div class="col-md-4">
            <label class="form-label">Customer *</label>
            <select id="customer_id" name="customer_id" class="form-select" required></select>
          </div>

          <div class="col-md-4">
            <label class="form-label">Sales Delivery (optional)</label>
            <select id="sales_delivery_id" name="sales_delivery_id" class="form-select"></select>
            <small class="text-muted">Attach this return to a delivery if needed.</small>
          </div>

          <div class="col-md-4">
            <label class="form-label">Entry Date *</label>
            <input type="date" id="entry_date" name="entry_date" class="form-control"
                   value="{{ now()->toDateString() }}" required>
          </div>

          <div class="col-md-4">
            <label class="form-label">Reference</label>
            <input type="text" id="reference" name="reference" class="form-control">
          </div>

          <div class="col-md-12">
            <label class="form-label">Remarks / Notes</label>
            {{-- ✅ stock_entries uses remarks, not reason --}}
            <input type="text" id="remarks" name="remarks" class="form-control">
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-sm table-bordered align-middle">
            <thead class="table-light">
              <tr>
                <th style="width:45%">Variant *</th>
                <th style="width:20%" class="text-end">Qty *</th>
                <th style="width:20%" class="text-end">Unit Cost *</th>
                <th style="width:15%" class="text-center">
                  <button type="button" class="btn btn-sm btn-success" id="addLineBtn">
                    <i class="fas fa-plus"></i>
                  </button>
                </th>
              </tr>
            </thead>
            <tbody id="linesBody"></tbody>
          </table>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary" id="saveBtn">Save Draft</button>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
$.ajaxSetup({headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}});

const MODAL = $('#customerReturnModal');
const LINES = $('#linesBody');

const STORE_URL    = "{{ route('admin.inventory.api.stores') }}";
const CUSTOMER_URL = "{{ route('admin.inventory.returns.customer.select2.customers') }}";
const DELIVERY_URL = "{{ route('admin.inventory.returns.customer.select2.deliveries') }}";
const VARIANT_URL  = "{{ route('admin.inventory.stock_issues.fetch_variants') }}"; // reuse your existing variants select2

function destroySelect2($el){
  if ($el.data('select2')) {
    $el.select2('destroy');
  }
}

/** ✅ Select2 helper: set id + text properly */
function setSelect2Value($select, id, text){
  if(!id) return;
  const opt = new Option(text || (''+id), id, true, true);
  $select.append(opt).trigger('change');
}

/** ✅ Always (re)initialize select2 cleanly */
function initSelect2(){
  const $store    = $('#store_id');
  const $customer = $('#customer_id');
  const $delivery = $('#sales_delivery_id');

  destroySelect2($store);
  destroySelect2($customer);
  destroySelect2($delivery);

  $store.empty();
  $customer.empty();
  $delivery.empty();

  $store.select2({
    dropdownParent: MODAL,
    width:'100%',
    placeholder:'-- select store --',
    allowClear:true,
    ajax:{
      url: STORE_URL, dataType:'json', delay:250,
      data: p=>({q:p.term||''}),
      processResults: d=>({results:d})
    }
  });

  $customer.select2({
    dropdownParent: MODAL,
    width:'100%',
    placeholder:'-- search customer --',
    allowClear:true,
    minimumInputLength:2,
    ajax:{
      url: CUSTOMER_URL, dataType:'json', delay:250,
      data: p=>({q:p.term||''}),
      processResults: d=>({results:d})
    }
  });

  $delivery.select2({
    dropdownParent: MODAL,
    width:'100%',
    allowClear:true,
    placeholder:'-- optional: attach delivery --',
    minimumInputLength:1,
    ajax:{
      url: DELIVERY_URL, dataType:'json', delay:250,
      data: p=>({q:p.term||''}),
      processResults: d=>({results:d})
    }
  });
}

/** ✅ line row builder */
function newLine(prefill = {}){
  const idx = Date.now() + Math.floor(Math.random()*1000);

  const $tr = $(`
    <tr data-key="${idx}">
      <td>
        <select name="lines[${idx}][product_variant_id]" class="form-select variant-select" required></select>
      </td>
      <td>
        <input name="lines[${idx}][qty]" type="number"
               class="form-control text-end" min="0.001" step="0.001" required>
      </td>
      <td>
        <input name="lines[${idx}][unit_cost]" type="number"
               class="form-control text-end" min="0" step="0.01" required>
      </td>
      <td class="text-center">
        <button type="button" class="btn btn-sm btn-danger remBtn"><i class="fas fa-trash"></i></button>
      </td>
    </tr>
  `);

  LINES.append($tr);

  // Variant select2
  const $variant = $tr.find('.variant-select');
  destroySelect2($variant);
  $variant.empty();

  $variant.select2({
    dropdownParent: MODAL,
    width:'100%',
    placeholder:'-- choose variant --',
    minimumInputLength:2,
    ajax:{
      url: VARIANT_URL, dataType:'json', delay:250,
      data: p=>({q:p.term||''}),
      processResults: d=>({results:d})
    }
  });

  // prefill
  if(prefill.product_variant_id){
    setSelect2Value($variant, prefill.product_variant_id, prefill.text || prefill.sku || ('Variant #'+prefill.product_variant_id));
    $tr.find('[name$="[qty]"]').val(prefill.qty ?? '');
    $tr.find('[name$="[unit_cost]"]').val(prefill.unit_cost ?? '');
  }
}

$('#addLineBtn').on('click', ()=> newLine());
LINES.on('click','.remBtn', function(){ $(this).closest('tr').remove(); });

function resetForm(){
  $('#customerReturnForm')[0].reset();
  $('#returnId').val('');
  LINES.empty();

  // select2 will be re-init; just ensure empty
  $('#store_id').empty();
  $('#customer_id').empty();
  $('#sales_delivery_id').empty();

  $('#entry_date').val("{{ now()->toDateString() }}");
  newLine();
}

/**
 * ✅ Main modal opener
 * payload formats supported:
 * - null (new)
 * - server json from /{id}/json (edit)
 */
window.openCustomerReturnModal = function(payload){
  const isNew = !payload?.id;

  $('#customerReturnModalLabel').text(isNew ? 'New Customer Return' : 'Edit Customer Return');

  // Always reset and init select2 BEFORE setting values
  resetForm();
  initSelect2();

  if(!isNew){
    $('#returnId').val(payload.id);

    // Store/customer should come as {id,text}
    if(payload.store?.id){
      setSelect2Value($('#store_id'), payload.store.id, payload.store.text);
    } else if(payload.store_id){
      setSelect2Value($('#store_id'), payload.store_id, payload.store_name || ('Store #'+payload.store_id));
    }

    if(payload.customer?.id){
      setSelect2Value($('#customer_id'), payload.customer.id, payload.customer.text);
    } else if(payload.customer_id){
      setSelect2Value($('#customer_id'), payload.customer_id, payload.customer_name || ('Customer #'+payload.customer_id));
    }

    // Delivery optional (if your json includes it)
    if(payload.sales_delivery?.id){
      setSelect2Value($('#sales_delivery_id'), payload.sales_delivery.id, payload.sales_delivery.text);
    } else if(payload.sales_delivery_id){
      setSelect2Value($('#sales_delivery_id'), payload.sales_delivery_id, payload.sales_delivery_text || ('Delivery #'+payload.sales_delivery_id));
    }

    $('#entry_date').val(payload.entry_date || "{{ now()->toDateString() }}");
    $('#reference').val(payload.reference || '');
    $('#remarks').val(payload.remarks || '');

    // lines
    LINES.empty();
    (payload.lines || []).forEach(l => newLine(l));
    if(!(payload.lines||[]).length) newLine();
  }

  MODAL.modal('show');
};

$('#customerReturnForm').on('submit', function(e){
  e.preventDefault();

  if(!$('#store_id').val()) return Swal.fire('Error','Select a store','error');
  if(!$('#customer_id').val()) return Swal.fire('Error','Select a customer','error');
  if(LINES.find('tr').length < 1) return Swal.fire('Error','Add at least one line','error');

  const id  = $('#returnId').val();
  const url = id ? `${BASE_URL}/${id}` : `{{ route('admin.inventory.returns.customer.store') }}`;

  const data = $(this).serialize() + (id ? '&_method=PUT' : '');

  $('#saveBtn').prop('disabled', true);

  $.post(url, data)
    .done(res=>{
      MODAL.modal('hide');
      if(window.resetAndReloadCustomerReturnsTable) window.resetAndReloadCustomerReturnsTable();
      Swal.fire('Saved', res.message || 'Saved', 'success');
    })
    .fail(xhr=>{
      let msg = xhr.responseJSON?.message || 'Save failed';
      if(xhr.status===422 && xhr.responseJSON?.errors){
        msg = Object.values(xhr.responseJSON.errors)[0][0];
      }
      Swal.fire('Error', msg, 'error');
    })
    .always(()=> $('#saveBtn').prop('disabled', false));
});
</script>
@endpush
