@extends('layouts.master')
@section('title','Supplier Payments')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h3 text-primary mb-0">Supplier Payments</h1>
      <small class="text-muted">Finance / Payables</small>
    </div>

    <button class="btn btn-primary" id="createBtn">
      <i class="fas fa-plus"></i> New Payment
    </button>
  </div>

  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-md-2">
          <label class="text-muted small">Status</label>
          <select class="form-control" id="f_status">
            <option value="">All</option>
            <option value="draft">Draft</option>
            <option value="posted">Posted</option>
            <option value="voided">Voided</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="text-muted small">From</label>
          <input type="date" class="form-control" id="f_from">
        </div>
        <div class="col-md-2">
          <label class="text-muted small">To</label>
          <input type="date" class="form-control" id="f_to">
        </div>
        <div class="col-md-4">
          <label class="text-muted small">Search</label>
          <input type="text" class="form-control" id="f_q" placeholder="payment no, supplier, reference...">
        </div>
        <div class="col-md-2 d-flex gap-2">
          <button class="btn btn-outline-primary w-100" id="applyBtn"><i class="fas fa-filter"></i> Apply</button>
          <button class="btn btn-outline-secondary w-100" id="resetBtn"><i class="fas fa-undo"></i></button>
        </div>
      </div>

      <div class="alert alert-info mt-3 mb-0">
        <i class="fas fa-info-circle me-1"></i>
        Draft payments can be edited/deleted. Posting creates a Journal Entry and reduces supplier bills balance due.
      </div>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle" id="payTable" style="width:100%">
          <thead class="bg-light">
            <tr>
              <th style="width:70px;">ID</th>
              <th style="width:160px;">Payment No</th>
              <th style="width:120px;">Date</th>
              <th>Supplier</th>
              <th style="width:160px;">Bank</th>
              <th style="width:70px;">Curr</th>
              <th style="width:140px;" class="text-end">Amount</th>
              <th style="width:110px;">Status</th>
              <th style="width:250px;">Actions</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>

      <small class="text-muted">
        AP Control Account points to <code>finance_accounts</code> (GL). If blank, posting should fallback to Company Settings mapping.
      </small>
    </div>
  </div>
</div>

@include('finance.supplier_payments.partials.modal')
@endsection

@push('scripts')
<script>
$.ajaxSetup({headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}});

const dtUrl   = "{{ url('admin/finance/supplier-payments/datatable') }}";
const storeUrl= "{{ url('admin/finance/supplier-payments') }}";
const baseUrl = "{{ url('admin/finance/supplier-payments') }}";

const suppliersUrl = "{{ url('admin/finance/lookups/suppliers') }}";
const billsUrl     = "{{ url('admin/finance/lookups/open-supplier-bills') }}";
const bankUrl      = "{{ url('admin/finance/lookups/bank-accounts') }}";
const curUrl       = "{{ url('admin/finance/lookups/currencies') }}";
const apUrl        = "{{ url('admin/finance/lookups/ap-control-accounts') }}";

let DT=null;

function swalOk(msg){ return (window.Swal?.fire) ? Swal.fire({icon:'success',title:'Success',text:msg||'Done.'}) : alert(msg||'Done'); }
function swalErr(msg){ return (window.Swal?.fire) ? Swal.fire({icon:'error',title:'Error',text:msg||'Something went wrong.'}) : alert(msg||'Error'); }
function swalAsk(opts){ return (window.Swal?.fire) ? Swal.fire(opts) : Promise.resolve({isConfirmed: confirm(opts?.title || 'Confirm?')}); }

function initDT(){
  DT = $('#payTable').DataTable({
    processing:true, serverSide:true, responsive:true, pageLength:10,
    ajax:{ url: dtUrl, data(d){
      d.status=$('#f_status').val(); d.date_from=$('#f_from').val(); d.date_to=$('#f_to').val(); d.q=$('#f_q').val();
    }},
    columns:[
      {data:'id'},
      {data:'payment_no'},
      {data:'payment_date'},
      {data:'supplier'},
      {data:'bank'},
      {data:'currency'},
      {data:'amount', className:'text-end'},
      {data:'status', orderable:false, searchable:false},
      {data:'actions', orderable:false, searchable:false},
    ],
    order:[[0,'desc']]
  });
}
function refreshDT(){ DT.ajax.reload(null,false); }

