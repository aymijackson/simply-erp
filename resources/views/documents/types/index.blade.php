@extends('layouts.master')

@section('title', 'Document Types')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Document Types</h1>
            <p class="mb-0 text-muted">Maintain document type rules and upload restrictions.</p>
        </div>

        @can('documents.types.create')
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTypeModal">
            <i class="fas fa-plus me-1"></i> New Type
        </button>
        @endcan
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card shadow">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Code</th>
                        <th>Allowed Extensions</th>
                        <th>Max Size (MB)</th>
                        <th>Expiry Required</th>
                        <th>Status</th>
                        <th width="140">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($types as $row)
                    <tr>
                        <td>{{ $row->name }}</td>
                        <td>{{ optional($row->category)->name ?: '-' }}</td>
                        <td>{{ $row->code }}</td>
                        <td>{{ $row->allowed_extensions ?: '-' }}</td>
                        <td>{{ $row->max_file_size_mb }}</td>
                        <td>{{ $row->requires_expiry_date ? 'Yes' : 'No' }}</td>
                        <td>{{ $row->is_active ? 'Active' : 'Inactive' }}</td>
                        <td>
                            @can('documents.types.edit')
                            <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#editTypeModal{{ $row->id }}">
                                <i class="fas fa-edit"></i>
                            </button>
                            @endcan

                            @can('documents.types.delete')
                            <form action="{{ route('admin.document-types.destroy', $row->id) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Delete this document type?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            @endcan
                        </td>
                    </tr>

                    <div class="modal fade" id="editTypeModal{{ $row->id }}" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <form method="POST" action="{{ route('admin.document-types.update', $row->id) }}" class="modal-content">
                                @csrf
                                @method('PUT')
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Document Type</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Category</label>
                                            <select name="category_id" class="form-select">
                                                <option value="">-- Select --</option>
                                                @foreach($categories as $category)
                                                    <option value="{{ $category->id }}" @selected($row->category_id == $category->id)>
                                                        {{ $category->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Name</label>
                                            <input name="name" class="form-control" value="{{ $row->name }}" required>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Code</label>
                                            <input name="code" class="form-control" value="{{ $row->code }}">
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Max File Size (MB)</label>
                                            <input type="number" name="max_file_size_mb" class="form-control" value="{{ $row->max_file_size_mb }}" min="1" max="100" required>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Allowed Extensions</label>
                                            <input name="allowed_extensions" class="form-control" value="{{ $row->allowed_extensions }}" placeholder="pdf,doc,docx,jpg,png">
                                        </div>

                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">Description</label>
                                            <textarea name="description" class="form-control">{{ $row->description }}</textarea>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" name="requires_expiry_date" value="1" {{ $row->requires_expiry_date ? 'checked' : '' }}>
                                                <label class="form-check-label">Requires Expiry Date</label>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" name="is_active" value="1" {{ $row->is_active ? 'checked' : '' }}>
                                                <label class="form-check-label">Active</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button class="btn btn-light" type="button" data-bs-dismiss="modal">Close</button>
                                    <button class="btn btn-primary" type="submit">Save</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted">No document types found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            {{ $types->links() }}
        </div>
    </div>
</div>

<div class="modal fade" id="createTypeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('admin.document-types.store') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">New Document Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">-- Select --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Name</label>
                        <input name="name" class="form-control" required>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Code</label>
                        <input name="code" class="form-control">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Max File Size (MB)</label>
                        <input type="number" name="max_file_size_mb" class="form-control" min="1" max="100" value="20" required>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Allowed Extensions</label>
                        <input name="allowed_extensions" class="form-control" placeholder="pdf,doc,docx,jpg,png">
                    </div>

                    <div class="col-md-12 mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control"></textarea>
                    </div>

                    <div class="col-md-6">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="requires_expiry_date" value="1">
                            <label class="form-check-label">Requires Expiry Date</label>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="is_active" value="1" checked>
                            <label class="form-check-label">Active</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" type="button" data-bs-dismiss="modal">Close</button>
                <button class="btn btn-primary" type="submit">Create</button>
            </div>
        </form>
    </div>
</div>
@endsection