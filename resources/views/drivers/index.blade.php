@extends('layouts.master')

@section('title', 'Drivers')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">Drivers</h1>
            <small class="text-muted">Logistics / Drivers</small>
        </div>

        <button class="btn btn-primary" id="addDriverBtn">
            <i class="fas fa-plus mr-1"></i> New Driver
        </button>
    </div>

    <div class="card shadow mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0 text-primary">
                <i class="fas fa-filter mr-1"></i> Filters
            </h6>
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
                    <small class="text-muted">Optional filter</small>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table id="driversTable" class="table table-bordered table-hover w-100">
                    <thead class="bg-light">
                        <tr>
                            <th>#</th>
                            <th>Full Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Vehicles</th>
                            <th>Company</th>
                            <th>User</th>
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

{{-- Modal --}}
<div class="modal fade" id="driverModal" tabindex="-1" aria-hidden="true">
<div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-md-down">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="driverModalTitle">New Driver</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="driverForm">
        <div class="modal-body">

            <input type="hidden" id="driver_id" value="">

            {{-- Make spacing consistent --}}
            <div class="row g-3">

                <div class="col-lg-4 col-md-6">
                    <label class="form-label mb-1">First Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="first_name" required>
                    <small class="text-danger d-none" id="err_first_name"></small>
                </div>

                <div class="col-lg-4 col-md-6">
                    <label class="form-label mb-1">Last Name</label>
                    <input type="text" class="form-control" id="last_name">
                    <small class="text-danger d-none" id="err_last_name"></small>
                </div>

                <div class="col-lg-4 col-md-6">
                    <label class="form-label mb-1">Phone</label>
                    <input type="text" class="form-control" id="phone">
                </div>

                <div class="col-lg-4 col-md-6">
                    <label class="form-label mb-1">Email</label>
                    <input type="email" class="form-control" id="email">
                </div>

                <div class="col-lg-4 col-md-6">
                    <label class="form-label mb-1">License No</label>
                    <input type="text" class="form-control" id="license_no">
                </div>

                <div class="col-lg-4 col-md-6">
                    <label class="form-label mb-1">User Account (optional)</label>
                    <select class="form-control" id="user_id" style="width:100%;">
                        <option value=""></option>
                    </select>
                </div>

                <div class="col-lg-6 col-md-12">
                    <label class="form-label mb-1">Company (optional)</label>
                    <select class="form-control" id="company_id" style="width:100%;">
                        <option value=""></option>
                    </select>
                </div>

                <div class="col-lg-6 col-md-12">
                    <label class="form-label mb-1">Vehicles (optional)</label>
                    <select class="form-control" id="vehicle_ids" multiple style="width:100%;"></select>
                </div>

                <div class="col-lg-6 col-md-12">
                    <label class="form-label mb-1">Primary Vehicle (optional)</label>
                    <select class="form-control" id="primary_vehicle_id" style="width:100%;">
                        <option value=""></option>
                    </select>
                   
                </div>

                <div class="col-lg-6 col-md-12 d-flex align-items-end">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="is_active" checked>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>

            </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary" id="saveDriverBtn">
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
let driversTable;
const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

function initSelect2Ajax(selector, url, placeholder, dropdownParent = null) {
    const $el = $(selector);
    if ($el.hasClass('select2-hidden-accessible')) return;

    $el.select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder,
        allowClear: true,
        dropdownParent: dropdownParent ? $(dropdownParent) : $(document.body),
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
    ['first_name','last_name','vehicle_ids','primary_vehicle_id','user_id','company_id'].forEach(k => {
        const el = document.getElementById('err_'+k);
        if (!el) return;
        el.classList.add('d-none');
        el.textContent = '';
    });
}

function showErrors(errors){
    Object.keys(errors || {}).forEach(k => {
        const el = document.getElementById('err_'+k);
        if (!el) return;
        el.classList.remove('d-none');
        el.textContent = errors[k][0] ?? 'Invalid';
    });
}

