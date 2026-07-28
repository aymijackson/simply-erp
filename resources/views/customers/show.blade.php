@extends('layouts.master')

@section('title', 'Customer: ' . $customer->name)

@section('content')
<div class="container-fluid">

    {{-- ── Header ──────────────────────────────────────────────────────── --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">{{ $customer->name }}</h1>
            <p class="text-muted mb-0 small">Customer Profile &bull; CRM Overview</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.customers.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    {{-- ── KPI Cards ────────────────────────────────────────────────────── --}}
    <div class="row mb-3 g-2">
        <div class="col-6 col-md-2">
            <div class="card shadow-sm text-center h-100">
                <div class="card-body py-2">
                    <div class="text-muted small">Status</div>
                    {!! $customer->status_badge !!}
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card shadow-sm text-center h-100">
                <div class="card-body py-2">
                    <div class="text-muted small">Credit Limit</div>
                    <div class="fw-bold">{{ number_format($customer->credit_limit ?? 0, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card shadow-sm text-center h-100">
                <div class="card-body py-2">
                    <div class="text-muted small">Payment Terms</div>
                    <div class="fw-bold">{{ $customer->credit_terms_label }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm text-center h-100">
                <div class="card-body py-2">
                    <div class="text-muted small">Email</div>
                    <div class="fw-bold text-truncate">{{ $customer->email ?? '-' }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm text-center h-100">
                <div class="card-body py-2">
                    <div class="text-muted small">Company</div>
                    <div class="fw-bold">{{ $customer->company?->name ?? '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Tabs ─────────────────────────────────────────────────────────── --}}
    <div class="card shadow-sm">
        <div class="card-header bg-white p-0">
            <ul class="nav nav-tabs" id="customerTabs">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabOverview">
                        <i class="fas fa-info-circle me-1"></i> Overview
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabContacts">
                        <i class="fas fa-address-book me-1"></i> Contacts
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabAddresses">
                        <i class="fas fa-map-marker-alt me-1"></i> Addresses
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabOpportunities">
                        <i class="fas fa-bullseye me-1"></i> Opportunities
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabInvoices">
                        <i class="fas fa-file-invoice-dollar me-1"></i> Invoices
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabInteractions">
                        <i class="fas fa-comments me-1"></i> Interactions
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabTickets">
                        <i class="fas fa-life-ring me-1"></i> Tickets
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabDocuments">
                        <i class="fas fa-paperclip me-1"></i> Documents
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body">
            <div class="tab-content">

                {{-- ── OVERVIEW ──────────────────────────────────────────── --}}
                <div class="tab-pane fade show active" id="tabOverview">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-bordered table-sm">
                                <tr><th width="180">Name</th><td>{{ $customer->name }}</td></tr>
                                <tr><th>Position</th><td>{{ $customer->position ?? '-' }}</td></tr>
                                <tr><th>Email</th><td>{{ $customer->email ?? '-' }}</td></tr>
                                <tr><th>Phone</th><td>{{ $customer->phone ?? '-' }}</td></tr>
                                <tr><th>Website</th>
                                    <td>
                                        @if($customer->website)
                                            <a href="{{ $customer->website }}" target="_blank">{{ $customer->website }}</a>
                                        @else -
                                        @endif
                                    </td>
                                </tr>
                                <tr><th>Address</th><td>{{ $customer->address ?? '-' }}</td></tr>
                                <tr><th>Status</th><td>{!! $customer->status_badge !!}</td></tr>
                                <tr><th>Created</th><td>{{ $customer->created_at?->format('d M Y H:i') }}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-bordered table-sm">
                                <tr><th width="180">Company</th><td>{{ $customer->company?->name ?? '-' }}</td></tr>
                                <tr><th>Tax ID / VAT</th><td>{{ $customer->tax_id ?? '-' }}</td></tr>
                                <tr><th>Currency</th><td>{{ $customer->currency_code ?? '-' }}</td></tr>
                                <tr><th>Credit Limit</th><td>{{ number_format($customer->credit_limit ?? 0, 2) }}</td></tr>
                                <tr><th>Payment Terms</th><td>{{ $customer->credit_terms_label }}</td></tr>
                                <tr><th>Internal Notes</th><td>{{ $customer->internal_notes ?? '-' }}</td></tr>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- ── CONTACTS ──────────────────────────────────────────── --}}
                <div class="tab-pane fade" id="tabContacts">
                    <div class="d-flex justify-content-end mb-2">
                        <button class="btn btn-primary btn-sm" id="btnAddContact">
                            <i class="fas fa-plus me-1"></i> Add Contact
                        </button>
                    </div>
                    <table class="table table-bordered table-sm w-100" id="contactsTable">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th><th>Position</th><th>Email</th>
                                <th>Phone</th><th>Primary</th><th>Status</th><th>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>

                {{-- ── ADDRESSES ─────────────────────────────────────────── --}}
                <div class="tab-pane fade" id="tabAddresses">
                    <div class="d-flex justify-content-end mb-2">
                        <button class="btn btn-primary btn-sm" id="btnAddAddress">
                            <i class="fas fa-plus me-1"></i> Add Address
                        </button>
                    </div>
                    <table class="table table-bordered table-sm w-100" id="addressesTable">
                        <thead class="table-light">
                            <tr>
                                <th>Type</th><th>Address</th><th>Country</th>
                                <th>Postal</th><th>Default</th><th>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>

                {{-- ── OPPORTUNITIES ─────────────────────────────────────── --}}
                <div class="tab-pane fade" id="tabOpportunities">
                    <table id="opportunitiesTable" class="table table-bordered table-sm w-100">
                        <thead class="table-light">
                            <tr>
                                <th>Title</th><th>Value</th><th>Stage</th>
                                <th>Probability</th><th>Close Date</th><th>Owner</th><th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>

                {{-- ── INVOICES ──────────────────────────────────────────── --}}
                <div class="tab-pane fade" id="tabInvoices">
                    <table id="invoicesTable" class="table table-bordered table-sm w-100">
                        <thead class="table-light">
                            <tr>
                                <th>Invoice</th><th>Amount</th><th>Status</th>
                                <th>Due Date</th><th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>

                {{-- ── INTERACTIONS ──────────────────────────────────────── --}}
                <div class="tab-pane fade" id="tabInteractions">
                    <table id="interactionsTable" class="table table-bordered table-sm w-100">
                        <thead class="table-light">
                            <tr>
                                <th>Subject</th><th>Type</th><th>Outcome</th>
                                <th>Date</th><th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>

                {{-- ── SUPPORT TICKETS ───────────────────────────────────── --}}
                <div class="tab-pane fade" id="tabTickets">
                    <table id="ticketsTable" class="table table-bordered table-sm w-100">
                        <thead class="table-light">
                            <tr>
                                <th>Ticket</th><th>Subject</th><th>Status</th>
                                <th>Priority</th><th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>

                {{-- ── DOCUMENTS ─────────────────────────────────────────── --}}
                <div class="tab-pane fade" id="tabDocuments">
                    @include('documents.partials.linked-documents-tab', ['model' => $customer])
                </div>

            </div>
        </div>
    </div>
</div>

{{-- ===================== CONTACT MODAL ===================== --}}
<div class="modal fade" id="contactModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="contactModalLabel">Add Contact</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="contactForm" novalidate>
                    @csrf
                    <input type="hidden" id="contactId">
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="c_first_name" name="first_name" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Last Name</label>
                            <input type="text" class="form-control" id="c_last_name" name="last_name">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Position</label>
                            <input type="text" class="form-control" id="c_position" name="position">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" class="form-control" id="c_email" name="email">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Phone</label>
                            <input type="text" class="form-control" id="c_phone" name="phone">
                        </div>
                        <div class="col-3 d-flex align-items-end">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="c_is_primary" name="is_primary" value="1">
                                <label class="form-check-label" for="c_is_primary">Primary</label>
                            </div>
                        </div>
                        <div class="col-3 d-flex align-items-end">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="c_is_active" name="is_active" value="1" checked>
                                <label class="form-check-label" for="c_is_active">Active</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="btnSaveContact">
                    <i class="fas fa-save me-1"></i> Save
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ===================== ADDRESS MODAL ===================== --}}
<div class="modal fade" id="addressModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addressModalLabel">Add Address</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addressForm" novalidate>
                    @csrf
                    <input type="hidden" id="addressId">
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="a_type" name="type" required>
                                <option value="billing">Billing</option>
                                <option value="shipping">Shipping</option>
                                <option value="headquarters">Headquarters</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-6 d-flex align-items-end">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="a_is_default"
                                       name="is_default" value="1">
                                <label class="form-check-label" for="a_is_default">Set as Default</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Line 1 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="a_line1" name="line1" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Line 2</label>
                            <input type="text" class="form-control" id="a_line2" name="line2">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Country</label>
                            <select class="form-select" id="a_country_id" name="country_id">
                                <option value="">-- Select --</option>
                                @foreach($countries as $country)
                                    <option value="{{ $country->id }}">{{ $country->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">State</label>
                            <select class="form-select" id="a_state_id" name="state_id">
                                <option value="">-- Select --</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Postal Code</label>
                            <input type="text" class="form-control" id="a_postal_code" name="postal_code">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="btnSaveAddress">
                    <i class="fas fa-save me-1"></i> Save
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(function () {
    const CSRF = $('meta[name="csrf-token"]').attr('content');

    {{--
        All URLs are generated server-side via Laravel route() helper.
        No JS string construction of URLs — avoids the wrong-base-URL problem entirely.
        For update/destroy we use a placeholder (__ID__) and replace at call-time.
    --}}
    const URLS = {
        // ── Contacts ────────────────────────────────────────────────────
        contactsDT:    '{{ route('admin.crm.customers.contacts.datatable', $customer) }}',
        contactsStore: '{{ route('admin.crm.customers.contacts.store', $customer) }}',
        contactUpdate: (id) => '{{ route('admin.crm.customers.contacts.update',  [$customer->id, '__ID__']) }}'.replace('__ID__', id),
        contactDelete: (id) => '{{ route('admin.crm.customers.contacts.destroy', [$customer->id, '__ID__']) }}'.replace('__ID__', id),

        // ── Addresses ───────────────────────────────────────────────────
        addressesDT:    '{{ route('admin.crm.customers.addresses.datatable', $customer) }}',
        addressesStore: '{{ route('admin.crm.customers.addresses.store', $customer) }}',
        addressUpdate:  (id) => '{{ route('admin.crm.customers.addresses.update',  [$customer->id, '__ID__']) }}'.replace('__ID__', id),
        addressDelete:  (id) => '{{ route('admin.crm.customers.addresses.destroy', [$customer->id, '__ID__']) }}'.replace('__ID__', id),

        // ── Existing sub-datatables ──────────────────────────────────────
        opportunities: '{{ route('admin.customers.show.opportunities.datatable', $customer) }}',
        invoices:      '{{ route('admin.customers.show.invoices.datatable', $customer) }}',
        interactions:  '{{ route('admin.customers.show.interactions.datatable', $customer) }}',
        tickets:       '{{ route('admin.customers.show.support-tickets.datatable', $customer) }}',

        // ── Helpers ─────────────────────────────────────────────────────
        statesSearch:  '{{ route('admin.states.search') }}',
    };

    const contactModal = new bootstrap.Modal(document.getElementById('contactModal'));
    const addressModal = new bootstrap.Modal(document.getElementById('addressModal'));

    // ── Helper ─────────────────────────────────────────────────────────────
    function ajaxSave(url, data, successCb) {
        $.post(url, data)
            .done(res => {
                Swal.fire({ icon:'success', title:'Saved', text: res.message,
                            timer:1600, showConfirmButton:false });
                successCb();
            })
            .fail(xhr => {
                const errors = xhr.responseJSON?.errors;
                const msg    = errors
                    ? Object.values(errors).flat().join('\n')
                    : (xhr.responseJSON?.message || 'Error saving.');
                Swal.fire('Error', msg, 'error');
            });
    }

    // ── Lazy-init DataTables on tab show ───────────────────────────────────
    let contactsDT, addressesDT, oppDT, invDT, interDT, ticketDT;

    document.getElementById('customerTabs').addEventListener('shown.bs.tab', function (e) {
        const target = e.target.getAttribute('data-bs-target');

        if (target === '#tabContacts' && !contactsDT) {
            contactsDT = $('#contactsTable').DataTable({
                processing: true, serverSide: true,
                ajax: { url: URLS.contactsDT, dataSrc: 'data' },
                responsive: true,
                columns: [
                    { data: 'full_name' },
                    { data: 'position',      defaultContent: '-' },
                    { data: 'email',         defaultContent: '-' },
                    { data: 'phone',         defaultContent: '-' },
                    { data: 'primary_badge', orderable: false },
                    { data: 'status_badge',  orderable: false },
                    { data: 'actions',       orderable: false, searchable: false },
                ],
            });
        }

        if (target === '#tabAddresses' && !addressesDT) {
            addressesDT = $('#addressesTable').DataTable({
                processing: true, serverSide: true,
                responsive: true,
                ajax: { url: URLS.addressesDT, dataSrc: 'data' },
                columns: [
                    { data: 'type_badge',    orderable: false },
                    { data: 'full_address',  orderable: false },
                    { data: 'country_name',  defaultContent: '-', orderable: false },
                    { data: 'postal_code',   defaultContent: '-' },
                    { data: 'default_badge', orderable: false },
                    { data: 'actions',       orderable: false, searchable: false },
                ],
            });
        }

        if (target === '#tabOpportunities' && !oppDT) {
            oppDT = $('#opportunitiesTable').DataTable({
                processing: true, serverSide: true,
                ajax: URLS.opportunities,
                columns: [
                    {data:'title'},{data:'value'},{data:'stage'},
                    {data:'probability'},{data:'close_date'},{data:'owner'},
                    {data:'action', orderable:false},
                ],
            });
        }

        if (target === '#tabInvoices' && !invDT) {
            invDT = $('#invoicesTable').DataTable({
                processing: true, serverSide: true,
                ajax: URLS.invoices,
                responsive: true,
                columns: [
                    {data:'invoice_number'},{data:'total_amount'},
                    {data:'payment_status'},{data:'due_date'},
                    {data:'action', orderable:false},
                ],
            });
        }

        if (target === '#tabInteractions' && !interDT) {
            interDT = $('#interactionsTable').DataTable({
                processing: true, serverSide: true,
                ajax: URLS.interactions,
                responsive: true,
                columns: [
                    {data:'subject'},{data:'interaction_type'},
                    {data:'outcome'},{data:'interaction_date'},
                    {data:'action', orderable:false},
                ],
            });
        }

        if (target === '#tabTickets' && !ticketDT) {
            ticketDT = $('#ticketsTable').DataTable({
                processing: true, serverSide: true,
                ajax: URLS.tickets,
                responsive: true,
                columns: [
                    {data:'ticket_no'},{data:'subject'},{data:'status'},
                    {data:'priority'},{data:'action', orderable:false},
                ],
            });
        }
    });

    // ── CONTACTS ───────────────────────────────────────────────────────────

    $('#btnAddContact').on('click', function () {
        $('#contactForm')[0].reset();
        $('#contactId').val('');
        $('#c_is_active').prop('checked', true);
        $('#contactModalLabel').text('Add Contact');
        contactModal.show();
    });

    $('#contactsTable').on('click', '.btn-edit-contact', function () {
        const r = $(this).data('record');
        $('#contactId').val(r.id);
        $('#c_first_name').val(r.first_name);
        $('#c_last_name').val(r.last_name);
        $('#c_position').val(r.position);
        $('#c_email').val(r.email);
        $('#c_phone').val(r.phone);
        $('#c_is_primary').prop('checked', !!r.is_primary);
        $('#c_is_active').prop('checked', !!r.is_active);
        $('#contactModalLabel').text('Edit Contact');
        contactModal.show();
    });

    $('#btnSaveContact').on('click', function () {
        const id   = $('#contactId').val();
        const url  = id ? URLS.contactUpdate(id) : URLS.contactsStore;
        const data = $('#contactForm').serialize() + (id ? '&_method=PUT' : '');
        ajaxSave(url, data, () => { contactModal.hide(); contactsDT?.ajax.reload(); });
    });

    $('#contactsTable').on('click', '.btn-delete-contact', function () {
        const id = $(this).data('id');
        Swal.fire({ title:'Delete contact?', icon:'warning',
                    showCancelButton:true, confirmButtonColor:'#e74a3b',
                    confirmButtonText:'Delete' })
            .then(r => {
                if (!r.isConfirmed) return;
                $.post(URLS.contactDelete(id), { _token:CSRF, _method:'DELETE' })
                    .done(() => contactsDT?.ajax.reload())
                    .fail(() => Swal.fire('Error','Could not delete contact.','error'));
            });
    });

    // ── ADDRESSES ──────────────────────────────────────────────────────────

    $('#btnAddAddress').on('click', function () {
        $('#addressForm')[0].reset();
        $('#addressId').val('');
        $('#addressModalLabel').text('Add Address');
        addressModal.show();
    });

    $('#a_country_id').on('change', function () {
        const cid = $(this).val();
        if (!cid) { $('#a_state_id').html('<option value="">-- Select --</option>'); return; }
        $.get(URLS.statesSearch, { country_id: cid })
            .done(function (states) {
                let opts = '<option value="">-- Select --</option>';
                states.forEach(s => opts += `<option value="${s.id}">${s.name}</option>`);
                $('#a_state_id').html(opts);
            });
    });

    $('#addressesTable').on('click', '.btn-edit-address', function () {
        const r = $(this).data('record');
        $('#addressId').val(r.id);
        $('#a_type').val(r.type);
        $('#a_line1').val(r.line1);
        $('#a_line2').val(r.line2);
        $('#a_country_id').val(r.country_id).trigger('change');
        setTimeout(() => $('#a_state_id').val(r.state_id), 400);
        $('#a_postal_code').val(r.postal_code);
        $('#a_is_default').prop('checked', !!r.is_default);
        $('#addressModalLabel').text('Edit Address');
        addressModal.show();
    });

    $('#btnSaveAddress').on('click', function () {
        const id   = $('#addressId').val();
        const url  = id ? URLS.addressUpdate(id) : URLS.addressesStore;
        const data = $('#addressForm').serialize() + (id ? '&_method=PUT' : '');
        ajaxSave(url, data, () => { addressModal.hide(); addressesDT?.ajax.reload(); });
    });

    $('#addressesTable').on('click', '.btn-delete-address', function () {
        const id = $(this).data('id');
        Swal.fire({ title:'Delete address?', icon:'warning',
                    showCancelButton:true, confirmButtonColor:'#e74a3b',
                    confirmButtonText:'Delete' })
            .then(r => {
                if (!r.isConfirmed) return;
                $.post(URLS.addressDelete(id), { _token:CSRF, _method:'DELETE' })
                    .done(() => addressesDT?.ajax.reload())
                    .fail(() => Swal.fire('Error','Could not delete address.','error'));
            });
    });
});
</script>
@endpush