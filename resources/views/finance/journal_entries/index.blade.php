@extends('layouts.master')

@section('title','Journal Entries')

@section('content')
@push('styles')
<style>
  .audit-col {
    min-width: 220px;
    white-space: normal !important;
    vertical-align: top;
  }

  .audit-cell .badge {
    font-size: .72rem;
  }

  .audit-cell .text-muted.small {
    line-height: 1.2;
  }
</style>
@endpush
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h3 text-primary mb-0">Journal Entries</h1>
      <small class="text-muted">Finance / General Ledger</small>
    </div>

    <button class="btn btn-primary" id="createBtn">
      <i class="fas fa-plus"></i> New Journal Entry
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
            <option value="reversed">Reversed</option>
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
          <input type="text" class="form-control" id="f_q" placeholder="entry no, reference, memo...">
        </div>
        <div class="col-md-2 d-flex gap-2">
          <button class="btn btn-outline-primary w-100" id="applyBtn"><i class="fas fa-filter"></i> Apply</button>
          <button class="btn btn-outline-secondary w-100" id="resetBtn"><i class="fas fa-undo"></i></button>
        </div>
      </div>

      <div class="alert alert-info mt-3 mb-0">
        <i class="fas fa-info-circle me-1"></i>
        Draft entries can be edited. Posted entries can be reversed. Voided entries remain for audit.
      </div>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle" id="jeTable" style="width:100%">
          <thead class="bg-light">
            <tr>
              <th style="width:70px;">ID</th>
              <th style="width:170px;">Entry No</th>
              <th style="width:120px;">Date</th>
              <th>Reference</th>
              <th style="width:140px;">Total</th>
              <th style="width:120px;">Status</th>
              @can('finance.journals.audit')
              <th style="width:220px;">Audit</th>
              @endcan
              <th style="width:210px;">Actions</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

@include('finance.journal_entries.partials.modal')
@include('finance.journal_entries.partials.show_modal')

@endsection

@push('scripts')
<script>
$.ajaxSetup({headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}});

const dtUrl    = "{{ route('admin.finance.journal_entries.datatable') }}";
const storeUrl = "{{ route('admin.finance.journal_entries.store') }}";
const baseUrl  = "{{ url('admin/finance/journal-entries') }}";

const glUrl    = "{{ route('admin.finance.journal_entries.accounts') }}";
const bankUrl  = "{{ route('admin.finance.journal_entries.bank_accounts') }}";
const curUrl   = "{{ route('admin.finance.journal_entries.currencies') }}";

let DT = null;

// ✅ Safe SweetAlert wrapper (supports SweetAlert v1, SweetAlert2, and avoids "class constructor" crash)
function swalWrapper(optsOrTitle, text, icon){
  const isString = (typeof optsOrTitle === 'string');

  // SweetAlert2 style: Swal.fire(...)
  if (typeof Swal !== 'undefined' && Swal && typeof Swal.fire === 'function') {
    if (isString) return Swal.fire({ title: optsOrTitle, text: text || '', icon: icon || 'info' });
    return Swal.fire(optsOrTitle || {});
  }

  // Some templates alias SweetAlert2 into "swal" as an object/class with .fire()
  if (typeof window.swal !== 'undefined' && window.swal && typeof window.swal.fire === 'function') {
    if (isString) return window.swal.fire({ title: optsOrTitle, text: text || '', icon: icon || 'info' });
    return window.swal.fire(optsOrTitle || {});
  }

  // SweetAlert v1 style: swal(title, text, icon) OR swal(options)
  if (typeof window.swal === 'function') {
    if (isString) return window.swal(optsOrTitle, text || '', icon || 'info');
    return window.swal(optsOrTitle || {});
  }

  // Final fallback
  alert(isString ? (optsOrTitle + (text ? '\n' + text : '')) : (optsOrTitle?.text || 'Done'));
}

function swalOk(msg){
  return swalWrapper({ icon:'success', title:'Success', text: msg || 'Done.' });
}
function swalErr(msg){
  return swalWrapper({ icon:'error', title:'Error', text: msg || 'Something went wrong.' });
}

