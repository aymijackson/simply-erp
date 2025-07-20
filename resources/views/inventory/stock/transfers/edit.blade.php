@extends('layouts.master')
@section('title','Transfer')

@section('content')
@php $isEdit = isset($transfer); @endphp
<div class="container-fluid">
 <h1 class="h3 mb-3">{{ $isEdit ? 'Edit' : 'New' }} Transfer</h1>

 <form id="hdrForm" method="POST"
       action="{{ $isEdit ? route('admin.inventory.stock.transfers.post',$transfer) : route('admin.inventory.stock.transfers.store') }}">
   @csrf
   @if($isEdit)
       <div class="mb-2">Transfer #: <strong>{{ $transfer->transfer_no }}</strong></div>
   @endif

   <div class="row g-3 mb-3">
     <div class="col-md-4">
       <label class="form-label">From Store *</label>
       <select name="from_store_id" class="form-control" {{ $isEdit ? 'disabled' : '' }}>
         <option value="">--</option>
         @foreach($stores as $s)
           <option value="{{ $s->id }}" @selected($isEdit && $transfer->from_store_id==$s->id)>{{ $s->name }}</option>
         @endforeach
       </select>
     </div>
     <div class="col-md-4">
       <label class="form-label">To Store *</label>
       <select name="to_store_id" class="form-control" {{ $isEdit ? 'disabled' : '' }}>
         <option value="">--</option>
         @foreach($stores as $s)
           <option value="{{ $s->id }}" @selected($isEdit && $transfer->to_store_id==$s->id)>{{ $s->name }}</option>
         @endforeach
       </select>
     </div>
     <div class="col-md-4">
       <label class="form-label">Reason</label>
       <input type="text" name="reason" class="form-control" value="{{ $transfer->reason ?? '' }}">
     </div>
   </div>

   {{-- Lines table --}}
   @include('inventory.stock.transfers.partials.lines-table')

   <div class="mt-3">
       @if(!$isEdit)
         <button type="submit" class="btn btn-success">Save Draft</button>
       @elseif($transfer->status==='draft')
         <button type="submit" formaction="{{ route('admin.inventory.stock.transfers.post',$transfer) }}"
                 class="btn btn-primary">Post Transfer</button>
       @endif
       <a href="{{ route('admin.inventory.stock.transfers.index') }}" class="btn btn-secondary">Back</a>
   </div>
 </form>
</div>
@endsection
