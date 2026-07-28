@extends('layouts.master')

@section('title', 'Notifications')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h3 text-primary mb-0">Notifications</h1>
            <small class="text-muted">System alerts, workflow updates and user-specific events</small>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <button class="btn btn-success" id="markAllReadBtn">
                <i class="fas fa-check-double me-1"></i> Mark All Read
            </button>
            <button class="btn btn-danger d-none" id="bulkDeleteBtn">
                <i class="fas fa-trash me-1"></i> Delete Selected
            </button>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="text-muted small">Type</label>
                    <select class="form-control" id="f_type">
                        <option value="">All</option>
                        <option value="info">Info</option>
                        <option value="success">Success</option>
                        <option value="warning">Warning</option>
                        <option value="danger">Danger</option>
                        <option value="error">Error</option>
                        <option value="workflow">Workflow</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="text-muted small">Status</label>
                    <select class="form-control" id="f_status">
                        <option value="">All</option>
                        <option value="unread">Unread</option>
                        <option value="read">Read</option>
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

                <div class="col-md-2">
                    <label class="text-muted small">Search</label>
                    <input type="text" class="form-control" id="f_q" placeholder="title, message, reference...">
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-outline-primary w-100" id="applyBtn">
                        <i class="fas fa-filter me-1"></i> Apply
                    </button>
                    <button class="btn btn-outline-secondary w-100" id="resetBtn">
                        <i class="fas fa-undo"></i>
                    </button>
                </div>
            </div>

            <div class="alert alert-info mt-3 mb-0">
                <i class="fas fa-info-circle me-1"></i>
                Click any notification action to view details, mark as read, mark as unread or delete.
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle" id="notificationsTable" style="width:100%">
                    <thead class="bg-light">
                        <tr>
                            <th style="width:35px;"><input type="checkbox" id="checkAll"></th>
                            <th style="width:70px;">ID</th>
                            <th style="width:180px;">Title</th>
                            <th>Message</th>
                            <th style="width:110px;">Type</th>
                            <th style="width:170px;">Reference</th>
                            <th style="width:110px;">Status</th>
                            <th style="width:150px;">Created</th>
                            <th style="width:220px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@include('notifications.partials.view-modal')
@endsection

@push('scripts')
<script>
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

const dtUrl          = "{{ route('admin.notifications.datatable') }}";
const baseUrl        = "{{ url('admin/notifications') }}";
const markAllReadUrl = "{{ route('admin.notifications.markAllRead') }}";
const bulkDeleteUrl  = "{{ route('admin.notifications.bulkDelete') }}";

let DT = null;

function swalOk(msg){
    if (window.Swal?.fire) return Swal.fire({icon:'success', title:'Success', text: msg || 'Done.'});
    alert(msg || 'Done.');
}
function swalErr(msg){
    if (window.Swal?.fire) return Swal.fire({icon:'error', title:'Error', text: msg || 'Something went wrong.'});
    alert(msg || 'Something went wrong.');
}
function swalAsk(opts){
    if (window.Swal?.fire) return Swal.fire(opts);
    return Promise.resolve({isConfirmed: confirm(opts?.title || 'Confirm?')});
}
function swalLoading(title = 'Processing...'){
    if (window.Swal?.fire) {
        Swal.fire({
            title,
            allowOutsideClick:false,
            allowEscapeKey:false,
            didOpen:()=>Swal.showLoading()
        });
    }
}
function swalClose(){
    if (window.Swal?.close) Swal.close();
}

function initDT(){
    DT = $('#notificationsTable').DataTable({
        processing:true,
        serverSide:true,
        responsive:true,
        pageLength:10,
        ajax:{
            url: dtUrl,
            data:function(d){
                d.type = $('#f_type').val();
                d.status = $('#f_status').val();
                d.date_from = $('#f_from').val();
                d.date_to = $('#f_to').val();
                d.q = $('#f_q').val();
            }
        },
        columns:[
            {data:'check', orderable:false, searchable:false},
            {data:'id'},
            {data:'title'},
            {data:'message'},
            {data:'type', orderable:false, searchable:false},
            {data:'reference', orderable:false, searchable:false},
            {data:'status', orderable:false, searchable:false},
            {data:'created_at'},
            {data:'actions', orderable:false, searchable:false},
        ],
        order:[[1,'desc']],
        drawCallback:function(){
            $('#checkAll').prop('checked', false);
            toggleBulkDeleteBtn();
        },
        columnDefs:[
            {targets:[0,3,4,5,6,8], render:function(data){ return data; }}
        ]
    });
}

function refreshDT(){
    if (DT) DT.ajax.reload(null, false);
    refreshNotificationBadge();
}

function toggleBulkDeleteBtn(){
    const any = $('.row-check:checked').length > 0;
    $('#bulkDeleteBtn').toggleClass('d-none', !any);
}

