@extends('layouts.master')
@section('title','Stock Transactions')
@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
  <h1 class="h3 text-primary mb-4">Stock Ledger</h1>
  <div class="card shadow-sm">
    <div class="card-body">
      <table id="txTable" class="table table-striped w-100">
          <thead>
            <tr>
              <th>Tx Date</th>
              <th>Type</th>
              <th>Variant</th>
              <th>Store</th>
              <th class="text-end">Qty (+/-)</th>
              <th class="text-end">Unit Cost</th>
              <th>Source</th>
            </tr>
          </thead>
      </table>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(function(){
  $('#txTable').DataTable({
      serverSide:true, responsive:true,
      ajax:"{{ route('admin.inventory.stock.transactions.datatable') }}",
      order:[[0,'desc']],
      columns:[
        {data:'tx_date'},
        {data:'tx_type'},
        {data:'variant'},
        {data:'store'},
        {data:'qty',       className:'text-end'},
        {data:'unit_cost', className:'text-end'},
        {data:'source'},   // e.g. "Entry #12"
      ]
  });
});
</script>
@endpush
