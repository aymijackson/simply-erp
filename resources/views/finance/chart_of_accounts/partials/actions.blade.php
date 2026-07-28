@php
  // $json is passed from controller as ['json' => $json]
@endphp

<div class="btn-group btn-group-sm">
  <button type="button"
          class="btn btn-outline-primary btn-edit-coa"
          data-json='@json($json)'>
    <i class="fas fa-edit"></i>
  </button>

  <button type="button"
          class="btn btn-outline-danger btn-del-coa"
          data-id="{{ $json['id'] }}">
    <i class="fas fa-trash"></i>
  </button>
</div>