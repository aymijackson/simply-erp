@extends('layouts.master')
@section('title', 'Purchase Requisitions')

@section('content')
<div class="container-fluid">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 text-primary mb-0">Purchase Requisitions</h1>
    <div>
      <button class="btn btn-primary" id="addBtn">
        <i class="fas fa-plus me-1"></i> New Requisition
      </button>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <table id="prTbl" class="table table-bordered w-100">
        <thead class="table-light">
        <tr>
          <th><input type="checkbox" id="checkAll"></th>
          <th>Req No</th>
          <th>Requested By</th>
          <th>Needed By</th>
          <th>Status</th>
          <th class="text-end">Est. Total</th>
          <th class="text-end">Actions</th>
        </tr>
        </thead>
      </table>
    </div>
  </div>
</div>

{{-- Modal --}}
<div class="modal fade" id="prModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <form id="prForm" class="modal-content">
      @csrf
      <input type="hidden" id="pr_id">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">New Requisition</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Requested By *</label>
            <select id="requested_by" name="requested_by" class="form-select" required></select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Needed By *</label>
            <input type="date" id="needed_by_date" name="needed_by_date" class="form-control" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Status *</label>
            <select id="status" name="status" class="form-select" required>
              <option value="draft">Draft</option>
              <option value="submitted">Submitted</option>
              <option value="finance_approved">Finance Approved</option>
              <option value="rejected">Rejected</option>
              <option value="closed">Closed</option>
            </select>
          </div>
          <div class="col-md-12">
            <label class="form-label">Purpose</label>
            <textarea id="purpose" name="purpose" rows="2" class="form-control"></textarea>
          </div>
        </div>

        <hr>

        <div class="d-flex justify-content-between align-items-center mb-2">
          <h6 class="mb-0">Lines</h6>
          <button type="button" id="addLine" class="btn btn-sm btn-success">
            <i class="fas fa-plus"></i> Add Line
          </button>
        </div>

        <div class="table-responsive">
          <table class="table table-sm table-bordered align-middle" id="linesTbl">
            <thead class="table-light">
            <tr>
              <th style="width:45%">Item</th>
              <th style="width:15%" class="text-end">Qty</th>
              <th style="width:20%" class="text-end">Est. Unit Cost</th>
              <th style="width:10%" class="text-end">Est. Ext.</th>
              <th style="width:10%" class="text-center">#</th>
            </tr>
            </thead>
            <tbody></tbody>
            <tfoot>
              <tr>
                <th colspan="3" class="text-end">Estimated Total</th>
                <th class="text-end" id="estTotal">0.00</th>
                <th></th>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-success" id="saveBtn">Save</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
const DT_URL   = "{{ route('admin.procurement.requisitions.datatable') }}";
const STORE    = "{{ route('admin.procurement.requisitions.store') }}";
const SHOW     = id => "{{ route('admin.procurement.requisitions.show',':id') }}".replace(':id', id);
const UPDATE   = id => "{{ route('admin.procurement.requisitions.update',':id') }}".replace(':id', id);
const DESTROY  = id => "{{ route('admin.procurement.requisitions.destroy',':id') }}".replace(':id', id);
const SUBMIT   = id => "{{ route('admin.procurement.requisitions.submit',':id') }}".replace(':id', id);
const APPROVE  = id => "{{ route('admin.procurement.requisitions.approve',':id') }}".replace(':id', id);
const REJECT   = id => "{{ route('admin.procurement.requisitions.reject',':id') }}".replace(':id', id);

// Select2 sources
const S2_USERS    = "{{ route('admin.procurement.select2.users') }}";
const S2_VARIANTS = "{{ route('admin.procurement.select2.variants') }}";

function lineTpl(idx, prefill={}) {
  return `
    <tr data-key="${idx}">
      <td>
        <select class="form-select s2-variant" name="lines[${idx}][product_variant_id]" required></select>
      </td>
      <td class="text-end">
        <input type="number" min="1" step="1" class="form-control text-end qty" name="lines[${idx}][qty]" value="${prefill.qty ?? 1}">
      </td>
      <td class="text-end">
        <input type="number" min="0" step="0.01" class="form-control text-end ucost" name="lines[${idx}][est_unit_cost]" value="${prefill.est_unit_cost ?? ''}">
      </td>
      <td class="text-end ext">0.00</td>
      <td class="text-center">
        <button type="button" class="btn btn-sm btn-danger rm-line"><i class="fas fa-trash"></i></button>
      </td>
    </tr>`;
}

function rebuildExt() {
  let total = 0;
  $('#linesTbl tbody tr').each(function(){
    const qty  = parseFloat($(this).find('.qty').val() || '0');
    const cost = parseFloat($(this).find('.ucost').val() || '0');
    const ext  = qty * cost;
    total += ext;
    $(this).find('.ext').text(ext.toLocaleString(undefined,{minimumFractionDigits:2, maximumFractionDigits:2}));
  });
  $('#estTotal').text(total.toLocaleString(undefined,{minimumFractionDigits:2, maximumFractionDigits:2}));
}

function initVariantSelect($sel, pre) {
  $sel.select2({
    ajax: { url: S2_VARIANTS, dataType:'json', delay:250, data:p=>({q:p.term}), processResults:d=>({results:d}) },
    width:'100%', dropdownParent: $('#prModal'), placeholder:'-- select item --', minimumInputLength:0
  });
  if (pre?.id && pre?.text) {
    const opt = new Option(pre.text, pre.id, true, true);
    $sel.append(opt).trigger('change');
  }
}

