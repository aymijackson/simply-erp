@extends('layouts.master')

@section('title', 'Sales Payments')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">Sales Payments</h1>
            <small class="text-muted">Sales / Payments</small>
        </div>
        <div>
            @can('sales.payments.create')
            <a href="{{ route('admin.sales.payments.create') }}" class="btn btn-primary">
                <i class="fas fa-plus mr-1"></i> New Payment
            </a>
            @endcan
        </div>
    </div>

    {{-- Filters --}}
    <div class="card shadow mb-3">
        <div class="card-body">
            <div class="row g-3">

                <div class="col-md-3">
                    <label class="form-label mb-1">Payment No</label>
                    <input type="text" id="f_payment_no" class="form-control" placeholder="PAY-...">
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-1">Customer</label>
                    <select id="f_customer_id" class="form-control" style="width:100%;"></select>
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-1">Status</label>
                    <select id="f_status" class="form-control">
                        <option value="">All</option>
                        <option value="draft">Draft</option>
                        <option value="posted">Posted</option>
                        <option value="void">Void</option>
                    </select>
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <button id="filterBtn" class="btn btn-outline-primary mr-2">
                        <i class="fas fa-search mr-1"></i> Apply
                    </button>
                    <button id="resetBtn" class="btn btn-outline-secondary">
                        Reset
                    </button>
                </div>

            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table id="paymentsTable" class="table table-bordered table-hover w-100">
                    <thead class="bg-light">
                        <tr>
                            <th>ID</th>
                            <th>Payment No</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">Allocated</th>
                            <th class="text-end">Unallocated</th>
                            <th>Status</th>
                            <th style="width:160px;">Actions</th>
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
<script>
$(function(){

    // ✅ Customer select2 filter
    $('#f_customer_id').select2({
        theme: 'bootstrap-5',
        placeholder: 'All customers',
        allowClear: true,
        ajax: {
            url: "{{ route('admin.customers.select2') }}",
            dataType: 'json',
            delay: 250,
            data: params => ({ q: params.term || '' }),
            processResults: data => data.results ? data : {results:data}
        }
    });

    // ✅ DataTable
    const table = $('#paymentsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('admin.sales.payments.datatable') }}",
            data: function(d){
                d.payment_no  = $('#f_payment_no').val();
                d.customer_id = $('#f_customer_id').val();
                d.status      = $('#f_status').val();
            }
        },
        columns: [
            {data:'id'},
            {data:'payment_no'},
            {data:'customer'},
            {data:'payment_date'},
            {data:'amount', className:'text-end'},
            {data:'allocated', className:'text-end'},
            {data:'unallocated', className:'text-end'},
            {data:'status', orderable:false, searchable:false},
            {data:'actions', orderable:false, searchable:false},
        ],
        order: [[0,'desc']]
    });

    // ✅ Filters
    $('#filterBtn').on('click', function(){
        table.ajax.reload();
    });

    $('#resetBtn').on('click', function(){
        $('#f_payment_no').val('');
        $('#f_status').val('');
        $('#f_customer_id').val(null).trigger('change');
        table.ajax.reload();
    });


    const csrf = $('meta[name="csrf-token"]').attr('content');

    async function doAction(url, method='POST'){
        const res = await fetch(url, {
            method,
            headers:{
                'X-CSRF-TOKEN': csrf,
                'Accept':'application/json',
                'Content-Type':'application/json'
            }
        });
        const data = await res.json().catch(()=>({}));
        if(!res.ok) throw new Error(data.message || 'Action failed');
        return data;
    }

    // POST payment
    $(document).on('click', '.js-post-payment', async function(){
        const id = $(this).data('id');
        const url = `{{ url('admin/sales/payments') }}/${id}/post`;

        const ok = await Swal.fire({
            icon:'question',
            title:'Post payment?',
            text:'This will mark payment as posted.',
            showCancelButton:true,
            confirmButtonText:'Yes, post'
        });

        if(!ok.isConfirmed) return;

        try{
            const data = await doAction(url, 'POST');
            Swal.fire({icon:'success', title:'Posted', text:data.message || 'Payment posted', timer:1200, showConfirmButton:false});
            $('#paymentsTable').DataTable().ajax.reload(null,false);
        }catch(e){
            Swal.fire({icon:'error', title:'Error', text:e.message});
        }
    });

    // VOID payment
    $(document).on('click', '.js-void-payment', async function(){
        const id = $(this).data('id');
        const url = `{{ url('admin/sales/payments') }}/${id}/void`;

        const ok = await Swal.fire({
            icon:'warning',
            title:'Void payment?',
            text:'This will void the posted payment.',
            showCancelButton:true,
            confirmButtonText:'Yes, void'
        });

        if(!ok.isConfirmed) return;

        try{
            const data = await doAction(url, 'POST');
            Swal.fire({icon:'success', title:'Voided', text:data.message || 'Payment voided', timer:1200, showConfirmButton:false});
            $('#paymentsTable').DataTable().ajax.reload(null,false);
        }catch(e){
            Swal.fire({icon:'error', title:'Error', text:e.message});
        }
    });

    // DELETE payment
    $(document).on('click', '.js-delete-payment', async function(){
        const id = $(this).data('id');
        const url = `{{ url('admin/sales/payments') }}/${id}`;

        const ok = await Swal.fire({
            icon:'warning',
            title:'Delete payment?',
            text:'This will permanently remove the draft payment.',
            showCancelButton:true,
            confirmButtonText:'Yes, delete'
        });

        if(!ok.isConfirmed) return;

        try{
            const res = await fetch(url, {
                method:'DELETE',
                headers:{
                    'X-CSRF-TOKEN': csrf,
                    'Accept':'application/json'
                }
            });
            const data = await res.json().catch(()=>({}));
            if(!res.ok) throw new Error(data.message || 'Delete failed');

            Swal.fire({icon:'success', title:'Deleted', text:data.message || 'Deleted', timer:1200, showConfirmButton:false});
            $('#paymentsTable').DataTable().ajax.reload(null,false);
        }catch(e){
            Swal.fire({icon:'error', title:'Error', text:e.message});
        }
    });
});
</script>
@endpush