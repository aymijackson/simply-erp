@extends('layouts.master')

@section('content')
<div class="container-fluid">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h4 class="mb-0">Fixed Asset Categories</h4>
      <div class="text-muted small">Define default COA mappings including Gain/Loss on disposal.</div>
    </div>
    @can('finance.fixed_asset_categories.create')
    <button class="btn btn-primary" id="btnAddCat"><i class="fas fa-plus me-1"></i> New Category</button>
    @endcan
  </div>

  <div class="card">
    <div class="card-body">
      <table class="table table-bordered table-striped w-100" id="catsTable">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Code</th>
            <th>Method</th>
            <th>Life (m)</th>
            <th>Active</th>
            <th style="width:160px;">Actions</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>

</div>

<div class="modal fade" id="catModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="catTitle">New Category</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <form id="catForm">
        <div class="modal-body">
          <input type="hidden" id="cat_id">

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Name</label>
              <input class="form-control" id="name" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Code</label>
              <input class="form-control" id="code">
            </div>
            <div class="col-md-3">
              <label class="form-label">Active</label>
              <select class="form-select" id="is_active">
                <option value="1">Yes</option>
                <option value="0">No</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label">Default Asset Account</label>
              <select class="form-select" id="default_asset_account_id">
                <option value="">-- select --</option>
                @foreach($accounts as $a)
                  <option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>
                @endforeach
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label">Default Accum. Depreciation</label>
              <select class="form-select" id="default_accum_depr_account_id">
                <option value="">-- select --</option>
                @foreach($accounts as $a)
                  <option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>
                @endforeach
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label">Default Depreciation Expense</label>
              <select class="form-select" id="default_depr_expense_account_id">
                <option value="">-- select --</option>
                @foreach($accounts as $a)
                  <option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>
                @endforeach
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Default Disposal Gain Account</label>
              <select class="form-select" id="default_disposal_gain_account_id">
                <option value="">-- select --</option>
                @foreach($accounts as $a)
                  <option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>
                @endforeach
              </select>
              <div class="small text-muted">Other Income / Gain on Disposal</div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Default Disposal Loss Account</label>
              <select class="form-select" id="default_disposal_loss_account_id">
                <option value="">-- select --</option>
                @foreach($accounts as $a)
                  <option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>
                @endforeach
              </select>
              <div class="small text-muted">Other Expense / Loss on Disposal</div>
            </div>

            <div class="col-md-4">
              <label class="form-label">Default Method</label>
              <select class="form-select" id="default_depr_method">
                <option value="straight_line">Straight Line</option>
                <option value="declining_balance">Declining Balance</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Default Life (months)</label>
              <input type="number" class="form-control" id="default_useful_life_months" min="1">
            </div>
            <div class="col-md-4">
              <label class="form-label">Default Salvage</label>
              <input type="number" step="0.01" class="form-control" id="default_salvage_value" value="0">
            </div>

            <div class="col-md-12">
              <label class="form-label">Notes</label>
              <textarea class="form-control" id="notes" rows="2"></textarea>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Close</button>
          <button class="btn btn-primary" type="submit"><i class="fas fa-save me-1"></i> Save</button>
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

function openCatNew(){
  $('#catTitle').text('New Category');
  $('#cat_id').val('');
  $('#catForm')[0].reset();
  $('#default_salvage_value').val(0);
  $('#is_active').val(1);
  $('#catModal').modal('show');
}

