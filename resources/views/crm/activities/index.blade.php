@extends('layouts.master')

@section('title', 'CRM Activities')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 text-primary">Activities <small class="text-muted">CRM</small></h1>

    <div class="d-flex gap-2">
      @can('crm.activities.bulk_delete')
        <button class="btn btn-danger d-none" id="bulkDeleteBtn">
          <i class="fas fa-trash me-1"></i> Delete Selected
        </button>
      @endcan

      @can('crm.activities.create')
        <button class="btn btn-primary" id="openAddModal">
          <i class="fas fa-plus me-1"></i> Add Activity
        </button>
      @endcan
    </div>
  </div>

  {{-- Filters --}}
  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-2">
        <div class="col-md-3">
          <label class="form-label">Owner</label>
          <select class="form-control" id="filterOwner">
            <option value="">All</option>
            @foreach($employees as $e)
              <option value="{{ $e->id }}">{{ $e->first_name }} {{ $e->last_name }}</option>
            @endforeach
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label">Type</label>
          <select class="form-control" id="filterType">
            <option value="">All</option>
            <option value="call">Call</option>
            <option value="meeting">Meeting</option>
            <option value="email">Email</option>
            <option value="task">Task</option>
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label">Status</label>
          <select class="form-control" id="filterStatus">
            <option value="">All</option>
            <option value="pending">Pending</option>
            <option value="completed">Completed</option>
            <option value="overdue">Overdue</option>
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label">Due From</label>
          <input type="date" class="form-control" id="filterDueFrom">
        </div>

        <div class="col-md-2">
          <label class="form-label">Due To</label>
          <input type="date" class="form-control" id="filterDueTo">
        </div>

        <div class="col-md-1 d-flex align-items-end">
          <button class="btn btn-outline-secondary w-100" id="filterBtn">Apply</button>
        </div>
      </div>
    </div>
  </div>

  {{-- Table --}}
  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered" id="activitiesTable" width="100%">
          <thead>
            <tr>
              <th style="width:40px;"><input type="checkbox" id="checkAll"></th>
              <th>Subject</th>
              <th>Type</th>
              <th>Status</th>
              <th>Due</th>
              <th>Owner</th>
              <th>Related</th>
              <th>Created By</th>
              <th>Updated By</th>
              <th style="width:140px;">Actions</th>
            </tr>
          </thead>
        </table>
      </div>
    </div>
  </div>

</div>

