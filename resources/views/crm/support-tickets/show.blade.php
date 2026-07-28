@extends('layouts.master')

@section('title', 'Support Ticket')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 text-primary mb-0">Ticket: {{ $ticket->ticket_no }}</h1>
            <small class="text-muted">Created {{ optional($ticket->created_at)->format('d-m-Y h:i a') }}</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.crm.support_tickets.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="row g-3">
        {{-- Ticket Info --}}
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h5 class="mb-1">{{ $ticket->subject }}</h5>
                            <div class="text-muted small">
                                Customer: <strong>{{ $ticket->customer?->name ?? '—' }}</strong>
                                &nbsp;|&nbsp; Status: <strong>{{ ucfirst($ticket->status) }}</strong>
                                &nbsp;|&nbsp; Priority: <strong>{{ ucfirst($ticket->priority) }}</strong>
                            </div>
                        </div>
                        <div class="text-end small">
                            <div>
                                Assigned:
                                <strong>
                                    {{ trim(($ticket->assignee?->first_name ?? '').' '.($ticket->assignee?->last_name ?? '')) ?: '—' }}
                                </strong>
                            </div>
                            <div>
                                Created by:
                                <strong>
                                    {{ trim(($ticket->creator?->first_name ?? '').' '.($ticket->creator?->last_name ?? '')) ?: '—' }}
                                </strong>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <div>
                        <h6>Description</h6>
                        <div class="border rounded p-3 bg-light">
                            {!! nl2br(e($ticket->description)) !!}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Comments --}}
            <div class="card shadow-sm mt-3">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <strong>Comments</strong>
                </div>
                <div class="card-body">
                    @can('crm.support_tickets.update')
                    <form id="commentForm" class="mb-3">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label mb-1">Author</label>
                                <select id="author_id" class="form-control" required>
                                    @php
                                        $defaultEmployeeId = optional(auth()->user())->employee_id;
                                    @endphp
                                    @foreach($employees as $e)
                                        <option value="{{ $e->id }}" @selected($defaultEmployeeId && (int)$defaultEmployeeId === (int)$e->id)>
                                            {{ trim(($e->first_name ?? '').' '.($e->last_name ?? '')) ?: ('Employee #'.$e->id) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-8">
                                <label class="form-label mb-1">Comment</label>
                                <textarea id="comment" class="form-control" rows="2" placeholder="Write a comment..." required></textarea>
                            </div>

                            <div class="col-12">
                                <button class="btn btn-primary btn-sm" type="submit" id="commentBtn">
                                    <i class="fas fa-comment me-1"></i> Add Comment
                                </button>
                            </div>
                        </div>
                    </form>
                    @endcan

                    <div id="commentsWrap">
                        @forelse($ticket->comments as $c)
                            <div class="border rounded p-2 mb-2">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="small">
                                        <strong>{{ trim(($c->author?->first_name ?? '').' '.($c->author?->last_name ?? '')) ?: '—' }}</strong>
                                        <span class="text-muted">• {{ optional($c->created_at)->format('d-m-Y h:i a') }}</span>
                                    </div>

                                    {{-- Optional: if you add a route/controller for deleting comments later --}}
                                    {{-- @can('crm.support_tickets.update')
                                        <button class="btn btn-sm btn-outline-danger delete-comment" data-id="{{ $c->id }}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    @endcan --}}
                                </div>

                                <div class="mt-1">{!! nl2br(e($c->message)) !!}</div>
                            </div>
                        @empty
                            <div class="text-muted">No comments yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- Attachments --}}
        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <strong>Attachments</strong>
                </div>

                <div class="card-body">
                    @can('crm.support_tickets.update')
                    <form id="attachmentForm" enctype="multipart/form-data" class="mb-3">
                        <div class="mb-2">
                            <label class="form-label mb-1">Uploaded By</label>
                            <select id="uploaded_by" class="form-control" required>
                                @php
                                    $defaultEmployeeId = optional(auth()->user())->employee_id;
                                @endphp
                                @foreach($employees as $e)
                                    <option value="{{ $e->id }}" @selected($defaultEmployeeId && (int)$defaultEmployeeId === (int)$e->id)>
                                        {{ trim(($e->first_name ?? '').' '.($e->last_name ?? '')) ?: ('Employee #'.$e->id) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-2">
                            <label class="form-label mb-1">File</label>
                            <input type="file" id="file" name="file" class="form-control" required>
                            <small class="text-muted">Max 5MB</small>
                        </div>

                        <button class="btn btn-primary btn-sm" type="submit" id="uploadBtn">
                            <i class="fas fa-upload me-1"></i> Upload
                        </button>
                    </form>
                    @endcan

                    <div id="attachmentsWrap">
                        @forelse($ticket->attachments as $a)
                            <div class="border rounded p-2 mb-2 d-flex justify-content-between align-items-start">
                                <div class="small">
                                    <div><strong>{{ $a->file_name }}</strong></div>
                                    <div class="text-muted">
                                        {{ optional($a->created_at)->format('d-m-Y h:i a') }}
                                        • by {{ trim(($a->uploader?->first_name ?? '').' '.($a->uploader?->last_name ?? '')) ?: '—' }}
                                    </div>
                                    @if($a->file_path)
                                        <a href="{{ asset('storage/'.$a->file_path) }}" target="_blank" class="small">
                                            View file
                                        </a>
                                    @endif
                                </div>

                                @can('crm.support_tickets.update')
                                <button class="btn btn-sm btn-outline-danger delete-attachment" data-id="{{ $a->id }}">
                                    <i class="fas fa-trash"></i>
                                </button>
                                @endcan
                            </div>
                        @empty
                            <div class="text-muted">No attachments yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Quick metadata --}}
            <div class="card shadow-sm mt-3">
                <div class="card-body small">
                    <div><strong>Status:</strong> {{ ucfirst($ticket->status) }}</div>
                    <div><strong>Priority:</strong> {{ ucfirst($ticket->priority) }}</div>
                    <div><strong>Channel:</strong> {{ $ticket->channel ?: '—' }}</div>
                    <div><strong>Category:</strong> {{ $ticket->category ?: '—' }}</div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
(function(){
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    const routes = {
        addComment: @json(route('admin.crm.support_tickets.comments', $ticket->id)),
        addAttachment: @json(route('admin.crm.support_tickets.attachment', $ticket->id)),
        delAttachment: @json(route('admin.crm.support_tickets.attachment_delete', ['ticket' => $ticket->id, 'attachment' => '__A__'])),
    };

    function urlA(tpl, id){ return tpl.replace('__A__', id); }

    function swalOk(title, text=''){
        Swal.fire({ icon:'success', title, text, timer:1500, showConfirmButton:false });
    }
    function swalErr(text){
        Swal.fire({ icon:'error', title:'Error', text:text || 'Something went wrong.' });
    }
    function confirmDelete(text){
        return Swal.fire({
            icon:'warning',
            title:'Confirm',
            text: text || 'Are you sure?',
            showCancelButton:true,
            confirmButtonText:'Yes, delete',
            cancelButtonText:'Cancel'
        });
    }

    function setBtnLoading($btn, loading) {
        $btn.prop('disabled', loading);
        if (loading) {
            $btn.data('old', $btn.html());
            $btn.html('<i class="fas fa-spinner fa-spin me-1"></i> Please wait...');
        } else {
            $btn.html($btn.data('old') || $btn.html());
        }
    }

    // Add comment
    $('#commentForm').on('submit', function(e){
        e.preventDefault();

        const $btn = $('#commentBtn');
        setBtnLoading($btn, true);

        $.ajax({
            url: routes.addComment,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf },
            data: {
                message: $('#comment').val(),
                author_id: $('#author_id').val(),
            },
            success: function(res){
                swalOk('Saved', res.message || 'Comment added.');
                window.location.reload();
            },
            error: function(xhr){
                swalErr(xhr.responseJSON?.message || 'Failed to add comment.');
            },
            complete: function() {
                setBtnLoading($btn, false);
            }
        });
    });

    // Upload attachment
    $('#attachmentForm').on('submit', function(e){
        e.preventDefault();

        const fileEl = $('#file')[0];
        if (!fileEl.files || !fileEl.files[0]) {
            swalErr('Please select a file.');
            return;
        }

        const $btn = $('#uploadBtn');
        setBtnLoading($btn, true);

        const fd = new FormData();
        fd.append('file', fileEl.files[0]);
        fd.append('uploaded_by', $('#uploaded_by').val());

        $.ajax({
            url: routes.addAttachment,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf },
            data: fd,
            processData: false,
            contentType: false,
            success: function(res){
                swalOk('Uploaded', res.message || 'Attachment uploaded.');
                window.location.reload();
            },
            error: function(xhr){
                swalErr(xhr.responseJSON?.message || 'Upload failed.');
            },
            complete: function() {
                setBtnLoading($btn, false);
            }
        });
    });

    // Delete attachment
    $(document).on('click', '.delete-attachment', async function(){
        const id = $(this).data('id');
        const res = await confirmDelete('Delete this attachment?');
        if (!res.isConfirmed) return;

        $.ajax({
            url: urlA(routes.delAttachment, id),
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf },
            success: function(res){
                swalOk('Deleted', res.message || 'Attachment deleted.');
                window.location.reload();
            },
            error: function(xhr){
                swalErr(xhr.responseJSON?.message || 'Failed to delete attachment.');
            }
        });
    });

})();
</script>
@endpush