function openCatEdit(id){
  $.get(`{{ url('admin/finance/fixed-assets/categories') }}/${id}/json`)
    .done(res=>{
      const d = res.data;
      $('#catTitle').text(`Edit Category: ${d.name}`);
      $('#cat_id').val(d.id);
      $('#name').val(d.name);
      $('#code').val(d.code || '');
      $('#is_active').val(d.is_active ? 1 : 0);

      $('#default_asset_account_id').val(d.default_asset_account_id);
      $('#default_accum_depr_account_id').val(d.default_accum_depr_account_id);
      $('#default_depr_expense_account_id').val(d.default_depr_expense_account_id);
      $('#default_disposal_gain_account_id').val(d.default_disposal_gain_account_id);
      $('#default_disposal_loss_account_id').val(d.default_disposal_loss_account_id);

      $('#default_depr_method').val(d.default_depr_method);
      $('#default_useful_life_months').val(d.default_useful_life_months);
      $('#default_salvage_value').val(d.default_salvage_value);
      $('#notes').val(d.notes || '');

      $('#catModal').modal('show');
    })
    .fail(xhr=>Swal.fire('Error', xhr.responseJSON?.message || 'Failed', 'error'));
}

$(function(){
  dt = $('#catsTable').DataTable({
    ajax: "{{ route('admin.finance.fixed_assets.categories.datatable') }}",
    columns:[
      {data:'id'},
      {data:'name'},
      {data:'code'},
      {data:'default_depr_method'},
      {data:'default_useful_life_months'},
      {data:'is_active', render:(d)=> d ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>'},
      {data:null, orderable:false, searchable:false, render:(row)=>{
        let html = '';
        @can('finance.fixed_asset_categories.update')
          html += `<button class="btn btn-sm btn-outline-secondary me-1 btnEdit" data-id="${row.id}"><i class="fas fa-edit"></i></button>`;
        @endcan
        @can('finance.fixed_asset_categories.delete')
          html += `<button class="btn btn-sm btn-outline-danger btnDel" data-id="${row.id}"><i class="fas fa-trash"></i></button>`;
        @endcan
        return html || '-';
      }}
    ]
  });

  @can('finance.fixed_asset_categories.create')
  $('#btnAddCat').on('click', openCatNew);
  @endcan

  $(document).on('click','.btnEdit', function(){ openCatEdit($(this).data('id')); });

  $('#catForm').on('submit', function(e){
    e.preventDefault();
    const id = $('#cat_id').val();
    const payload = {
      name: $('#name').val(),
      code: $('#code').val(),
      is_active: $('#is_active').val(),

      default_asset_account_id: $('#default_asset_account_id').val(),
      default_accum_depr_account_id: $('#default_accum_depr_account_id').val(),
      default_depr_expense_account_id: $('#default_depr_expense_account_id').val(),
      default_disposal_gain_account_id: $('#default_disposal_gain_account_id').val(),
      default_disposal_loss_account_id: $('#default_disposal_loss_account_id').val(),

      default_depr_method: $('#default_depr_method').val(),
      default_useful_life_months: $('#default_useful_life_months').val(),
      default_salvage_value: $('#default_salvage_value').val(),
      notes: $('#notes').val(),
    };

    const url = id ? `{{ url('admin/finance/fixed-assets/categories') }}/${id}` : `{{ route('admin.finance.fixed_assets.categories.store') }}`;
    const method = id ? 'PUT' : 'POST';

    $.ajax({url, method, data: payload})
      .done(res=>{ $('#catModal').modal('hide'); dt.ajax.reload(null,false); Swal.fire('Success', res.message, 'success'); })
      .fail(xhr=>Swal.fire('Error', xhr.responseJSON?.message || 'Failed', 'error'));
  });

  $(document).on('click','.btnDel', function(){
    const id = $(this).data('id');
    Swal.fire({title:'Delete category?', icon:'warning', showCancelButton:true, confirmButtonText:'Delete'})
    .then(r=>{
      if(!r.isConfirmed) return;
      $.ajax({url:`{{ url('admin/finance/fixed-assets/categories') }}/${id}`, method:'DELETE'})
        .done(res=>{ dt.ajax.reload(null,false); Swal.fire('Deleted', res.message, 'success'); })
        .fail(xhr=>Swal.fire('Error', xhr.responseJSON?.message || 'Failed', 'error'));
    });
  });

});
</script>
@endpush