@extends('layouts.master')
@section('title','Inventory Dashboard')

@section('content')
<div class="container-fluid">

  {{-- KPIs ---------------------------------------------------------------- --}}
  <div class="row text-center mb-4" id="cardRow">
     <div class="col-md-4"><div class="card shadow-sm">
         <div class="card-body"><h6>Total Variants</h6><h3 id="cVariants">‑‑</h3></div></div>
     </div>
     <div class="col-md-4"><div class="card shadow-sm">
         <div class="card-body"><h6>Total Qty on hand</h6><h3 id="cQty">‑‑</h3></div></div>
     </div>
     <div class="col-md-4"><div class="card shadow-sm">
         <div class="card-body"><h6>Total Stock Value</h6><h3 id="cValue">‑‑</h3></div></div>
     </div>
  </div>

  {{-- Aging bar ----------------------------------------------------------- --}}
  <div class="card mb-4 shadow-sm">
    <div class="card-body">
      <canvas id="agingChart" height="80"></canvas>
    </div>
  </div>

  <div class="row">
     {{-- Top movers -------------------------------------------------------- --}}
     <div class="col-lg-6 mb-4">
        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white">Top Movers (7 days)</div>
          <div class="card-body">
            <table id="moverTbl" class="table table-sm table-bordered w-100">
               <thead class="table-light"><tr><th>SKU</th><th class="text-end">Qty moved</th></tr></thead>
            </table>
          </div>
        </div>
     </div>
     {{-- Low stock --------------------------------------------------------- --}}
     <div class="col-lg-6 mb-4">
        <div class="card shadow-sm">
          <div class="card-header bg-danger text-white">Low Stock</div>
          <div class="card-body">
            <table id="lowTbl" class="table table-sm table-bordered w-100">
               <thead class="table-light">
                 <tr><th>SKU</th><th class="text-end">ROP</th><th class="text-end">On hand</th></tr>
               </thead>
            </table>
          </div>
        </div>
     </div>
  </div>
</div>
@endsection

@push('scripts')
{{-- Chart.js 4 & DT bundle already loaded in master --}}
<script>
$(function(){

  /* ----- KPI cards ----- */
  axios.get("{{ route('admin.inventory.stock.dashboard.cards') }}")
       .then(r=>{
           $('#cVariants').text( r.data.variants.toLocaleString() );
           $('#cQty')     .text( r.data.total_qty.toLocaleString() );
           $('#cValue')   .text(
              r.data.total_value.toLocaleString(undefined,{style:'currency',currency:'USD'})
           );
       });

  /* ----- Aging stacked bar ----- */
  axios.get("{{ route('admin.inventory.stock.dashboard.aging-chart') }}")
       .then(r=>{
          const labels = r.data.map(o=>o.age_bucket);
          const qty    = r.data.map(o=>o.qty);
          new Chart($('#agingChart'),{
              type:'bar',
              data:{labels,
                    datasets:[{label:'Qty',data:qty,stack:'s',borderWidth:1}]},
              options:{plugins:{legend:{display:false}},
                       scales:{x:{stacked:true},y:{stacked:true,beginAtZero:true}}}
          });
       });

  /* ----- DTs ----- */
  $('#moverTbl').DataTable({
      paging:false, searching:false, ordering:false, info:false,
      ajax:"{{ route('admin.inventory.stock.dashboard.top-movers') }}",
      columns:[{data:'sku'},{data:'moved_qty',className:'text-end'}]
  });

  $('#lowTbl').DataTable({
      paging:false, searching:false, ordering:false, info:false,
      ajax:"{{ route('admin.inventory.stock.dashboard.low-stock') }}",
      columns:[
          {data:'sku'},
          {data:'reorder_point', className:'text-end'},
          {data:'qty_on_hand',   className:'text-end'},
      ]
  });

});
</script>
@endpush
