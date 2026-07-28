@php
  $jsonAttr = htmlspecialchars(json_encode($json ?? []), ENT_QUOTES, 'UTF-8');
@endphp

<div class="btn-group btn-group-sm" role="group">
  @if(($cr->status ?? 'draft') === 'draft')
    <button type="button" class="btn btn-outline-primary btn-edit-cr" data-json="{{ $jsonAttr }}">
      <i class="fas fa-edit"></i> Edit
    </button>
    <button type="button" class="btn btn-outline-success btn-post-cr" data-id="{{ $cr->id }}">
      <i class="fas fa-check"></i> Post
    </button>
    <button type="button" class="btn btn-outline-danger btn-del-cr" data-id="{{ $cr->id }}">
      <i class="fas fa-trash"></i> Delete
    </button>
  @elseif(($cr->status ?? '') === 'posted')
    <button type="button" class="btn btn-outline-dark btn-void-cr" data-id="{{ $cr->id }}">
      <i class="fas fa-ban"></i> Void
    </button>
  @else
    <span class="text-muted small">No actions</span>
  @endif
</div>