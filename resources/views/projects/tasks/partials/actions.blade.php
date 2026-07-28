<div class="btn-group btn-group-sm" role="group">
    <button type="button"
        class="btn btn-outline-primary btn-edit-task"
        data-json='@json($json)'>
        <i class="fas fa-edit"></i> Edit
    </button>

    <button type="button"
        class="btn btn-outline-danger btn-del-task"
        data-id="{{ $row->id }}">
        <i class="fas fa-trash"></i> Delete
    </button>
</div>