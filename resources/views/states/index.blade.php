@extends('layouts.master')

@section('title', 'Manage States')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800">States</h1>

        <div>
            <button id="addState" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add State
            </button>

            <button id="bulkDeleteStates" class="btn btn-danger">
                <i class="fas fa-trash"></i> Delete Selected
            </button>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="statesTable" width="100%">
                    <thead class="thead-light">
                        <tr>
                            <th width="5%">
                                <input type="checkbox" id="selectAll">
                            </th>
                            <th>Name</th>
                            <th>Country</th>
                            <th width="15%">Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    @include('states.modal')

</div>
@endsection
@push('scripts')
<script>
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        timer: 2000,
        showConfirmButton: false,
        timerProgressBar: true
    });

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    });

    $(function () {

        // ------------------------------------
        // DataTable
        // ------------------------------------
        const table = $('#statesTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route("admin.states.list") }}',
            pageLength: 10,
            order: [[1, 'asc']],
            columns: [
                {
                    data: 'id',
                    orderable: false,
                    searchable: false,
                    render: id => `<input type="checkbox" class="state-checkbox" value="${id}">`
                },
                { data: 'name' },
                { data: 'country', defaultContent: '' },
                { data: 'actions', orderable: false, searchable: false }
            ]
        });

        // ------------------------------------
        // Add State
        // ------------------------------------
        $('#addState').on('click', () => {
            $('#stateForm')[0].reset();
            $('#state_id').val('');
            $('#stateModalLabel').text('Add State');
            $('#stateModal').modal('show');
        });

        // ------------------------------------
        // Save State (Create/Update)
        // ------------------------------------
        $('#stateForm').on('submit', function (e) {
            e.preventDefault();

            const id = $('#state_id').val();
            const url = id ? `/admin/states/${id}` : '{{ route("admin.states.store") }}';
            const method = id ? 'PUT' : 'POST';

            $.ajax({
                url,
                method,
                data: $(this).serialize(),
                success: res => {
                    $('#stateModal').modal('hide');
                    table.ajax.reload(null, false);

                    Toast.fire({
                        icon: 'success',
                        title: res.message
                    });
                },
                error: xhr => {
                    Toast.fire({
                        icon: 'error',
                        title: xhr.responseJSON?.message || 'An error occurred'
                    });
                }
            });
        });

        // ------------------------------------
        // Edit State
        // ------------------------------------
        $('body').on('click', '.edit-state', function () {
            const id = $(this).data('id');

            $.get(`/admin/states/${id}/edit`, res => {
                const s = res.state;

                $('#state_id').val(s.id);
                $('#state_name').val(s.name);
                $('#country_id').val(s.country_id);

                $('#stateModalLabel').text('Edit State');
                $('#stateModal').modal('show');
            });
        });

        // ------------------------------------
        // Delete Single State
        // ------------------------------------
        $('body').on('click', '.delete-state', function () {
            const id = $(this).data('id');

            Swal.fire({
                title: 'Delete State?',
                text: 'This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Delete'
            }).then(result => {
                if (!result.isConfirmed) return;

                $.ajax({
                    url: `/admin/states/${id}`,
                    method: 'DELETE',
                    success: res => {
                        table.ajax.reload(null, false);
                        Toast.fire({ icon: 'success', title: res.message });
                    },
                    error: () => {
                        Toast.fire({ icon: 'error', title: 'Failed to delete state' });
                    }
                });
            });
        });

        // ------------------------------------
        // Select All Checkbox
        // ------------------------------------
        $('#selectAll').on('change', function () {
            $('.state-checkbox').prop('checked', this.checked);
        });

        // ------------------------------------
        // Bulk Delete
        // ------------------------------------
        $('#bulkDeleteStates').on('click', function () {
            const ids = $('.state-checkbox:checked').map(function () {
                return this.value;
            }).get();

            if (ids.length === 0) {
                return Toast.fire({ icon: 'info', title: 'Select at least one state' });
            }

            Swal.fire({
                title: 'Delete Selected States?',
                text: 'This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Delete'
            }).then(result => {
                if (!result.isConfirmed) return;

                $.ajax({
                    url: '{{ route("admin.states.bulk-delete") }}',
                    method: 'DELETE',
                    data: { ids },
                    success: res => {
                        table.ajax.reload(null, false);
                        Toast.fire({ icon: 'success', title: res.message });
                    },
                    error: () => {
                        Toast.fire({ icon: 'error', title: 'Bulk delete failed' });
                    }
                });
            });
        });

    });
</script>
@endpush