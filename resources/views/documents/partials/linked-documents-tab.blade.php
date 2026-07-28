@php
    $linkedDocuments = optional($model->documentLinks())
        ->with(['document.category', 'document.type', 'document.uploader'])
        ->latest()
        ->get() ?? collect();
@endphp

<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <strong>Documents</strong>
            <div class="small text-muted">Attached files for this record</div>
        </div>

        <div class="d-flex gap-2">
            @can('documents.create')
                <button type="button"
                        class="btn btn-sm btn-primary"
                        data-bs-toggle="modal"
                        data-bs-target="#uploadAndAttachDocumentModal">
                    <i class="fas fa-upload me-1"></i> Upload & Attach
                </button>
            @endcan

            @can('documents.links.create')
                <button type="button"
                        class="btn btn-sm btn-outline-primary"
                        data-bs-toggle="modal"
                        data-bs-target="#attachExistingDocumentModal">
                    <i class="fas fa-paperclip me-1"></i> Attach Existing
                </button>
            @endcan
        </div>
    </div>

    <div class="card-body">
        @if($linkedDocuments->isNotEmpty())
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Document No</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Type</th>
                            <th>Relation</th>
                            <th>File</th>
                            <th>Expiry</th>
                            <th width="180">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($linkedDocuments as $link)
                            @php
                                $doc = $link->document;
                            @endphp

                            <tr>
                                <td>{{ $doc?->document_no ?? '-' }}</td>

                                <td>
                                    <div class="fw-bold">{{ $doc?->title ?? '[Missing document]' }}</div>
                                    <small class="text-muted">v{{ $doc?->version_no ?? '-' }}</small>
                                </td>

                                <td>{{ $doc?->category?->name ?? '-' }}</td>
                                <td>{{ $doc?->type?->name ?? '-' }}</td>
                                <td>{{ $link->relation_type ?? '-' }}</td>

                                <td>
                                    {{ $doc?->original_file_name ?? '-' }}<br>
                                    <small class="text-muted">{{ $doc?->human_file_size ?? '-' }}</small>
                                </td>

                                <td>
                                    {{ $doc?->expiry_date ? $doc->expiry_date->format('d M Y') : '-' }}
                                </td>

                                <td>
                                    @if($doc)
                                        <a href="{{ route('admin.documents.show', $doc->id) }}"
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        @can('documents.preview')
                                            <a href="{{ route('admin.documents.preview', $doc->id) }}"
                                               target="_blank"
                                               class="btn btn-sm btn-outline-info">
                                                <i class="fas fa-file-alt"></i>
                                            </a>
                                        @endcan

                                        @can('documents.download')
                                            <a href="{{ route('admin.documents.download', $doc->id) }}"
                                               class="btn btn-sm btn-outline-success">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        @endcan
                                    @endif

                                    @can('documents.links.delete')
                                        <form action="{{ route('admin.document-links.destroy', $link->id) }}"
                                              method="POST"
                                              class="d-inline"
                                              onsubmit="return confirm('Detach this document from this record?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-unlink"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-muted">No documents attached yet.</div>
        @endif
    </div>
</div>

@can('documents.links.create')
    @include('documents.partials.attach-existing-modal', ['model' => $model])
@endcan

@can('documents.create')
    @include('documents.partials.upload-attach-modal', [
        'model' => $model,
        'categories' => \Modules\Document\Models\DocumentCategory::active()->orderBy('name')->get(),
        'types' => \Modules\Document\Models\DocumentType::active()->orderBy('name')->get(),
    ])
@endcan