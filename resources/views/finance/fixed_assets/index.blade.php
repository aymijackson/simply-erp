@extends('layouts.master')

@section('content')
<div class="container-fluid">

  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <div>
      <h4 class="mb-0">Fixed Assets Register</h4>
      <div class="text-muted small">Create assets, activate them, and manage acquisition/disposal via transactions.</div>
    </div>
    <div class="d-flex gap-2">
      @can('finance.fixed_assets.create')
      <button class="btn btn-primary" id="btnAdd">
        <i class="fas fa-plus me-1"></i> New Asset
      </button>
      @endcan
      @can('finance.fixed_asset_categories.view')
      <a class="btn btn-outline-secondary" href="{{ route('admin.finance.fixed_assets.categories.index') }}">
        <i class="fas fa-tags me-1"></i> Categories
      </a>
      @endcan
      @can('finance.fixed_asset_depreciation.view')
      <a class="btn btn-outline-secondary" href="{{ route('admin.finance.fixed_assets.depreciation.index') }}">
        <i class="fas fa-chart-line me-1"></i> Depreciation
      </a>
      @endcan
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-striped w-100" id="assetsTable">
          <thead>
            <tr>
              <th>ID</th>
              <th>Asset Code</th>
              <th>Name</th>
              <th>Category</th>
              <th>Purchase Date</th>
              <th>In Service</th>
              <th>Cost</th>
              <th>Status</th>
              <th style="width:190px;">Actions</th>
            </tr>
          </thead>
        </table>
      </div>
    </div>
  </div>

</div>

{{-- Modal --}}
<div class="modal fade" id="assetModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="assetModalTitle">New Asset</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <form id="assetForm">
        <div class="modal-body">
          <input type="hidden" id="asset_id">

          <div class="row g-3">

            <div class="col-md-4">
              <label class="form-label">Category <span class="text-danger">*</span></label>
              <select class="form-select" id="category_id" required>
                <option value="">-- Select --</option>
                @foreach($categories as $c)
                  <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label">Asset Code <span class="text-danger">*</span></label>
              <input class="form-control" id="asset_code" maxlength="60" required>
            </div>

            <div class="col-md-4">
              <label class="form-label">Asset Name <span class="text-danger">*</span></label>
              <input class="form-control" id="name" maxlength="200" required>
            </div>

            <div class="col-md-12">
              <label class="form-label">Description</label>
              <textarea class="form-control" id="description" rows="2"></textarea>
            </div>

            <div class="col-md-3">
              <label class="form-label">Purchase Date</label>
              <input type="date" class="form-control" id="purchase_date" required>
            </div>

            <div class="col-md-3">
              <label class="form-label">In Service Date</label>
              <input type="date" class="form-control" id="in_service_date" required>
            </div>

            <div class="col-md-3">
              <label class="form-label">Purchase Cost</label>
              <input type="number" step="0.01" class="form-control" id="purchase_cost" required>
            </div>

            <div class="col-md-3">
              <label class="form-label">Salvage Value</label>
              <input type="number" step="0.01" class="form-control" id="salvage_value" value="0">
            </div>

            <div class="col-md-3">
              <label class="form-label">Method</label>
              <select class="form-select" id="depr_method" required>
                <option value="straight_line">Straight Line</option>
                <option value="declining_balance">Declining Balance</option>
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label">Useful Life (months)</label>
              <input type="number" class="form-control" id="useful_life_months" min="1" required>
            </div>

            <div class="col-md-3">
              <label class="form-label">Rate (optional)</label>
              <input type="number" step="0.000001" class="form-control" id="depr_rate">
            </div>

            <div class="col-md-3">
              <label class="form-label">Location</label>
              <input class="form-control" id="location" maxlength="150">
            </div>

            <hr class="my-2">

            <div class="col-md-4">
              <label class="form-label">Asset Account</label>
              <select class="form-select" id="asset_account_id">
                <option value="">-- from category default --</option>
                @foreach($accounts as $a)
                  <option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>
                @endforeach
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label">Accum. Depreciation</label>
              <select class="form-select" id="accum_depr_account_id">
                <option value="">-- from category default --</option>
                @foreach($accounts as $a)
                  <option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>
                @endforeach
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label">Depreciation Expense</label>
              <select class="form-select" id="depr_expense_account_id">
                <option value="">-- from category default --</option>
                @foreach($accounts as $a)
                  <option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>
                @endforeach
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Disposal Gain Account</label>
              <select class="form-select" id="disposal_gain_account_id">
                <option value="">-- from category default --</option>
                @foreach($accounts as $a)
                  <option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>
                @endforeach
              </select>
              <div class="small text-muted">Recommended: Other Income / Gain on Disposal</div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Disposal Loss Account</label>
              <select class="form-select" id="disposal_loss_account_id">
                <option value="">-- from category default --</option>
                @foreach($accounts as $a)
                  <option value="{{ $a->id }}">{{ $a->code }} - {{ $a->name }}</option>
                @endforeach
              </select>
              <div class="small text-muted">Recommended: Other Expense / Loss on Disposal</div>
            </div>

            <hr class="my-2">

            <div class="col-md-3">
              <label class="form-label">Serial No</label>
              <input class="form-control" id="serial_no" maxlength="120">
            </div>
            <div class="col-md-3">
              <label class="form-label">Supplier</label>
              <input class="form-control" id="supplier_name" maxlength="150">
            </div>
            <div class="col-md-3">
              <label class="form-label">Invoice No</label>
              <input class="form-control" id="invoice_no" maxlength="80">
            </div>

          </div>

          <div class="alert alert-info small mt-3 mb-0">
            <b>Workflow:</b> Create asset as <b>Draft</b> → <b>Activate</b> → Post <b>Acquisition</b> and run <b>Depreciation</b> → Post <b>Disposal</b> when needed.
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Close</button>
          <button class="btn btn-primary" type="submit" id="btnSave">
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

