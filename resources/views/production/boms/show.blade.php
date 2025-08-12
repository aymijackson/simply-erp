@extends('layouts.master')
@section('title', 'BOM · '.$bom->name.' - '.$bom->bom_code)

@push('styles')
<link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css"/>
@endpush

@section('content')
<div class="container-fluid">

  {{-- Header ---------------------------------------------------------------- --}}
  <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h3 text-primary">
          Bill of Materials <small class="text-muted">#{{ $bom->id }}</small>
      </h1>

      <div class="d-print-none">
          @if($canEdit)
            <button id="editBtn"  class="btn btn-warning"><i class="fas fa-edit me-1"></i> Edit</button>
            <button id="saveBtn"  class="btn btn-success d-none"><i class="fas fa-save me-1"></i> Save</button>
            <button id="cancelBtn"class="btn btn-secondary d-none">Cancel</button>
          @endif
          <button onclick="window.print()" class="btn btn-outline-secondary">
              <i class="fas fa-print me-1"></i> Print
          </button>
          <a href="{{ route('admin.production.boms.index') }}"
             class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Back</a>
      </div>
  </div>

  {{-- Parent info ------------------------------------------------------------ --}}
  @include('production.boms.partials.parent-info') {{-- keep your old card here --}}

  {{-- Component items (view-mode table) -------------------------------------- --}}
  <div id="viewCard" class="card shadow-sm">
     <div class="card-body table-responsive">
        @include('production.boms.partials.items-table') {{-- original table --}}
     </div>
  </div>

  {{-- Editable form (hidden by default) -------------------------------------- --}}
  <form id="editForm" class="card shadow-sm d-none"
        action="{{ route('admin.production.boms.update',$bom) }}" method="POST">
      @csrf @method('PUT')
      <div class="card-body">
        {{-- lines grid --}}
        <table class="table table-sm table-bordered align-middle" id="linesTbl">
           <thead class="table-light">
               <tr>
                 <th style="width:50%">Component *</th>
                 <th style="width:15%" class="text-end">Qty / FG *</th>
                 <th style="width:15%" class="text-end">Unit Cost</th>
                 <th style="width:5%"  class="text-center">
                     <button type="button" id="addLn" class="btn btn-success btn-sm"><i class="fas fa-plus"></i></button>
                 </th>
               </tr>
           </thead>
           <tbody id="linesBody"></tbody>
        </table>
      </div>
  </form>

</div>
@endsection


@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
const canEdit = @json($canEdit);

if(canEdit){
/* ---------- helpers ---------- */
function tpl(idx){
return `<tr data-key="${idx}">
    <td>
       <select name="items[${idx}][product_variant_id]"
               class="form-select variant-select" required></select>
    </td>
    <td><input name="items[${idx}][qty_per_parent]" type="number" step="0.0001"
               class="form-control text-end" required></td>
    <td><input name="items[${idx}][unit_cost]" type="number" step="0.0001"
               class="form-control text-end"></td>
    <td class="text-center">
       <button type="button" class="btn btn-danger btn-sm remLn">
          <i class="fas fa-trash"></i>
       </button>
    </td>
</tr>`;}

function addLine(prefill={}){
   const idx = Date.now();
   $('#linesBody').append(tpl(idx));
   const $row = $('#linesBody tr:last');

   /* Select2 with remote search */
   $row.find('.variant-select').select2({
     ajax:{
        url:"{{ route('admin.inventory.stock_issues.fetch_variants') }}",
        dataType:'json',delay:250,
        data:params=>({q:params.term}),
        processResults:data=>({results:data})
     },
     dropdownParent: $('#editForm'),
     placeholder:'-- variant --', minimumInputLength:2, width:'100%'
   });

   if(prefill.id){
      const opt = new Option(prefill.text,prefill.id,true,true);
      $row.find('.variant-select').append(opt).trigger('change');
      $row.find('[name$="[qty]"]').val(prefill.yield_qty);
      $row.find('[name$="[unit_cost]"]').val(prefill.unit_cost);
   }
}

/* ---------- bootstrap existing lines into edit table ---------- */
window.populateLines = function(){
    $('#linesBody').empty();
    @foreach($bom->items as $ln)
       addLine({
           id:  {{ $ln->product_variant_id }},
           text:"{{ $ln->product_variant->sku }}",
           qty: {{ $ln->qty_per_parent }},
       });
    @endforeach
}

/* ---------- events ---------- */
$('#addLn').on('click',()=>addLine());
$('#linesBody').on('click','.remLn', function(){
   $(this).closest('tr').remove();
});

/* toggle view / edit */
$('#editBtn').on('click',()=>{
   populateLines();
   $('#viewCard').addClass('d-none');
   $('#editForm, #saveBtn, #cancelBtn').removeClass('d-none');
   $(this).addClass('d-none');
});
$('#cancelBtn').on('click',()=>{
   $('#editForm, #saveBtn, #cancelBtn').addClass('d-none');
   $('#viewCard, #editBtn').removeClass('d-none');
});

/* submit */
$('#saveBtn').on('click',()=>$('#editForm').submit());
}
</script>
@endpush
