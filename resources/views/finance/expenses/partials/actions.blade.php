@php
  $status = strtolower($json['status'] ?? 'draft');
@endphp

<div class="btn-group btn-group-sm">
  @if($status === 'draft')
    <button class="btn btn-outline-primary btn-edit-exp" data-id="{{ $json['id'] }}" title="Edit">
      <i class="fas fa-edit"></i>
    </button>

    <button class="btn btn-outline-success btn-post-exp" data-id="{{ $json['id'] }}" title="Post">
      <i class="fas fa-check"></i>
    </button>

    <button class="btn btn-outline-danger btn-del-exp" data-id="{{ $json['id'] }}" title="Delete">
      <i class="fas fa-trash"></i>
    </button>
  @elseif($status === 'posted')
    <button class="btn btn-outline-secondary btn-void-exp" data-id="{{ $json['id'] }}" title="Void">
      <i class="fas fa-ban"></i>
    </button>
  @else
    <button class="btn btn-outline-secondary" type="button" disabled title="No further actions">
      <i class="fas fa-ban"></i>
    </button>
  @endif
</div>