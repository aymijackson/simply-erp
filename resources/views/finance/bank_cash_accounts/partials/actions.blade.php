@php
  $payload = [
    'id' => $row->id,
    'type' => $row->type,
    'name' => $row->name,
    'bank_name' => $row->bank_name,
    'account_no' => $row->account_no,
    'currency' => $row->currency,
    'gl_account_id' => $row->gl_account_id,
    'opening_balance' => $row->opening_balance,
    'is_default' => (int)$row->is_default,
    'is_active' => (int)$row->is_active,
  ];
@endphp

<div class="btn-group btn-group-sm">
  <button type="button"
          class="btn btn-outline-primary btn-edit-bc"
          data-json='@json($payload)'>
    <i class="fas fa-edit"></i>
  </button>

  <button type="button"
          class="btn btn-outline-success btn-default-bc"
          data-id="{{ $row->id }}"
          data-name="{{ e($row->name) }}">
    <i class="fas fa-star"></i>
  </button>

  <button type="button"
          class="btn btn-outline-danger btn-del-bc"
          data-id="{{ $row->id }}">
    <i class="fas fa-trash"></i>
  </button>
</div>