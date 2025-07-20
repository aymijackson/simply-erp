@extends('layouts.master')

@section('title', 'Manage Subregions')

@section('content')
<div class="container-fluid">
        <h1 class="h3 mb-4 text-gray-800">Subregions</h1>
        <button class="btn btn-primary mb-3" id="createSubregion">Add Subregion</button>
        <button id="bulkDeleteSubregions" class="btn btn-danger mb-3">Delete Selected</button>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="subregionsTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>Name</th>
                            <th>Region</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    @include('subregions.modal')
</div>
@endsection

@push('scripts')
<script>
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    });
    $(function () {
        const table = $('#subregionsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route('admin.subregions.list') }}',
            columns: [
                { data: 'id', render: id => `<input type="checkbox" name="subregion_checkbox[]" value="${id}">`, orderable: false, searchable: false },
                { data: 'name' },
                { data: 'region' },
                { data: 'actions', orderable: false, searchable: false },
            ]
        });

        $('#createSubregion').on('click', function () {
            $('#subregionForm')[0].reset();
            $('#subregion_id').val('');
            $('#subregionModalLabel').text('Add Subregion');
            $('#subregionModal').modal('show');
        });


        $('#subregionForm').on('submit', function (e) {
            e.preventDefault();
            const id = $('#subregion_id').val();
            const url = id ? `/admin/subregions/${id}` : '{{ route('admin.subregions.store') }}';
            const type = id ? 'PUT' : 'POST';

            $.ajax({
                url: url,
                method: type,
                data: $(this).serialize(),
                success: function (response) {
                    $('#subregionModal').modal('hide');
                    table.ajax.reload();
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    $('#subregionForm')[0].reset();
                },
                error: function (xhr) {
                    Swal.fire('Error', xhr.responseJSON.message || 'An error occurred', 'error');
                }
            });
        });

        $('body').on('click', '.edit-subregion', function () {
            const id = $(this).data('id');
            $.get(`/admin/subregions/${id}/edit`, function (res) {
                const s = res.subregion;
                $('#subregion_id').val(s.id);
                $('#subregion_name').val(s.name);
                $('#region_id').val(s.region_id);
                $('#subregionModalLabel').text('Edit Subregion');
                $('#subregionModal').modal('show');
            });
        });

        $('body').on('click', '.delete-subregion', function () {
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
                        url: `/admin/subregions/${id}`,
                        method: 'DELETE',
                        success: function (res) {
                            table.ajax.reload();
                            Swal.fire('Deleted!', res.message, 'success');
                        },
                        error: function () {
                            Swal.fire('Error', 'Failed to delete subregion.', 'error');
                        }
                    });
                }
            });
        });

        $('#selectAll').on('click', function () {
            $('input[name="subregion_checkbox[]"]').prop('checked', this.checked);
        });

        $('#bulkDeleteSubregions').on('click', function () {
            const ids = $('input[name="subregion_checkbox[]"]:checked').map(function() { return this.value }).get();
            if (ids.length === 0) {
                return Swal.fire('Select at least one!', '', 'info');
            }
            Swal.fire({
                title: 'Are you sure?',
                text: 'This will delete selected subregions.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete',
            }).then(result => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '{{ route('admin.subregions.bulk-delete') }}',
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
