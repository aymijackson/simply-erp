@php
  $j = $json ?? [];
@endphp

<div class="btn-group btn-group-sm" role="group">
  <button type="button"
          class="btn btn-outline-primary btn-edit-cat"
          data-json='@json($j)'>
    <i class="fas fa-edit"></i>
  </button>

  <button type="button"
          class="btn btn-outline-danger btn-del-cat"
          data-id="{{ $j['id'] ?? '' }}">
    <i class="fas fa-trash"></i>
  </button>
</div>