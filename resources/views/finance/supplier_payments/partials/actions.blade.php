<div class="btn-group" role="group">
  @if(($p->status ?? 'draft') === 'draft')
    <button class="btn btn-outline-primary btn-sm btn-edit-pay" data-json='@json($json)'>
      <i class="fas fa-edit"></i> Edit
    </button>
    <button class="btn btn-outline-success btn-sm btn-post-pay" data-id="{{ $p->id }}">
      <i class="fas fa-check"></i> Post
    </button>
    <button class="btn btn-outline-danger btn-sm btn-del-pay" data-id="{{ $p->id }}">
      <i class="fas fa-trash"></i> Delete
    </button>
  @elseif(($p->status ?? '') === 'posted')
    <button class="btn btn-outline-dark btn-sm btn-void-pay" data-id="{{ $p->id }}">
      <i class="fas fa-ban"></i> Void
    </button>
  @endif
</div>