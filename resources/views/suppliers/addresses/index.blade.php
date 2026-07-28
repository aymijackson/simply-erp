@extends('layouts.master')

@section('title', 'All Supplier Addresses')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container">
    <h3 class="mb-4">All Supplier Addresses</h3>

    <button class="btn btn-primary mb-3" id="addAddressBtn">Add New Address</button>
    <button class="btn btn-danger mb-3" id="bulkDeleteBtn" disabled>Delete Selected</button>

    <table id="supplierAddressTable" class="table table-bordered">
        <thead>
            <tr>
                <th><input type="checkbox" id="selectAll"></th>
                <th>#</th>
                <th>Supplier Name</th>
                <th>Address Line</th>
                <th>City</th>
                <th>State</th>
                <th>Country</th>
                <th>Postal Code</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
        </thead>
    </table>
</div>

<!-- Modal -->
<div class="modal fade" id="addressModal" tabindex="-1" role="dialog" aria-labelledby="addressModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="addressForm">
            @csrf
            <input type="hidden" name="id" id="addressId">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add / Edit Supplier Address</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="supplier_id">Supplier</label>
                        <select name="supplier_id" id="supplier_id" class="form-control" required>
                            <option value=""></option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="type">Address Type</label>
                        <select name="type" id="type" class="form-control" required>
                            <option value="shipping">Shipping</option>
                            <option value="billing">Billing</option>
                            <option value="headquarters">Headquarters</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Address Line 1</label>
                        <input type="text" name="line1" id="line1" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Address Line 2</label>
                        <input type="text" name="line2" id="line2" class="form-control">
                    </div>

                    <div class="form-group">
                        <label for="country_id">Country</label>
                        <select name="country_id" id="country_id" class="form-control" required>
                            <option value="">-- Select Country --</option>
                            @foreach($countries as $country)
                                <option value="{{ $country->id }}">{{ $country->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="state_id">State</label>
                        <select name="state_id" id="state_id" class="form-control" required>
                            <option value="">-- Select State --</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="city_id">City</label>
                        <select name="city_id" id="city_id" class="form-control" required>
                            <option value="">-- Select City --</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Postal Code</label>
                        <input type="text" name="postal_code" id="postal_code" class="form-control">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Save</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                </div>

            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(function () {
    const csrf = $('meta[name="csrf-token"]').attr('content');

    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': csrf }
    });

    const table = $('#supplierAddressTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("admin.suppliers.addresses.datatable") }}',
        columns: [
            { data: 'checkbox', name: 'checkbox', orderable: false, searchable: false },
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'supplier.name', name: 'supplier.name' },
            { data: 'full_address', name: 'full_address' },
            { data: 'city_text', name: 'city.name' },
            { data: 'state_text', name: 'state.name' },
            { data: 'country_text', name: 'country.name' },
            { data: 'postal_code', name: 'postal_code' },
            { data: 'created_at', name: 'supplier_addresses.created_at' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
    });

    // Dependent dropdowns
    $('#country_id').change(function () {
        let countryId = $(this).val();
        $('#state_id').html('<option value="">Loading...</option>');
        $('#city_id').html('<option value="">-- Select City --</option>');

        if (countryId) {
            $.get(`/states/by-country/${countryId}`, function (data) {
                let options = '<option value="">-- Select State --</option>';
                data.forEach(state => {
                    options += `<option value="${state.id}">${state.name}</option>`;
                });
                $('#state_id').html(options);
            });
        } else {
            $('#state_id').html('<option value="">-- Select State --</option>');
        }
    });

    $('#state_id').change(function () {
        let stateId = $(this).val();
        $('#city_id').html('<option value="">Loading...</option>');

        if (stateId) {
            $.get(`/cities/by-state/${stateId}`, function (data) {
                let options = '<option value="">-- Select City --</option>';
                data.forEach(city => {
                    options += `<option value="${city.id}">${city.name}</option>`;
                });
                $('#city_id').html(options);
            });
        } else {
            $('#city_id').html('<option value="">-- Select City --</option>');
        }
    });

    // Bulk select toggle
    $(document).on('change', '#selectAll', function () {
        $('.row-checkbox').prop('checked', $(this).prop('checked'));
        toggleBulkDelete();
    });

    $(document).on('change', '.row-checkbox', function () {
        toggleBulkDelete();
    });

    function toggleBulkDelete() {
        $('#bulkDeleteBtn').prop('disabled', $('.row-checkbox:checked').length === 0);
    }

    // ✅ Bulk delete with Swal
    $('#bulkDeleteBtn').click(function () {
        const ids = $('.row-checkbox:checked').map(function () {
            return $(this).val();
        }).get();

        if (!ids.length) return;

        Swal.fire({
            title: 'Delete selected addresses?',
            text: `You are about to delete ${ids.length} address(es). This cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: '{{ route("admin.suppliers.addresses.bulk-delete") }}',
                method: 'POST',
                data: { ids },
                success: function (resp) {
                    $('#selectAll').prop('checked', false);
                    $('#bulkDeleteBtn').prop('disabled', true);
                    table.ajax.reload(null, false);
                    Swal.fire('Deleted!', resp.message ?? 'Selected addresses deleted.', 'success');
                },
                error: function (xhr) {
                    Swal.fire('Error', xhr.responseJSON?.message ?? 'Bulk delete failed.', 'error');
                }
            });
        });
    });

    // Add
    $('#addAddressBtn').click(function () {
        $('#addressForm')[0].reset();
        $('#addressId').val('');
        $('#state_id').html('<option value="">-- Select State --</option>');
        $('#city_id').html('<option value="">-- Select City --</option>');
        $('#addressModal').modal('show');
    });

    // Save
    $('#addressForm').submit(function (e) {
        e.preventDefault();

        let formData = $(this).serialize();
        let id = $('#addressId').val();

        let url = id
            ? `/admin/suppliers/addresses/${id}`
            : '{{ route("admin.suppliers.addresses.store") }}';

        let method = id ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            type: method,
            data: formData,
            success: function (response) {
                $('#addressModal').modal('hide');
                table.ajax.reload(null, false);
                Swal.fire('Success', response.message ?? 'Address saved successfully.', 'success');
            },
            error: function (xhr) {
                const msg = xhr.responseJSON?.message ?? 'Failed to save address.';
                Swal.fire('Error', msg, 'error');
            }
        });
    });

    // Edit
    $(document).on('click', '.edit-btn', function () {
        let data = $(this).data();

        $('#addressId').val(data.id);
        $('#supplier_id').val(data.supplier_id);
        $('#type').val(data.type);
        $('#line1').val(data.line1);
        $('#line2').val(data.line2);
        $('#postal_code').val(data.postal_code);
        
        setSelect2Value(
            $('#supplier_id'),
            data.supplier_id,
            data.supplier_name   
        );
        
        $('#country_id').val(data.country_id).trigger('change');

        // Load state and city then set selected
        $.get(`/states/by-country/${data.country_id}`, function (states) {
            let stateOptions = '<option value="">-- Select State --</option>';
            states.forEach(s => {
                stateOptions += `<option value="${s.id}" ${s.id == data.state_id ? 'selected' : ''}>${s.name}</option>`;
            });
            $('#state_id').html(stateOptions);

            $.get(`/cities/by-state/${data.state_id}`, function (cities) {
                let cityOptions = '<option value="">-- Select City --</option>';
                cities.forEach(c => {
                    cityOptions += `<option value="${c.id}" ${c.id == data.city_id ? 'selected' : ''}>${c.name}</option>`;
                });
                $('#city_id').html(cityOptions);
            });
        });

        $('#addressModal').modal('show');
    });

    // ✅ Delete with Swal
    $(document).on('click', '.delete-btn', function () {
        const id = $(this).data('id');

        Swal.fire({
            title: 'Delete this address?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: `/admin/suppliers/addresses/${id}`,
                type: 'DELETE',
                data: {},
                success: function (resp) {
                    table.ajax.reload(null, false);
                    Swal.fire('Deleted!', resp.message ?? 'Address deleted.', 'success');
                },
                error: function (xhr) {
                    Swal.fire('Error', xhr.responseJSON?.message ?? 'Failed to delete address.', 'error');
                }
            });
        });
    });

    // Supplier Select2
    $('#supplier_id').select2({
        theme: 'bootstrap-5',
        dropdownParent: $('#addressModal'),
        placeholder: 'Select supplier...',
        allowClear: true,
        ajax: {
            url: "{{ route('admin.suppliers.select2') }}",
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term || ''
                };
            },
            processResults: function (data) {
                return { results: data };
            }
        }
    });
    
    function setSelect2Value($el, id, text) {
        if (!id) {
            $el.val(null).trigger('change');
            return;
        }
        const opt = new Option(text, id, true, true);
        $el.append(opt).trigger('change');
    }
});
</script>
@endpush
