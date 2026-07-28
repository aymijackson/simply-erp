@extends('layouts.master')

@section('title', 'Sales Deliveries')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">Sales Deliveries</h1>
            <small class="text-muted">Sales / Deliveries</small>
        </div>
        <a class="btn btn-primary" href="{{ route('admin.sales.deliveries.create') }}">
            <i class="fas fa-plus mr-1"></i> New Delivery
        </a>
    </div>

    <div class="card shadow mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0 text-primary"><i class="fas fa-filter mr-1"></i> Filters</h6>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-primary" id="applyFiltersBtn" type="button"><i class="fas fa-filter mr-1"></i> Apply</button>
                <button class="btn btn-sm btn-outline-secondary" id="resetFiltersBtn" type="button"><i class="fas fa-undo mr-1"></i> Reset</button>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label mb-1">Status</label>
                    <select id="filter_status" class="form-control" style="width:100%;">
                        <option value="">All</option>
                        <option value="draft">Draft</option>
                        <option value="posted">Posted</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label mb-1">Customer</label>
                    <select id="filter_customer_id" class="form-control" style="width:100%;"><option value=""></option></select>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table id="deliveriesTable" class="table table-bordered table-hover w-100">
                    <thead class="bg-light">
                        <tr>
                            <th>#</th>
                            <th>Delivery No</th>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Driver</th>
                            <th>Vehicle</th>
                            <th>Status</th>
                            <th>Ship Date</th>
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
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let deliveriesTable;
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
            processResults: data => data.results ? data : ({ results: Array.isArray(data) ? data : [] })
        }
    });
}

function getFilters(){
    return {
        status: $('#filter_status').val(),
        customer_id: $('#filter_customer_id').val(),
    };
}

document.addEventListener('DOMContentLoaded', function(){

    $('#filter_status').select2({ theme:'bootstrap-5', width:'100%', allowClear:true, placeholder:'All' });

    // Use your existing customers select2 route if you have one
    initSelect2Ajax('#filter_customer_id', "{{ route('admin.customers.select2') ?? '' }}", 'All Customers');

    deliveriesTable = $('#deliveriesTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        autoWidth: false,
        order: [[0,'desc']],
        ajax: {
            url: "{{ route('admin.sales.deliveries.datatable') }}",
            type: 'GET',
            data: d => Object.assign(d, getFilters()),
        },
        columns: [
            { data:'id', name:'id' },
            { data:'delivery_no', name:'delivery_no' },
            { data:'order', name:'order.order_no' },
            { data:'customer', name:'customer.name' },
            { data:'driver', name:'driver.first_name' },
            { data:'vehicle', name:'vehicle.registration_no' },
            { data:'status', name:'status', orderable:false, searchable:false },
            { data:'ship_date', name:'ship_date' },
            { data:'created', name:'created_at' },
            { data:'actions', name:'actions', orderable:false, searchable:false },
        ],
    });

    $('#applyFiltersBtn').on('click', () => deliveriesTable.ajax.reload(null,true));
    $('#resetFiltersBtn').on('click', () => {
        $('#filter_status').val(null).trigger('change.select2');
        $('#filter_customer_id').val(null).trigger('change.select2');
        deliveriesTable.ajax.reload(null,true);
    });
});

// actions
window.deleteDelivery = function(id){
    Swal.fire({
        icon:'warning',
        title:'Delete delivery?',
        text:'Only draft deliveries can be deleted.',
        showCancelButton:true,
        confirmButtonText:'Yes, delete',
        confirmButtonColor:'#dc3545'
    }).then(async (r) => {
        if (!r.isConfirmed) return;
        try {
            const res = await fetch(`{{ url('admin/sales/deliveries') }}/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
            });
            const data = await res.json().catch(()=>({}));
            if(!res.ok) throw new Error(data.message || 'Delete failed');
            Swal.fire({ icon:'success', title:'Deleted', timer:1200, showConfirmButton:false });
            deliveriesTable.ajax.reload(null,false);
        } catch (e) {
            Swal.fire({ icon:'error', title:'Error', text: e.message || 'Delete failed' });
        }
    });
};
</script>
@endpush
