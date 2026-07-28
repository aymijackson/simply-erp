@php
  $payload = [
    'id' => $r->id,
    'registration_no' => $r->registration_no,
    'make' => $r->make,
    'model' => $r->model,
    'color' => $r->color,
    'year' => $r->year,
    'vin' => $r->vin,
    'notes' => $r->notes,
    'is_active' => (bool)$r->is_active,
    'company_id' => $r->company_id,
    'company_text' => $r->company?->name,
  ];
@endphp

<div class="d-flex gap-1">
    <button class="btn btn-sm btn-outline-primary" onclick='editVehicle(@json($payload))'>
        <i class="fas fa-edit"></i>
    </button>

    <button class="btn btn-sm btn-outline-danger" onclick="deleteVehicle({{ $r->id }})">
        <i class="fas fa-trash"></i>
    </button>
</div>
