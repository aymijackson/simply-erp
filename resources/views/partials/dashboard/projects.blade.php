@if(!empty($lowStockTop) && count($lowStockTop))
<div class="card shadow mb-4">
  <div class="card-header py-3 d-flex justify-content-between align-items-center">
    <h6 class="m-0 font-weight-bold text-danger">Low Stock Alerts</h6>
    <span class="small text-muted">At/below reorder point</span>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-sm">
        <thead><tr><th>Product</th><th>SKU</th><th class="text-end">On Hand</th><th class="text-end">Reorder</th></tr></thead>
        <tbody>
          @foreach($lowStockTop as $x)
            <tr>
              <td>{{ $x->product_name }}</td>
              <td>{{ $x->sku }}</td>
              <td class="text-end">{{ number_format($x->qty_on_hand, 2) }}</td>
              <td class="text-end">{{ number_format($x->reorder_point, 2) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endif
