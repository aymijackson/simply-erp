@extends('layouts.master')
@section('title','Customer Returns')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h3 text-primary"><i class="fas fa-undo me-1"></i> Customer Returns</h1>
      <button id="addBtn" class="btn btn-primary"><i class="fas fa-plus me-1"></i> New Return</button>
  </div>

  <div class="card shadow-sm">
     <div class="card-body">
       <table id="retTbl" class="table table-bordered w-100">
          <thead class="table-light">
            <tr>
              <th>No</th><th>Store</th><th>Customer</th>
              <th>Status</th><th>Posted at</th><th class="text-end">Actions</th>
            </tr>
          </thead>
       </table>
     </div>
  </div>
</div>

{{-- include modal --}}
@include('inventory.returns.customer.partials.modal', ['stores'=>$stores])

@endsection

@push('scripts')
{{-- DT + Select2 already loaded in master --}}
<script>
$.ajaxSetup({headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}});

/* -------------- DataTable -------------- */
const tbl = $('#retTbl').DataTable({
   serverSide:true, responsive:true,
   ajax:"{{ route('admin.inventory.returns.customer.datatable') }}",
   columns:[
     {data:'id'},
     {data:'store.name',  defaultContent:'-'},
     {data:'customer',    defaultContent:'-'},
     {data:'status_badge', orderable:false, searchable:false},
     {data:'posted_at',   defaultContent:'-'},
     {data:'actions',     orderable:false, searchable:false, className:'text-end'}
   ],
   drawCallback(){
      $('#retTbl tbody').off('click','.edit-btn').on('click','.edit-btn', function(){
          openModal( $(this).data('json') );
      });
      $('#retTbl tbody').off('click','.approve-btn').on('click','.approve-btn', doApprove);
      $('#retTbl tbody').off('click','.post-btn').on('click','.post-btn',    doPost);
   }
});

/* ---------------------------------------------------------- *
 *  Shared helpers                                             *
 * ---------------------------------------------------------- */
const modal      = $('#returnModal');
const linesBody  = $('#linesBody');
const variantUrl = "{{ route('admin.inventory.stock_issues.fetch_variants') }}";

/* Build one <tr> row, wire-up Select2, then (optionally) pre-fill -------- */
function newLine(selected = {}) {

    const idx = Date.now();                 // unique key for array-style inputs

    const $tr = $(`
        <tr data-key="${idx}">
            <td>
                <select name="lines[${idx}][product_variant_id]"
                        class="form-select variant-select" required></select>
            </td>
            <td>
                <input type="number" name="lines[${idx}][qty]"
                       class="form-control text-end" min="0.001" step="0.001" required>
            </td>
            <td>
                <input type="number" name="lines[${idx}][unit_cost]"
                       class="form-control text-end" min="0" step="0.01">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-link text-danger remLineBtn">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>`);

    linesBody.append($tr);

    /* Select2 remote search */
    $tr.find('.variant-select').select2({
        dropdownParent : modal,
        ajax           : {
            url      : variantUrl,
            dataType : 'json',
            delay    : 250,
            data     : params => ({ q : params.term }),
            processResults : data => ({ results : data })
        },
        placeholder        : '-- choose variant --',
        minimumInputLength : 2,
        width              : '100%'
    });

    /* Pre-fill when editing --------------------------------------------- */
    if (selected.variant_id) {
        const opt = new Option(selected.text, selected.variant_id, true, true);
        $tr.find('.variant-select').append(opt).trigger('change');
        $tr.find('[name$="[qty]"]').val(selected.qty);
        $tr.find('[name$="[unit_cost]"]').val(selected.unit_cost ?? '');
    }
}

/* remove line */
linesBody.on('click', '.remLineBtn', function(){
   $(this).closest('tr').remove();
});

/* ---------------------------------------------------------- *
 *  Modal open / reset                                         *
 * ---------------------------------------------------------- */
function resetForm() {
    $('#returnForm')[0].reset();
    $('#returnId').val('');
    $('#customer_id').val(null).trigger('change');
    linesBody.empty();
}

function openModal(ret = null) {

    resetForm();                      // always start fresh

    if (ret) {                        // -------- EDIT ----------
        $('#returnModalLabel').text('Edit Customer Return');
        $('#returnId').val(ret.id);

        $('#entry_date').val(ret.entry_date);
        $('#store_id')  .val(ret.from_store_id);
        $('#reference') .val(ret.reference ?? '');
        $('#reason')    .val(ret.reason ?? '');

        /* customer pre-select */
        if (ret.customer) {
            const opt = new Option(ret.customer.customer_name,
                                    ret.customer_id, true, true);
            $('#customer_id').append(opt).trigger('change');
        }

        /* lines */
        ret.lines.forEach(l => newLine({
            variant_id  : l.product_variant_id,
            text        : l.product_variant.sku,      // what will be shown in the box
            qty         : l.qty,
            unit_cost   : l.unit_cost
        }));

    } else {                          // -------- NEW ----------
        $('#returnModalLabel').text('New Customer Return');
        $('#entry_date').val(new Date().toISOString().slice(0,10));
        newLine();                    // at least one blank line
    }

    modal.modal('show');
}

/* open from “New Return” toolbar btn */
$('#addBtn').on('click', () => openModal());

/* ---------------------------------------------------------- *
 *  Form submit (create / update)                              *
 * ---------------------------------------------------------- */
$('#returnForm').on('submit', function (e) {
    e.preventDefault();

    const id  = $('#returnId').val();
    const url = id
        ? `/admin/inventory/returns/${id}`          // PUT existing
        : `{{ route('admin.inventory.returns.customer.store') }}`; // POST new

    const payload = $(this).serialize() + (id ? '&_method=PUT' : '');

    $.post(url, payload)
      .done(res => {
          modal.modal('hide');
          tbl.ajax.reload(null, false);
          Swal.fire('Success', res.message || 'Saved', 'success');
      })
      .fail(xhr => {
          // extract first validation / error message
          let msg = 'Save failed';
          if (xhr.status === 422 && xhr.responseJSON?.errors) {
              msg = Object.values(xhr.responseJSON.errors)[0][0];
          } else if (xhr.responseJSON?.message) {
              msg = xhr.responseJSON.message;
          }
          Swal.fire('Error', msg, 'error');
      });
});

/* approve & post buttons */
function doApprove(){
   const id=$(this).data('id');
   Swal.fire({title:'Approve?',icon:'question',showCancelButton:true})
     .then(r=>{
        if(r.isConfirmed){
           $.post(`/admin/inventory/returns/${id}/approve`)
            .done(()=>{tbl.ajax.reload(null,false); Swal.fire('Approved','','success');});
        }
     });
}
function doPost(){
   const id=$(this).data('id');
   Swal.fire({title:'Post?',icon:'question',showCancelButton:true})
     .then(r=>{
        if(r.isConfirmed){
           $.post(`/admin/inventory/returns/${id}/post`)
            .done(()=>{tbl.ajax.reload(null,false); Swal.fire('Posted','','success');});
        }
     });
}

/* ---------- Select2 helpers ---------- */
$('#customer_id').select2({
   ajax:{url:"{{ route('admin.crm.customers.select2') }}", dataType:'json',
        delay:250, data:params=>({q:params.term}),
        processResults:data=>({results:data})},
   minimumInputLength:2, placeholder:'-- choose customer --',
   dropdownParent:$('#returnModal'), width:'100%'
});
</script>
@endpush
