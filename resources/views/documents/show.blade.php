@extends('layouts.master')

@section('title', 'Document Details')

@push('styles')
<style>
    .table td, .table th {
        vertical-align: middle;
    }
</style>
@endpush

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800">{{ $document->title }}</h1>
            <p class="mb-0 text-muted">{{ $document->document_no }}</p>
        </div>

        <div class="d-flex gap-2">
            @can('documents.preview')
            <a href="{{ route('admin.documents.preview', $document->id) }}" target="_blank" class="btn btn-info">
                <i class="fas fa-file-alt me-1"></i> Preview
            </a>
            @endcan

            @can('documents.download')
            <a href="{{ route('admin.documents.download', $document->id) }}" class="btn btn-success">
                <i class="fas fa-download me-1"></i> Download
            </a>
            @endcan

            @can('documents.versions.create')
            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#newVersionModal">
                <i class="fas fa-code-branch me-1"></i> New Version
            </button>
            @endcan
        </div>
    </div>

    @if(session('success'))
        <script>
            document.addEventListener("DOMContentLoaded", () => {
                Swal.fire({
                    icon: "success",
                    title: "Success",
                    text: @json(session('success')),
                    timer: 2000,
                    showConfirmButton: false
                });
            });
        </script>
    @endif

    <div class="row">

        {{-- Left Column --}}
        <div class="col-md-8">

            {{-- Document Information --}}
            <div class="card shadow mb-4">
                <div class="card-header"><strong>Document Information</strong></div>
                <div class="card-body">
                    <table class="table table-striped table-bordered">
                        <tr><th width="220">Document No</th><td>{{ $document->document_no }}</td></tr>
                        <tr><th>Title</th><td>{{ $document->title }}</td></tr>
                        <tr><th>Category</th><td>{{ optional($document->category)->name ?: '-' }}</td></tr>
                        <tr><th>Type</th><td>{{ optional($document->type)->name ?: '-' }}</td></tr>
                        <tr><th>Status</th><td>{{ ucfirst($document->status) }}</td></tr>
                        <tr><th>Confidentiality</th><td>{{ ucfirst($document->confidentiality_level) }}</td></tr>
                        <tr><th>File Name</th><td>{{ $document->original_file_name }}</td></tr>
                        <tr><th>File Size</th><td>{{ $document->human_file_size }}</td></tr>
                        <tr><th>Mime Type</th><td>{{ $document->mime_type }}</td></tr>
                        <tr><th>Effective Date</th><td>{{ $document->effective_date?->format('d M Y') ?: '-' }}</td></tr>
                        <tr><th>Expiry Date</th><td>{{ $document->expiry_date?->format('d M Y') ?: '-' }}</td></tr>
                        <tr><th>Description</th><td>{{ $document->description ?: '-' }}</td></tr>
                        <tr><th>Notes</th><td>{{ $document->notes ?: '-' }}</td></tr>
                        <tr><th>Uploaded By</th><td>{{ optional($document->uploader)->name ?: '-' }}</td></tr>
                        <tr><th>Created At</th><td>{{ $document->created_at?->format('d M Y H:i') ?: '-' }}</td></tr>
                    </table>
                </div>
            </div>

            {{-- Audit Trail --}}
            @can('documents.audit.view')
            <div class="card shadow mb-4">
                <div class="card-header"><strong>Audit Trail</strong></div>
                <div class="card-body">
                    @if($document->audits && $document->audits->count())
                        <div class="table-responsive">
                            <table id="auditTable" class="table table-striped table-bordered w-100">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Action</th>
                                        <th>Description</th>
                                        <th>User</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($document->audits as $audit)
                                        <tr>
                                            <td>{{ $audit->created_at ? $audit->created_at->format('d M Y H:i') : '-' }}</td>
                                            <td>{{ ucfirst($audit->action) }}</td>
                                            <td>{{ $audit->description ?: '-' }}</td>
                                            <td>
                                                {{ optional($audit->user)->name
                                                    ?? optional($audit->performedBy)->name
                                                    ?? $audit->performed_by
                                                    ?? '-' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">No audit records found.</p>
                    @endif
                </div>
            </div>
            @endcan

        </div>

        {{-- Right Column --}}
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header"><strong>Version History</strong></div>
                <div class="card-body">

                    @php
                        $root = $document->parent_document_id ? ($document->parent ?: $document) : $document;
                        $allVersions = \Modules\Document\Models\Document::where(function($q) use ($root) {
                            $q->where('id', $root->id)->orWhere('parent_document_id', $root->id);
                        })->orderBy('version_no')->get();
                    @endphp

                    @if($allVersions->count())
                        <ul class="list-group">
                            @foreach($allVersions as $version)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>v{{ $version->version_no }}</strong>
                                        @if($version->is_latest)
                                            <span class="badge bg-success ms-2">Latest</span>
                                        @endif
                                        <div class="small text-muted">{{ $version->original_file_name }}</div>
                                    </div>
                                    <a href="{{ route('admin.documents.show', $version->id) }}" class="btn btn-sm btn-outline-primary">
                                        View
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted mb-0">No versions found.</p>
                    @endif

                </div>
            </div>
        </div>

    </div>
</div>

{{-- New Version Modal --}}
@can('documents.versions.create')
    @include('documents.partials.new-version-modal', ['document' => $document])
@endcan

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function () {
    if ($.fn.DataTable && $('#auditTable').length) {
        $('#auditTable').DataTable({
            order: [[0, 'desc']],
            pageLength: 10,
            responsive: true
        });
    }
});

document.addEventListener("DOMContentLoaded", () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const versionForm = document.getElementById('newVersionForm');
    const versionModalEl = document.getElementById('newVersionModal');
    const versionSubmitBtn = document.getElementById('newVersionSubmitBtn');
    const versionErrorBox = document.getElementById('newVersionErrors');

    if (versionForm) {
        versionForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            if (versionErrorBox) {
                versionErrorBox.classList.add('d-none');
                versionErrorBox.innerHTML = '';
            }

            const originalBtnText = versionSubmitBtn ? versionSubmitBtn.innerHTML : 'Create New Version';

            if (versionSubmitBtn) {
                versionSubmitBtn.disabled = true;
                versionSubmitBtn.innerHTML = 'Uploading...';
            }

            try {
                const formData = new FormData(versionForm);

                const response = await fetch(versionForm.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                let result;
                try {
                    result = await response.json();
                } catch (jsonError) {
                    const raw = await response.text();
                    console.error('Non-JSON response:', raw);

                    Swal.fire({
                        title: 'Server Error',
                        text: 'The server did not return a valid JSON response.',
                        icon: 'error'
                    });
                    return;
                }

                if (response.status === 422) {
                    let html = '<ul class="mb-0">';
                    if (result.errors) {
                        Object.keys(result.errors).forEach(function (field) {
                            result.errors[field].forEach(function (message) {
                                html += `<li>${message}</li>`;
                            });
                        });
                    }
                    html += '</ul>';

                    if (versionErrorBox) {
                        versionErrorBox.innerHTML = html;
                        versionErrorBox.classList.remove('d-none');
                    }

                    Swal.fire({
                        title: 'Validation Error',
                        text: result.message || 'Please fix the errors and try again.',
                        icon: 'error'
                    });
                    return;
                }

                if (!response.ok) {
                    Swal.fire({
                        title: 'Upload Failed',
                        text: result.message || result.error || 'An unexpected error occurred.',
                        icon: 'error'
                    });
                    return;
                }

                const modal = versionModalEl ? bootstrap.Modal.getInstance(versionModalEl) : null;
                if (modal) {
                    modal.hide();
                }

                versionForm.reset();

                Swal.fire({
                    title: 'Success',
                    text: result.message || 'New version uploaded successfully.',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                });

                if (result.redirect_url) {
                    window.location.href = result.redirect_url;
                } else {
                    window.location.reload();
                }

            } catch (error) {
                console.error('Version upload error:', error);

                Swal.fire({
                    title: 'Error',
                    text: error.message || 'A network error occurred.',
                    icon: 'error'
                });
            } finally {
                if (versionSubmitBtn) {
                    versionSubmitBtn.disabled = false;
                    versionSubmitBtn.innerHTML = originalBtnText;
                }
            }
        });
    }
});
</script>
@endpush