$('#applyBtn').on('click', refreshDT);
$('#resetBtn').on('click', ()=>{ $('#f_status,#f_from,#f_to,#f_q').val(''); refreshDT(); });

function s2($el, url, placeholder){
  $el.select2({
    theme:'bootstrap-5', width:'100%', placeholder, allowClear:true,
    dropdownParent: $('#payModal'),
    ajax:{ url, dataType:'json', delay:200,
      data: p => ({ q: p.term || '', supplier_id: $('#supplier_id').val() || '' }),
      processResults: d => d,
      cache:true
    }
  });
}

// Fix modal overflow + Select2 dropdown z-index
$('#payModal').on('shown.bs.modal', function(){
  $(this).find('.modal-body').css({'max-height':'calc(100vh - 200px)','overflow-y':'auto'});
});
$(document).on('select2:open', () => {
  $('.select2-container--bootstrap-5 .select2-dropdown').css('z-index', 20000);
});

function initModalSelects(){
  s2($('#supplier_id'), suppliersUrl, 'Select supplier...');
  s2($('#currency_code'), curUrl, 'Select currency...');
  s2($('#bank_account_id'), bankUrl, 'Select bank account...');
  s2($('#ap_control_account_id'), apUrl, 'Select AP control account (optional)...');
  s2($('#bill_picker'), billsUrl, 'Search open bills...');
}

function resetModal(){
  $('#payForm')[0].reset();
  $('#payment_id').val('');
  $('#pay_status_badge').html('');
  $('#allocTbody').html('');

  $('#supplier_id,#currency_code,#bank_account_id,#ap_control_account_id,#bill_picker').val(null).trigger('change');
  recalcAmount();
}

function addAllocationRow(bill){
  const idx = Date.now() + Math.floor(Math.random()*1000);
  const balance = parseFloat(bill.balance_due || 0);

  const tr = $(`
    <tr data-row="${idx}">
      <td style="width:45%"><input class="form-control" readonly value="${bill.text || ''}"></td>
      <td style="width:25%" class="text-end"><input class="form-control text-end" readonly value="${balance.toFixed(2)}"></td>
      <td style="width:25%"><input type="number" step="0.01" min="0.01" class="form-control text-end alloc-amt" name="allocations[${idx}][allocated_amount]" value="${balance.toFixed(2)}"></td>
      <td style="width:5%">
        <input type="hidden" name="allocations[${idx}][supplier_bill_id]" value="${bill.id}">
        <button type="button" class="btn btn-outline-danger btn-sm btn-del-alloc"><i class="fas fa-times"></i></button>
      </td>
    </tr>
  `);

  $('#allocTbody').append(tr);
  recalcAmount();
}

function recalcAmount(){
  let sum=0;
  $('.alloc-amt').each(function(){ sum += parseFloat($(this).val()||'0') || 0; });
  $('#amountLbl').text(sum.toFixed(2));
}

$(document).on('input', '.alloc-amt', recalcAmount);
$(document).on('click', '.btn-del-alloc', function(){ $(this).closest('tr').remove(); recalcAmount(); });

$('#supplier_id').on('change', function(){
  // refresh bill search to this supplier
  $('#bill_picker').val(null).trigger('change');
});

$('#bill_picker').on('select2:select', function(e){
  const bill = e.params.data;
  // prevent duplicates
  const exists = $('#allocTbody input[type="hidden"][value="'+bill.id+'"]').length > 0;
  if(exists) return;
  addAllocationRow(bill);
  $('#bill_picker').val(null).trigger('change');
});

$('#createBtn').on('click', ()=>{ resetModal(); $('#payModalTitle').text('New Supplier Payment'); $('#payModal').modal('show'); });

