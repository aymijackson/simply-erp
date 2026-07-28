@extends('layouts.master')

@section('title', 'Vehicles')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">Vehicles</h1>
            <small class="text-muted">Logistics / Vehicles</small>
        </div>

        <button class="btn btn-primary" id="addVehicleBtn">
            <i class="fas fa-plus mr-1"></i> New Vehicle
        </button>
    </div>

    <div class="card shadow mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0 text-primary"><i class="fas fa-filter mr-1"></i> Filters</h6>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-primary" id="applyFiltersBtn" type="button">
                    <i class="fas fa-filter mr-1"></i> Apply
                </button>
                <button class="btn btn-sm btn-outline-secondary" id="resetFiltersBtn" type="button">
                    <i class="fas fa-undo mr-1"></i> Reset
                </button>
            </div>
        </div>

        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label mb-1">Status</label>
                    <select id="filter_is_active" class="form-control" style="width:100%;">
                        <option value="">All</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label mb-1">Company</label>
                    <select id="filter_company_id" class="form-control" style="width:100%;">
                        <option value=""></option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table id="vehiclesTable" class="table table-bordered table-hover w-100">
                    <thead class="bg-light">
                        <tr>
                            <th>#</th>
                            <th>Registration</th>
                            <th>Make</th>
                            <th>Model</th>
                            <th>Color</th>
                            <th>Year</th>
                            <th>Company</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th style="width:160px;">Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<div class="modal fade" id="vehicleModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="vehicleModalTitle">New Vehicle</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <form id="vehicleForm">
        <div class="modal-body">
            <input type="hidden" id="vehicle_id" value="">

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label mb-1">Registration No <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="registration_no" required>
                    <small class="text-danger d-none" id="err_registration_no"></small>
                </div>

                <div class="col-md-6">
                    <label class="form-label mb-1">Company (optional)</label>
                    <select class="form-control" id="company_id" style="width:100%;"></select>
                </div>

                <div class="col-md-4">
                    <label class="form-label mb-1">Make</label>
                    <input type="text" class="form-control" id="make">
                </div>

                <div class="col-md-4">
                    <label class="form-label mb-1">Model</label>
                    <input type="text" class="form-control" id="model">
                </div>

                <div class="col-md-4">
                    <label class="form-label mb-1">Color</label>
                    <input type="text" class="form-control" id="color">
                </div>

                <div class="col-md-4">
                    <label class="form-label mb-1">Year</label>
                    <input type="number" class="form-control" id="year" min="1900" max="2100">
                </div>

                <div class="col-md-8">
                    <label class="form-label mb-1">VIN</label>
                    <input type="text" class="form-control" id="vin">
                </div>

                <div class="col-md-12">
                    <label class="form-label mb-1">Notes</label>
                    <textarea class="form-control" id="notes" rows="2"></textarea>
                </div>

                <div class="col-md-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="is_active" checked>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">
              <i class="fas fa-save mr-1"></i> Save
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
let vehiclesTable;
const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

function initSelect2Ajax(selector, url, placeholder) {
    const $el = $(selector);
    if ($el.hasClass('select2-hidden-accessible')) return;

    $el.select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder,
        allowClear: true,
        ajax: {
            url,
            dataType: 'json',
            delay: 250,
            data: params => ({ q: params.term || '', page: params.page || 1 }),
            processResults: data => {
                if (Array.isArray(data)) return { results: data };
                if (data.results) return data;
                return { results: [] };
            }
        }
    });
}

function clearErrors(){
    const el = document.getElementById('err_registration_no');
    if (el){ el.classList.add('d-none'); el.textContent=''; }
}

function showErrors(errors){
    if (errors?.registration_no?.[0]) {
        const el = document.getElementById('err_registration_no');
        el.classList.remove('d-none');
        el.textContent = errors.registration_no[0];
    }
}

function resetModal(){
    $('#vehicle_id').val('');
    $('#vehicleModalTitle').text('New Vehicle');
    $('#registration_no').val('');
    $('#make').val('');
    $('#model').val('');
    $('#color').val('');
    $('#year').val('');
    $('#vin').val('');
    $('#notes').val('');
    $('#company_id').val(null).trigger('change.select2');
    $('#is_active').prop('checked', true);
    clearErrors();
}

function getFilters(){
    return {
        is_active: $('#filter_is_active').val(),
        company_id: $('#filter_company_id').val(),
    };
}

