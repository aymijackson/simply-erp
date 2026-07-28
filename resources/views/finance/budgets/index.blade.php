{{-- File: Modules/Finance/Resources/views/finance/budgets/index.blade.php --}}
@extends('layouts.master')

@section('content')
<div class="container-fluid">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Budgets</h4>

    @can('finance.budgets.create')
      <a href="{{ route('admin.finance.budgets.create') }}" class="btn btn-primary">
        New Budget
      </a>
    @endcan
  </div>

  <div class="card">
    <div class="card-body p-0">

      <div class="table-responsive">
        <table class="table table-sm table-striped align-middle mb-0" id="budgetsTable" style="width:100%">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Range</th>
              <th>Period</th>
              <th>Status</th>
              <th style="width:180px" class="text-center">Actions</th>
            </tr>
          </thead>
        </table>
      </div>

    </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
function formatDate(d) {
    if (!d) return '';
    const date = new Date(d);
    const dd = String(date.getDate()).padStart(2,'0');
    const mm = String(date.getMonth()+1).padStart(2,'0');
    const yyyy = date.getFullYear();
    return `${dd}-${mm}-${yyyy}`;
}

function statusBadge(status) {
    if (status === 'active') return '<span class="badge bg-success">Active</span>';
    if (status === 'archived') return '<span class="badge bg-secondary">Archived</span>';
    return '<span class="badge bg-warning text-dark">Draft</span>';
}

$(document).ready(function () {
    $('#budgetsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: @json(route('admin.finance.budgets.dt')),
            type: 'GET'
        },
        columns: [
            { data: 'id', name: 'id' },

            { data: 'name', name: 'name' },

            { 
                data: null,
                name: 'range',
                render: function(row){
                    return `${formatDate(row.start_date)} → ${formatDate(row.end_date)}`;
                }
            },

            { data: 'period_type', name: 'period_type' },

            { 
                data: 'status',
                name: 'status',
                render: function(status){
                    return statusBadge(status);
                }
            },

            { 
                data: 'id',
                orderable: false,
                searchable: false,
                className: 'text-center',
                render: function(id){
                    return `
                        <a href="/admin/finance/budgets/${id}/edit" class="btn btn-sm btn-outline-primary me-1">Edit</a>
                        <a href="/finance/budget-vs-actual/${id}" class="btn btn-sm btn-outline-secondary">Report</a>
                    `;
                }
            }
        ],
        order: [[0, 'desc']],
        responsive: true,
        pageLength: 25
    });
});
</script>
@endpush