function initDT(){
  DT = $('#jeTable').DataTable({
    processing:true,
    serverSide:true,
    responsive:true,
    pageLength:10,
    ajax:{
      url: dtUrl,
      data: function(d){
        d.status = $('#f_status').val();
        d.date_from = $('#f_from').val();
        d.date_to = $('#f_to').val();
        d.q = $('#f_q').val();
      }
    },
    columns:[
      {data:'id'},
      {data:'entry_no'},
      {data:'entry_date'},
      {data:'reference'},
      {data:'total', className:'text-end'},
      {data:'status', orderable:false, searchable:false},
      @can('finance.journals.audit')
      {data:'audit'},
      @endcan
      {data:'actions', orderable:false, searchable:false},
    ],
    order:[[0,'desc']]
  });
}
function refreshDT(){ DT.ajax.reload(null,false); }

$('#applyBtn').on('click', refreshDT);
$('#resetBtn').on('click', function(){
  $('#f_status').val('');
  $('#f_from').val('');
  $('#f_to').val('');
  $('#f_q').val('');
  refreshDT();
});

/** ===== Modal + Lines ===== */

function initSelect2GL($el){
  $el.select2({
    theme:'bootstrap-5',
    width:'100%',
    placeholder:'Select GL account...',
    allowClear:true,
    dropdownParent: $('#jeModal'),
    ajax:{
      url: glUrl, dataType:'json', delay:200,
      data: p => ({ q: p.term || '' }),
      processResults: d => d, cache:true
    }
  });
}
function initSelect2Bank($el){
  $el.select2({
    theme:'bootstrap-5',
    width:'100%',
    placeholder:'Bank account (optional)...',
    allowClear:true,
    dropdownParent: $('#jeModal'),
    ajax:{
      url: bankUrl, dataType:'json', delay:200,
      data: p => ({ q: p.term || '' }),
      processResults: d => d, cache:true
    }
  });
}
function initSelect2Currency($el){
  $el.select2({
    theme:'bootstrap-5',
    width:'100%',
    placeholder:'Currency (optional)...',
    allowClear:true,
    dropdownParent: $('#jeModal'),
    ajax:{
      url: curUrl, dataType:'json', delay:200,
      data: p => ({ q: p.term || '' }),
      processResults: d => d, cache:true
    }
  });
}

function resetModal(){
  $('#jeForm')[0].reset();
  $('#je_id').val('');
  $('#linesTbody').html('');
  $('#je_status_badge').html('');
  addLine();
  addLine();
  recalcTotals();
}

function addLine(line=null){
  const idx = Date.now() + Math.floor(Math.random()*1000);

  const tr = $(`
    <tr data-row="${idx}">
      <td style="width:28%">
        <select class="form-control gl-select" name="lines[${idx}][account_id]" required></select>
      </td>
      <td style="width:16%">
        <input type="number" step="0.01" class="form-control line-debit text-end" name="lines[${idx}][debit]" value="${line?.debit ?? ''}" placeholder="0.00">
      </td>
      <td style="width:16%">
        <input type="number" step="0.01" class="form-control line-credit text-end" name="lines[${idx}][credit]" value="${line?.credit ?? ''}" placeholder="0.00">
      </td>
      <td style="width:16%">
        <select class="form-control cur-select" name="lines[${idx}][currency_code]"></select>
      </td>
      <td style="width:10%">
        <input type="number" step="0.000001" class="form-control line-fx text-end" name="lines[${idx}][fx_rate]" value="${line?.fx_rate ?? ''}" placeholder="1.000000">
      </td>
      <td style="width:10%">
        <select class="form-control bank-select" name="lines[${idx}][bank_account_id]"></select>
      </td>
      <td style="width:4%">
        <button type="button" class="btn btn-outline-danger btn-sm btn-del-line"><i class="fas fa-times"></i></button>
      </td>
    </tr>
  `);

  $('#linesTbody').append(tr);

  const $gl = tr.find('.gl-select');
  initSelect2GL($gl);
  if (line?.account_id && line?.account_label) {
    const opt = new Option(line.account_label, line.account_id, true, true);
    $gl.append(opt).trigger('change');
  }

  const $cur = tr.find('.cur-select');
  initSelect2Currency($cur);
  if (line?.currency_code) {
    const optC = new Option(line.currency_code, line.currency_code, true, true);
    $cur.append(optC).trigger('change');
  }

  const $bank = tr.find('.bank-select');
  initSelect2Bank($bank);
  if (line?.bank_account_id && line?.bank_account_label) {
    const optB = new Option(line.bank_account_label, line.bank_account_id, true, true);
    $bank.append(optB).trigger('change');
  }

  recalcTotals();
}

