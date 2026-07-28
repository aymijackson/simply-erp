<tr id="doc-row-{{ $doc->id }}">
    <td>{{ $doc->document_no }}</td>
    <td>
        <div class="fw-bold">{{ $doc->title }}</div>
        <small class="text-muted">v{{ $doc->version_no }}</small>
    </td>
    <td>{{ optional($doc->category)->name ?: '-' }}</td>
    <td>{{ optional($doc->type)->name ?: '-' }}</td>
    <td>
        <span class="badge bg-info text-dark">{{ ucfirst($doc->status) }}</span>
    </td>
    <td>
        <span class="badge bg-secondary">{{ ucfirst($doc->confidentiality_level) }}</span>
    </td>
    <td>
        {{ $doc->original_file_name }}<br>
        <small class="text-muted">{{ $doc->human_file_size }}</small>
    </td>
    <td>{{ optional($doc->uploader)->name ?: '-' }}</td>
    <td>{{ $doc->expiry_date ? $doc->expiry_date->format('d M Y') : '-' }}</td>
    <td>
        <a href="{{ route('admin.documents.show', $doc->id) }}" class="btn btn-sm btn-outline-primary mb-1">
            <i class="fas fa-eye"></i>
        </a>

        @can('documents.edit')
        <button type="button"
                class="btn btn-sm btn-outline-warning mb-1 edit-document-btn"
                data-id="{{ $doc->id }}"
                data-title="{{ $doc->title }}"
                data-category_id="{{ $doc->category_id }}"
                data-type_id="{{ $doc->type_id }}"
                data-description="{{ $doc->description }}"
                data-notes="{{ $doc->notes }}"
                data-status="{{ $doc->status }}"
                data-confidentiality_level="{{ $doc->confidentiality_level }}"
                data-effective_date="{{ $doc->effective_date ? $doc->effective_date->format('Y-m-d') : '' }}"
                data-expiry_date="{{ $doc->expiry_date ? $doc->expiry_date->format('Y-m-d') : '' }}">
            <i class="fas fa-edit"></i>
        </button>
        @endcan

        @can('documents.preview')
        <a href="{{ route('admin.documents.preview', $doc->id) }}" target="_blank" class="btn btn-sm btn-outline-info mb-1">
            <i class="fas fa-file-alt"></i>
        </a>
        @endcan

        @can('documents.download')
        <a href="{{ route('admin.documents.download', $doc->id) }}" class="btn btn-sm btn-outline-success mb-1">
            <i class="fas fa-download"></i>
        </a>
        @endcan

        @can('documents.delete')
        <button type="button"
                class="btn btn-sm btn-outline-danger mb-1 delete-document-btn"
                data-id="{{ $doc->id }}"
                data-url="{{ route('admin.documents.destroy', $doc->id) }}">
            <i class="fas fa-trash"></i>
        </button>
        @endcan
    </td>
</tr>