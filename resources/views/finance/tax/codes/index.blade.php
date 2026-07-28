@extends('layouts.master')

@section('content')
<div class="container-fluid">
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div>
      <h4 class="mb-0">Tax Codes</h4>
      <div class="text-muted small">Map business tax behaviour like Standard, Zero, Exempt, Out of Scope, Reverse Charge.</div>
    </div>

    @can('finance.tax.codes.create')
      <button class="btn btn-primary" id="btnAdd">
        <i class="fas fa-plus me-1"></i> New Tax Code
      </button>
    @endcan
  </div>

  <div class="card">
    <div class="card-body table-responsive">
      <table class="table table-bordered table-striped w-100" id="codesTable">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Code</th>
            <th>Type</th>
            <th>Rate</th>
            <th>Flags</th>
            <th>Active</th>
            <th style="width:180px;">Actions</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>

<div class="modal fade" id="codeModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><span id="modalTitle">New Tax Code</span></h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <form id="codeForm">
        <div class="modal-body">
          <input type="hidden" id="row_id">

          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Name</label>
              <input class="form-control" id="name" required maxlength="120">
            </div>

            <div class="col-md-2">
              <label class="form-label">Code</label>
              <input class="form-control" id="code" required maxlength="20" placeholder="S">
            </div>

            <div class="col-md-3">
              <label class="form-label">Tax Type</label>
              <select class="form-select" id="tax_type" required>
                <option value="vat">VAT</option>
                <option value="sales_tax">Sales Tax</option>
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Rate</label>
              <select class="form-select" id="rate_id">
                <option value="">-- none / zero behaviour --</option>
                @foreach($rates as $r)
                  <option value="{{ $r->id }}">
                    {{ $r->code }} - {{ $r->name }} ({{ number_format((float)$r->rate, 4) }}%)
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label">Reverse Charge?</label>
              <select class="form-select" id="is_reverse_charge">
                <option value="0">No</option>
                <option value="1">Yes</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label">Exempt?</label>
              <select class="form-select" id="is_exempt">
                <option value="0">No</option>
                <option value="1">Yes</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label">Out of Scope?</label>
              <select class="form-select" id="is_out_of_scope">
                <option value="0">No</option>
                <option value="1">Yes</option>
              </select>
            </div>

            <div class="col-md-8">
              <label class="form-label">Notes</label>
              <input class="form-control" id="notes" maxlength="255">
            </div>

            <div class="col-md-4">
              <label class="form-label">Active?</label>
              <select class="form-select" id="is_active">
                <option value="1">Yes</option>
                <option value="0">No</option>
              </select>
            </div>
          </div>

          <div class="alert alert-info small mt-3 mb-0">
            Examples:
            <b>S</b> Standard Rated,
            <b>Z</b> Zero Rated,
            <b>E</b> Exempt,
            <b>OOS</b> Out of Scope,
            <b>RC</b> Reverse Charge.
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-1"></i> Save
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
$.ajaxSetup({headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}});

let dt;

function yesNo(v){
  return Number(v) === 1 ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>';
}

function renderFlags(row){
  let flags = [];
  if(Number(row.is_reverse_charge) === 1) flags.push('<span class="badge bg-warning text-dark me-1">RC</span>');
  if(Number(row.is_exempt) === 1) flags.push('<span class="badge bg-info text-dark me-1">Exempt</span>');
  if(Number(row.is_out_of_scope) === 1) flags.push('<span class="badge bg-dark me-1">OOS</span>');
  return flags.length ? flags.join(' ') : '<span class="text-muted">None</span>';
}

function renderRate(row){
  if(!row.rate_id) return '<span class="text-muted">-</span>';
  return `${row.rate_code || ''} (${Number(row.rate || 0).toFixed(4)}%)`;
}

function openNewModal(){
  $('#modalTitle').text('New Tax Code');
  $('#row_id').val('');
  $('#codeForm')[0].reset();
  $('#is_reverse_charge').val('0');
  $('#is_exempt').val('0');
  $('#is_out_of_scope').val('0');
  $('#is_active').val('1');
  $('#codeModal').modal('show');
}