function badgeStatus(s){
  const map = {draft:'secondary', active:'success', disposed:'warning', written_off:'danger'};
  return `<span class="badge bg-${map[s]||'secondary'}">${s}</span>`;
}

function openModalNew(){
  $('#assetModalTitle').text('New Asset');
  $('#asset_id').val('');
  $('#assetForm')[0].reset();
  $('#salvage_value').val(0);
  $('#assetModal').modal('show');
}

function openModalEdit(id){
  $.get(`{{ url('admin/finance/fixed-assets') }}/${id}/json`)
    .done(res=>{
      const d = res.data;
      $('#assetModalTitle').text(`Edit Asset: ${d.asset_code}`);
      $('#asset_id').val(d.id);

      $('#category_id').val(d.category_id);
      $('#asset_code').val(d.asset_code);
      $('#name').val(d.name);
      $('#description').val(d.description || '');
      $('#purchase_date').val(d.purchase_date);
      $('#in_service_date').val(d.in_service_date);
      $('#purchase_cost').val(d.purchase_cost);
      $('#salvage_value').val(d.salvage_value);

      $('#depr_method').val(d.depr_method);
      $('#useful_life_months').val(d.useful_life_months);
      $('#depr_rate').val(d.depr_rate);

      $('#asset_account_id').val(d.asset_account_id);
      $('#accum_depr_account_id').val(d.accum_depr_account_id);
      $('#depr_expense_account_id').val(d.depr_expense_account_id);
      $('#disposal_gain_account_id').val(d.disposal_gain_account_id);
      $('#disposal_loss_account_id').val(d.disposal_loss_account_id);

      $('#location').val(d.location || '');
      $('#serial_no').val(d.serial_no || '');
      $('#supplier_name').val(d.supplier_name || '');
      $('#invoice_no').val(d.invoice_no || '');

      $('#assetModal').modal('show');
    })
    .fail(xhr=>Swal.fire('Error', xhr.responseJSON?.message || 'Failed to load asset', 'error'));
}

