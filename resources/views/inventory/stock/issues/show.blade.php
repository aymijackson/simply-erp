@extends('layouts.master')
@section('title','Stock Issue #'.$issue->issue_no)

@section('content')
@php
  $type = $issue->issue_type; // 'normal' | 'bom' | 'sales'
  $typeMeta = [
    'normal' => ['label' => 'Normal Issue', 'icon' => 'fa-dolly',          'class' => 'secondary'],
    'bom'    => ['label' => 'BOM Issue',    'icon' => 'fa-diagram-project','class' => 'info'],
    'sales'  => ['label' => 'Sales Issue',  'icon' => 'fa-truck',          'class' => 'warning'],
  ][$type] ?? ['label'=>'Issue','icon'=>'fa-dolly','class'=>'secondary'];
@endphp
<div class="container-fluid">
  {{-- Header ---------------------------------------------------------------- --}}
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-3 text-primary">
      <i class="fas {{ $typeMeta['icon'] }} me-2"></i>
      Stock Issue <small>#{{ $issue->issue_no }}</small>
    </h1>
    <div class="d-print-none">
    
          <span class="text-white btn btn-{{ $issue->status=='posted'?'success':'secondary' }}">
            {{ ucfirst($issue->status) }}
          </span>
          @if(true)
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
   {{-- header block --}}
   <div class="card mb-4 shadow-sm">
     <div class="card-body row g-3">
        <div class="col-md-3">
            <strong>From Store</strong><br>{{ $issue->fromStore->name }}
        </div>
        <div class="col-md-3">
          <strong>Issue Type</strong><br>
          <span class="badge bg-{{ $typeMeta['class'] }} text-white">
            <i class="fas {{ $typeMeta['icon'] }} me-1"></i> {{ $typeMeta['label'] }}
          </span>
        </div>
        @if($issue->bomHeader)
          <div class="col-md-3">
            <strong>Linked BOM</strong><br>
            {{ ('#'.$issue->bomHeader->bom_code .' - '.$issue->bomHeader->name ) ?? '—' }}
          </div>
        @endif

        @if($issue->salesDelivery)
          <div class="col-md-3">
            <strong>Sales Delivery</strong><br>
            {{ $issue->salesDelivery->reference ?? ('#'.$issue->sales_delivery_id) }}
          </div>
        @endif
        
        <div class="col-md-3">
          <strong>Posted&nbsp;at</strong><br>
          {{ optional($issue->posted_at)->format('d M Y H:i') ?? '—' }}
        </div>
        <div class="col-md-3">
            <strong>Reference</strong><br>{{ $issue->reference ?? '—' }}
        </div>
        <div class="col-md-9">
            <strong>Reason</strong><br>{{ $issue->reason ?? '—' }}
        </div>
     </div>
   </div>

   {{-- lines table --}}
   <div class="card shadow-sm">
     <div class="card-body">
        <table id="lineTbl" class="table table-bordered w-100">
           <thead class="table-light">
             <tr>
               <th>SKU</th><th>Product</th>
               <th class="text-end">Qty</th>
               <th class="text-end">Unit Cost</th>
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
   $('#lineTbl').DataTable({
       paging:false, searching:false, ordering:false, info:false,
       ajax: "{{ route('admin.inventory.stock_issues.lines',$issue) }}",
       columns:[
          {data:'sku'},
          {data:'name'},
          {data:'qty',   className:'text-end'},
          {data:'u_cost',className:'text-end'},
          {data:'value', className:'text-end'}
       ],
       responsive:true
   });
});
</script>
@endpush
