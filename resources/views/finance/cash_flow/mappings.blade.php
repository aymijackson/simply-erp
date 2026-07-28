@extends('layouts.master')

@section('title','Cash Flow Mappings')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h3 text-primary mb-0">Cash Flow Mappings</h1>
      <small class="text-muted">Finance / Reports / Cash Flow</small>
    </div>

    <a class="btn btn-outline-secondary" href="{{ route('admin.finance.reports.cash_flow.index') }}">
      <i class="fas fa-arrow-left"></i> Back to Cash Flow
    </a>
  </div>

  <div class="alert alert-info">
    <i class="fas fa-info-circle me-1"></i>
    Map GL accounts to a Cash Flow section so the report can classify Investing, Financing and Non-cash movements.
    Accounts left unmapped are treated as Operating.
  </div>

  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-md-5">
          <label class="text-muted small">GL Account <span class="text-danger">*</span></label>
          <select class="form-control" id="gl_account_id" style="width:100%"></select>
        </div>

        <div class="col-md-3">
          <label class="text-muted small">Section <span class="text-danger">*</span></label>
          <select class="form-control" id="section">
            <option value="operating">Operating</option>
            <option value="investing">Investing</option>
            <option value="financing">Financing</option>
            <option value="non_cash">Non-cash</option>
          </select>
        </div>

        <div class="col-md-3">
          <label class="text-muted small">Label</label>
          <input type="text" class="form-control" id="label" placeholder="Optional display label">
        </div>

        <div class="col-md-1">
          <button class="btn btn-primary w-100" id="saveBtn"><i class="fas fa-save"></i></button>
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <table class="table table-sm table-bordered w-100" id="mappingsTable">
        <thead>
          <tr>
            <th>GL Account</th>
            <th>Section</th>
            <th>Label</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
$.ajaxSetup({headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}});

const dataUrl  = "{{ route('admin.finance.reports.cash_flow.mappings.data') }}";
const storeUrl = "{{ route('admin.finance.reports.cash_flow.mappings.store') }}";
const glUrl    = "{{ route('admin.finance.reports.cash_flow.lookups.gl') }}";
const deleteUrlBase = "{{ url('admin/finance/reports/cash-flow/mappings') }}";

const sectionLabels = {
  operating: 'Operating',
  investing: 'Investing',
  financing: 'Financing',
  non_cash: 'Non-cash',
};

function initGlSelect(){
  $('#gl_account_id').select2({
    theme: 'bootstrap-5',
    width: '100%',
    placeholder: 'Select GL account...',
    allowClear: true,
    ajax: {
      url: glUrl,
      dataType: 'json',
      delay: 200,
      data: p => ({ q: p.term || '' }),
      processResults: d => d,
      cache: true,
    },
  });
}

function loadMappings(){
  $.get(dataUrl).done(res => {
    const rows = res.data || [];
    const $tbody = $('#mappingsTable tbody').empty();

    if (!rows.length) {
      $tbody.append('<tr><td colspan="4" class="text-muted text-center">No mappings configured.</td></tr>');
      return;
    }

    rows.forEach(r => {
      $tbody.append(`
        <tr>
          <td>${r.gl_code} - ${r.gl_name}</td>
          <td>${sectionLabels[r.section] || r.section}</td>
          <td>${r.label || ''}</td>
          <td class="text-end">
            <button class="btn btn-sm btn-danger btn-delete-mapping" data-id="${r.id}">
              <i class="fas fa-trash-alt"></i>
            </button>
          </td>
        </tr>
      `);
    });
  });
}

$('#saveBtn').on('click', function(){
  const gl_account_id = $('#gl_account_id').val();
  const section = $('#section').val();
  const label = $('#label').val();

  if (!gl_account_id) {
    return swal('Missing GL Account', 'Please select a GL account.', 'warning');
  }

  $.post(storeUrl, { gl_account_id, section, label })
    .done(res => {
      $('#gl_account_id').val(null).trigger('change');
      $('#label').val('');
      loadMappings();
    })
    .fail(xhr => {
      swal('Error', xhr?.responseJSON?.message || 'Failed to save mapping.', 'error');
    });
});

$(document).on('click', '.btn-delete-mapping', function(){
  const id = $(this).data('id');

  $.ajax({
    url: `${deleteUrlBase}/${id}`,
    type: 'DELETE',
  }).done(() => loadMappings())
    .fail(xhr => swal('Error', xhr?.responseJSON?.message || 'Failed to delete mapping.', 'error'));
});

$(function(){
  initGlSelect();
  loadMappings();
});
</script>
@endpush
