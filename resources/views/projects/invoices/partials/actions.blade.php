@php
    $status = $row->status ?? 'draft';
@endphp

<div class="btn-group btn-group-sm" role="group">
    @if($status === 'draft')
        <button type="button"
            class="btn btn-outline-primary btn-edit-project-invoice"
            data-json='@json($json)'>
            <i class="fas fa-edit"></i> Edit
        </button>

        <button type="button"
            class="btn btn-outline-success btn-post-project-invoice"
            data-id="{{ $row->id }}">
            <i class="fas fa-check"></i> Post
        </button>

        <button type="button"
            class="btn btn-outline-danger btn-del-project-invoice"
            data-id="{{ $row->id }}">
            <i class="fas fa-trash"></i> Delete
        </button>
    @else
        <button type="button" class="btn btn-outline-secondary" disabled>
            <i class="fas fa-lock"></i> Locked
        </button>

        @if(in_array($status, ['posted', 'part_paid']))
            <button type="button"
                class="btn btn-outline-dark btn-void-project-invoice"
                data-id="{{ $row->id }}">
                <i class="fas fa-ban"></i> Void
            </button>
        @endif
    @endif
</div>