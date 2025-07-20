@extends('layouts.master')
@section('title','Inventory Aging')

@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h3 text-primary">Inventory Aging</h1>
      <button id="expXls" class="btn btn-success">
          <i class="fas fa-file-excel me-1"></i> Export
      </button>
  </div>
    
  <div class="card shadow-sm">
    <div class="card-body">
        <hr>
        <h5 class="mb-2">Aging buckets – Qty stacked</h5>
        <canvas id="agingChart" height="140"></canvas>
        <div class="row g-2 mb-2">
            <div class="col-auto">
                <input type="number" id="ageFrom" class="form-control" placeholder="From (days)" value="0">
            </div>
            <div class="col-auto">
                <input type="number" id="ageTo" class="form-control" placeholder="To (days)" value="30">
            </div>
            <div class="col-auto">
                <button id="ageFilterBtn" class="btn btn-primary">Apply</button>
            </div>
        </div>

      <table id="ageTable" class="table table-bordered w-100">
        <thead class="table-light">
          <tr>
            <th>Store</th>
            <th>Variant</th>
            <th>Age Bucket</th>
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
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
$(function () {

/* ---------- DataTable ---------- */
const ageTable = $('#ageTable').DataTable({          // 🟢 keep the var name consistent
    serverSide: true,
    responsive: true,
    ajax: "{{ route('admin.inventory.stock.aging.datatable') }}",
    order: [[2,'asc']],
    dom: 'Bfrtip',
    buttons: [{
        extend:'excelHtml5',
        title:'Inventory Aging '+new Date().toISOString().slice(0,10),
        exportOptions:{columns:[0,1,2,3,4]}
    }],
    columns:[
        {data:'store'},
        {data:'variant'},
        {data:'age_bucket'},
        {data:'qty',   className:'text-end'},
        {data:'value', className:'text-end'}
    ],
    drawCallback: drawChart                // when table (re)draws, rebuild chart
});

/* export button */
$('#expXls').on('click', () => ageTable.button(0).trigger());

/* dynamic bucket size filter */
$('#ageFilterBtn').on('click', () => {
    const url = `{{ route('admin.inventory.stock.aging.datatable') }}`
              + '?from='+$('#ageFrom').val()+'&to='+$('#ageTo').val();
    ageTable.ajax.url(url).load();
});

/* ---------- Chart (stacked bar) ---------- */
let chart;      // hold the Chart instance so we can destroy/rebuild
function toFloat(val){
    // remove commas, thin‑spaces, NBSP etc.  → parseFloat
    return parseFloat(String(val).replace(/[\s, ]/g,''));
}

function drawChart () {
    const rows = ageTable.ajax.json().data || [];
    const buckets = {};

    rows.forEach(r=>{
        const qty = toFloat(r.qty);          // 🟢 convert safely
        if(isNaN(qty)) return;               // skip bad rows
        (buckets[r.age_bucket] ??= {});
        buckets[r.age_bucket][r.store] = (buckets[r.age_bucket][r.store]||0) + qty;
    });
    const labels   = Object.keys(buckets).sort();               // bucket names
    const stores   = [...new Set(rows.map(r=>r.store))].sort(); // every store
    const datasets = stores.map((s,i)=>({
        label: s,
        data: labels.map(b=> buckets[b][s]||0),
        // Chart.js v4 will auto‑assign colours; remove if you need custom palette
    }));

    // destroy old chart to avoid “already exists” errors
    if(chart) chart.destroy();

    const ctx = document.getElementById('agingChart');
    if(!ctx) return;     // canvas missing – nothing to do

    chart = new Chart(ctx, {
        type:'bar',
        data:{labels,datasets},
        options:{
            responsive:true,
            plugins:{legend:{position:'top'}},
            scales:{x:{stacked:true}, y:{stacked:true}}
        }
    });
}
});

</script>
@endpush