$('#applyBtn').on('click', refreshDT);

$('#resetBtn').on('click', function(){
    $('#f_type').val('');
    $('#f_status').val('');
    $('#f_from').val('');
    $('#f_to').val('');
    $('#f_q').val('');
    refreshDT();
});

$('#checkAll').on('change', function(){
    $('.row-check').prop('checked', this.checked).trigger('change');
});

$(document).on('change', '.row-check', function(){
    toggleBulkDeleteBtn();
});

$('#markAllReadBtn').on('click', async function(){
    const r = await swalAsk({
        icon:'question',
        title:'Mark all notifications as read?',
        showCancelButton:true,
        confirmButtonText:'Yes, mark all'
    });

    if (!r.isConfirmed) return;

    swalLoading('Marking all as read...');
    $.post(markAllReadUrl)
        .done(res => {
            swalClose();
            swalOk(res.message || 'All marked as read.');
            refreshDT();
        })
        .fail(xhr => {
            swalClose();
            swalErr(xhr?.responseJSON?.message || 'Failed to mark all as read.');
        });
});

$('#bulkDeleteBtn').on('click', async function(){
    const ids = $('.row-check:checked').map((i,el)=>$(el).val()).get();

    if (!ids.length) return;

    const r = await swalAsk({
        icon:'warning',
        title:'Delete selected notifications?',
        text:'This action cannot be undone.',
        showCancelButton:true,
        confirmButtonText:'Yes, delete'
    });

    if (!r.isConfirmed) return;

    swalLoading('Deleting selected notifications...');
    $.post(bulkDeleteUrl, {ids})
        .done(res => {
            swalClose();
            swalOk(res.message || 'Deleted.');
            refreshDT();
        })
        .fail(xhr => {
            swalClose();
            swalErr(xhr?.responseJSON?.message || 'Failed to delete selected notifications.');
        });
});

$(document).on('click', '.btn-view-notification', function(){
    const id = $(this).data('id');

    swalLoading('Loading notification...');
    $.get(`${baseUrl}/${id}`)
        .done(function(res){
            swalClose();

            $('#view_title').text(res.title || '—');
            $('#view_type').text((res.type || 'info').toUpperCase());
            $('#view_status').html(
                parseInt(res.is_read) === 1
                    ? '<span class="badge bg-success">READ</span>'
                    : '<span class="badge bg-warning text-dark">UNREAD</span>'
            );
            $('#view_reference').text((res.reference_type || '—') + (res.reference_id ? ' #' + res.reference_id : ''));
            $('#view_created_at').text(res.created_at || '—');
            $('#view_message').text(res.message || '—');

            $('#notificationViewModal').modal('show');
        })
        .fail(function(xhr){
            swalClose();
            swalErr(xhr?.responseJSON?.message || 'Failed to load notification.');
        });
});

$(document).on('click', '.btn-read-notification', function(){
    const id = $(this).data('id');

    $.post(`${baseUrl}/${id}/read`)
        .done(res => {
            swalOk(res.message || 'Marked as read.');
            refreshDT();
        })
        .fail(xhr => swalErr(xhr?.responseJSON?.message || 'Failed to mark as read.'));
});

$(document).on('click', '.btn-unread-notification', function(){
    const id = $(this).data('id');

    $.post(`${baseUrl}/${id}/unread`)
        .done(res => {
            swalOk(res.message || 'Marked as unread.');
            refreshDT();
        })
        .fail(xhr => swalErr(xhr?.responseJSON?.message || 'Failed to mark as unread.'));
});

$(document).on('click', '.btn-del-notification', async function(){
    const id = $(this).data('id');

    const r = await swalAsk({
        icon:'warning',
        title:'Delete notification?',
        text:'This action cannot be undone.',
        showCancelButton:true,
        confirmButtonText:'Yes, delete'
    });

    if (!r.isConfirmed) return;

    $.ajax({
        url: `${baseUrl}/${id}`,
        method: 'DELETE'
    })
    .done(res => {
        swalOk(res.message || 'Deleted.');
        refreshDT();
    })
    .fail(xhr => swalErr(xhr?.responseJSON?.message || 'Failed to delete.'));
});

function refreshNotificationBadge(){
    const badge = document.getElementById('notifCount');
    if (!badge) return;

    fetch("{{ route('admin.notifications.unreadCount') }}")
        .then(res => res.json())
        .then(data => {
            const count = parseInt(data.count || 0);
            badge.innerText = count > 99 ? '99+' : count;
            badge.style.display = count > 0 ? 'inline-block' : 'none';
        })
        .catch(() => {});
}

$(function(){
    initDT();
    refreshNotificationBadge();
});
</script>
@endpush