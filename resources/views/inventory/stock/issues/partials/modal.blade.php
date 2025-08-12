{{-- resources/views/inventory/stock/issues/_modal.blade.php – simplified (no bom_item / delivery_line pickers) --}}
<div class="modal fade" id="issueModal" tabindex="-1" aria-labelledby="issueModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <form id="issueForm" class="modal-content">
        @csrf
        <input type="hidden" id="issueId" name="id">

        <div class="modal-header bg-primary text-white">
            <h5 class="modal-title" id="issueModalLabel">New Stock Issue</h5>
            <button type="button" class="btn-close text-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
            {{-- ── Header fields ─────────────────────────────────────────────────── --}}
            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <label class="form-label">Issue type *</label>
                    <select id="issue_type" name="issue_type" class="form-select form-control" required>
                        <option value="normal" selected>Normal</option>
                        <option value="bom">Material Issue (BOM)</option>
                        <option value="sales">Sales Issue</option>
                        <option value="scrap">Scrap / Write-off</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">From store *</label>
                    <select id="from_store_id" name="from_store_id" class="form-select form-control" required>
                        <option value="">-- select --</option>
                        @foreach($stores as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                {{-- BOM Header (for bom issues) --}}
                <div class="col-md-3 bom-only d-none">
                    <label class="form-label">BOM header *</label>
                    <select id="bom_header_id" name="bom_header_id" class="form-select"></select>
                </div>
                {{-- Sales Delivery (for sales issues) --}}
                <div class="col-md-3 sales-only d-none">
                    <label class="form-label">Sales delivery *</label>
                    <select id="sales_delivery_id" name="sales_delivery_id" class="form-select"></select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Reference</label>
                    <input type="text" id="reference" name="reference" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Reason</label>
                    <input type="text" id="reason" name="reason" class="form-control">
                </div>
            </div>

            {{-- ── Lines table (Variant, Qty, Cost only) ─────────────────────────── --}}
            <div class="table-responsive">
              <table id="linesTbl" class="table table-sm table-bordered align-middle">
                 <thead class="table-light">
                   <tr>
                     <th style="width:45%">Variant *</th>
                     <th style="width:20%" class="text-end">Qty *</th>
                     <th style="width:20%" class="text-end">Unit cost</th>
                     <th style="width:15%" class="text-center">
                         <button type="button" class="btn btn-sm btn-success" id="addLineBtn"><i class="fas fa-plus"></i></button>
                     </th>
                   </tr>
                 </thead>
                 <tbody id="linesBody"></tbody>
              </table>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-success">Save draft</button>
        </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
/* ▼ Toggle header visibility when issue_type changes */
function toggleIssueMode(){
  const mode = $('#issue_type').val();
  $('.bom-only').toggleClass('d-none', mode!=='bom');
  $('.sales-only').toggleClass('d-none',mode!=='sales');
  $('#bom_header_id').prop('required', mode==='bom');
  $('#sales_delivery_id').prop('required', mode==='sales');
}
$('#issue_type').on('change', toggleIssueMode);

/* ▼ Generic Select2 helper */
function s2($el, cfg){ cfg.dropdownParent=$('#issueModal'); cfg.width='100%'; $el.select2(cfg);} 

s2($('#bom_header_id'),{
   ajax:{url:'{{ route('admin.production.boms.headers.select2') }}',dataType:'json',delay:250,data:p=>({q:p.term}),processResults:d=>({results:d})},
   placeholder:'-- BOM --',minimumInputLength:1
});

s2($('#sales_delivery_id'),{
   ajax:{url:'{{ route('admin.sales.delivery.select2') }}',dataType:'json',delay:250,data:p=>({q:p.term}),processResults:d=>({results:d})},
   placeholder:'-- delivery --',minimumInputLength:1
});

function initVariant($el){
  s2($el,{
     ajax:{
        url:'/admin/inventory/stock/transfers/fetch-variants',
        dataType:'json', delay:250,
        data: params => ({ q: params.term }),
        processResults: data => ({
            results: (Array.isArray(data) ? data : data.results || []).map(v => ({
                id: v.id,
                text: `${v.sku} – ${v.product_name}`
            }))
        })
     },
     placeholder:'-- variant --',
     minimumInputLength:1
  });
}

/* ▼ Build line row (Variant, Qty, Cost only) */
function buildRow(data={}){
  const key=Date.now();
  const $row=$( `<tr data-key="${key}">
      <td><select name="lines[${key}][product_variant_id]" class="form-select variant"></select></td>
      <td><input type="number" name="lines[${key}][qty]" class="form-control text-end" min="0.001" step="0.001" value="${data.qty||1}" required></td>
      <td><input type="number" name="lines[${key}][unit_cost]" class="form-control text-end" step="0.01" value="${data.unit_cost||''}"></td>
      <td class="text-center"><button type="button" class="btn btn-link text-danger rm"><i class="fas fa-times"></i></button></td>
    </tr>`);
  $('#linesBody').append($row);
  initVariant($row.find('.variant'));
  if(data.variant){
     const opt=new Option(data.variant.text,data.variant.id,true,true);
     $row.find('.variant').append(opt).trigger('change');
  }
}

$('#addLineBtn').on('click',()=>buildRow());
$('#linesBody').on('click','.rm',e=>$(e.currentTarget).closest('tr').remove());

/* ▼ open modal (new) */
$('#addBtn').on('click',()=>{
   $('#issueForm')[0].reset(); $('#issueId').val(''); $('#linesBody').empty(); buildRow(); toggleIssueMode();
   $('#issueModalLabel').text('New Stock Issue'); $('#issueModal').modal('show');
});

/* ▼ open for edit (DataTable passes JSON) */
window.openIssueModal=function(json){
  $('#issueForm')[0].reset(); $('#issueId').val(json.id);
  $('#issue_type').val(json.issue_type).trigger('change');
  $('#from_store_id').val(json.from_store_id);
  $('#reference').val(json.reference); $('#reason').val(json.reason);
  if(json.issue_type==='bom'){
     const opt=new Option(json.bom_header.text,json.bom_header.id,true,true);
     $('#bom_header_id').append(opt).trigger('change');
  }
  if(json.issue_type==='sales'){
     const opt=new Option(json.sales_delivery.text,json.sales_delivery.id,true,true);
     $('#sales_delivery_id').append(opt).trigger('change');
  }
  $('#linesBody').empty();
  json.lines.forEach(l=>buildRow({qty:l.qty,unit_cost:l.unit_cost,variant:{id:l.product_variant_id,text:l.variant.sku}}));
  toggleIssueMode(); $('#issueModalLabel').text('Edit Stock Issue'); $('#issueModal').modal('show');
};

/* ▼ submit */
$('#issueForm').on('submit',function(e){
  e.preventDefault(); 
  const empty = $('#linesBody .variant').filter(function () {
        return !$(this).val();   // no variant chosen
    });
    if (empty.length) {
        e.preventDefault();
        Swal.fire('Choose a variant on every row');
        return;
    }
  const id=$('#issueId').val(); const url=id?`/admin/inventory/stock-issues/${id}`:"{{ route('admin.inventory.stock_issues.store') }}";
  $.post(url,$(this).serialize()+(id?'&_method=PUT':''))
   .done(r=>{ $('#issueModal').modal('hide'); $('#issueTbl').DataTable().ajax.reload(null,false); Swal.fire('Saved',r.message,'success'); })
   .fail(xhr => {
        /* ── 422 coming from ValidationException ───────────── */
        if (xhr.status === 422 && xhr.responseJSON?.errors?.qty?.length) {
            Swal.fire({
                icon: 'error',
                title: 'Insufficient stock',
                html: xhr.responseJSON.errors.qty[0]     // e.g. “122aaaa22 (have 0, need 3) …”
            });
            return;
        }

        /* ── generic fallback ──────────────────────────────── */
        Swal.fire('Error',
                  xhr.responseJSON?.message || 'Save failed',
                  'error');
    });
});
</script>
@endpush
