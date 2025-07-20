<div class="btn-group" role="group">
  <button type="button"
          class="btn btn-sm btn-primary edit-store"
          data-id="{{ $store->id }}">
    <i class="fas fa-edit"></i>
  </button>
  <button type="button"
          class="btn btn-sm btn-danger delete-store"
          data-id="{{ $store->id }}">
    <i class="fas fa-trash"></i>
  </button>
</div>
