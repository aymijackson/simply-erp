@extends('layouts.master')
@section('title','AP Aging')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h3 text-primary mb-0">AP Aging</h1>
      <small class="text-muted">Finance / Payables</small>
    </div>
  </div>

  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-md-3">
          <label class="text-muted small">As at</label>
          <input type="date" class="form-control" id="as_at" value="{{ date('Y-m-d') }}">
        </div>
        <div class="col-md-6">
          <label class="text-muted small">Supplier (optional)</label>
          <select class="form-control" id="supplier_id" style="width:100%"></select>
        </div>
        <div class="col-md-3 d-flex gap-2">
          <button class="btn btn-outline-primary w-100" id="runBtn"><i class="fas fa-play"></i> Run</button>
          <button class="btn btn-outline-secondary w-100" id="resetBtn"><i class="fas fa-undo"></i></button>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-3">
    @foreach(['Current','1-30','31-60','61-90','91-120','120+','Total'] as $x)
    <div class="col-md-3">
      <div class="card shadow-sm">
        <div class="card-body py-3">
          <div class="text-muted small">{{ $x }}</div>
          <div class="h5 mb-0" id="sum_{{ str_replace(['+','-'],'',strtolower($x)) }}">0.00</div>
        </div>
      </div>
    </div>
    @endforeach
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle" id="agingTable" style="width:100%">
          <thead class="bg-light">
            <tr>
              <th>Supplier</th>
              <th style="width:150px;">Bill No</th>
              <th style="width:120px;">Bill Date</th>
              <th style="width:120px;">Due Date</th>
              <th style="width:80px;">Curr</th>
              <th style="width:140px;" class="text-end">Balance Due</th>
              <th style="width:120px;" class="text-end">Days Overdue</th>
              <th style="width:90px;">Bucket</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
      <small class="text-muted">Source: Posted supplier bills with balance due.</small>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<style>.select2-container{z-index:2060 !important;}</style>
<script>
$.ajaxSetup({headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}});

const agingUrl = "{{ url('admin/finance/ap-aging/datatable') }}";
const supplierUrl = "{{ url('admin/finance/supplier-payments/lookups/suppliers') }}"; // reuse existing supplier lookup

function s2($el,url,placeholder){
  $el.select2({
    theme:'bootstrap-5', width:'100%', placeholder, allowClear:true,
    ajax:{ url, dataType:'json', delay:200, data:p=>({q:p.term||''}), processResults:d=>d, cache:true }
  });
}

let DT=null;

function setSummary(s){
  $('#sum_current').text((s.current||0).toFixed(2));
  $('#sum_130').text((s.d1_30||0).toFixed(2));
  $('#sum_3160').text((s.d31_60||0).toFixed(2));
  $('#sum_6190').text((s.d61_90||0).toFixed(2));
  $('#sum_91120').text((s.d91_120||0).toFixed(2));
  $('#sum_120').text((s.d120_plus||0).toFixed(2));
  $('#sum_total').text((s.total||0).toFixed(2));
}

function run(){
  const as_at = $('#as_at').val();
  const supplier_id = $('#supplier_id').val();

  $.get(agingUrl, {as_at, supplier_id})
    .done(res=>{
      setSummary(res.summary||{});
      if(DT){ DT.clear().rows.add(res.data||[]).draw(); return; }
      DT = $('#agingTable').DataTable({
        data: res.data||[],
        columns:[
          {data:'supplier'},
          {data:'bill_no'},
          {data:'bill_date'},
          {data:'due_date'},
          {data:'currency'},
          {data:'balance_due', className:'text-end'},
          {data:'days_overdue', className:'text-end'},
          {data:'bucket'},
        ],
        order:[[0,'asc']]
      });
    });
}

$('#runBtn').on('click', run);
$('#resetBtn').on('click', ()=>{
  $('#as_at').val(new Date().toISOString().slice(0,10));
  $('#supplier_id').val(null).trigger('change');
  run();
});

$(function(){
  s2($('#supplier_id'), supplierUrl, 'Select supplier (optional)...');
  run();
});
</script>
@endpush