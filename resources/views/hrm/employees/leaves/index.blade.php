
@extends('layouts.master')

@section('title', 'Leave Management')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 text-primary">Employee Leave Requests</h1>
        <div>
            <button class="btn btn-primary" id="addLeaveBtn"><i class="fas fa-plus me-1"></i> Add Leave</button>
            <button class="btn btn-danger d-none" id="bulkDeleteBtn"><i class="fas fa-trash-alt"></i> Delete Selected</button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered" id="leaveTable">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>ID</th>
                        <th>Employee</th>
                        <th>Type</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Leave Modal -->
<div class="modal fade" id="leaveModal" tabindex="-1" aria-labelledby="leaveModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" id="leaveForm">
      @csrf
      <input type="hidden" id="leave_id" name="id">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="leaveModalLabel">Add Leave</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Employee</label>
          <select class="form-control" name="employee_id" id="employee_id" required>
            <option value="">-- Select Employee --</option>
            @foreach($employees as $emp)
              <option value="{{ $emp->id }}">{{ $emp->first_name }} {{ $emp->last_name }}</option>
            @endforeach
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Leave Type</label>
          <input type="text" class="form-control" name="leave_type" id="leave_type" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Start Date</label>
          <input type="date" class="form-control" name="start_date" id="start_date" required>
        </div>
        <div class="mb-3">
          <label class="form-label">End Date</label>
          <input type="date" class="form-control" name="end_date" id="end_date" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Reason</label>
          <textarea class="form-control" name="reason" id="reason"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-success">Save</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    const modal = new bootstrap.Modal(document.getElementById('leaveModal'));

    const table = $('#leaveTable').DataTable({
        ajax: '{{ route('admin.hrm.employees.leaves.datatable') }}',
        columns: [
            { data: 'checkbox', orderable: false, searchable: false },
            { data: 'id' },
            { data: 'employee' },
            { data: 'leave_type' },
            { data: 'start_date' },
            { data: 'end_date' },
            { data: 'status' },
            { data: 'actions', orderable: false, searchable: false }
        ]
    });

    $('#addLeaveBtn').on('click', function(){
        $('#leaveForm')[0].reset();
        $('#leave_id').val('');
        $('#leaveModalLabel').text('Add Leave');
        modal.show();
    });

    $('#leaveForm').on('submit', function(e){
        e.preventDefault();
        const id = $('#leave_id').val();
        const url = id ? `/admin/hrm/employees/leaves/${id}` : `{{ route('admin.hrm.employees.leaves.store') }}`;
        const data = $(this).serialize() + (id ? '&_method=PUT' : '');
        $.post(url, data).done(res=>{
            table.ajax.reload(null,false);
            modal.hide();
            Swal.fire('Success', res.message,'success');
        }).fail(xhr=>{
            Swal.fire('Error', xhr.responseJSON?.message||'Failed','error');
        });
    });

    $('#leaveTable').on('click','.edit-leave',function(){
        const b=$(this);
        $('#leave_id').val(b.data('id'));
        $('#employee_id').val(b.data('employee_id'));
        $('#leave_type').val(b.data('leave_type'));
        $('#start_date').val(b.data('start_date'));
        $('#end_date').val(b.data('end_date'));
        $('#reason').val(b.data('reason'));
        $('#leaveModalLabel').text('Edit Leave');
        modal.show();
    });

    $('#leaveTable').on('click','.delete-leave',function(){
        const id=$(this).data('id');
        Swal.fire({title:'Delete?',icon:'warning',showCancelButton:true,confirmButtonColor:'#d33'}).then(r=>{
            if(r.isConfirmed){
                $.ajax({url:`/admin/hrm/employees/leaves/${id}`,method:'DELETE',data:{_token:'{{ csrf_token() }}'}}).done(res=>{
                    table.ajax.reload(null,false);
                    Swal.fire('Deleted',res.message,'success');
                });
            }
        });
    });

    // Approve / Reject buttons with confirmation and mutual exclusivity
    $('#leaveTable').on('click','.approve-leave, .reject-leave',function(){
        const id=$(this).data('id');
        const isApprove=$(this).hasClass('approve-leave');
        const action=isApprove?'approve':'reject';
        const confirmText=isApprove?'Yes, approve it!':'Yes, reject it!';
        const confirmTitle=isApprove?'Approve this leave request?':'Reject this leave request?';

        Swal.fire({
            title: confirmTitle,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: confirmText,
            confirmButtonColor: isApprove ? '#28a745' : '#d33'
        }).then(result => {
            if (result.isConfirmed) {
                $.post(`/admin/hrm/employees/leaves/${id}/${action}`, {_token:'{{ csrf_token() }}'}).done(res=>{
                    table.ajax.reload(null,false);
                    Swal.fire('Success',res.message,'success');
                }).fail(() => Swal.fire('Error','Operation failed','error'));
            }
        });
    });

    // Bulk delete/Select all
    $('#selectAll').on('click',function(){
        $('.row-checkbox').prop('checked',this.checked);
        toggleBulkDelete();
    });
    $('#leaveTable tbody').on('change','.row-checkbox',toggleBulkDelete);
    function toggleBulkDelete(){
        $('#bulkDeleteBtn').toggleClass('d-none',$('.row-checkbox:checked').length===0);
    }

    $('#bulkDeleteBtn').on('click',function(){
        const ids=$('.row-checkbox:checked').map(function(){return $(this).val();}).get();
        if(ids.length){
            Swal.fire({title:'Delete Selected?',icon:'warning',showCancelButton:true,confirmButtonColor:'#d33'}).then(r=>{
                if(r.isConfirmed){
                    $.post(`{{ route('admin.hrm.employees.leaves.bulk-delete') }}`,{_token:'{{ csrf_token() }}',ids:ids}).done(res=>{
                        table.ajax.reload(null,false);
                        $('#bulkDeleteBtn').addClass('d-none');
                        Swal.fire('Deleted',res.message,'success');
                    });
                }
            });
        }
    });
});
</script>
@endpush