function openEditModal(id){
  $.get(`{{ url('admin/finance/tax/codes') }}/${id}/json`)
    .done(res=>{
      const d = res.data;
      $('#modalTitle').text(`Edit Tax Code: ${d.code}`);
      $('#row_id').val(d.id);
      $('#name').val(d.name);
      $('#code').val(d.code);
      $('#tax_type').val(d.tax_type);
      $('#rate_id').val(d.rate_id || '');
      $('#is_reverse_charge').val(d.is_reverse_charge ? '1' : '0');
      $('#is_exempt').val(d.is_exempt ? '1' : '0');
      $('#is_out_of_scope').val(d.is_out_of_scope ? '1' : '0');
      $('#notes').val(d.notes || '');
      $('#is_active').val(d.is_active ? '1' : '0');
      $('#codeModal').modal('show');
    })
    .fail(xhr=>Swal.fire('Error', xhr.responseJSON?.message || 'Failed to load', 'error'));
}

$(function(){
  dt = $('#codesTable').DataTable({
    ajax: "{{ route('admin.finance.tax.codes.datatable') }}",
    columns: [
      {data:'id'},
      {data:'name'},
      {data:'code'},
      {data:'tax_type'},
      {data:null, render:(row)=>renderRate(row)},
      {data:null, render:(row)=>renderFlags(row)},
      {data:'is_active', render:(d)=>yesNo(d)},
      {data:null, orderable:false, searchable:false, render:(row)=>{
        let html = '';
        @can('finance.tax.codes.update')
          html += `<button class="btn btn-sm btn-outline-secondary me-1 btnEdit" data-id="${row.id}">
                    <i class="fas fa-edit"></i>
                  </button>`;
        @endcan
        @can('finance.tax.codes.delete')
          html += `<button class="btn btn-sm btn-outline-danger btnDel" data-id="${row.id}">
                    <i class="fas fa-trash"></i>
                  </button>`;
        @endcan
        return html || '-';
      }}
    ]
  });

  @can('finance.tax.codes.create')
  $('#btnAdd').on('click', openNewModal);
  @endcan

  $(document).on('click', '.btnEdit', function(){
    openEditModal($(this).data('id'));
  });

  $('#codeForm').on('submit', function(e){
    e.preventDefault();

    const id = $('#row_id').val();
    const payload = {
      name: $('#name').val(),
      code: $('#code').val(),
      tax_type: $('#tax_type').val(),
      rate_id: $('#rate_id').val(),
      is_reverse_charge: $('#is_reverse_charge').val(),
      is_exempt: $('#is_exempt').val(),
      is_out_of_scope: $('#is_out_of_scope').val(),
      notes: $('#notes').val(),
      is_active: $('#is_active').val(),
    };

    const url = id
      ? `{{ url('admin/finance/tax/codes') }}/${id}`
      : `{{ route('admin.finance.tax.codes.store') }}`;

    const method = id ? 'PUT' : 'POST';

    $.ajax({url, method, data: payload})
      .done(res=>{
        $('#codeModal').modal('hide');
        dt.ajax.reload(null,false);
        Swal.fire('Success', res.message || 'Saved', 'success');
      })
      .fail(xhr=>{
        Swal.fire('Error', xhr.responseJSON?.message || 'Failed', 'error');
      });
  });

  $(document).on('click', '.btnDel', function(){
    const id = $(this).data('id');

    Swal.fire({
      title:'Delete tax code?',
      text:'This will soft delete the tax code.',
      icon:'warning',
      showCancelButton:true,
      confirmButtonText:'Delete'
    }).then(r=>{
      if(!r.isConfirmed) return;

      $.ajax({
        url:`{{ url('admin/finance/tax/codes') }}/${id}`,
        method:'DELETE'
      })
      .done(res=>{
        dt.ajax.reload(null,false);
        Swal.fire('Deleted', res.message || 'Deleted', 'success');
      })
      .fail(xhr=>{
        Swal.fire('Error', xhr.responseJSON?.message || 'Delete failed', 'error');
      });
    });
  });

});
</script>
@endpush