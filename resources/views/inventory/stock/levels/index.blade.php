@extends('layouts.master')
@section('title','Stock on Hand')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">

  <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h3 text-primary">Stock on Hand</h1>
      <span class="h5 mb-0" id="totalValueSpan"></span>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <table id="levelTable" class="table table-bordered w-100">
        <thead class="table-light">
          <tr>
            <th>Store</th>
            <th>Variant</th>
            <th class="text-end">Qty</th>
            <th class="text-end">Value</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>
@endsection

@push('scripts')

<script>
$(function(){
    $('#levelTable').DataTable({
        serverSide:true, 
        responsive:true,
        dom: 'Bfrtip',
        ajax:"{{ route('admin.inventory.stock.levels.datatable') }}",
        order:[[0,'asc']],
        columns:[
          {data:'store'},
          {data:'variant'},
          {data:'qty_on_hand',   className:'text-end'},
          {data:'value_on_hand', className:'text-end'},
        ],
        buttons:[{
            extend:'excelHtml5',
            title: 'Stock-on-hand ' + new Date().toISOString().slice(0,10),
            exportOptions:{columns:[0,1,2,3]} // export visible columns
        }],
        drawCallback:updateTotals
    });

    /* one‑off fetch for total value card */
    function updateTotals(){
        $.getJSON("{{ route('admin.inventory.stock.levels.totals') }}", res=>{
            $('#totalValueSpan').text(
                'Total Value: '+ Number(res.value).toLocaleString(undefined,{style:'currency',currency:'USD'})
            );
        });
    }
    updateTotals();

    

});
</script>
@endpush