{{-- Add/Edit Modal --}}
<div class="modal fade" id="activityModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <form id="activityForm">
      @csrf
      <input type="hidden" id="activity_id">

      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="activityModalTitle">Add Activity</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="row g-2">

            <div class="col-md-12">
              <label class="form-label">Subject</label>
              <input type="text" class="form-control" id="subject" required>
            </div>

            <div class="col-md-12">
              <label class="form-label">Description</label>
              <textarea class="form-control" id="description" rows="3"></textarea>
            </div>

            <div class="col-md-4">
              <label class="form-label">Type</label>
              <select class="form-control" id="activity_type" required>
                <option value="call">Call</option>
                <option value="meeting">Meeting</option>
                <option value="email">Email</option>
                <option value="task">Task</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label">Status</label>
              <select class="form-control" id="status" required>
                <option value="pending">Pending</option>
                <option value="completed">Completed</option>
                <option value="overdue">Overdue</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label">Due Date</label>
              <input type="date" class="form-control" id="due_date" required>
            </div>

            <div class="col-md-6">
              <label class="form-label">Owner (Employee)</label>
              <select class="form-control" id="owner_id" required>
                <option value="">Select...</option>
                @foreach($employees as $e)
                  <option value="{{ $e->id }}">{{ $e->first_name }} {{ $e->last_name }}</option>
                @endforeach
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Related (optional)</label>
              <div class="d-flex gap-2">
                <input type="text" class="form-control" id="related_type" placeholder="e.g. Modules\CRM\Models\Lead">
                <input type="number" class="form-control" id="related_id" placeholder="ID">
              </div>
              <small class="text-muted">You can keep this blank if not linking to a record.</small>
            </div>

          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary" id="saveActivityBtn">Save</button>
        </div>

      </div>
    </form>
  </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
  $.ajaxSetup({
    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}
  });

  const canEdit = @json(auth()->user()->can('crm.activities.edit'));
  const canDelete = @json(auth()->user()->can('crm.activities.delete'));

  const table = $('#activitiesTable').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: "{{ route('admin.crm.activities.datatable') }}",
      data: function(d){
        d.owner_id = $('#filterOwner').val();
        d.activity_type = $('#filterType').val();
        d.status = $('#filterStatus').val();
        d.due_from = $('#filterDueFrom').val();
        d.due_to = $('#filterDueTo').val();
      }
    },
    columns: [
      { data: 'checkbox', orderable:false, searchable:false },
      { data: 'subject', name:'subject' },
      { data: 'activity_type', name:'activity_type' },
      { data: 'status', name:'status' },
      { data: 'due_date', name:'due_date' },
      { data: 'owner', orderable:false, searchable:false },
      { data: 'related_to', orderable:false, searchable:false },
      { data: 'created_by', orderable:false, searchable:false },
      { data: 'updated_by', orderable:false, searchable:false },
      { data: 'actions', orderable:false, searchable:false },
    ],
    order: [[1,'asc']],
    drawCallback: function() { syncBulkUI(); }
  });

  $('#filterBtn').on('click', function(){ table.ajax.reload(); });

  function resetForm(){
    $('#activity_id').val('');
    $('#subject').val('');
    $('#description').val('');
    $('#activity_type').val('call');
    $('#status').val('pending');
    $('#due_date').val('');
    $('#owner_id').val('');
    $('#related_type').val('');
    $('#related_id').val('');
  }

  $('#openAddModal').on('click', function(){
    resetForm();
    $('#activityModalTitle').text('Add Activity');
    $('#saveActivityBtn').text('Create');
    $('#activityModal').modal('show');
  });

  $(document).on('click', '.edit-activity', function(){
    if (!canEdit) return;
    const rec = $(this).data('record');
    resetForm();
    $('#activityModalTitle').text('Edit Activity');
    $('#saveActivityBtn').text('Update');

    $('#activity_id').val(rec.id);
    $('#subject').val(rec.subject);
    $('#description').val(rec.description);
    $('#activity_type').val(rec.activity_type);
    $('#status').val(rec.status);
    $('#due_date').val(rec.due_date);
    $('#owner_id').val(rec.owner_id);
    $('#related_type').val(rec.related_type);
    $('#related_id').val(rec.related_id);

    $('#activityModal').modal('show');
  });

  $('#activityForm').on('submit', function(e){
    e.preventDefault();

    const id = $('#activity_id').val();
    const payload = {
      subject: $('#subject').val(),
      description: $('#description').val(),
      activity_type: $('#activity_type').val(),
      status: $('#status').val(),
      due_date: $('#due_date').val(),
      owner_id: $('#owner_id').val(),
      related_type: $('#related_type').val(),
      related_id: $('#related_id').val(),
    };

    const isEdit = !!id;

    const url = isEdit
      ? "{{ url('admin/crm/activities') }}/" + id
      : "{{ route('admin.crm.activities.store') }}";

    const method = isEdit ? 'PUT' : 'POST';

    $.ajax({
      url, method, data: payload,
      success: function(res){
        $('#activityModal').modal('hide');
        table.ajax.reload(null,false);
        Swal.fire('Success', res.message || 'Saved', 'success');
      },
      error: function(xhr){
        const msg = xhr.responseJSON?.message || 'Validation failed';
        Swal.fire('Error', msg, 'error');
      }
    });
  });

  $(document).on('click', '.delete-activity', function(){
    if (!canDelete) return;
    const id = $(this).data('id');

    Swal.fire({
      title:'Delete?',
      text:'This will remove the activity.',
      icon:'warning',
      showCancelButton:true,
      confirmButtonText:'Yes, delete'
    }).then((r)=>{
      if(!r.isConfirmed) return;

      $.ajax({
        url: "{{ url('admin/crm/activities') }}/" + id,
        method: 'DELETE',
        success: function(res){
          table.ajax.reload(null,false);
          Swal.fire('Deleted', res.message || 'Deleted', 'success');
        },
        error: function(){
          Swal.fire('Error', 'Failed to delete', 'error');
        }
      });
    });
  });

  // Bulk selection
  $('#checkAll').on('change', function(){
    $('.row-checkbox').prop('checked', $(this).is(':checked'));
    syncBulkUI();
  });

  $(document).on('change', '.row-checkbox', function(){
    syncBulkUI();
  });

  function syncBulkUI(){
    const any = $('.row-checkbox:checked').length > 0;
    $('#bulkDeleteBtn').toggleClass('d-none', !any);
  }

  $('#bulkDeleteBtn').on('click', function(){
    const ids = $('.row-checkbox:checked').map(function(){ return $(this).val(); }).get();

    Swal.fire({
      title:'Delete selected?',
      text:`You are deleting ${ids.length} record(s).`,
      icon:'warning',
      showCancelButton:true,
      confirmButtonText:'Yes, delete'
    }).then((r)=>{
      if(!r.isConfirmed) return;

      $.post("{{ route('admin.crm.activities.bulk_delete') }}", { ids }, function(res){
        $('#checkAll').prop('checked', false);
        table.ajax.reload();
        Swal.fire('Deleted', res.message || 'Deleted', 'success');
      }).fail(function(xhr){
        Swal.fire('Error', xhr.responseJSON?.message || 'Failed', 'error');
      });
    });
  });

})();
</script>
@endpush
