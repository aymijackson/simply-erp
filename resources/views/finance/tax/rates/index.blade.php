@extends('layouts.master')

@section('content')
<div class="container-fluid">
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div>
      <h4 class="mb-0">Tax Rates</h4>
      <div class="text-muted small">Manage VAT and Sales Tax percentages with effective dates.</div>
    </div>

    @can('finance.tax.rates.create')
      <button class="btn btn-primary" id="btnAdd">
        <i class="fas fa-plus me-1"></i> New Tax Rate
      </button>
    @endcan
  </div>

  <div class="card">
    <div class="card-body table-responsive">
      <table class="table table-bordered table-striped w-100" id="ratesTable">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Code</th>
            <th>Type</th>
            <th class="text-end">Rate %</th>
            <th>Effective</th>
            <th>Compound</th>
            <th>Active</th>
            <th style="width:180px;">Actions</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>

<div class="modal fade" id="rateModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><span id="modalTitle">New Tax Rate</span></h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <form id="rateForm">
        <div class="modal-body">
          <input type="hidden" id="row_id">

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Name</label>
              <input class="form-control" id="name" required maxlength="120">
            </div>

            <div class="col-md-3">
              <label class="form-label">Code</label>
              <input class="form-control" id="code" required maxlength="30" placeholder="VAT20">
            </div>

            <div class="col-md-3">
              <label class="form-label">Rate %</label>
              <input type="number" step="0.0001" class="form-control" id="rate" required>
            </div>

            <div class="col-md-4">
              <label class="form-label">Tax Type</label>
              <select class="form-select" id="tax_type" required>
                <option value="vat">VAT</option>
                <option value="sales_tax">Sales Tax</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label">Effective From</label>
              <input type="date" class="form-control" id="effective_from">
            </div>

            <div class="col-md-4">
              <label class="form-label">Effective To</label>
              <input type="date" class="form-control" id="effective_to">
            </div>

            <div class="col-md-6">
              <label class="form-label">Compound?</label>
              <select class="form-select" id="is_compound">
                <option value="0">No</option>
                <option value="1">Yes</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Active?</label>
              <select class="form-select" id="is_active">
                <option value="1">Yes</option>
                <option value="0">No</option>
              </select>
            </div>
          </div>

          <div class="alert alert-info small mt-3 mb-0">
            Recommended examples: VAT20, VAT5, VAT0, SALES7.5. Use codes that are short and stable.
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

function effRange(row){
  const from = row.effective_from || '-';
  const to = row.effective_to || '-';
  return `${from} → ${to}`;
}

function openNewModal(){
  $('#modalTitle').text('New Tax Rate');
  $('#row_id').val('');
  $('#rateForm')[0].reset();
  $('#is_compound').val('0');
  $('#is_active').val('1');
  $('#rateModal').modal('show');
}

function openEditModal(id){
  $.get(`{{ url('admin/finance/tax/rates') }}/${id}/json`)
    .done(res=>{
      const d = res.data;
      $('#modalTitle').text(`Edit Tax Rate: ${d.code}`);
      $('#row_id').val(d.id);
      $('#name').val(d.name);
      $('#code').val(d.code);
      $('#rate').val(d.rate);
      $('#tax_type').val(d.tax_type);
      $('#effective_from').val(d.effective_from || '');
      $('#effective_to').val(d.effective_to || '');
      $('#is_compound').val(d.is_compound ? '1' : '0');
      $('#is_active').val(d.is_active ? '1' : '0');
      $('#rateModal').modal('show');
    })
    .fail(xhr=>Swal.fire('Error', xhr.responseJSON?.message || 'Failed to load', 'error'));
}

$(function(){
  dt = $('#ratesTable').DataTable({
    ajax: "{{ route('admin.finance.tax.rates.datatable') }}",
    columns: [
      {data:'id'},
      {data:'name'},
      {data:'code'},
      {data:'tax_type'},
      {data:'rate', className:'text-end', render:(d)=>Number(d || 0).toFixed(4)},
      {data:null, render:(row)=>effRange(row)},
      {data:'is_compound', render:(d)=>yesNo(d)},
      {data:'is_active', render:(d)=>yesNo(d)},
      {data:null, orderable:false, searchable:false, render:(row)=>{
        let html = '';
        @can('finance.tax.rates.update')
          html += `<button class="btn btn-sm btn-outline-secondary me-1 btnEdit" data-id="${row.id}">
                    <i class="fas fa-edit"></i>
                  </button>`;
        @endcan
        @can('finance.tax.rates.delete')
          html += `<button class="btn btn-sm btn-outline-danger btnDel" data-id="${row.id}">
                    <i class="fas fa-trash"></i>
                  </button>`;
        @endcan
        return html || '-';
      }}
    ]
  });

  @can('finance.tax.rates.create')
  $('#btnAdd').on('click', openNewModal);
  @endcan

  $(document).on('click', '.btnEdit', function(){
    openEditModal($(this).data('id'));
  });

  $('#rateForm').on('submit', function(e){
    e.preventDefault();

    const id = $('#row_id').val();
    const payload = {
      name: $('#name').val(),
      code: $('#code').val(),
      rate: $('#rate').val(),
      tax_type: $('#tax_type').val(),
      effective_from: $('#effective_from').val(),
      effective_to: $('#effective_to').val(),
      is_compound: $('#is_compound').val(),
      is_active: $('#is_active').val(),
    };

    const url = id
      ? `{{ url('admin/finance/tax/rates') }}/${id}`
      : `{{ route('admin.finance.tax.rates.store') }}`;

    const method = id ? 'PUT' : 'POST';

    $.ajax({url, method, data: payload})
      .done(res=>{
        $('#rateModal').modal('hide');
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
      title:'Delete tax rate?',
      text:'This will soft delete the tax rate.',
      icon:'warning',
      showCancelButton:true,
      confirmButtonText:'Delete'
    }).then(r=>{
      if(!r.isConfirmed) return;

      $.ajax({
        url:`{{ url('admin/finance/tax/rates') }}/${id}`,
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