$(function(){
  dt = $('#assetsTable').DataTable({
    processing:true,
    serverSide:false,
    ajax: "{{ route('admin.finance.fixed_assets.datatable') }}",
    columns:[
      {data:'id'},
      {data:'asset_code'},
      {data:'name'},
      {data:'category'},
      {data:'purchase_date'},
      {data:'in_service_date'},
      {data:'purchase_cost', render:(d)=>Number(d||0).toFixed(2)},
      {data:'status', render:(d)=>badgeStatus(d)},
      {data:null, orderable:false, searchable:false, render:(row)=>{
        const viewUrl = `{{ url('admin/finance/fixed-assets') }}/${row.id}`;
        let html = `<a class="btn btn-sm btn-outline-primary me-1" href="${viewUrl}"><i class="fas fa-eye"></i></a>`;

        @can('finance.fixed_assets.update')
          html += `<button class="btn btn-sm btn-outline-secondary me-1 btnEdit" data-id="${row.id}" ${row.status!=='draft'?'disabled':''}>
                    <i class="fas fa-edit"></i>
                  </button>`;
        @endcan

        @can('finance.fixed_assets.activate')
          if(row.status==='draft'){
            html += `<button class="btn btn-sm btn-outline-success me-1 btnActivate" data-id="${row.id}">
                      <i class="fas fa-check"></i>
                    </button>`;
          }
        @endcan

        @can('finance.fixed_assets.delete')
          if(row.status==='draft'){
            html += `<button class="btn btn-sm btn-outline-danger btnDel" data-id="${row.id}">
                      <i class="fas fa-trash"></i>
                    </button>`;
          }
        @endcan

        return html;
      }},
    ]
  });

  @can('finance.fixed_assets.create')
  $('#btnAdd').on('click', openModalNew);
  @endcan

  $(document).on('click', '.btnEdit', function(){ openModalEdit($(this).data('id')); });

  $('#assetForm').on('submit', function(e){
    e.preventDefault();

    const id = $('#asset_id').val();
    const payload = {
      category_id: $('#category_id').val(),
      asset_code: $('#asset_code').val(),
      name: $('#name').val(),
      description: $('#description').val(),
      purchase_date: $('#purchase_date').val(),
      in_service_date: $('#in_service_date').val(),
      purchase_cost: $('#purchase_cost').val(),
      salvage_value: $('#salvage_value').val(),
      depr_method: $('#depr_method').val(),
      useful_life_months: $('#useful_life_months').val(),
      depr_rate: $('#depr_rate').val(),
      asset_account_id: $('#asset_account_id').val(),
      accum_depr_account_id: $('#accum_depr_account_id').val(),
      depr_expense_account_id: $('#depr_expense_account_id').val(),
      disposal_gain_account_id: $('#disposal_gain_account_id').val(),
      disposal_loss_account_id: $('#disposal_loss_account_id').val(),
      location: $('#location').val(),
      serial_no: $('#serial_no').val(),
      supplier_name: $('#supplier_name').val(),
      invoice_no: $('#invoice_no').val(),
    };

    const url = id ? `{{ url('admin/finance/fixed-assets') }}/${id}` : `{{ route('admin.finance.fixed_assets.store') }}`;
    const method = id ? 'PUT' : 'POST';

    $.ajax({url, method, data: payload})
      .done(res=>{
        $('#assetModal').modal('hide');
        dt.ajax.reload(null,false);
        Swal.fire('Success', res.message || 'Saved', 'success');
      })
      .fail(xhr=>Swal.fire('Error', xhr.responseJSON?.message || 'Failed', 'error'));
  });

  $(document).on('click','.btnActivate', function(){
    const id = $(this).data('id');
    Swal.fire({
      title:'Activate asset?',
      text:'This enables depreciation and disposal processing.',
      icon:'question',
      showCancelButton:true,
      confirmButtonText:'Activate'
    }).then(r=>{
      if(!r.isConfirmed) return;
      $.post(`{{ url('admin/finance/fixed-assets') }}/${id}/activate`)
        .done(res=>{ dt.ajax.reload(null,false); Swal.fire('Done', res.message, 'success'); })
        .fail(xhr=>Swal.fire('Error', xhr.responseJSON?.message || 'Failed', 'error'));
    });
  });

  $(document).on('click','.btnDel', function(){
    const id = $(this).data('id');
    Swal.fire({title:'Delete asset?', icon:'warning', showCancelButton:true, confirmButtonText:'Delete'})
    .then(r=>{
      if(!r.isConfirmed) return;
      $.ajax({url:`{{ url('admin/finance/fixed-assets') }}/${id}`, method:'DELETE'})
        .done(res=>{ dt.ajax.reload(null,false); Swal.fire('Deleted', res.message, 'success'); })
        .fail(xhr=>Swal.fire('Error', xhr.responseJSON?.message || 'Failed', 'error'));
    });
  });

});
</script>
@endpush