{{-- inventory/stock/returns/_modal.blade.php --}}
<div class="modal fade" id="returnModal" tabindex="-1"
     aria-labelledby="returnModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
     <form id="returnForm" class="modal-content">
        @csrf
        <input type="hidden" id="returnId" name="id">

        <div class="modal-header bg-success text-white">
           <h5 class="modal-title" id="returnModalLabel">New Customer Return</h5>
           <button type="button" class="btn-close text-white"
                   data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
           {{-- ───── header fields ───── --}}
           <div class="row g-3 mb-3">
              <div class="col-md-4">
                 <label class="form-label">Return to Store *</label>
                 <select id="store_id" name="store_id"
                         class="form-select form-control" required>
                     <option value="">-- select --</option>
                     @foreach($stores as $s)
                         <option value="{{ $s->id }}">{{ $s->name }}</option>
                     @endforeach
                 </select>
              </div>

              <div class="col-md-4">
                 <label class="form-label">Customer *</label>
                 <select id="customer_id" name="customer_id"
                         class="form-select form-control" required></select>
              </div>

              <div class="col-md-4">
                 <label class="form-label">Reference / RMA</label>
                 <input type="text" id="reference" name="reference"
                        class="form-control">
              </div>

              <div class="col-md-4">
                 <label class="form-label">Entry Date</label>
                 <input type="date" id="entry_date" name="entry_date"
                        value="{{now()}}" class="form-control">
              </div>

              <div class="col-md-12">
                 <label class="form-label">Reason / Notes</label>
                 <input type="text" id="reason" name="reason"
                        class="form-control">
              </div>
           </div>

           {{-- ───── lines table ───── --}}
           <div class="table-responsive">
             <table class="table table-sm table-bordered align-middle" id="returnLineTbl">
                <thead class="table-light">
                   <tr>
                     <th style="width:45%">Variant *</th>
                     <th style="width:15%" class="text-end">Qty *</th>
                     <th style="width:20%" class="text-end">Unit Cost *</th>
                     <th style="width:20%" class="text-center">
                         <button type="button" class="btn btn-sm btn-success"
                                 id="addReturnLineBtn">
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
           <button type="button" class="btn btn-secondary"
                   data-bs-dismiss="modal">Cancel</button>
           <button type="submit" class="btn btn-success">Save Draft</button>
        </div>
     </form>
  </div>
</div>

@push('scripts')
<script>
$.ajaxSetup({headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}});

/* ───────── dynamic line helper ───────── */
function newReturnLine(data = {}) {
   const idx = Date.now();   // crude unique key
   const tr = $(`
     <tr data-key="${idx}">
        <td><select name="lines[${idx}][product_variant_id]"
                    class="form-select variant-select" required></select></td>
        <td><input  name="lines[${idx}][qty]" type="number"
                    class="form-control text-end" min="1" step="1" required></td>
        <td><input  name="lines[${idx}][unit_cost]" type="number"
                    class="form-control text-end" min="0" step="0.01" required></td>
        <td class="text-center">
           <button class="btn btn-sm btn-danger remReturnLineBtn" type="button">
               <i class="fas fa-trash"></i>
           </button>
        </td>
     </tr>`);
   $('#returnLinesBody').append(tr);

   // Variant Select2
   tr.find('.variant-select').select2({
        dropdownParent: $('#returnModal'),
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

   // pre-fill when editing
   if (data.variant_id) {
       const opt = new Option(data.sku, data.variant_id, true, true);
       tr.find('.variant-select').append(opt).trigger('change');
       tr.find('[name$="[qty]"]').val(data.qty);
       tr.find('[name$="[unit_cost]"]').val(data.unit_cost);
   }
}

/* add / remove */
$('#addReturnLineBtn').on('click', ()=> newReturnLine());
$('#returnLinesBody').on('click', '.remReturnLineBtn', function(){
    $(this).closest('tr').remove();
});

/* ───────── Customer Select2 ───────── */
$('#customer_id').select2({
    dropdownParent: $('#returnModal'),
    ajax:{
       url:"{{ route('admin.crm.customers.select2') }}",
       dataType:'json',
       delay:250,
       data:params=>({q:params.term}),
       processResults:data=>({results:data})
    },
    placeholder:'-- search customer --',
    minimumInputLength:2,
    width:'100%'
});

/* ───────── open for edit ───────── */
window.openReturnModal = function (payload){
   $('#returnModalLabel').text('Edit Customer Return');
   $('#returnId')   .val(payload.id);
   $('#store_id')  .val(payload.store_id);
   if (payload.customer){
      const opt = new Option(payload.customer.text,
                             payload.customer.id, true, true);
      $('#customer_id').append(opt).trigger('change');
   }
   $('#reference') .val(payload.reference);
   $('#reason')    .val(payload.reason);

   $('#returnLinesBody').empty();
   payload.lines.forEach(l=>newReturnLine({
        variant_id : l.product_variant_id,
        sku        : l.variant.sku,
        qty        : l.qty,
        unit_cost  : l.unit_cost
   }));
   $('#returnModal').modal('show');
};

/* ───────── submit ───────── */
$('#returnForm').on('submit', function(e){
   e.preventDefault();
   const id  = $('#returnId').val();
   const url = id
       ? `/admin/inventory/stock-returns/${id}`
       : "{{ route('admin.inventory.returns.customer.store') }}";
   const data = $(this).serialize() + (id ? '&_method=PUT' : '');

   $.post(url,data)
     .done(r=>{
        $('#returnModal').modal('hide');
        $('#returnTbl').DataTable().ajax.reload(null,false);
        Swal.fire('Saved',r.message,'success');
     })
     .fail(xhr=>{
        let msg = 'Save failed';
        if (xhr.status===422 && xhr.responseJSON?.errors){
           msg = Object.values(xhr.responseJSON.errors)[0][0];
        } else if (xhr.responseJSON?.message){
           msg = xhr.responseJSON.message;
        }
        Swal.fire('Error',msg,'error');
     });
});
</script>
@endpush