function resetModal(){
    $('#driver_id').val('');
    $('#driverModalTitle').text('New Driver');
    $('#first_name').val('');
    $('#last_name').val('');
    $('#phone').val('');
    $('#email').val('');
    $('#license_no').val('');

    // vehicles
    $('#vehicle_ids').val(null).trigger('change.select2');
    $('#primary_vehicle_id').val(null).trigger('change.select2');

    // company & user
    $('#company_id').val(null).trigger('change.select2');
    $('#user_id').val(null).trigger('change.select2');

    $('#is_active').prop('checked', true);
    clearErrors();
}

function getFilters(){
    return {
        is_active: $('#filter_is_active').val(),
        company_id: $('#filter_company_id').val(),
    };
}

function reloadTable(){
    if (driversTable) driversTable.ajax.reload(null, true);
}

function setSelect2Value($el, id, text) {
    if (!id) return;
    const opt = new Option(text || ('Item #' + id), id, true, true);
    $el.append(opt).trigger('change.select2');
}

// Keep primary vehicle restricted to selected vehicles
function enforcePrimaryWithinSelected() {
    const selected = ($('#vehicle_ids').val() || []).map(String);
    const primary = String($('#primary_vehicle_id').val() || '');

    if (primary && !selected.includes(primary)) {
        $('#primary_vehicle_id').val(null).trigger('change.select2');
    }
}

