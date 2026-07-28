@extends('layouts.master')

@section('title', 'Sales Invoices')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">Sales Invoices</h1>
            <small class="text-muted">Sales / Invoices</small>
        </div>

        @can('sales.invoices.create')
        <a href="{{ route('admin.sales.invoices.create') }}" class="btn btn-primary">
            <i class="fas fa-plus mr-1"></i> New Invoice
        </a>
        @endcan
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
                    <select id="filter_status" class="form-control" style="width:100%;">
                        <option value="">All</option>
                        <option value="draft">Draft</option>
                        <option value="posted">Posted</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="col-md-5">
                    <label class="form-label mb-1">Customer</label>
                    <select id="filter_customer_id" class="form-control" style="width:100%;">
                        <option value=""></option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label mb-1">Invoice No (text)</label>
                    <input type="text" id="filter_invoice_no" class="form-control" placeholder="e.g. INV-20260205-0001">
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table id="invoicesTable" class="table table-bordered table-hover w-100">
                    <thead class="bg-light">
                        <tr>
                            <th>#</th>
                            <th>Invoice No</th>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Invoice Date</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th style="width:170px;">Action</th>
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
let invoicesTable;
const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

function initSelect2Ajax(selector, url, placeholder = 'All') {
    const $el = $(selector);
    if ($el.hasClass('select2-hidden-accessible')) return;

    $el.select2({
        theme: 'bootstrap-5',
        placeholder,
        allowClear: true,
        width: '100%',
        ajax: {
            url,
            dataType: 'json',
            delay: 250,
            data: params => ({ q: params.term || '', page: params.page || 1 }),
            processResults: function (data) {
                if (Array.isArray(data)) return { results: data };
                if (data.results) return data;
                return { results: [] };
            }
        }
    });
}

function getFilters() {
    return {
        status: $('#filter_status').val(),
        customer_id: $('#filter_customer_id').val(),
        invoice_no: $('#filter_invoice_no').val(),
    };
}

document.addEventListener('DOMContentLoaded', function () {

    $('#filter_status').select2({ theme:'bootstrap-5', width:'100%', allowClear:true, placeholder:'All' });
    initSelect2Ajax('#filter_customer_id', "{{ route('admin.customers.select2') }}", 'All customers');

    invoicesTable = $('#invoicesTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        autoWidth: false,
        order: [[0,'desc']],
        ajax: {
            url: "{{ route('admin.sales.invoices.datatable') }}",
            type: 'GET',
            data: d => Object.assign(d, getFilters())
        },
        columns: [
            { data:'id', name:'id' },
            { data:'invoice_no', name:'invoice_no' },
            { data:'order', name:'order.order_no' },
            { data:'customer', name:'customer.name' },
            { data:'invoice_date', name:'invoice_date' },
            { data:'total', name:'grand_total' },
            { data:'status', name:'status', orderable:false, searchable:false },
            { data:'created', name:'created_at' },
            { data:'actions', name:'actions', orderable:false, searchable:false },
        ]
    });

    $('#applyFiltersBtn').on('click', () => invoicesTable.ajax.reload(null,true));

    $('#resetFiltersBtn').on('click', function(){
        $('#filter_status').val(null).trigger('change.select2');
        $('#filter_customer_id').val(null).trigger('change.select2');
        $('#filter_invoice_no').val('');
        invoicesTable.ajax.reload(null,true);
    });

    $('#filter_invoice_no').on('keyup', function(e){
        if (e.key === 'Enter') invoicesTable.ajax.reload(null,true);
    });
});

// actions
window.deleteInvoice = function(id){
    Swal.fire({
        icon:'warning',
        title:'Delete invoice?',
        text:'Only draft invoices can be deleted.',
        showCancelButton:true,
        confirmButtonText:'Yes, delete',
        confirmButtonColor:'#dc3545'
    }).then(async (r) => {
        if (!r.isConfirmed) return;

        try {
            const res = await fetch(`{{ url('admin/sales/invoices') }}/${id}`, {
                method:'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept':'application/json' }
            });
            const data = await res.json().catch(()=>({}));
            if(!res.ok) throw new Error(data.message || 'Delete failed');

            Swal.fire({ icon:'success', title:'Deleted', timer:1200, showConfirmButton:false });
            invoicesTable.ajax.reload(null,false);
        } catch(e){
            Swal.fire({ icon:'error', title:'Error', text:e.message || 'Delete failed' });
        }
    });
};
</script>
@endpush
