@extends('layouts.master')
@section('title','Stock Transfers')

@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h3 text-primary">Stock Transfers</h1>
      <a href="{{ route('admin.inventory.stock.transfers.create') }}" class="btn btn-primary">
          <i class="fas fa-plus me-1"></i> New Transfer
      </a>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <table id="trfTable" class="table table-bordered w-100">
        <thead class="table-light">
          <tr>
            <th>No</th><th>From → To</th><th>Lines</th><th>Status</th><th>Posted</th><th>Actions</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
  $('#trfTable').DataTable({
      serverSide:true, responsive:true,
      ajax:"{{ route('admin.inventory.stock.transfers.datatable') }}",
      columns:[
        {data:'transfer_no'},
        {data:'stores'},
        {data:'lines', className:'text-end'},
        {data:'status'},
        {data:'posted_at'},
        {data:'actions', orderable:false, searchable:false}
      ]
  });
});
</script>
@endpush
