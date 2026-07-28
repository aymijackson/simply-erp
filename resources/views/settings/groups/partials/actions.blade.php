@php
  $json = [
    'id' => $g->id,
    'module' => $g->module,
    'code' => $g->code,
    'name' => $g->name,
    'description' => $g->description,
    'sort_order' => (int)($g->sort_order ?? 0),
    'is_active' => (int)$g->is_active,
  ];
@endphp

<div class="btn-group btn-group-sm">
  <button type="button"
          class="btn btn-outline-primary btn-edit-group"
          data-json='@json($json)'>
    <i class="fas fa-edit"></i>
  </button>

  <button type="button"
          class="btn btn-outline-danger btn-del-group"
          data-id="{{ $g->id }}">
    <i class="fas fa-trash"></i>
  </button>
</div>
