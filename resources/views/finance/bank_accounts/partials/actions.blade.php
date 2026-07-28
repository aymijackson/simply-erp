{{-- resources/views/finance/bank_accounts/partials/actions.blade.php --}}
@php
  // controller passes $json
@endphp

<div class="btn-group btn-group-sm">
  <button type="button"
          class="btn btn-outline-primary btn-edit-bank"
          data-json='@json($json)'>
    <i class="fas fa-edit"></i>
  </button>

  <button type="button"
          class="btn btn-outline-danger btn-del-bank"
          data-id="{{ $json['id'] ?? '' }}">
    <i class="fas fa-trash"></i>
  </button>
</div>