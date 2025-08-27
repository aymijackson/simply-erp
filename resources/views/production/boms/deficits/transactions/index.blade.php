@extends('layouts.master')
@section('title','BOM Deficit Transactions')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
@endpush

@section('content')
<div class="container-fluid">
  <div class="row g-2 align-items-end mb-3">
    <div class="col-md-3">
      <label class="form-label">BOM</label>
      <select id="f_bom" class="form-select">
        <option value="">-- All --</option>
        @foreach($boms as $b)
          <option value="{{ $b->id }}">#{{ $b->bom_code }} — {{ $b->name }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label">Variant ID</label>
      <input id="f_variant" class="form-control" placeholder="(optional)">
    </div>
    <div class="col-md-2">
      <label class="form-label">Direction</label>
      <select id="f_dir" class="form-select">
        <option value="">-- Any --</option>
        <option value="borrow">Borrow</option>
        <option value="repay">Repay</option>
        <option value="writeoff">Write-off</option>
        <option value="adjust">Adjust</option>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label">From</label>
      <input type="date" id="f_from" class="form-control">
    </div>
    <div class="col-md-2">
      <label class="form-label">To</label>
      <input type="date" id="f_to" class="form-control">
    </div>
    <div class="col-md-1">
      <button id="f_apply" class="btn btn-primary w-100">Apply</button>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <table id="txnTbl" class="table table-bordered w-100">
        <thead class="table-light">
          <tr>
            <th>When</th>
            <th>BOM</th>
            <th>Source</th>
            <th>SKU</th>
            <th>Product</th>
            <th>Type</th>
            <th class="text-end">Qty</th>
            <th class="text-end">Unit Cost</th>
            <th class="text-end">Ext. Cost</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script>
const tbl = $('#txnTbl').DataTable({
  serverSide:true, responsive:true,
  dom: 'Blfrtip',
  buttons:[
    {extend:'excelHtml5', className:'btn btn-sm btn-success', text:'<i class="fas fa-file-excel me-1"></i> Excel'},
    {extend:'pdfHtml5',   className:'btn btn-sm btn-danger',  text:'<i class="fas fa-file-pdf me-1"></i> PDF', orientation:'landscape', pageSize:'A4'},
  ],
  ajax:{
    url: "{{ route('admin.production.boms.deficits.transactions.datatable') }}",
    data: d => {
      d.bom_id    = $('#f_bom').val();
      d.variant_id= $('#f_variant').val();
      d.direction = $('#f_dir').val();
      d.from      = $('#f_from').val();
      d.to        = $('#f_to').val();
    }
  },
  columns:[
    {data:'created_at'},
    {data:'bom'},

    // SOURCE column: render gracefully from whatever fields are present
    {
      data:null,
      name:'source_bom',
      render: function(_data, _type, row){
        if (row.source_bom && row.source_bom.trim() !== '') {
          return row.source_bom;
        }
        if (row.source_bom_code || row.source_bom_name) {
          const code = row.source_bom_code ? `#${row.source_bom_code}` : '';
          const name = row.source_bom_name ? ` — ${row.source_bom_name}` : '';
          return (code + name) || '—';
        }
        if (row.source_bom_id) {
          return `#${row.source_bom_id}`;
        }
        return '—';
      }
    },

    {data:'sku'},
    {data:'product'},
    {data:'direction'},
    {data:'qty_fmt', className:'text-end'},
    {data:'unit_cost', className:'text-end'},
    {data:'ext_cost', className:'text-end'},
    {data:'actions', orderable:false, searchable:false, className:'text-end'}
  ],

  // Inject a "view note" button into Actions when a note exists
  rowCallback: function(row, data){
    const $actions = $('td:last', row);
    // Avoid duplicates when table redraws
    $actions.find('.view-note').remove();

    if (data.note && String(data.note).trim() !== '') {
      const btnHtml = `
        <button type="button" class="btn btn-sm btn-outline-info me-1 view-note"
                data-note="${$('<div>').text(data.note).html()}">
          <i class="fas fa-sticky-note"></i>
        </button>`;
      $actions.prepend(btnHtml);
    }
  },

  drawCallback(){
    // delete button
    $('.del-txn').off().on('click', function(){
      const id = $(this).data('id');
      Swal.fire({title:'Delete transaction?', icon:'warning', showCancelButton:true})
      .then(r=>{
        if(!r.isConfirmed) return;
        $.ajax({
          url: "{{ route('admin.production.boms.deficits.transactions.destroy',':id') }}"
                 .replace(':id', id),
          type: 'DELETE',
          data: {_token:'{{ csrf_token() }}'}
        })
        .done(()=>{ tbl.ajax.reload(null,false); Swal.fire('Deleted','', 'success'); })
        .fail(x=> Swal.fire('Error', x.responseJSON?.message || 'Failed', 'error'));
      });
    });

    // view note button
    $('.view-note').off().on('click', function(){
      const note = $(this).data('note') || '';
      Swal.fire({
        title: 'Note',
        html: note.replace(/\n/g,'<br>') || '<em class="text-muted">No note</em>',
        confirmButtonText: 'Close'
      });
    });
  }
});

$('#f_apply').on('click', ()=> tbl.ajax.reload());

</script>
@endpush