document.addEventListener('DOMContentLoaded', function(){
    $('#filter_is_active').select2({ theme:'bootstrap-5', width:'100%', allowClear:true, placeholder:'All' });

    // Replace this with your real company select2 route if different
    initSelect2Ajax('#filter_company_id', "{{ route('admin.companies.select2') ?? '' }}", 'All Companies');
    initSelect2Ajax('#company_id', "{{ route('admin.companies.select2') ?? '' }}", 'Select company');

    vehiclesTable = $('#vehiclesTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        autoWidth: false,
        order: [[0,'desc']],
        ajax: {
            url: "{{ route('admin.vehicles.datatable') }}",
            type: 'GET',
            data: d => Object.assign(d, getFilters()),
        },
        columns: [
            { data:'id', name:'id' },
            { data:'registration_no', name:'registration_no' },
            { data:'make', name:'make' },
            { data:'model', name:'model' },
            { data:'color', name:'color' },
            { data:'year', name:'year' },
            { data:'company', name:'company.name' },
            { data:'status', name:'is_active' },
            { data:'created', name:'created_at' },
            { data:'actions', name:'actions', orderable:false, searchable:false },
        ],
    });

    $('#applyFiltersBtn').on('click', () => vehiclesTable.ajax.reload(null,true));
    $('#resetFiltersBtn').on('click', function(){
        $('#filter_is_active').val(null).trigger('change.select2');
        $('#filter_company_id').val(null).trigger('change.select2');
        vehiclesTable.ajax.reload(null,true);
    });

    $('#addVehicleBtn').on('click', function(){
        resetModal();
        $('#vehicleModal').modal('show');
    });

    $('#vehicleForm').on('submit', async function(e){
        e.preventDefault();
        clearErrors();

        const id = $('#vehicle_id').val();
        const isUpdate = !!id;

        const payload = {
            registration_no: $('#registration_no').val(),
            company_id: $('#company_id').val() || null,
            make: $('#make').val(),
            model: $('#model').val(),
            color: $('#color').val(),
            year: $('#year').val() || null,
            vin: $('#vin').val(),
            notes: $('#notes').val(),
            is_active: $('#is_active').is(':checked') ? 1 : 0,
        };

        const url = isUpdate ? `{{ url('admin/vehicles') }}/${id}` : `{{ route('admin.vehicles.store') }}`;
        const method = isUpdate ? 'PUT' : 'POST';

        try {
            const res = await fetch(url, {
                method,
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json().catch(()=>({}));

            if(!res.ok){
                if (data.errors) showErrors(data.errors);
                throw new Error(data.message || 'Save failed');
            }

            Swal.fire({ icon:'success', title:'Saved', timer:1200, showConfirmButton:false });
            $('#vehicleModal').modal('hide');
            vehiclesTable.ajax.reload(null,false);

        } catch (err) {
            Swal.fire({ icon:'error', title:'Error', text: err.message || 'Save failed' });
        }
    });
});

window.editVehicle = function(vehicle){
    resetModal();
    $('#vehicleModalTitle').text('Edit Vehicle');
    $('#vehicle_id').val(vehicle.id);
    $('#registration_no').val(vehicle.registration_no);
    $('#make').val(vehicle.make || '');
    $('#model').val(vehicle.model || '');
    $('#color').val(vehicle.color || '');
    $('#year').val(vehicle.year || '');
    $('#vin').val(vehicle.vin || '');
    $('#notes').val(vehicle.notes || '');
    $('#is_active').prop('checked', !!vehicle.is_active);

    if (vehicle.company_id && vehicle.company_text) {
        const opt = new Option(vehicle.company_text, vehicle.company_id, true, true);
        $('#company_id').append(opt).trigger('change.select2');
    } else {
        $('#company_id').val(null).trigger('change.select2');
    }

    $('#vehicleModal').modal('show');
};

window.deleteVehicle = function(id){
    Swal.fire({
        icon:'warning',
        title:'Delete vehicle?',
        text:'This cannot be undone.',
        showCancelButton:true,
        confirmButtonText:'Yes, delete',
        confirmButtonColor:'#dc3545'
    }).then(async (r) => {
        if (!r.isConfirmed) return;

        try {
            const res = await fetch(`{{ url('admin/vehicles') }}/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
            });
            const data = await res.json().catch(()=>({}));

            if(!res.ok) throw new Error(data.message || 'Delete failed');

            Swal.fire({ icon:'success', title:'Deleted', timer:1200, showConfirmButton:false });
            vehiclesTable.ajax.reload(null,false);
        } catch (err) {
            Swal.fire({ icon:'error', title:'Error', text: err.message || 'Delete failed' });
        }
    });
};
</script>
@endpush
