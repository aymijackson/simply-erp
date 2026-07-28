@php
  // $json expected: header object for edit
  // $lines expected optionally: array for edit
@endphp

<div class="btn-group btn-group-sm">
  @if(($json['status'] ?? '') === 'draft')
    <button type="button"
            class="btn btn-outline-primary btn-edit-tx"
            data-json='@json($json)'
            data-lines='@json($lines ?? [])'>
      <i class="fas fa-edit"></i>
    </button>

    <button type="button"
            class="btn btn-outline-danger btn-del-tx"
            data-id="{{ $json['id'] }}">
      <i class="fas fa-trash"></i>
    </button>

    <button type="button"
            class="btn btn-outline-success btn-post-tx"
            data-id="{{ $json['id'] }}">
      <i class="fas fa-check"></i>
    </button>
  @elseif(($json['status'] ?? '') === 'posted')
    <button type="button"
            class="btn btn-outline-warning btn-unpost-tx"
            data-id="{{ $json['id'] }}">
      <i class="fas fa-undo"></i>
    </button>
  @else
    <span class="text-muted small">—</span>
  @endif
</div>