@php
  // pack full record for edit (avoid extra endpoint)
  $json = [
    'id' => $s->id,
    'setting_group_id' => $s->setting_group_id,
    'key' => $s->key,
    'label' => $s->label,
    'description' => $s->description,
    'value_type' => $s->value_type,
    'value' => $s->value,
    'scope' => $s->scope,
    'scope_id' => $s->scope_id,
    'is_sensitive' => (int)$s->is_sensitive,
    'is_required' => (int)$s->is_required,
    'is_active' => (int)$s->is_active,
    'sort_order' => (int)$s->sort_order,
  ];
@endphp

<div class="btn-group btn-group-sm">
  <button type="button"
          class="btn btn-outline-primary btn-edit-setting"
          data-json='@json($json)'>
    <i class="fas fa-edit"></i>
  </button>

  <button type="button"
          class="btn btn-outline-danger btn-del-setting"
          data-id="{{ $s->id }}">
    <i class="fas fa-trash"></i>
  </button>
</div>
