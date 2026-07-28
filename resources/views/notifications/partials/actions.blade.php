<div class="btn-group btn-group-sm" role="group">
    <button type="button"
        class="btn btn-outline-primary btn-view-notification"
        data-id="{{ $n->id }}">
        <i class="fas fa-eye"></i>
    </button>

    @if((int)$n->is_read === 0)
        <button type="button"
            class="btn btn-outline-success btn-read-notification"
            data-id="{{ $n->id }}">
            <i class="fas fa-check"></i>
        </button>
    @else
        <button type="button"
            class="btn btn-outline-warning btn-unread-notification"
            data-id="{{ $n->id }}">
            <i class="fas fa-envelope"></i>
        </button>
    @endif

    <button type="button"
        class="btn btn-outline-danger btn-del-notification"
        data-id="{{ $n->id }}">
        <i class="fas fa-trash"></i>
    </button>
</div>