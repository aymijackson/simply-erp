{{-- resources/views/inventory/suppliers/show.blade.php --}}
@extends('layouts.master')

@section('title', 'Supplier Details')

@push('styles')
<style>
    .select2-container { width: 100% !important; }
    .tab-pane .table td,
    .tab-pane .table th { vertical-align: middle; }
</style>
@endpush

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">{{ $supplier->name }}</h1>
            <p class="text-muted mb-0">Supplier Details • Manage Contacts, Addresses & Documents</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.suppliers.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    {{-- Summary cards --}}
    <div class="row mb-3">
        <div class="col-md-3 mb-2">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Status</div>
                    <div class="h5 mb-0 text-capitalize">{{ $supplier->status }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-2">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Currency</div>
                    <div class="h5 mb-0">{{ $supplier->default_currency ?: '-' }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-2">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Lead Time (days)</div>
                    <div class="h5 mb-0">{{ $supplier->lead_time_days ?: '-' }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-2">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Rating</div>
                    <div class="h5 mb-0">{{ is_null($supplier->rating) ? '-' : number_format((float) $supplier->rating, 1) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Tabs --}}
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <ul class="nav nav-tabs card-header-tabs" id="supplierDetailTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active"
                            id="overview-tab"
                            data-bs-toggle="tab"
                            data-bs-target="#overviewPane"
                            type="button"
                            role="tab">
                        <i class="fas fa-info-circle me-1"></i> Overview
                    </button>
                </li>

                <li class="nav-item" role="presentation">
                    <button class="nav-link"
                            id="documents-tab"
                            data-bs-toggle="tab"
                            data-bs-target="#documentsPane"
                            type="button"
                            role="tab">
                        <i class="fas fa-folder-open me-1"></i> Documents
                    </button>
                </li>

                <li class="nav-item" role="presentation">
                    <button class="nav-link"
                            id="contacts-tab"
                            data-bs-toggle="tab"
                            data-bs-target="#contactsPane"
                            type="button"
                            role="tab">
                        <i class="fas fa-user-friends me-1"></i> Contacts
                    </button>
                </li>

                <li class="nav-item" role="presentation">
                    <button class="nav-link"
                            id="addresses-tab"
                            data-bs-toggle="tab"
                            data-bs-target="#addressesPane"
                            type="button"
                            role="tab">
                        <i class="fas fa-map-marker-alt me-1"></i> Addresses
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body">
            <div class="tab-content">

                {{-- OVERVIEW --}}
                <div class="tab-pane fade show active"
                     id="overviewPane"
                     role="tabpanel"
                     aria-labelledby="overview-tab">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <table class="table table-bordered">
                                <tr>
                                    <th style="width: 35%;">Supplier Name</th>
                                    <td>{{ $supplier->name }}</td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td class="text-capitalize">{{ $supplier->status }}</td>
                                </tr>
                                <tr>
                                    <th>Default Currency</th>
                                    <td>{{ $supplier->default_currency ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Lead Time (Days)</th>
                                    <td>{{ $supplier->lead_time_days ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Rating</th>
                                    <td>{{ is_null($supplier->rating) ? '-' : number_format((float) $supplier->rating, 1) }}</td>
                                </tr>
                            </table>
                        </div>

                        <div class="col-md-6 mb-3">
                            <table class="table table-bordered">
                                <tr>
                                    <th style="width: 35%;">Created At</th>
                                    <td>{{ $supplier->created_at ? $supplier->created_at->format('d M Y H:i') : '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Updated At</th>
                                    <td>{{ $supplier->updated_at ? $supplier->updated_at->format('d M Y H:i') : '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Notes</th>
                                    <td>{{ $supplier->notes ?: '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- DOCUMENTS --}}
                <div class="tab-pane fade"
                     id="documentsPane"
                     role="tabpanel"
                     aria-labelledby="documents-tab">
                    @include('documents.partials.linked-documents-tab', [
                        'model' => $supplier
                    ])
                </div>

                {{-- CONTACTS --}}
                <div class="tab-pane fade"
                     id="contactsPane"
                     role="tabpanel"
                     aria-labelledby="contacts-tab">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Contacts</h5>
                        <button class="btn btn-sm btn-primary" id="addContactBtn">
                            <i class="fas fa-plus me-1"></i> Add Contact
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered w-100" id="contactsTable">
                            <thead class="bg-light">
                                <tr>
                                    <th style="width:22%;">Name</th>
                                    <th style="width:18%;">Role</th>
                                    <th style="width:20%;">Email</th>
                                    <th style="width:15%;">Phone</th>
                                    <th>Notes</th>
                                    <th style="width:10%;">Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

                {{-- ADDRESSES --}}
                <div class="tab-pane fade"
                     id="addressesPane"
                     role="tabpanel"
                     aria-labelledby="addresses-tab">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Addresses</h5>
                        <button class="btn btn-sm btn-primary" id="addAddressBtn">
                            <i class="fas fa-plus me-1"></i> Add Address
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered w-100" id="addressesTable">
                            <thead class="bg-light">
                                <tr>
                                    <th style="width:12%;">Type</th>
                                    <th style="width:28%;">Line 1</th>
                                    <th style="width:22%;">Line 2</th>
                                    <th style="width:12%;">Country</th>
                                    <th style="width:12%;">State</th>
                                    <th style="width:12%;">City</th>
                                    <th style="width:12%;">Postcode</th>
                                    <th style="width:10%;">Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>

{{-- Contact Modal --}}
<div class="modal fade" id="contactModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form id="contactForm" class="modal-content">
            @csrf
            <input type="hidden" id="contact_id">

            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="contactModalTitle">Add Contact</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="contact_supplier_id" value="{{ $supplier->id }}">

                <div class="mb-2">
                    <label class="form-label">Name</label>
                    <input type="text" class="form-control" id="contact_name" required>
                </div>

                <div class="mb-2">
                    <label class="form-label">Role</label>
                    <input type="text" class="form-control" id="contact_role">
                </div>

                <div class="mb-2">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" id="contact_email">
                </div>

                <div class="mb-2">
                    <label class="form-label">Phone</label>
                    <input type="text" class="form-control" id="contact_phone">
                </div>

                <div class="mb-2">
                    <label class="form-label">Notes</label>
                    <textarea class="form-control" id="contact_notes" rows="2"></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-success" type="submit">Save</button>
            </div>
        </form>
    </div>
</div>

{{-- Address Modal --}}
<div class="modal fade" id="addressModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="addressForm" class="modal-content">
            @csrf
            <input type="hidden" id="address_id">

            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addressModalTitle">Add Address</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="address_supplier_id" value="{{ $supplier->id }}">

                <div class="row">
                    <div class="col-md-4 mb-2">
                        <label class="form-label">Type</label>
                        <select class="form-control" id="address_type" required>
                            <option value="shipping">Shipping</option>
                            <option value="billing">Billing</option>
                            <option value="headquarters">Headquarters</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="col-md-4 mb-2">
                        <label class="form-label">Country</label>
                        <select class="form-control" id="address_country_id" required>
                            <option value=""></option>
                        </select>
                    </div>

                    <div class="col-md-4 mb-2">
                        <label class="form-label">State</label>
                        <select class="form-control" id="address_state_id">
                            <option value=""></option>
                        </select>
                    </div>

                    <div class="col-md-4 mb-2">
                        <label class="form-label">City</label>
                        <select class="form-control" id="address_city_id">
                            <option value=""></option>
                        </select>
                    </div>

                    <div class="col-md-6 mb-2">
                        <label class="form-label">Line 1</label>
                        <input class="form-control" id="address_line1" required>
                    </div>

                    <div class="col-md-6 mb-2">
                        <label class="form-label">Line 2</label>
                        <input class="form-control" id="address_line2">
                    </div>

                    <div class="col-md-4 mb-2">
                        <label class="form-label">Postal Code</label>
                        <input class="form-control" id="address_postal_code">
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-success" type="submit">Save</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    const supplierId = {{ (int) $supplier->id }};
    const csrf = $('meta[name="csrf-token"]').attr('content');

    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': csrf }
    });

    const contactModalEl = document.getElementById('contactModal');
    const addressModalEl = document.getElementById('addressModal');

    const contactModal = new bootstrap.Modal(contactModalEl);
    const addressModal = new bootstrap.Modal(addressModalEl);

    const contactsTable = $('#contactsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.suppliers.show.contacts.datatable', $supplier->id) }}",
        columns: [
            { data: 'name' },
            { data: 'role' },
            { data: 'email' },
            { data: 'phone' },
            { data: 'notes' },
            { data: 'action', orderable: false, searchable: false },
        ],
        language: { emptyTable: "No contacts found." }
    });

    const addressesTable = $('#addressesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.suppliers.show.addresses.datatable', $supplier->id) }}",
        columns: [
            { data: 'type' },
            { data: 'line1' },
            { data: 'line2' },
            { data: 'country_text' },
            { data: 'state_text' },
            { data: 'city_text' },
            { data: 'postal_code' },
            { data: 'action', orderable: false, searchable: false },
        ],
        language: { emptyTable: "No addresses found." }
    });

    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        $.fn.dataTable
            .tables({ visible: true, api: true })
            .columns.adjust();
    });

    $('#addContactBtn').on('click', function () {
        $('#contactForm')[0].reset();
        $('#contact_id').val('');
        $('#contactModalTitle').text('Add Contact');
        contactModal.show();
    });

    $(document).on('click', '.edit-contact', function () {
        $('#contact_id').val($(this).data('id'));
        $('#contact_name').val($(this).data('name'));
        $('#contact_role').val($(this).data('role'));
        $('#contact_email').val($(this).data('email'));
        $('#contact_phone').val($(this).data('phone'));
        $('#contact_notes').val($(this).data('notes'));
        $('#contactModalTitle').text('Edit Contact');
        contactModal.show();
    });

    $('#contactForm').on('submit', function (e) {
        e.preventDefault();

        const id = $('#contact_id').val();

        const payload = {
            supplier_id: supplierId,
            name: $('#contact_name').val(),
            role: $('#contact_role').val(),
            email: $('#contact_email').val(),
            phone: $('#contact_phone').val(),
            notes: $('#contact_notes').val(),
        };

        const url = id
            ? `{{ url('admin/suppliers') }}/${supplierId}/contacts/${id}`
            : `{{ route('admin.suppliers.show.contacts.store', $supplier->id) }}`;

        $.ajax({
            url: url,
            type: id ? 'PUT' : 'POST',
            data: payload,
            success: function (resp) {
                contactModal.hide();
                contactsTable.ajax.reload(null, false);
                Swal.fire('Success', resp.message ?? 'Saved', 'success');
            },
            error: function () {
                Swal.fire('Error', 'Failed to save contact.', 'error');
            }
        });
    });

    $(document).on('click', '.delete-contact', function () {
        const id = $(this).data('id');

        Swal.fire({
            title: 'Delete contact?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete'
        }).then(result => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: `{{ url('admin/suppliers') }}/${supplierId}/contacts/${id}`,
                type: 'DELETE',
                data: {},
                success: function (resp) {
                    contactsTable.ajax.reload(null, false);
                    Swal.fire('Deleted', resp.message ?? 'Deleted', 'success');
                },
                error: function () {
                    Swal.fire('Error', 'Failed to delete contact.', 'error');
                }
            });
        });
    });

    $('#addAddressBtn').on('click', function () {
        $('#addressForm')[0].reset();
        $('#address_id').val('');
        $('#address_country_id').val(null).trigger('change');
        $('#address_state_id').val(null).trigger('change');
        $('#address_city_id').val(null).trigger('change');
        $('#addressModalTitle').text('Add Address');
        addressModal.show();
    });

    $('#addressForm').on('submit', function (e) {
        e.preventDefault();

        const id = $('#address_id').val();

        const payload = {
            supplier_id: supplierId,
            type: $('#address_type').val(),
            line1: $('#address_line1').val(),
            line2: $('#address_line2').val(),
            country_id: $('#address_country_id').val(),
            state_id: $('#address_state_id').val(),
            city_id: $('#address_city_id').val(),
            postal_code: $('#address_postal_code').val(),
        };

        const url = id
            ? `{{ url('admin/suppliers') }}/${supplierId}/addresses/${id}`
            : `{{ route('admin.suppliers.show.addresses.store', $supplier->id) }}`;

        $.ajax({
            url: url,
            type: id ? 'PUT' : 'POST',
            data: payload,
            success: function (resp) {
                addressModal.hide();
                addressesTable.ajax.reload(null, false);
                Swal.fire('Success', resp.message ?? 'Saved', 'success');
            },
            error: function () {
                Swal.fire('Error', 'Failed to save address.', 'error');
            }
        });
    });

    $(document).on('click', '.delete-address', function () {
        const id = $(this).data('id');

        Swal.fire({
            title: 'Delete address?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete'
        }).then(result => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: `{{ url('admin/suppliers') }}/${supplierId}/addresses/${id}`,
                type: 'DELETE',
                data: {},
                success: function (resp) {
                    addressesTable.ajax.reload(null, false);
                    Swal.fire('Deleted', resp.message ?? 'Deleted', 'success');
                },
                error: function () {
                    Swal.fire('Error', 'Failed to delete address.', 'error');
                }
            });
        });
    });

    function initAddressSelect2() {
        const $modal = $('#addressModal');

        $('#address_country_id').select2({
            theme: 'bootstrap-5',
            dropdownParent: $modal,
            placeholder: 'Select country...',
            allowClear: true,
            ajax: {
                url: "{{ route('admin.suppliers.countries.select2') }}",
                dataType: 'json',
                delay: 250,
                data: params => ({ q: params.term || '' }),
                processResults: data => ({ results: data }),
            }
        });

        $('#address_state_id').select2({
            theme: 'bootstrap-5',
            dropdownParent: $modal,
            placeholder: 'Select state...',
            allowClear: true,
            ajax: {
                url: "{{ route('admin.suppliers.states.select2') }}",
                dataType: 'json',
                delay: 250,
                data: params => ({
                    q: params.term || '',
                    country_id: $('#address_country_id').val()
                }),
                processResults: data => ({ results: data }),
            }
        });

        $('#address_city_id').select2({
            theme: 'bootstrap-5',
            dropdownParent: $modal,
            placeholder: 'Select city...',
            allowClear: true,
            ajax: {
                url: "{{ route('admin.suppliers.cities.select2') }}",
                dataType: 'json',
                delay: 250,
                data: params => ({
                    q: params.term || '',
                    state_id: $('#address_state_id').val()
                }),
                processResults: data => ({ results: data }),
            }
        });

        $('#address_country_id').on('change', function () {
            $('#address_state_id').val(null).trigger('change');
            $('#address_city_id').val(null).trigger('change');
        });

        $('#address_state_id').on('change', function () {
            $('#address_city_id').val(null).trigger('change');
        });
    }

    function setSelect2Value($el, id, text) {
        if (!id) {
            $el.val(null).trigger('change');
            return;
        }

        const option = new Option(text || ('#' + id), id, true, true);
        $el.append(option).trigger('change');
    }

    initAddressSelect2();

    $(document).on('click', '.edit-address', function () {
        $('#address_id').val($(this).data('id'));
        $('#address_type').val($(this).data('type'));
        $('#address_line1').val($(this).data('line1'));
        $('#address_line2').val($(this).data('line2'));
        $('#address_postal_code').val($(this).data('postal_code'));

        setSelect2Value($('#address_country_id'), $(this).data('country_id'), $(this).data('country_text'));
        setSelect2Value($('#address_state_id'), $(this).data('state_id'), $(this).data('state_text'));
        setSelect2Value($('#address_city_id'), $(this).data('city_id'), $(this).data('city_text'));

        $('#addressModalTitle').text('Edit Address');
        addressModal.show();
    });
});
</script>
@endpush