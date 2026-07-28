@php
  // Build a compact payload for edit modal
  $payload = [
    'id' => $r->id,
    'first_name' => $r->first_name,
    'last_name' => $r->last_name,
    'phone' => $r->phone,
    'email' => $r->email,
    'license_no' => $r->license_no,
    'vehicle_no' => $r->vehicle_no,
    'company_id' => $r->company_id,
    'company_text' => $r->company?->name,
    'user_id' => $r->user_id,
    'user_text' => $r->user ? trim(($r->user->name ?? 'User') . (($r->user->email ?? '') ? ' ('.$r->user->email.')' : '')) : null,
    'is_active' => (bool)$r->is_active,
  ];
@endphp

<div class="d-flex gap-1">
    <button class="btn btn-sm btn-outline-primary"
            onclick='editDriver(@json($payload))'>
        <i class="fas fa-edit"></i>
    </button>

    <button class="btn btn-sm btn-outline-danger"
            onclick="deleteDriver({{ $r->id }})">
        <i class="fas fa-trash"></i>
    </button>
</div>
