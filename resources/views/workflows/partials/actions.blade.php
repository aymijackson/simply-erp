<div class="btn-group btn-group-sm" role="group">
    <button type="button" class="btn btn-outline-primary btn-view-workflow" data-id="{{ $w->id }}">
        <i class="fas fa-eye"></i>
    </button>

    <button type="button" class="btn btn-outline-info btn-edit-workflow" data-id="{{ $w->id }}">
        <i class="fas fa-edit"></i>
    </button>

    <button type="button" class="btn btn-outline-secondary btn-logs-workflow" data-id="{{ $w->id }}">
        <i class="fas fa-list"></i>
    </button>

    <button type="button" class="btn btn-outline-warning btn-toggle-workflow" data-id="{{ $w->id }}">
        <i class="fas fa-power-off"></i>
    </button>

    <button type="button" class="btn btn-outline-danger btn-del-workflow" data-id="{{ $w->id }}">
        <i class="fas fa-trash"></i>
    </button>
</div>