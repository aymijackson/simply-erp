@extends('layouts.master')

@section('title', 'Manage States')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">States</h1>
    <button id="addState" class="btn btn-primary mb-3">Add State</button>
    <button id="bulkDeleteStates" class="btn btn-danger mb-3">Delete Selected</button>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="statesTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>Name</th>
                            <th>Country</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <!-- State Modal -->
    @include('states.modal')
@endsection

@push('scripts')
<script>
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    });
    $(function () {
        const table = $('#statesTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route("admin.states.list") }}',
            columns: [
                { data: 'id', render: id => `<input type="checkbox" name="state_checkbox[]" value="${id}">`, orderable: false, searchable: false },
                { data: 'name' },
                { data: 'country', defaultContent: '' },
                { data: 'actions', orderable: false, searchable: false },
            ]
        });

        $('#addState').on('click', function () {
            $('#stateForm')[0].reset();
            $('#state_id').val('');
            $('#stateModalLabel').text('Add State');
            $('#stateModal').modal('show');
        });

        $('#stateForm').on('submit', function (e) {
            e.preventDefault();
            const id = $('#state_id').val();
            const url = id ? `/admin/states/${id}` : '{{ route("admin.states.store") }}';
            const type = id ? 'PUT' : 'POST';

            $.ajax({
                url: url,
                method: type,
                data: $(this).serialize(),
                success: function (response) {
                    $('#stateModal').modal('hide');
                    table.ajax.reload();
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    $('#stateForm')[0].reset();
                },
                error: function (xhr) {
                    Swal.fire('Error', xhr.responseJSON.message || 'An error occurred', 'error');
                }
            });
        });

        $('body').on('click', '.edit-state', function () {
            const id = $(this).data('id');
            $.get(`/admin/states/${id}/edit`, function (res) {
                const s = res.state;
                $('#state_id').val(s.id);
                $('#state_name').val(s.name);
                $('#country_id').val(s.country_id);
                $('#stateModalLabel').text('Edit State');
                $('#stateModal').modal('show');
            });
        });

        $('body').on('click', '.delete-state', function () {
            const id = $(this).data('id');
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/admin/states/${id}`,
                        method: 'DELETE',
                        success: function (res) {
                            table.ajax.reload();
                            Swal.fire('Deleted!', res.message, 'success');
                        },
                        error: function () {
                            Swal.fire('Error', 'Failed to delete state.', 'error');
                        }
                    });
                }
            });
        });

        $('#selectAll').on('click', function () {
            $('input[name="state_checkbox[]"]').prop('checked', this.checked);
        });

        $('#bulkDeleteStates').on('click', function () {
            const ids = $('input[name="state_checkbox[]"]:checked').map(function() { return this.value }).get();
            if (ids.length === 0) {
                return Swal.fire('Select at least one!', '', 'info');
            }
            Swal.fire({
                title: 'Are you sure?',
                text: 'This will delete selected states.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete',
            }).then(result => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '{{ route("admin.states.bulk-delete") }}',
                        method: 'DELETE',
                        data: { ids, _token: '{{ csrf_token() }}' },
                        success: res => { table.ajax.reload(); Swal.fire('Deleted', res.message, 'success'); },
                        error: () => Swal.fire('Error', 'Failed to delete.', 'error')
                    });
                }
            });
        });
    });
</script>
@endpush
