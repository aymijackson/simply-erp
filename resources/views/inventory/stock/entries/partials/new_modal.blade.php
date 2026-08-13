{{-- Stock Entry Modal (with BORROW support) --}}
<div class="modal fade" id="entryModal" tabindex="-1" aria-labelledby="entryModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen-lg-down modal-xl">
    <form id="entryForm" class="modal-content">
      @csrf
      <input type="hidden" id="entryId">

      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="entryModalLabel">Add Stock Entry</h5>
        <button type="button" class="btn-close text-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        {{-- ---------- Header ---------- --}}
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Store *</label>
                <select name="store_id" id="store_id" class="form-select form-control" required>
                    <option value="">-- Select Store --</option>
                    @foreach($stores as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Entry Date *</label>
                <input type="date" name="entry_date" id="entry_date"
                       class="form-control" value="{{ now()->toDateString() }}" required>
            </div>

            <div class="col-md-3">
                <label class="form-label">Reference #</label>
                <input type="text" name="reference" id="reference" class="form-control">
            </div>

            <div class="col-md-3">
                <label class="form-label">Entry Type *</label>
                <select name="entry_type" id="entry_type" class="form-select form-control" required>
                    <option value="normal" selected>Normal Entry</option>
                    <option value="cust_return">Customer Return</option>
                    <option value="borrow">Borrow from another BOM</option>
                </select>
            </div>

            {{-- Supplier (normal only) --}}
            <div class="col-md-4 entry-supplier">
                <label class="form-label">Supplier</label>
                <select name="supplier_id" id="supplier_id" class="form-select"></select>
            </div>

            {{-- Customer (return only) --}}
            <div class="col-md-4 entry-customer d-none">
                <label class="form-label">Customer *</label>
                <select name="customer_id" id="customer_id" class="form-select"></select>
            </div>

            {{-- Borrow fields (borrow only) --}}
            <div class="col-md-4 entry-borrow d-none">
                <label class="form-label">Source BOM (lender)</label>
                <select name="borrow_source_bom_id" id="borrow_source_bom_id" class="form-select"></select>
            </div>
            <div class="col-md-4 entry-borrow d-none">
                <label class="form-label">Target BOM (borrower)</label>
                <select name="borrow_target_bom_id" id="borrow_target_bom_id" class="form-select"></select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select name="status" id="status" class="form-select form-control">
                    <option value="draft" selected>Draft</option>
                    <option value="approved">Approved</option>
                    <option value="posted">Posted</option>
                </select>
            </div>
        </div>

        <hr>

        {{-- ---------- Lines ---------- --}}
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0">Lines</h5>
            <div class="d-flex gap-2">
              <button type="button" id="addLineBtn" class="btn btn-sm btn-outline-primary">
                  <i class="fas fa-plus"></i> Add Line
              </button>
              {{-- Optional: restrict variants to source BOM products --}}
              <button type="button" id="reloadVariantsFromSourceBom" class="btn btn-sm btn-outline-secondary d-none">
                  <i class="fas fa-sync"></i> Load source-BOM variants
              </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered" id="linesTable">
                <thead class="table-light">
                    <tr>
                        <th style="width:40%">Variant</th>
                        <th style="width:15%">Qty</th>
                        <th style="width:20%">Unit Cost</th>
                        <th class="return-only d-none" style="width:15%">Invoice Line</th>
                        <th class="return-only d-none" style="width:15%">Delivery Line</th>
                        <th style="width:5%"></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" id="cancelEntryBtn" class="btn btn-secondary"  data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-success">Save Entry</button>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
(function(){
  // CSRF
  $.ajaxSetup({headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}});

  // Variant options (fallback, server-rendered)
  let variantOptions = `{!! collect($variants)->map(fn($v)=>
    "<option value=\"{$v->id}\">{$v->sku} – {$v->product->product_name}</option>"
  )->join('') !!}`;

  // Helper: Select2 init functions
  function initPartySelect($sel, url, parent){
    $sel.select2({
      ajax:{ url, dataType:'json', delay:250, data: p=>({q:p.term}), processResults:d=>({results:d}) },
      placeholder:'-- select --', minimumInputLength:2, dropdownParent: parent, width:'100%'
    });
  }
  function initBomSelect($sel){
    $sel.select2({
      ajax:{ url: '{{ route('admin.production.boms.headers.select2') }}', dataType:'json', delay:250, data: p=>({q:p.term}), processResults:d=>({results:d}) },
      placeholder:'-- select BOM --', minimumInputLength:0, dropdownParent: $('#entryModal'), width:'100%'
    });
  }

  // Initialize selects
  initPartySelect($('#supplier_id'), "{{ route('admin.suppliers.select2') }}", $('#entryModal'));
  initPartySelect($('#customer_id'), "{{ route('admin.customers.select2') }}", $('#entryModal'));
  initBomSelect($('#borrow_source_bom_id'));
  initBomSelect($('#borrow_target_bom_id'));

  // Invoice/Delivery Select2 for returns
  function initInvoiceSelect($sel, variantId){
    $sel.select2({
      ajax:{ url:'{{ route('admin.sales.invoices.lines.select2') }}', dataType:'json', delay:250, data: p=>({q:p.term, customer_id:$('#customer_id').val(), variant_id:variantId}), processResults:d=>({results:d}) },
      placeholder:'-- invoice line --', dropdownParent:$('#entryModal'), width:'100%', minimumInputLength:0
    });
  }
  function initDeliverySelect($sel, variantId){
    $sel.select2({
      ajax:{ url:'{{ route('admin.sales.delivery.lines.select2') }}', dataType:'json', delay:250, data: p=>({q:p.term, customer_id:$('#customer_id').val(), variant_id:variantId}), processResults:d=>({results:d}) },
      placeholder:'-- delivery line --', dropdownParent:$('#entryModal'), width:'100%', minimumInputLength:0
    });
  }

  // Build a line row
  function lineRow(pref={}){
    return `
    <tr>
      <td>
        <select name="lines[variant_id][]" class="form-select variant-select form-control" required>
          <option value="">-- Select Variant --</option>${variantOptions}
        </select>
      </td>
      <td><input type="number" name="lines[qty][]" class="form-control" step="0.001" min="0.001" value="${pref.qty||1}" required></td>
      <td><input type="number" name="lines[unit_cost][]" class="form-control" step="0.01" value="${pref.unit_cost||''}"></td>
      <td class="return-only d-none"><select name="lines[invoice_line_id][]" class="form-select inv-line-select"></select></td>
      <td class="return-only d-none"><select name="lines[delivery_line_id][]" class="form-select del-line-select"></select></td>
      <td class="text-center"><button type="button" class="btn btn-link btn-sm text-danger rm-line"><i class="fas fa-times"></i></button></td>
    </tr>`;
  }

  function addLine(pref={}){
    $('#linesTable tbody').append(lineRow(pref));
    const $tr = $('#linesTable tbody tr:last');
    $tr.find('.variant-select').val(pref.variant_id||'');
    initInvoiceSelect($tr.find('.inv-line-select'), pref.variant_id||null);
    initDeliverySelect($tr.find('.del-line-select'), pref.variant_id||null);
    toggleReturnColumns();
  }

  function resetForm(){
    $('#entryForm')[0].reset();
    $('#entryId').val('');
    $('#linesTable tbody').empty();
    addLine();
    toggleEntryTypeFields();
  }

  // Show/hide blocks per type
  function toggleReturnColumns(){
    const show = $('#entry_type').val()==='cust_return';
    $('.return-only').toggleClass('d-none', !show);
  }
  function toggleEntryTypeFields(){
    const type = $('#entry_type').val();
    const isReturn = type==='cust_return';
    const isBorrow = type==='borrow';
    $('.entry-supplier').toggleClass('d-none', isReturn || isBorrow);
    $('.entry-customer').toggleClass('d-none', !isReturn);
    $('.entry-borrow').toggleClass('d-none', !isBorrow);
    $('#customer_id').prop('required', isReturn);
    $('#borrow_source_bom_id, #borrow_target_bom_id').prop('required', isBorrow);
    toggleReturnColumns();
    $('#reloadVariantsFromSourceBom').toggleClass('d-none', !isBorrow);
  }

  // Variant filtering by Source BOM (optional endpoint)
  $('#reloadVariantsFromSourceBom').on('click', function(){
    const bomId = $('#borrow_source_bom_id').val();
    if(!bomId){ return; }
    $.getJSON(`{{ url('/admin/production/boms') }}/${bomId}/variants/select2`, function(list){
      const options = list.map(o=>`<option value="${o.id}">${o.text}</option>`).join('');
      variantOptions = options || variantOptions; // fallback if empty
      // update existing rows (keep selected value if still present)
      $('#linesTable tbody .variant-select').each(function(){
        const current = $(this).val();
        $(this).html(`<option value="">-- Select Variant --</option>${variantOptions}`);
        if(current){ $(this).val(current); }
      });
    });
  });

  // events
  $('#entry_type').on('change', toggleEntryTypeFields);
  $('#addLineBtn').on('click', ()=> addLine());
  $('#linesTable').on('click','.rm-line', e=> $(e.currentTarget).closest('tr').remove());

  // Expose helpers for outer page scripts (edit filling, etc.)
  window.__entryModal = { resetForm, addLine, toggleEntryTypeFields };
})();
</script>
@endpush
