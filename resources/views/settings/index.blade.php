@extends('layouts.master')

@section('title','Settings')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">Settings</h1>
            <small class="text-muted">Core / Settings</small>
        </div>

        <div class="d-flex gap-2">
            <button class="btn btn-danger d-none" id="bulkDeleteBtn">
                <i class="fas fa-trash"></i> Delete Selected
            </button>

            <button class="btn btn-primary" id="createBtn">
                <i class="fas fa-plus"></i> New Setting
            </button>
        </div>
    </div>

    <div class="row">
        {{-- Left: module/group filter --}}
        <div class="col-lg-3 mb-3">
            <div class="card shadow-sm">
                <div class="card-header fw-bold">
                    <i class="fas fa-layer-group me-1"></i> Groups
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <label class="text-muted small mb-1">Module</label>
                        <select class="form-control" id="filterModule">
                            <option value="">All modules</option>
                            @foreach($modules as $m)
                                <option value="{{ $m }}">{{ strtoupper($m) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="text-muted small mb-1">Group</label>
                        <select class="form-control" id="filterGroup">
                            <option value="">All groups</option>
                            @foreach($groups as $g)
                                <option value="{{ $g->id }}" data-module="{{ $g->module }}">
                                    {{ strtoupper($g->module) }} / {{ $g->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="text-muted small mb-1">Scope</label>
                        <select class="form-control" id="filterScope">
                            <option value="">All scopes</option>
                            <option value="global">global</option>
                            <option value="company">company</option>
                            <option value="location">location</option>
                            <option value="user">user</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-muted small mb-1">Key contains</label>
                        <input type="text" class="form-control" id="filterKey" placeholder="e.g. receipt_">
                    </div>

                    <button class="btn btn-outline-secondary btn-sm mt-3 w-100" id="applyFiltersBtn">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                </div>
            </div>
        </div>

        {{-- Right: datatable --}}
        <div class="col-lg-9 mb-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="settingsTable" style="width:100%;">
                            <thead class="bg-light">
                                <tr>
                                    <th style="width:35px;">
                                        <input type="checkbox" id="checkAll">
                                    </th>
                                    <th>ID</th>
                                    <th>Group</th>
                                    <th>Key</th>
                                    <th>Label</th>
                                    <th>Type</th>
                                    <th>Scope</th>
                                    <th>Value</th>
                                    <th>Active</th>
                                    <th style="width:120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>

                    <small class="text-muted">
                        Sensitive values are masked. File values show filename.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

@include('settings.partials.modal')
@endsection

@push('scripts')
<script>
$.ajaxSetup({headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}});

const dtUrl      = "{{ route('admin.settings.datatable') }}";
const storeUrl   = "{{ route('admin.settings.store') }}";
const uploadUrl  = "{{ route('admin.settings.upload') }}";

let DT = null;

function swalOk(msg){
  Swal.fire({icon:'success', title:'Success', text: msg || 'Done.'});
}
function swalErr(xhr, fallback){
  const msg = xhr?.responseJSON?.message || fallback || 'Something went wrong.';
  Swal.fire({icon:'error', title:'Error', text: msg});
}

function resetModal(){
  $('#settingForm')[0].reset();
  $('#setting_id').val('');
  $('#valueInputWrap').html('');
  $('#fileUploadWrap').addClass('d-none');
  $('#uploadedFilePath').val('');
}

function renderValueInput(type, value){
  value = value ?? '';
  let html = '';

  if(type === 'text'){
    html = `<textarea class="form-control" name="value" rows="3">${value}</textarea>`;
  } else if(type === 'bool'){
    const checked = (value === 1 || value === '1' || value === true || value === 'true') ? 'checked' : '';
    html = `
      <div class="custom-control custom-switch">
        <input type="hidden" name="value" value="0">
        <input type="checkbox" class="custom-control-input" id="boolSwitch" name="value" value="1" ${checked}>
        <label class="custom-control-label" for="boolSwitch">Enabled</label>
      </div>`;
  } else if(type === 'json'){
    html = `<textarea class="form-control" name="value" rows="4" placeholder='{"key":"value"}'>${value}</textarea>`;
  } else if(type === 'int'){
    html = `<input type="number" step="1" class="form-control" name="value" value="${value}">`;
  } else if(type === 'decimal'){
    html = `<input type="number" step="0.01" class="form-control" name="value" value="${value}">`;
  } else if(type === 'date'){
    html = `<input type="date" class="form-control" name="value" value="${value}">`;
  } else if(type === 'datetime'){
    html = `<input type="datetime-local" class="form-control" name="value" value="${value}">`;
  } else if(type === 'email'){
    html = `<input type="email" class="form-control" name="value" value="${value}">`;
  } else if(type === 'phone'){
    html = `<input type="text" class="form-control" name="value" value="${value}" placeholder="+234...">`;
  } else if(type === 'url'){
    html = `<input type="url" class="form-control" name="value" value="${value}">`;
  } else if(type === 'file'){
    // file handled via upload widget
    $('#fileUploadWrap').removeClass('d-none');
    html = `<input type="text" class="form-control" name="value" value="${value}" readonly placeholder="Upload a file below...">`;
  } else {
    html = `<input type="text" class="form-control" name="value" value="${value}">`;
  }

  $('#valueInputWrap').html(html);
}

function initDT(){
  DT = $('#settingsTable').DataTable({
    processing: true,
    serverSide: true,
    responsive: true,
    pageLength: 10,
    ajax: {
      url: dtUrl,
      data: function(d){
        d.module = $('#filterModule').val();
        d.setting_group_id = $('#filterGroup').val();
        d.scope = $('#filterScope').val();
        d.key = $('#filterKey').val();
      }
    },
    columns: [
      { data: 'id', orderable:false, searchable:false, render: function(id){
          return `<input type="checkbox" class="row-check" value="${id}">`;
        }
      },
      { data: 'id' },
      { data: 'group', orderable:false },
      { data: 'key' },
      { data: 'label' },
      { data: 'type' },
      { data: 'scope', orderable:false },
      { data: 'value', orderable:false },
      { data: 'active', orderable:false, searchable:false },
      { data: 'actions', orderable:false, searchable:false },
    ],
    order: [[1,'desc']]
  });
}

function refreshDT(){
  DT.ajax.reload(null,false);
}

$(document).on('change', '#filterModule', function(){
  const m = $(this).val();
  $('#filterGroup option').each(function(){
    const gm = $(this).data('module');
    if(!gm) return; // "all groups"
    $(this).toggle(!m || gm === m);
  });
  // reset group if hidden
  if($('#filterGroup option:selected').is(':hidden')) $('#filterGroup').val('');
});

$('#applyFiltersBtn').on('click', function(){
  refreshDT();
});

$('#checkAll').on('change', function(){
  $('.row-check').prop('checked', this.checked).trigger('change');
});

$(document).on('change', '.row-check', function(){
  const any = $('.row-check:checked').length > 0;
  $('#bulkDeleteBtn').toggleClass('d-none', !any);
});

$('#bulkDeleteBtn').on('click', function(){
  const ids = $('.row-check:checked').map((i,el)=>$(el).val()).get();
  Swal.fire({
    icon:'warning',
    title:'Delete selected?',
    text:'This will permanently delete the selected settings.',
    showCancelButton:true,
    confirmButtonText:'Yes, delete',
  }).then((r)=>{
    if(!r.isConfirmed) return;

    $.post("{{ route('admin.settings.bulk_delete') }}", {ids})
      .done(res=>{ swalOk(res.message); refreshDT(); $('#bulkDeleteBtn').addClass('d-none'); $('#checkAll').prop('checked',false); })
      .fail(xhr=>swalErr(xhr,'Failed to delete.'));
  });
});

$('#createBtn').on('click', function(){
  resetModal();
  $('#settingModalTitle').text('New Setting');
  renderValueInput($('#value_type').val(), '');
  $('#settingModal').modal('show');
});

$(document).on('change', '#value_type', function(){
  // re-render value input on type change
  renderValueInput($(this).val(), $('input[name="value"]').val() || $('textarea[name="value"]').val() || '');
});

$(document).on('change', '#scope', function(){
  const sc = $(this).val();
  if(sc === 'global'){
    $('#scope_id').val('').prop('disabled', true);
  } else {
    $('#scope_id').prop('disabled', false);
  }
});

$('#uploadFileBtn').on('click', function(){
  const f = $('#settingFile')[0].files[0];
  if(!f){
    Swal.fire({icon:'info', title:'Select a file', text:'Choose a file to upload first.'});
    return;
  }

  const fd = new FormData();
  fd.append('file', f);

  $.ajax({
    url: uploadUrl,
    method: 'POST',
    data: fd,
    processData: false,
    contentType: false,
  }).done(function(res){
    $('#uploadedFilePath').val(res.path);
    // set value to uploaded path
    $('input[name="value"]').val(res.path);
    swalOk('File uploaded.');
  }).fail(function(xhr){
    swalErr(xhr,'Upload failed.');
  });
});

$('#saveSettingBtn').on('click', function(){
  const id = $('#setting_id').val();
  const method = id ? 'PUT' : 'POST';
  const url = id ? (`{{ url('admin/settings') }}/${id}`) : storeUrl;

  const form = $('#settingForm');
  let payload = form.serializeArray();

  // If file-type and uploaded file exists, force "value" to uploaded path
  if($('#value_type').val() === 'file'){
    const p = $('#uploadedFilePath').val();
    if(p) {
      payload = payload.filter(x=>x.name !== 'value');
      payload.push({name:'value', value:p});
    }
  }

  $.ajax({
    url, method,
    data: $.param(payload)
  }).done(function(res){
    $('#settingModal').modal('hide');
    swalOk(res.message || 'Saved.');
    refreshDT();
  }).fail(function(xhr){
    swalErr(xhr,'Failed to save.');
  });
});

// edit button
$(document).on('click', '.btn-edit-setting', function(){
  resetModal();
  const s = $(this).data('json');

  $('#settingModalTitle').text('Edit Setting');
  $('#setting_id').val(s.id);

  $('#setting_group_id').val(s.setting_group_id);
  $('#key').val(s.key);
  $('#label').val(s.label || '');
  $('#description').val(s.description || '');
  $('#value_type').val(s.value_type);
  $('#scope').val(s.scope);
  $('#scope_id').val(s.scope_id || '');

  $('#is_sensitive').prop('checked', !!s.is_sensitive);
  $('#is_required').prop('checked', !!s.is_required);
  $('#is_active').prop('checked', !!s.is_active);
  $('#sort_order').val(s.sort_order ?? 0);

  // render proper input
  renderValueInput(s.value_type, s.value ?? '');

  // scope_id enable/disable
  if(s.scope === 'global') $('#scope_id').prop('disabled', true);

  $('#settingModal').modal('show');
});

// delete button
$(document).on('click', '.btn-del-setting', function(){
  const id = $(this).data('id');

  Swal.fire({
    icon:'warning',
    title:'Delete this setting?',
    text:'This cannot be undone.',
    showCancelButton:true,
    confirmButtonText:'Yes, delete'
  }).then((r)=>{
    if(!r.isConfirmed) return;

    $.ajax({url:`{{ url('admin/settings') }}/${id}`, method:'DELETE'})
      .done(res=>{ swalOk(res.message); refreshDT(); })
      .fail(xhr=>swalErr(xhr,'Failed to delete.'));
  });
});

$(function(){
  // default state
  $('#scope_id').prop('disabled', true);
  renderValueInput($('#value_type').val(), '');
  initDT();
});
</script>
@endpush
