@extends('layouts.master')
@section('title', 'Routing · '.$routing->name)

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
@endpush

@section('content')
<div class="container-fluid">

  {{-- Header --}}
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 text-primary mb-0">
      Routing <small class="text-muted">— {{ $routing->name }}</small>
    </h1>
    <div class="d-print-none">
      <a href="{{ route('admin.production.routings.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i> Back
      </a>
    </div>
  </div>

  {{-- Tabs --}}
  <div class="card shadow-sm">
    <div class="card-body">
      <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-overview" type="button" role="tab">
            Overview
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-steps" type="button" role="tab">
            Steps
          </button>
        </li>
      </ul>

      <div class="tab-content pt-3">
        {{-- Overview tab --}}
        <div class="tab-pane fade show active" id="tab-overview" role="tabpanel">
          <div class="row g-3">
            <div class="col-md-4">
              <div class="border rounded p-3 h-100">
                <h6 class="text-muted mb-2">Routing</h6>
                <div><strong>Name:</strong> {{ $routing->name }}</div>
                <div><strong>Description:</strong> {{ $routing->description ?: '—' }}</div>
              </div>
            </div>
            <div class="col-md-8">
              <div class="border rounded p-3 h-100">
                <h6 class="text-muted mb-2">Product / Variant</h6>
                <div><strong>Variant SKU:</strong> {{ $routing->product_variant->sku ?? '—' }}</div>
                <div><strong>Product:</strong> {{ $routing->product_variant->product->product_name ?? '—' }}</div>
              </div>
            </div>
          </div>
        </div>

        {{-- Steps tab --}}
        <div class="tab-pane fade" id="tab-steps" role="tabpanel">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0">Steps for this routing</h6>
            <button id="addBtn" class="btn btn-primary">
              <i class="fas fa-plus me-1"></i> Add Step
            </button>
          </div>

          <div class="table-responsive">
            <table id="stepsTbl" class="table table-bordered w-100">
              <thead class="table-light">
              <tr>
                <th style="width:36px"><input type="checkbox" id="checkAll"></th>
                <th style="width:80px">#</th>
                <th style="width:120px">Sequence</th>
                <th>Step Name</th>
                <th>Instructions</th>
                <th style="width:150px">Created</th>
                <th style="width:110px" class="text-end">Actions</th>
              </tr>
              </thead>
            </table>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

{{-- Add/Edit Step Modal --}}
<div class="modal fade" id="stepModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="stepForm" class="modal-content">
      @csrf
      <input type="hidden" id="step_id">
      <input type="hidden" id="routing_id" name="routing_id" value="{{ $routing->id }}">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Add Step</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Sequence</label>
          <input type="number" min="0" step="1" class="form-control" id="sequence" name="sequence" placeholder="e.g. 10">
          <div class="form-text">Use gaps (10, 20, 30) so you can insert between later.</div>
        </div>
        <div class="mb-3">
          <label class="form-label">Step Name *</label>
          <input class="form-control" id="step_name" name="step_name" required>
        </div>
        <div class="mb-0">
          <label class="form-label">Instructions</label>
          <textarea class="form-control" id="instructions" name="instructions" rows="4"></textarea>
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
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script>
(function(){
  const ROUTING_ID = {{ $routing->id }};
  const CSRF       = @json(csrf_token());
  // keep URLs simple & avoid route() param errors
  const DT_URL     = "{{ url('admin/production/routings') }}/{{ $routing->id }}/steps/datatable";
  const BASE_CRUD  = "{{ url('admin/production/routings') }}/{{ $routing->id }}/steps"; // POST /, PUT/DELETE /{id}

  // DataTable (steps for this routing only)
  const tbl = $('#stepsTbl').DataTable({
    serverSide: true,
    responsive: true,
    ajax: { url: DT_URL },
    columns: [
      {data:'checkbox', orderable:false, searchable:false},
      {data:'DT_RowIndex', orderable:false, searchable:false},
      {data:'sequence', name:'sequence'},
      {data:'step_name', name:'step_name'},
      {data:'instructions'},
      {data:'created_at', name:'created_at'},
      {data:'actions', orderable:false, searchable:false, className:'text-end'}
    ],
    order: [[2,'asc'], [5,'desc']],
    dom: 'Blfrtip',
    buttons:[
      {extend:'excelHtml5', className:'btn btn-sm btn-success', text:'<i class="fas fa-file-excel me-1"></i> Excel'},
      {extend:'pdfHtml5',   className:'btn btn-sm btn-danger',  text:'<i class="fas fa-file-pdf me-1"></i> PDF', orientation:'landscape', pageSize:'A4'},
    ],
    createdRow: row => row.classList.add('align-middle')
  });

  // Modal wiring
  const modal  = new bootstrap.Modal('#stepModal');
  const $form  = $('#stepForm');
  const $id    = $('#step_id');
  const $seq   = $('#sequence');
  const $name  = $('#step_name');
  const $inst  = $('#instructions');

  // Add
  $('#addBtn').on('click', ()=>{
    $form[0].reset();
    $id.val('');
    $('.modal-title').text('Add Step');
    modal.show();
  });

  // Save (create/update)
  $('#saveBtn').on('click', function(e){
    e.preventDefault();
    const payload = {
      _token: CSRF,
      routing_id: ROUTING_ID,
      sequence: $seq.val() || null,
      step_name: $name.val(),
      instructions: $inst.val()
    };
    const id = $id.val();
    const method = id ? 'PUT' : 'POST';
    const url    = id ? `${BASE_CRUD}/${id}` : BASE_CRUD;

    $.ajax({ url, type: method, data: payload })
      .done(res => {
        modal.hide();
        tbl.ajax.reload(null,false);
        Swal.fire('Saved', res.message || 'OK', 'success');
      })
      .fail(x => {
        const msg  = x.responseJSON?.message || 'Save failed';
        const errs = x.responseJSON?.errors;
        Swal.fire('Error', errs ? Object.values(errs).flat().join('<br>') : msg, 'error');
      });
  });

  // Edit (delegated; works with responsive rows)
  $(document).on('click', '.edit-step', function(){
    const r = $(this).data('record'); // {id, sequence, step_name, instructions}
    $id.val(r.id);
    $seq.val(r.sequence || 0);
    $name.val(r.step_name || '');
    $inst.val(r.instructions || '');
    $('.modal-title').text('Edit Step');
    modal.show();
  });

  // Delete (delegated)
  $(document).on('click', '.del-step', function(){
    const id = $(this).data('id');
    Swal.fire({title:'Delete this step?', icon:'warning', showCancelButton:true})
      .then(r=>{
        if(!r.isConfirmed) return;
        $.ajax({ url: `${BASE_CRUD}/${id}`, type:'DELETE', data:{ _token: CSRF }})
          .done(res => { tbl.ajax.reload(null,false); Swal.fire('Deleted', res.message || 'Removed', 'success'); })
          .fail(x  => { Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error'); });
      });
  });

  // Select-all (optional)
  $('#checkAll').on('change', function(){
    $('.row-check').prop('checked', this.checked);
  });
})();
</script>
@endpush
