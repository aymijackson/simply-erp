{{-- resources/views/production/boms/partials/items-table.blade.php --}}
<div class="table-responsive">
  <table id="bomItemsDT" class="table table-sm table-bordered align-middle w-100">
    <thead class="table-light">
      <tr>
        <th>SKU</th>
        <th>Product</th>
        <th class="text-end">Qty / Parent</th>
        <th class="text-end">Unit Cost</th>
        <th class="text-end">Ext. Cost</th>
        <th class="text-end">Actions</th>
      </tr>
    </thead>
    <tfoot class="table-light">
      <tr>
        <th colspan="2" class="text-end">Totals</th>
        <th class="text-end ft-qty">—</th>
        <th></th>
        <th class="text-end ft-ext">—</th>
        <th></th>
      </tr>
    </tfoot>
  </table>
</div>

@push('styles')
  {{-- Buttons CSS (skip if you already load it) --}}
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
@endpush

@push('scripts')
  
  <script>
  $(function(){
    const table = $('#bomItemsDT').DataTable({
      processing: true,
      serverSide: true,
      responsive: true,
      dom: 'Blfrtip',
      buttons: [
        {extend:'excelHtml5', className:'btn btn-sm btn-success', text:'<i class="fas fa-file-excel me-1"></i> Excel', title: 'BOM_{{$bom->bom_code}}_Items'},
        {extend:'pdfHtml5',   className:'btn btn-sm btn-danger',  text:'<i class="fas fa-file-pdf me-1"></i> PDF',   title: 'BOM_{{$bom->bom_code}}_Items', orientation:'landscape', pageSize:'A4'},
      ],
      ajax: {
        url: "{{ '/admin/production/boms/'.$bom->id.'/items/datatable' }}",  // server endpoint
        data: function(d){
          d.bom_id = {{ (int) $bom->id }};   // filter by current BOM
        }
      },
      columns: [
        {data:'variant_sku',   name:'variant_sku'},
        {data:'product_name',  name:'variant.product.product_name'},
        {data:'qty_per_parent',name:'qty_per_parent', className:'text-end',
          render: function(v){ return (+v).toLocaleString(undefined,{minimumFractionDigits:4, maximumFractionDigits:4}); }},
        {data:'unit_cost',     name:'unit_cost', className:'text-end',
          render: function(v){ return v==null ? '—' : (+v).toLocaleString(undefined,{minimumFractionDigits:2, maximumFractionDigits:2}); }},
        {data:'ext_cost',      name:'ext_cost', className:'text-end',
          render: function(v){ return v==null ? '—' : (+v).toLocaleString(undefined,{minimumFractionDigits:2, maximumFractionDigits:2}); }},
        {data:'actions',       orderable:false, searchable:false, className:'text-end'}
      ],
      order: [[0,'asc']],
      lengthMenu: [[10,25,50,100,250],[10,25,50,100,250]],
      drawCallback: function(settings){
        // If your API returns { totals: { qty_per_parent, ext_cost } }
        const json = settings.json || {};
        if(json.totals){
          $('.ft-qty').text((+json.totals.qty_per_parent).toLocaleString(undefined,{minimumFractionDigits:4, maximumFractionDigits:4}));
          $('.ft-ext').text((+json.totals.ext_cost).toLocaleString(undefined,{minimumFractionDigits:2, maximumFractionDigits:2}));
        }
      }
    });
  });
  </script>
@endpush
