@extends('layouts.master')

@section('title','Low‑Stock Report')

@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h3 text-primary">Low Stock <small class="text-muted">Re‑order alert</small></h1>
      <button id="exportBtn" class="btn btn-success">
          <i class="fas fa-file-excel me-1"></i> Export Excel
      </button>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <table id="lowTable" class="table table-bordered w-100">
        <thead class="table-light">
          <tr>
            <th>SKU</th>
            <th>Product</th>
            <th>Brand</th>
            <th class="text-end">Qty On Hand</th>
            <th class="text-end">Re‑order Pt</th>
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

  const table = $('#lowTable').DataTable({
      serverSide: true,
      responsive: true,
      ajax:  "{{ route('admin.inventory.stock.levels.low.datatable') }}",
      order: [[3,'asc']],
      columns: [
          { data:'sku' },
          { data:'product' },
          { data:'brand' },
          { data:'qty', className:'text-end' },
          { data:'rop', className:'text-end' }
      ],
      dom: 'Bfrtip',
      buttons: [{
          extend: 'excelHtml5',
          title: 'Low-stock_'+ (new Date()).toISOString().slice(0,10),
          exportOptions: { columns: [0,1,2,3,4] }
      }]
  });

  /* manual export btn triggers DT button (nice UX for top-right position) */
  $('#exportBtn').on('click', () => table.button(0).trigger());
});
</script>
@endpush
