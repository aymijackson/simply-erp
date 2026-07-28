@extends('layouts.master')

@section('title', 'Manage Regions')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800">Regions</h1>

        <div>
            <button class="btn btn-primary" id="createRegion">
                <i class="fas fa-plus"></i> Add Region
            </button>

            <button id="bulkDeleteRegions" class="btn btn-danger">
                <i class="fas fa-trash"></i> Delete Selected
            </button>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="regionsTable" width="100%">
                    <thead class="thead-light">
                        <tr>
                            <th width="5%">
                                <input type="checkbox" id="selectAll">
                            </th>
                            <th>Name</th>
                            <th width="15%">Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    @include('regions.modal')

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

        // -----------------------------
        // DataTable
        // -----------------------------
        const table = $('#regionsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route('admin.regions.list') }}',
            pageLength: 10,
            order: [[1, 'asc']],
            columns: [
                {
                    data: 'id',
                    orderable: false,
                    searchable: false,
                    render: id => `
                        <input type="checkbox" class="region-checkbox" value="${id}">
                    `
                },
                { data: 'name' },
                { data: 'actions', orderable: false, searchable: false }
            ]
        });

        // -----------------------------
        // Create Region
        // -----------------------------
        $('#createRegion').on('click', () => {
            $('#regionForm')[0].reset();
            $('#region_id').val('');
            $('#regionModalLabel').text('Add Region');
            $('#regionModal').modal('show');
        });

        // -----------------------------
        // Save Region (Create/Update)
        // -----------------------------
        $('#regionForm').on('submit', function (e) {
            e.preventDefault();

            const id = $('#region_id').val();
            const url = id ? `/admin/regions/${id}` : '{{ route('admin.regions.store') }}';
            const method = id ? 'PUT' : 'POST';

            $.ajax({
                url,
                method,
                data: $(this).serialize(),
                success: res => {
                    $('#regionModal').modal('hide');
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

        // -----------------------------
        // Edit Region
        // -----------------------------
        $('body').on('click', '.edit-region', function () {
            const id = $(this).data('id');

            $.get(`/admin/regions/${id}/edit`, res => {
                const r = res.region;

                $('#region_id').val(r.id);
                $('#region_name').val(r.name);
                $('#regionModalLabel').text('Edit Region');
                $('#regionModal').modal('show');
            });
        });

        // -----------------------------
        // Delete Single Region
        // -----------------------------
        $('body').on('click', '.delete-region', function () {
            const id = $(this).data('id');

            Swal.fire({
                title: 'Delete Region?',
                text: 'This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Delete'
            }).then(result => {
                if (!result.isConfirmed) return;

                $.ajax({
                    url: `/admin/regions/${id}`,
                    method: 'DELETE',
                    success: res => {
                        table.ajax.reload(null, false);
                        Toast.fire({ icon: 'success', title: res.message });
                    },
                    error: () => {
                        Toast.fire({ icon: 'error', title: 'Failed to delete region' });
                    }
                });
            });
        });

        // -----------------------------
        // Select All Checkbox
        // -----------------------------
        $('#selectAll').on('change', function () {
            $('.region-checkbox').prop('checked', this.checked);
        });

        // -----------------------------
        // Bulk Delete
        // -----------------------------
        $('#bulkDeleteRegions').on('click', function () {
            const ids = $('.region-checkbox:checked').map(function () {
                return this.value;
            }).get();

            if (ids.length === 0) {
                return Toast.fire({ icon: 'info', title: 'Select at least one region' });
            }

            Swal.fire({
                title: 'Delete Selected Regions?',
                text: 'This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Delete'
            }).then(result => {
                if (!result.isConfirmed) return;

                $.ajax({
                    url: '{{ route('admin.regions.bulk-delete') }}',
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