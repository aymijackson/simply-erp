@extends('layouts.master')
@section('title','Stock Issues')

@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h3 text-primary"><i class="fas fa-dolly me-1"></i> Stock Issues</h1>
      <button id="addBtn" class="btn btn-primary"><i class="fas fa-plus me-1"></i> New Issue</button>
  </div>

  <div class="card shadow-sm">
     <div class="card-body">
       <table id="issueTbl" class="table table-bordered w-100">
          <thead class="table-light">
            <tr>
              <th>No</th><th>Store</th><th>Status</th><th>Posted at</th><th class="text-end">Actions</th>
            </tr>
          </thead>
       </table>
     </div>
  </div>
</div>

{{-- ─── Create/Edit modal (header + dynamic lines) ───────────────────────── --}}
@include('inventory.stock.issues.partials.modal')
@endsection


@push('scripts')
<script>
$(function(){
   const tbl = $('#issueTbl').DataTable({
       serverSide:true, responsive:true, ajax:"{{ route('admin.inventory.stock_issues.datatable') }}",
       columns:[
          {data:'issue_no'},
          {data:'store'},
          {data:'status'},
          {data:'posted_at'},
          {data:'actions', orderable:false, searchable:false, className:'text-end'}
       ],
    drawCallback(){
        /* one delegate per type avoids duplicate binding */
        $('#issueTbl tbody').off('click','.edit-btn').on('click','.edit-btn', function(){
            window.openIssueModal( $(this).data('json') );
        });

        $('#issueTbl tbody').off('click','.approve-btn').on('click','.approve-btn', function(){
            const id = $(this).closest('tr').attr('id');          // or store in data-id
            Swal.fire({title:'Approve this draft?',icon:'question',showCancelButton:true})
                .then(r=>{
                    if(r.isConfirmed){
                        $.post(`/admin/inventory/stock-issues/${id}/approve`, {_token:'{{ csrf_token() }}'})
                         .done(()=>{ tbl.ajax.reload(null,false); Swal.fire('Approved','','success'); });
                    }
                });
        });

        $('#issueTbl tbody').off('click','.post-btn').on('click','.post-btn', function(){
            const id = $(this).closest('tr').attr('id');
            Swal.fire({title:'Post this issue?',icon:'question',showCancelButton:true})
                .then(r=>{
                    if(r.isConfirmed){
                        $.post(`/admin/inventory/stock-issues/${id}/post`, {_token:'{{ csrf_token() }}'})
                         .done(()=>{ tbl.ajax.reload(null,false); Swal.fire('Posted','','success'); });
                    }
                });
        });
    },
    rowId: 'id'     // lets us grab id straight from <tr id="123">
   });

   // open modal
   $('#addBtn').on('click', ()=> $('#issueModal').modal('show'));

   // POST  (approved → posted)
$('#issueTbl').on('click', '.post-btn', function () {
    const id = $(this).data('id');

    Swal.fire({
        title: 'Post this issue?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, post'
    }).then(res => {
        if (!res.isConfirmed) return;

        $.post(`/admin/inventory/stock-issues/${id}/post`, {
            _token: '{{ csrf_token() }}'
        })
        .done(() => {
            tbl.ajax.reload(null, false);                 // keep paging
            Swal.fire('Posted', 'Issue successfully posted', 'success');
        })
        .fail(xhr => {
            /* 422 = ValidationException from StockIssueService */
            if (xhr.status === 422 && xhr.responseJSON?.errors?.qty?.length) {
                Swal.fire({
                    icon:   'error',
                    title:  'Insufficient stock',
                    html:   xhr.responseJSON.errors.qty[0]   // e.g. “122aaaa22 (have 0, need 3)…”
                });
                return;
            }

            /* fallback for any other server error */
            Swal.fire(
                'Error',
                xhr.responseJSON?.message || 'Post failed',
                'error'
            );
        });
    });
});

   
});
</script>
@endpush
