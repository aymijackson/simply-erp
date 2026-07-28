@extends('layouts.master')

@section('title', 'Documents')

@push('styles')
<style>
    .select2-container { width: 100% !important; }
    .select2-container--bootstrap-5 .select2-selection {
        min-height: 38px;
    }
</style>
@endpush

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800">Document Management</h1>
            <p class="mb-0 text-muted">Upload, organise, preview and control ERP documents.</p>
        </div>

        @can('documents.create')
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createDocumentModal">
            <i class="fas fa-upload me-1"></i> Upload Document
        </button>
        @endcan
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Please fix the following:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">All Documents</h6>
            <div class="small text-muted">Total: {{ $documents->total() }}</div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="documentsTable" class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Document No</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Confidentiality</th>
                            <th>File</th>
                            <th>Uploaded By</th>
                            <th>Expiry</th>
                            <th width="220">Actions</th>
                        </tr>
                    </thead>

                    <tbody id="documentsTableBody">
                        @foreach($documents as $doc)
                            @include('documents.partials.row', ['doc' => $doc])
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $documents->links() }}
            </div>
        </div>
    </div>
</div>

{{-- Edit Modal --}}
<div class="modal fade" id="editDocumentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form id="documentEditForm">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_document_id">

                <div class="modal-body">
                    <div id="editDocumentErrors" class="alert alert-danger d-none"></div>

                    <div class="mb-3">
                        <label class="form-label">Title *</label>
                        <input type="text" name="title" id="edit_title" class="form-control" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category</label>
                            <select name="category_id" id="edit_category_id" class="form-select">
                                <option value="">-- None --</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Type</label>
                            <select name="type_id" id="edit_type_id" class="form-select">
                                <option value="">-- None --</option>
                                @foreach($types as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="edit_description" class="form-control"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" id="edit_notes" class="form-control"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status *</label>
                            <select name="status" id="edit_status" class="form-select" required>
                                <option value="draft">Draft</option>
                                <option value="active">Active</option>
                                <option value="archived">Archived</option>
                                <option value="obsolete">Obsolete</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Confidentiality *</label>
                            <select name="confidentiality_level" id="edit_confidentiality_level" class="form-select" required>
                                <option value="public">Public</option>
                                <option value="internal">Internal</option>
                                <option value="restricted">Restricted</option>
                                <option value="confidential">Confidential</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Effective Date</label>
                            <input type="date" name="effective_date" id="edit_effective_date" class="form-control">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Expiry Date</label>
                            <input type="date" name="expiry_date" id="edit_expiry_date" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="documentEditSubmitBtn">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('documents.partials.create-modal')
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
let documentsTable = null;

function initDocumentSelect2() {
    const createModal = $('#createDocumentModal');
    const editModal = $('#editDocumentModal');

    if ($('#create_category_id').length) {
        $('#create_category_id').select2({
            theme: 'bootstrap-5',
            width: '100%',
            dropdownParent: createModal,
            placeholder: '-- None --',
            allowClear: true
        });
    }

    if ($('#create_type_id').length) {
        $('#create_type_id').select2({
            theme: 'bootstrap-5',
            width: '100%',
            dropdownParent: createModal,
            placeholder: '-- None --',
            allowClear: true
        });
    }

    if ($('#edit_category_id').length) {
        $('#edit_category_id').select2({
            theme: 'bootstrap-5',
            width: '100%',
            dropdownParent: editModal,
            placeholder: '-- None --',
            allowClear: true
        });
    }

    if ($('#edit_type_id').length) {
        $('#edit_type_id').select2({
            theme: 'bootstrap-5',
            width: '100%',
            dropdownParent: editModal,
            placeholder: '-- None --',
            allowClear: true
        });
    }
}

$(document).ready(function () {
    if ($.fn.DataTable && $('#documentsTable').length) {
        documentsTable = $('#documentsTable').DataTable({
            order: [[0, 'desc']],
            pageLength: 10,
            responsive: true
        });
    }

    initDocumentSelect2();
});

document.addEventListener("DOMContentLoaded", () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const uploadForm = document.getElementById("documentUploadForm");
    const uploadModalEl = document.getElementById("createDocumentModal");

    const editForm = document.getElementById("documentEditForm");
    const editModalEl = document.getElementById("editDocumentModal");
    const editModal = editModalEl ? new bootstrap.Modal(editModalEl) : null;

    document.addEventListener('click', async function (e) {
        const deleteBtn = e.target.closest('.delete-document-btn');
        if (deleteBtn) {
            e.preventDefault();

            const id = deleteBtn.dataset.id;
            const url = deleteBtn.dataset.url;

            const confirmResult = await Swal.fire({
                title: "Delete Document?",
                text: "This action cannot be undone.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, delete it",
                cancelButtonText: "Cancel",
                confirmButtonColor: "#d33"
            });

            if (!confirmResult.isConfirmed) return;

            try {
                const response = await fetch(url, {
                    method: "DELETE",
                    headers: {
                        "X-CSRF-TOKEN": csrfToken,
                        "Accept": "application/json"
                    }
                });

                let result = {};
                try { result = await response.json(); } catch (_) {}

                if (!response.ok) {
                    Swal.fire({
                        title: "Error",
                        text: result.message || "Unable to delete document.",
                        icon: "error"
                    });
                    return;
                }

                Swal.fire({
                    title: "Deleted",
                    text: result.message || "Document removed successfully.",
                    icon: "success",
                    timer: 1500,
                    showConfirmButton: false
                });

                const rowEl = document.getElementById(`doc-row-${id}`);

                if (rowEl && documentsTable) {
                    documentsTable.row(rowEl).remove().draw(false);
                } else if (rowEl) {
                    rowEl.remove();
                } else {
                    window.location.reload();
                }
                return;

            } catch (error) {
                console.error('Delete error:', error);
                Swal.fire({
                    title: "Error",
                    text: "A network error occurred.",
                    icon: "error"
                });
                return;
            }
        }

        const editBtn = e.target.closest('.edit-document-btn');
        if (editBtn) {
            e.preventDefault();

            document.getElementById('edit_document_id').value = editBtn.dataset.id || '';
            document.getElementById('edit_title').value = editBtn.dataset.title || '';
            $('#edit_category_id').val(editBtn.dataset.category_id || '').trigger('change');
            $('#edit_type_id').val(editBtn.dataset.type_id || '').trigger('change');
            document.getElementById('edit_description').value = editBtn.dataset.description || '';
            document.getElementById('edit_notes').value = editBtn.dataset.notes || '';
            document.getElementById('edit_status').value = editBtn.dataset.status || 'active';
            document.getElementById('edit_confidentiality_level').value = editBtn.dataset.confidentiality_level || 'internal';
            document.getElementById('edit_effective_date').value = editBtn.dataset.effective_date || '';
            document.getElementById('edit_expiry_date').value = editBtn.dataset.expiry_date || '';

            const errorBox = document.getElementById('editDocumentErrors');
            if (errorBox) {
                errorBox.classList.add('d-none');
                errorBox.innerHTML = '';
            }

            editModal.show();
            return;
        }
    });

    if (uploadForm) {
        uploadForm.addEventListener("submit", async (e) => {
            e.preventDefault();
    
            const errorBox = document.getElementById('createDocumentErrors');
            const submitBtn = document.getElementById('documentUploadSubmitBtn');
    
            if (errorBox) {
                errorBox.classList.add('d-none');
                errorBox.innerHTML = '';
            }
    
            const originalBtnText = submitBtn ? submitBtn.innerHTML : 'Upload';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'Uploading...';
            }
    
            const formData = new FormData(uploadForm);
    
            try {
                const response = await fetch("{{ route('admin.documents.store') }}", {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": csrfToken,
                        "Accept": "application/json"
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
                        title: "Server Error",
                        text: "The server did not return a valid JSON response.",
                        icon: "error"
                    });
                    return;
                }
    
                if (response.status === 422) {
                    let errors = '';
                    if (result.errors) {
                        errors = Object.values(result.errors).flat().join("<br>");
                    }
    
                    if (errorBox) {
                        errorBox.innerHTML = errors || result.message || 'Please check the form.';
                        errorBox.classList.remove('d-none');
                    }
    
                    Swal.fire({
                        title: "Validation Error",
                        html: errors || result.message || "Please check the form.",
                        icon: "error"
                    });
                    return;
                }
    
                if (!response.ok) {
                    Swal.fire({
                        title: "Upload Failed",
                        text: result.message || result.error || "An unexpected error occurred.",
                        icon: "error"
                    });
                    return;
                }
    
                Swal.fire({
                    title: "Uploaded",
                    text: result.message || "Document uploaded successfully.",
                    icon: "success",
                    timer: 1500,
                    showConfirmButton: false
                });
    
                const modal = uploadModalEl ? bootstrap.Modal.getInstance(uploadModalEl) : null;
                if (modal) {
                    modal.hide();
                }
    
                uploadForm.reset();
                $('#create_category_id').val('').trigger('change');
                $('#create_type_id').val('').trigger('change');
                window.location.reload();
    
            } catch (error) {
                console.error('Upload error:', error);
    
                Swal.fire({
                    icon: 'error',
                    title: 'Upload Failed',
                    text: error.message || 'Unexpected error occurred.'
                });
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                }
            }
        });
    }

    if (editForm) {
        editForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            const id = document.getElementById('edit_document_id').value;
            const errorBox = document.getElementById('editDocumentErrors');
            const submitBtn = document.getElementById('documentEditSubmitBtn');

            if (errorBox) {
                errorBox.classList.add('d-none');
                errorBox.innerHTML = '';
            }

            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Updating...';

            try {
                const formData = new FormData(editForm);
                formData.append('_method', 'PUT');

                const response = await fetch(`{{ url('admin/documents') }}/${id}`, {
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

                    if (errorBox) {
                        errorBox.innerHTML = html;
                        errorBox.classList.remove('d-none');
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
                        title: 'Update Failed',
                        text: result.message || result.error || 'An unexpected error occurred.',
                        icon: 'error'
                    });
                    return;
                }

                editModal.hide();

                Swal.fire({
                    title: 'Updated',
                    text: result.message || 'Document updated successfully.',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                });

                window.location.reload();

            } catch (error) {
                console.error('Update error:', error);
                Swal.fire({
                    title: 'Error',
                    text: error.message || 'A network error occurred.',
                    icon: 'error'
                });
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        });
    }
});
</script>
@endpush