<div class="modal fade" id="bomModal" tabindex="-1" aria-labelledby="bomModalLbl" aria-hidden="true">
 <div class="modal-dialog modal-xl">
  <form id="bomForm" class="modal-content">
   @csrf
   <input type="hidden" id="bomId" name="id">

   <div class="modal-header bg-primary text-white">
     <h5 class="modal-title" id="bomModalLbl">New BOM</h5>
     <button class="btn-close text-white" type="button" data-bs-dismiss="modal"></button>
   </div>

   <div class="modal-body">
     {{-- ----- header ----- --}}
     <div class="row g-3 mb-3">
       <div class="col-md-6">
         <label class="form-label">Finished Product *</label>
         <select id="product_variant_id" name="product_variant_id" class="form-select" required></select>
       </div>
       <div class="col-md-6">
         <label class="form-label">BOM Name *</label>
         <input type="text" id="name" name="name" class="form-control" required>
       </div>
       <div class="col-md-6">
         <label class="form-label">BOM Code *</label>
         <input type="text" id="bom_code" name="bom_code" class="form-control" required>
       </div>
       <div class="col-12">
         <label class="form-label">Description</label>
         <textarea id="description" name="description" rows="2" class="form-control"></textarea>
       </div>
     </div>

     {{-- ----- lines table ----- --}}
     <div class="table-responsive">
       <table class="table table-sm table-bordered align-middle" id="itemTbl">
         <thead class="table-light">
          <tr>
            <th style="width:55%">Component Variant *</th>
            <th style="width:25%" class="text-end">Qty/Parent *</th>
            <th style="width:20%" class="text-center">
               <button type="button" id="addLineBtn" class="btn btn-sm btn-success">
                 <i class="fas fa-plus"></i>
               </button>
            </th>
          </tr>
         </thead>
         <tbody id="itemsBody">@include('production.boms.partials.items-table')</tbody>
       </table>
     </div>
   </div>

   <div class="modal-footer">
     <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
     <button class="btn btn-success" type="submit">Save BOM</button>
   </div>
  </form>
 </div>
</div>

@push('scripts')
<script>
/* ------------ Select2 helpers ------------ */
function initProductSelect(sel){
    sel.select2({
        dropdownParent: $('#bomModal'),
        ajax:{
          url:"{{ route('admin.inventory.stock_issues.fetch_variants') }}",
          dataType:'json', delay:250,
          data: p => ({q:p.term}),
          processResults:d=>({results:d})
        },
        minimumInputLength:2, width:'100%', placeholder:'-- search product --'
    });
}
function initVariantSelect(sel){
    sel.select2({
        dropdownParent: $('#bomModal'),
        ajax:{
          url:"{{ route('admin.inventory.stock_issues.fetch_variants') }}",
          dataType:'json', delay:250,
          data: p => ({q:p.term}),
          processResults:d=>({results:d})
        },
        minimumInputLength:2, width:'100%', placeholder:'-- component variant --'
    });
}

/* ------------ dynamic line row ------------ */
function newLine(rd={}){
   let k = Date.now();
   let tr = $(`
     <tr data-key="${k}">
       <td><select name="items[${k}][product_variant_id]" class="form-select variant-sel" required></select></td>
       <td><input  name="items[${k}][qty_per_parent]" type="number" step="0.0001" min="0.0001"
                   class="form-control text-end" value="1" required></td>
       <td class="text-center">
           <button type="button" class="btn btn-sm btn-link text-danger rmLine">
             <i class="fas fa-times"></i>
           </button>
       </td>
     </tr>`);

   $('#itemsBody').append(tr);
   initVariantSelect(tr.find('.variant-sel'));

   if(rd.product_variant_id){
       const opt = new Option(rd.sku, rd.product_variant_id, true, true);
       tr.find('.variant-sel').append(opt).trigger('change');
       tr.find('input').val(rd.qty_per_parent);
   }
}

/* ------------ open / reset modal ------------ */
function openBomModal(data=null){
   $('#bomForm')[0].reset();
   $('#bomId').val('');
   $('#itemsBody').empty();
   initProductSelect($('#product_variant_id').empty());

   if(data){           // -------- EDIT ----------
      $('#bomModalLbl').text('Edit BOM');
      $('#bomId').val(data.id);
      // pre-select product
      const pOpt = new Option(data.product_name, data.product_variant_id, true, true);
      $('#product_variant_id').append(pOpt).trigger('change');
      $('#name').val(data.name);
      $('#description').val(data.description);
      data.items.forEach(i=>newLine(i));
   }else{              // -------- CREATE -------
      $('#bomModalLbl').text('New BOM');
      newLine();                               // at least one blank row
   }
   bootstrap.Modal.getOrCreateInstance('#bomModal').show();
}

/* ------------ row add / remove ------------ */
$('#addLineBtn').on('click', ()=>newLine());
$('#itemsBody').on('click','.rmLine',function(){
    $(this).closest('tr').remove();
});

/* ------------ submit ------------ */
$('#bomForm').submit(function(e){
   e.preventDefault();
   const id  = $('#bomId').val();
   const url = id ? `/admin/production/boms/${id}` 
                  : `{{ route('admin.production.boms.store') }}`;
   const data = $(this).serialize() + (id ? '&_method=PUT' : '');

   $.post(url, data)
     .done(r=>{
        bootstrap.Modal.getInstance('#bomModal').hide();
        $('#bomTbl').DataTable().ajax.reload(null,false);
        Swal.fire('Success', r.message,'success');
     })
     .fail(x=>{
        let m = x.responseJSON?.message || 'Save failed';
        if(x.status===422 && x.responseJSON?.errors){
            m = Object.values(x.responseJSON.errors)[0][0];
        }
        Swal.fire('Error',m,'error');
     });
});
</script>
@endpush