document.addEventListener('DOMContentLoaded', function(){

    // Filters
    $('#filter_is_active').select2({ theme:'bootstrap-5', width:'100%', allowClear:true, placeholder:'All' });

    // Filters + modal select2
    initSelect2Ajax('#filter_company_id', "{{ route('admin.companies.select2') ?? '' }}", 'All Companies');

    // Init select2 inside modal (important: dropdownParent fixes z-index)
    const modalEl = document.getElementById('driverModal');

    initSelect2Ajax('#company_id', "{{ route('admin.companies.select2') ?? '' }}", 'Select company', modalEl);
    initSelect2Ajax('#user_id', "{{ route('admin.users.select2') }}", 'Select user', modalEl);

    initSelect2Ajax('#vehicle_ids', "{{ route('admin.vehicles.select2') }}", 'Select vehicles', modalEl);
    initSelect2Ajax('#primary_vehicle_id', "{{ route('admin.vehicles.select2') }}", 'Select primary vehicle', modalEl);

    // When vehicle selection changes, ensure primary stays within
    $('#vehicle_ids').on('change', enforcePrimaryWithinSelected);
    $('#primary_vehicle_id').on('change', enforcePrimaryWithinSelected);

    driversTable = $('#driversTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        autoWidth: false,
        order: [[0,'desc']],
        ajax: {
            url: "{{ route('admin.drivers.datatable') }}",
            type: 'GET',
            data: d => Object.assign(d, getFilters()),
        },
        columns: [
            { data:'id', name:'id' },
            { data:'full_name', name:'first_name' },
            { data:'phone', name:'phone' },
            { data:'email', name:'email' },
            { data:'vehicles', name:'vehicles.registration_no', orderable:false, searchable:false },
            { data:'company', name:'company.name' },
            { data:'user', name:'user.name' },
            { data:'status', name:'is_active' },
            { data:'created', name:'created_at' },
            { data:'actions', name:'actions', orderable:false, searchable:false },
        ],
    });

    $('#applyFiltersBtn').on('click', reloadTable);

    $('#resetFiltersBtn').on('click', function(){
        $('#filter_is_active').val(null).trigger('change.select2');
        $('#filter_company_id').val(null).trigger('change.select2');
        reloadTable();
    });

    $('#addDriverBtn').on('click', function(){
        resetModal();
        $('#driverModal').modal('show');
    });

    // Submit create/update
    $('#driverForm').on('submit', async function(e){
        e.preventDefault();
        clearErrors();

        const id = $('#driver_id').val();
        const isUpdate = !!id;

        const vehicleIds = ($('#vehicle_ids').val() || []).map(v => parseInt(v, 10)).filter(n => Number.isFinite(n));
        const primaryVehicleId = $('#primary_vehicle_id').val() ? parseInt($('#primary_vehicle_id').val(), 10) : null;

        // Ensure primary is included in selected vehicles
        if (primaryVehicleId && !vehicleIds.includes(primaryVehicleId)) {
            Swal.fire({
                icon: 'warning',
                title: 'Primary vehicle not selected',
                text: 'Please select the primary vehicle in the Vehicles list.'
            });
            return;
        }

        const payload = {
            first_name: $('#first_name').val(),
            last_name: $('#last_name').val(),
            phone: $('#phone').val(),
            email: $('#email').val(),
            license_no: $('#license_no').val(),
            company_id: $('#company_id').val() || null,
            user_id: $('#user_id').val() || null,
            is_active: $('#is_active').is(':checked') ? 1 : 0,

            // ✅ vehicles
            vehicle_ids: vehicleIds,
            primary_vehicle_id: primaryVehicleId,
        };

        const url = isUpdate
            ? `{{ url('admin/drivers') }}/${id}`
            : `{{ route('admin.drivers.store') }}`;

        const method = isUpdate ? 'PUT' : 'POST';

        try {
            const res = await fetch(url, {
                method,
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const data = await res.json().catch(()=>({}));

            if(!res.ok){
                if (data.errors) showErrors(data.errors);
                throw new Error(data.message || 'Save failed');
            }

            Swal.fire({ icon:'success', title:'Saved', text:data.message || 'Driver saved', timer:1200, showConfirmButton:false });
            $('#driverModal').modal('hide');
            driversTable.ajax.reload(null,false);

        } catch (err) {
            Swal.fire({ icon:'error', title:'Error', text: err.message || 'Save failed' });
        }
    });

});

// Edit from action button
// Expect payload from partial to include:
// vehicle_ids: [1,2], vehicles_text: [{id,text}], primary_vehicle_id
window.editDriver = function(driver){
    resetModal();
    $('#driverModalTitle').text('Edit Driver');

    $('#driver_id').val(driver.id);
    $('#first_name').val(driver.first_name);
    $('#last_name').val(driver.last_name || '');
    $('#phone').val(driver.phone || '');
    $('#email').val(driver.email || '');
    $('#license_no').val(driver.license_no || '');
    $('#is_active').prop('checked', !!driver.is_active);

    // company
    if (driver.company_id && driver.company_text) {
        setSelect2Value($('#company_id'), driver.company_id, driver.company_text);
    }

    // user
    if (driver.user_id && driver.user_text) {
        setSelect2Value($('#user_id'), driver.user_id, driver.user_text);
    }

    // vehicles (multi)
    if (Array.isArray(driver.vehicles_text) && driver.vehicles_text.length) {
        driver.vehicles_text.forEach(v => setSelect2Value($('#vehicle_ids'), v.id, v.text));
    } else if (Array.isArray(driver.vehicle_ids) && driver.vehicle_ids.length) {
        // fallback: show ids only (not ideal but works)
        driver.vehicle_ids.forEach(id => setSelect2Value($('#vehicle_ids'), id, 'Vehicle #' + id));
    }

    // primary vehicle
    if (driver.primary_vehicle_id) {
        const pText = (driver.primary_vehicle_text) ? driver.primary_vehicle_text : ('Vehicle #' + driver.primary_vehicle_id);
        setSelect2Value($('#primary_vehicle_id'), driver.primary_vehicle_id, pText);
    }

    enforcePrimaryWithinSelected();

    $('#driverModal').modal('show');
};

window.deleteDriver = function(id){
    Swal.fire({
        icon:'warning',
        title:'Delete driver?',
        text:'This cannot be undone.',
        showCancelButton:true,
        confirmButtonText:'Yes, delete',
        confirmButtonColor:'#dc3545'
    }).then(async (r) => {
        if (!r.isConfirmed) return;

        try {
            const res = await fetch(`{{ url('admin/drivers') }}/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
            });

            const data = await res.json().catch(()=>({}));

            if(!res.ok){
                throw new Error(data.message || 'Delete failed');
            }

            Swal.fire({ icon:'success', title:'Deleted', timer:1200, showConfirmButton:false });
            driversTable.ajax.reload(null,false);

        } catch (err) {
            Swal.fire({ icon:'error', title:'Error', text: err.message || 'Delete failed' });
        }
    });
};
</script>
@endpush