$('#savePayBtn').on('click', function(){
  const id = $('#payment_id').val();
  const method = id ? 'PUT' : 'POST';
  const url = id ? `${baseUrl}/${id}` : storeUrl;

  if(!$('#payment_date').val()) return swalErr('Payment date is required.');
  if(!$('#supplier_id').val()) return swalErr('Supplier is required.');
  if(!$('#bank_account_id').val()) return swalErr('Bank account is required.');
  if($('#allocTbody tr').length < 1) return swalErr('Allocate at least one bill.');

  $.ajax({url, method, data: $('#payForm').serialize()})
    .done(res=>{ $('#payModal').modal('hide'); swalOk(res.message||'Saved.'); refreshDT(); })
    .fail(xhr=> swalErr(xhr?.responseJSON?.message || 'Failed to save.'));
});

$(document).on('click', '.btn-edit-pay', function(){
  resetModal();
  const p = $(this).data('json');

  $('#payModalTitle').text('Edit Supplier Payment');
  $('#payment_id').val(p.id);
  $('#payment_no').val(p.payment_no || '');
  $('#payment_date').val(p.payment_date || '');
  $('#reference').val(p.reference || '');
  $('#memo').val(p.memo || '');

  $('#pay_status_badge').html(p.status ? `<span class="badge bg-secondary">${String(p.status).toUpperCase()}</span>` : '');

  if(p.supplier_id && p.supplier_label){
    $('#supplier_id').append(new Option(p.supplier_label, p.supplier_id, true, true)).trigger('change');
  }
  if(p.currency_code){
    $('#currency_code').append(new Option(p.currency_code, p.currency_code, true, true)).trigger('change');
  }
  if(p.bank_account_id && p.bank_account_label){
    $('#bank_account_id').append(new Option(p.bank_account_label, p.bank_account_id, true, true)).trigger('change');
  }
  if(p.ap_control_account_id && p.ap_control_account_label){
    $('#ap_control_account_id').append(new Option(p.ap_control_account_label, p.ap_control_account_id, true, true)).trigger('change');
  }

  $.get(`${baseUrl}/${p.id}/allocations`)
    .done(r=>{
      $('#allocTbody').html('');
      (r.allocations||[]).forEach(a=>{
        addAllocationRow({id:a.supplier_bill_id, text:a.bill_label, balance_due:a.allocated_amount});
        // set exact allocated amount
        $('#allocTbody tr:last .alloc-amt').val(parseFloat(a.allocated_amount||0).toFixed(2));
      });
      recalcAmount();
      $('#payModal').modal('show');
    })
    .fail(()=> swalErr('Could not load allocations.'));
});

$(document).on('click', '.btn-del-pay', async function(){
  const id = $(this).data('id');
  const r = await swalAsk({icon:'warning',title:'Delete payment?',text:'Draft only. This is a soft delete.',showCancelButton:true,confirmButtonText:'Yes, delete'});
  if(!r.isConfirmed) return;
  $.ajax({url:`${baseUrl}/${id}`, method:'DELETE'})
    .done(res=>{ swalOk(res.message||'Deleted.'); refreshDT(); })
    .fail(xhr=> swalErr(xhr?.responseJSON?.message || 'Delete failed.'));
});

$(document).on('click', '.btn-post-pay', async function(){
  const id = $(this).data('id');
  const r = await swalAsk({icon:'warning',title:'Post payment?',text:'This will create a journal entry and reduce bills balance due.',showCancelButton:true,confirmButtonText:'Yes, post'});
  if(!r.isConfirmed) return;
  $.post(`${baseUrl}/${id}/post`)
    .done(res=>{ swalOk(res.message||'Posted.'); refreshDT(); })
    .fail(xhr=> swalErr(xhr?.responseJSON?.message || 'Post failed.'));
});

$(document).on('click', '.btn-void-pay', async function(){
  const id = $(this).data('id');
  const r = await swalAsk({icon:'warning',title:'Void payment?',text:'Voided payments remain for audit.',showCancelButton:true,confirmButtonText:'Yes, void'});
  if(!r.isConfirmed) return;
  $.post(`${baseUrl}/${id}/void`)
    .done(res=>{ swalOk(res.message||'Voided.'); refreshDT(); })
    .fail(xhr=> swalErr(xhr?.responseJSON?.message || 'Void failed.'));
});

$(function(){ initModalSelects(); initDT(); });
</script>
@endpush