(function(){
  const modal = new bootstrap.Modal('#prModal');

  // DataTable
  const tbl = $('#prTbl').DataTable({
    serverSide:true, responsive:true, ajax:{ url: DT_URL },
    columns:[
      {data:'checkbox', orderable:false, searchable:false},
      {data:'req_no'},
      {data:'requested_by_name'},
      {data:'needed_by', render:d=> d ? new Date(d).toLocaleDateString() : '—'},
      {data:'status', render:s=>{
        const map = {draft:'secondary', submitted:'info', finance_approved:'success', rejected:'danger', closed:'dark'};
        return `<span class="badge bg-${map[s]||'secondary'} text-uppercase">${s}</span>`
      }},
      {data:'total_est_cost', className:'text-end', render:v=> (parseFloat(v||0)).toLocaleString(undefined,{minimumFractionDigits:2})},
      {data:'actions', orderable:false, searchable:false, className:'text-end'}
    ],
    drawCallback(){
      // delegated events still recommended, but this helps in non-responsive tables too
    }
  });

  // Delegated actions (works in responsive child rows)
  $('#prTbl').on('click', '.edit-pr', function(){
    const id = $(this).data('id');
    $.get(SHOW(id)).done(fillModal).fail(()=>Swal.fire('Error','Load failed','error'));
  });
  $('#prTbl').on('click', '.del-pr', function(){
    const id = $(this).data('id');
    Swal.fire({title:'Delete requisition?', icon:'warning', showCancelButton:true})
      .then(r=>{
        if(!r.isConfirmed) return;
        $.ajax({url:DESTROY(id), type:'DELETE', data:{_token:'{{ csrf_token() }}'}})
          .done(()=>{ tbl.ajax.reload(null,false); Swal.fire('Deleted','','success'); })
          .fail(x=> Swal.fire('Error', x.responseJSON?.message || 'Delete failed','error'));
      });
  });
  $('#prTbl').on('click', '.submit-pr', function(){
    const id = $(this).data('id');
    $.post(SUBMIT(id), {_token:'{{ csrf_token() }}'}).done(()=>tbl.ajax.reload(null,false));
  });
  $('#prTbl').on('click', '.approve-pr', function(){
    const id = $(this).data('id');
    $.post(APPROVE(id), {_token:'{{ csrf_token() }}'}).done(()=>tbl.ajax.reload(null,false));
  });
  $('#prTbl').on('click', '.reject-pr', function(){
    const id = $(this).data('id');
    $.post(REJECT(id), {_token:'{{ csrf_token() }}'}).done(()=>tbl.ajax.reload(null,false));
  });

  // Modal open
  $('#addBtn').on('click', ()=>{
    resetForm();
    $('.modal-title').text('New Requisition');
    modal.show();
  });

  // Users select2
  $('#requested_by').select2({
    ajax:{ url:S2_USERS, dataType:'json', delay:250, data:p=>({q:p.term}), processResults:d=>({results:d}) },
    width:'100%', dropdownParent: $('#prModal'), placeholder:'-- select user --'
  });

  // Lines
  $('#addLine').on('click', ()=>{
    const idx = Date.now();
    $('#linesTbl tbody').append(lineTpl(idx));
    const $row = $('#linesTbl tbody tr:last');
    initVariantSelect($row.find('.s2-variant'));
  });
  $('#linesTbl').on('click', '.rm-line', function(){ $(this).closest('tr').remove(); rebuildExt(); });
  $('#linesTbl').on('input', '.qty,.ucost', rebuildExt);

  // Save
  $('#saveBtn').on('click', function(e){
    e.preventDefault();
    const id  = $('#pr_id').val();
    const url = id ? UPDATE(id) : STORE;

    const payload = {
      requested_by: $('#requested_by').val(),
      needed_by_date: $('#needed_by_date').val(),
      status: $('#status').val(),
      purpose: $('#purpose').val(),
      lines: []
    };
    $('#linesTbl tbody tr').each(function(){
      const variantId = $(this).find('.s2-variant').val();
      const qty  = $(this).find('.qty').val();
      const cost = $(this).find('.ucost').val();
      if (variantId && qty) payload.lines.push({product_variant_id: variantId, qty, est_unit_cost: cost || null});
    });

    $.ajax({url, type: id?'PUT':'POST', data: payload})
      .done(r=>{ modal.hide(); $('#prTbl').DataTable().ajax.reload(null,false); Swal.fire('Saved', r.message || '', 'success'); })
      .fail(x=> Swal.fire('Error', x.responseJSON?.message || 'Save failed','error'));
  });

  function resetForm(){
    $('#prForm')[0].reset();
    $('#pr_id').val('');
    $('#requested_by').val(null).trigger('change');
    $('#linesTbl tbody').empty();
    $('#estTotal').text('0.00');
  }

  function fillModal(pr){
    resetForm();
    $('.modal-title').text('Edit Requisition');
    $('#pr_id').val(pr.id);
    if (pr.requested_by && pr.requested_by_name) {
      const opt = new Option(pr.requested_by_name, pr.requested_by, true, true);
      $('#requested_by').append(opt).trigger('change');
    }
    $('#needed_by_date').val(pr.needed_by_date || '');
    $('#status').val(pr.status || 'draft');
    $('#purpose').val(pr.purpose || '');

    (pr.lines || []).forEach(l=>{
      const idx = Date.now() + Math.random();
      $('#linesTbl tbody').append(lineTpl(idx, {qty:l.qty, est_unit_cost:l.est_unit_cost}));
      const $row = $('#linesTbl tbody tr:last');
      initVariantSelect($row.find('.s2-variant'),
        l.variant ? {id:l.product_variant_id, text:`${l.variant.sku} — ${l.variant.product_name}`} : null
      );
    });
    rebuildExt();
    modal.show();
  }
})();
</script>
@endpush