function recalcTotals(){
  let dr = 0;
  let cr = 0;

  $('#linesTbody tr').each(function(){
    const debit  = parseFloat($(this).find('.line-debit').val() || '0');
    const credit = parseFloat($(this).find('.line-credit').val() || '0');

    dr += isNaN(debit) ? 0 : debit;
    cr += isNaN(credit) ? 0 : credit;
  });

  const diff = dr - cr;
  const balanced = Math.abs(diff) <= 0.005 && dr > 0 && cr > 0;

  $('#totDebit').text(dr.toFixed(2));
  $('#totCredit').text(cr.toFixed(2));
  $('#totDiff').text(diff.toFixed(2));

  $('#balanceBadge')
    .removeClass('bg-success bg-danger bg-secondary')
    .addClass(balanced ? 'bg-success' : 'bg-danger')
    .text(balanced ? 'BALANCED' : 'NOT BALANCED');
}
$(document).on('input', '.line-debit', function(){
  // prevent both debit and credit
  if (parseFloat($(this).val()||'0') > 0) $(this).closest('tr').find('.line-credit').val('');
  recalcTotals();
});
$(document).on('input', '.line-credit', function(){
  if (parseFloat($(this).val()||'0') > 0) $(this).closest('tr').find('.line-debit').val('');
  recalcTotals();
});
$(document).on('click', '.btn-del-line', function(){
  $(this).closest('tr').remove();
  if ($('#linesTbody tr').length < 2) addLine();
  recalcTotals();
});
$('#addLineBtn').on('click', ()=> addLine());

$('#createBtn').on('click', function(){
  resetModal();
  $('#jeModalTitle').text('New Journal Entry');
  $('#jeModal').modal('show');
});

// View
$(document).on('click','.viewEntry',function(){

let id=$(this).data('id');

$.get('/admin/finance/journal-entries/'+id,function(res){

$('#jeRef').text(res.entry.reference);

let html='';

res.lines.forEach(function(l){

html+=`
<tr>
<td>${l.code} - ${l.name}</td>
<td>${Number(l.debit).toFixed(2)}</td>
<td>${Number(l.credit).toFixed(2)}</td>
</tr>
`;

});

$('#journalLines').html(html);

$('#journalModal').modal('show');

});

});
// Save
$('#saveJeBtn').on('click', function(){
  const id = $('#je_id').val();
  const method = id ? 'PUT' : 'POST';
  const url = id ? `${baseUrl}/${id}` : storeUrl;

  // quick balance check
  const diff = parseFloat($('#totDiff').text() || '999999');
  const dr = parseFloat($('#totDebit').text() || '0');
  const cr = parseFloat($('#totCredit').text() || '0');
  if (Math.abs(diff) > 0.005 || dr <= 0 || cr <= 0) {
    swalErr('Journal entry must balance (Debit = Credit) and be > 0.');
    return;
  }

  $.ajax({url, method, data: $('#jeForm').serialize()})
    .done(res=>{
      $('#jeModal').modal('hide');
      swalOk(res.message || 'Saved.');
      refreshDT();
    })
    .fail(xhr=> swalErr(xhr?.responseJSON?.message || 'Failed to save.'));
});

