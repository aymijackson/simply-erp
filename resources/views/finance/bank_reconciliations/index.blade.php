@extends('layouts.master')

@section('content')
<div class="container-fluid">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Bank Reconciliations</h4>

    @can('finance.bank_reconciliation.create')
      <a href="{{ route('admin.finance.bank_reconciliations.create') }}" class="btn btn-primary">
        New Reconciliation
      </a>
    @endcan
  </div>

  <div class="card">
    <div class="card-body p-0">

      <div class="table-responsive">
        <table class="table table-sm table-striped align-middle mb-0" id="reconTable" style="width:100%">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Bank Account</th>
              <th>Period</th>
              <th>Status</th>
              <th>Statement Closing</th>
              <th>System Closing</th>
              <th>Actions</th>
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

$(document).ready(function () {
    $('#reconTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: @json(route('admin.finance.bank_reconciliations.dt')),
            type: 'GET'
        },
        columns: [
            { data: 'id', name: 'id' },

            { 
                data: 'bank_account.name',
                name: 'bank_account.name',
                defaultContent: '—'
            },

            { 
                data: null,
                name: 'period',
                render: function(row){
                    return `${formatDate(row.period_start)} → ${formatDate(row.period_end)}`;
                }
            },

            { 
                data: 'status',
                name: 'status',
                render: function(status){
                    if(status === 'closed') return '<span class="badge bg-success">Closed</span>';
                    if(status === 'in_progress') return '<span class="badge bg-warning text-dark">In Progress</span>';
                    return '<span class="badge bg-secondary">Unknown</span>';
                }
            },

            { 
                data: 'statement_closing_balance',
                name: 'statement_closing_balance',
                className: 'text-end',
                render: d => Number(d).toFixed(2)
            },

            { 
                data: 'system_closing_balance',
                name: 'system_closing_balance',
                className: 'text-end',
                render: d => Number(d).toFixed(2)
            },

            { 
                data: 'id',
                orderable: false,
                searchable: false,
                className: 'text-center',
                render: function(id){
                    return `<a href="/admin/finance/bank-reconciliations/${id}" class="btn btn-sm btn-outline-primary">Open</a>`;
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