@extends('layouts.master')
@section('title','Stock Issue #'.$issue->issue_no)

@section('content')
<div class="container-fluid">
   <h1 class="h3 mb-3 text-primary">
       <i class="fas fa-dolly me-1"></i> Stock Issue <small>#{{ $issue->issue_no }}</small>
   </h1>

   {{-- header block --}}
   <div class="card mb-4 shadow-sm">
     <div class="card-body row g-3">
        <div class="col-md-3">
            <strong>From Store</strong><br>{{ $issue->fromStore->name }}
        </div>
        <div class="col-md-3">
            <strong>Status</strong><br>
            <span class="text-white badge bg-{{ $issue->status=='posted'?'success':'secondary' }}">
               {{ ucfirst($issue->status) }}
            </span>
        </div>
        <div class="col-md-3">
            <strong>Posted at</strong><br>
            {{ optional($issue->created_at)->format('d M Y H:i') ?? '—' }}
        </div>
        <div class="col-md-3">
            <strong>Reference</strong><br>{{ $issue->reference ?? '—' }}
        </div>
        <div class="col-md-12">
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