// Edit: load lines via AJAX
$(document).on('click', '.btn-edit-je', function(){
  resetModal();
  const je = $(this).data('json');

  $('#jeModalTitle').text('Edit Journal Entry');
  $('#je_id').val(je.id);
  $('#entry_no').val(je.entry_no || '');
  $('#entry_date').val(je.entry_date || '');
  $('#reference').val(je.reference || '');
  $('#memo').val(je.memo || '');

  $('#je_status_badge').html(je.status ? `<span class="badge bg-secondary">${je.status.toUpperCase()}</span>` : '');

  // load lines
  $.get(`{{ url('admin/finance/journal-entries') }}/${je.id}/lines`)
    .done(r=>{
      $('#linesTbody').html('');
      (r.lines || []).forEach(ln=> addLine(ln));
      if ($('#linesTbody tr').length < 2){ addLine(); addLine(); }
      recalcTotals();
      $('#jeModal').modal('show');
    })
    .fail(()=> swalErr('Could not load lines.'));
});

// Delete
$(document).on('click', '.btn-del-je', function(){
  const id = $(this).data('id');

  swalWrapper({
    icon:'warning', title:'Delete draft entry?',
    text:'This will permanently delete the draft journal entry.',
    showCancelButton:true, confirmButtonText:'Yes, delete'
  }).then(r=>{
    const ok = (r && (r.isConfirmed === true || r === true));
    if(!ok) return;

    $.ajax({url:`${baseUrl}/${id}`, method:'DELETE'})
      .done(res=>{ swalOk(res.message||'Deleted.'); refreshDT(); })
      .fail(xhr=> swalErr(xhr?.responseJSON?.message || 'Delete failed.'));
  });
});

// Post / Reverse / Void
$(document).on('click', '.btn-post-je', function(){
  const id = $(this).data('id');
  swalWrapper({icon:'warning', title:'Post entry?', text:'This will lock the entry (audit).', showCancelButton:true, confirmButtonText:'Yes, post'})
    .then(r=>{
      const ok = (r && (r.isConfirmed === true || r === true));
      if(!ok) return;
      $.post(`${baseUrl}/${id}/post`)
        .done(res=>{ swalOk(res.message||'Posted.'); refreshDT(); })
        .fail(xhr=> swalErr(xhr?.responseJSON?.message || 'Post failed.'));
    });
});

$(document).on('click', '.btn-reverse-je', function(){
  const id = $(this).data('id');
  swalWrapper({icon:'warning', title:'Reverse entry?', text:'This creates a reversal journal entry.', showCancelButton:true, confirmButtonText:'Yes, reverse'})
    .then(r=>{
      const ok = (r && (r.isConfirmed === true || r === true));
      if(!ok) return;
      $.post(`${baseUrl}/${id}/reverse`)
        .done(res=>{ swalOk(res.message||'Reversed.'); refreshDT(); })
        .fail(xhr=> swalErr(xhr?.responseJSON?.message || 'Reverse failed.'));
    });
});

$(document).on('click', '.btn-void-je', function(){
  const id = $(this).data('id');
  swalWrapper({icon:'warning', title:'Void entry?', text:'Voided entries remain for audit trail.', showCancelButton:true, confirmButtonText:'Yes, void'})
    .then(r=>{
      const ok = (r && (r.isConfirmed === true || r === true));
      if(!ok) return;
      $.post(`${baseUrl}/${id}/void`)
        .done(res=>{ swalOk(res.message||'Voided.'); refreshDT(); })
        .fail(xhr=> swalErr(xhr?.responseJSON?.message || 'Void failed.'));
    });
});

$(document).on('select2:select', '.taxcode-select', function(e){
  const data = e.params.data || {};
  const $tr = $(this).closest('tr');

  let rate = parseFloat(data.rate || 0);
  if (data.is_exempt || data.is_out_of_scope) {
    rate = 0;
  }

  $tr.find('.ln-taxrate').val(rate.toFixed(4));
  recalcTotals();
});

$(document).on('select2:clear', '.taxcode-select', function(){
  const $tr = $(this).closest('tr');
  $tr.find('.ln-taxrate').val('');
  recalcTotals();
});

$(function(){
  initDT();
});
</script>
@endpush