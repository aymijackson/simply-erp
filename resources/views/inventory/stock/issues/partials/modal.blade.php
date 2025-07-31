{{-- inventory/stock/issues/_modal.blade.php --}}
<div class="modal fade" id="issueModal" tabindex="-1" aria-labelledby="issueModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
     <form id="issueForm" class="modal-content">
        @csrf                    {{-- always include for AJAX --}}
        <input type="hidden" id="issueId" name="id">

        <div class="modal-header bg-primary text-white">
           <h5 class="modal-title" id="issueModalLabel">New Stock Issue</h5>
           <button type="button" class="btn-close text-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
           {{-- ---------- header fields ---------- --}}
           <div class="row g-3 mb-3">
              <div class="col-md-4">
                 <label class="form-label">From Store *</label>
                 <select id="from_store_id" name="from_store_id" class="form-select form-control" required>
                     <option value="">-- select --</option>
                     @foreach($stores as $s)
                         <option value="{{ $s->id }}">{{ $s->name }}</option>
                     @endforeach
                 </select>
              </div>

              <div class="col-md-4">
                 <label class="form-label">Reference</label>
                 <input type="text" id="reference" name="reference" class="form-control">
              </div>

              <div class="col-md-4">
                 <label class="form-label">Reason</label>
                 <input type="text" id="reason" name="reason" class="form-control">
              </div>
           </div>

           {{-- ---------- lines table ---------- --}}
           <div class="table-responsive">
             <table class="table table-sm table-bordered align-middle" id="lineTbl">
                <thead class="table-light">
                   <tr>
                     <th style="width:55%">Variant *</th>
                     <th style="width:25%" class="text-end">Qty *</th>
                     <th style="width:25%" class="text-end">Unit Cost </th>
                     <th style="width:20%" class="text-center">
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
           <button type="submit" class="btn btn-success">Save Draft</button>
        </div>
     </form>
  </div>
</div>

@push('scripts')
<script>
/* ---------- dynamic lines helpers ---------- */
function newLine(rowData = {}) {
   let idx = Date.now();                 // simple unique key for DOM ids
   let tr  = $(`
     <tr data-key="${idx}">
        <td>
           <select name="lines[${idx}][product_variant_id]" class="form-select variant-select" required></select>
        </td>
        <td>
           <input type="number" name="lines[${idx}][qty]" class="form-control text-end" min="0.001" step="0.001" required>
        </td>
        <td>
           <input type="number" name="lines[${idx}][unit_cost]" class="form-control text-end" min="0.001" step="0.001" placeholder="Unit Cost">
        </td>
        <td class="text-center">
           <button type="button" class="btn btn-sm btn-danger remLineBtn">
              <i class="fas fa-trash"></i>
           </button>
        </td>
     </tr>`);
   $('#linesBody').append(tr);

   // bootstrap Select2 with remote search
   tr.find('.variant-select').select2({
        dropdownParent: $('#issueModal'),
        ajax:{
           url: "{{ route('admin.inventory.stock_issues.fetch_variants') }}",
           dataType:'json',
           delay:250,
           data: params => ({q: params.term}),
           processResults: data => ({results:data})
        },
        placeholder:'-- choose variant --',
        minimumInputLength:2,
        width:'100%'
   });

   // pre‑fill (edit mode)
   if (rowData.variant_id) {
       let opt = new Option(rowData.sku, rowData.product_variant_id, true, true);
       tr.find('.variant-select').append(opt).trigger('change');
       tr.find('[name$="[qty]"]').val(rowData.qty);
       tr.find('[name$="[unit_cost]"]').val(rowData.qty);
   }
}

/* add / remove line buttons */
$('#addLineBtn').on('click', ()=> newLine());
$('#linesBody').on('click', '.remLineBtn', function(){
    $(this).closest('tr').remove();
});

/* ---------- modal open from index page ---------- */
window.openIssueModal = function(issue){      // called from DataTable edit
   $('#issueModalLabel').text('Edit Stock Issue');
   $('#issueId').val(issue.id);
   $('#from_store_id').val(issue.from_store_id);
   $('#reference').val(issue.reference);
   $('#reason').val(issue.reason);

   $('#linesBody').empty();
   issue.lines.forEach(ln => newLine({
       variant_id: ln.product_variant_id,
       sku:        ln.variant.sku,
       qty:        ln.qty
   }));
   $('#issueModal').modal('show');
};

/* ---------- submit (create or update) ---------- */
$('#issueForm').on('submit', function(e){
  e.preventDefault();
  let id  = $('#issueId').val();
  let url = id ? `/admin/inventory/stock-issues/${id}` : "{{ route('admin.inventory.stock_issues.store') }}";
  let fd  = $(this).serialize();
  if(id) fd += '&_method=PUT';

  $.post(url, fd)
   .done(r=>{
        $('#issueModal').modal('hide');
        $('#issueTbl').DataTable().ajax.reload(null,false);
        Swal.fire('Success', r.message, 'success');
   })
   .fail(x=> Swal.fire('Error', x.responseJSON?.message || 'Save failed','error'));
});
</script>
@endpush
