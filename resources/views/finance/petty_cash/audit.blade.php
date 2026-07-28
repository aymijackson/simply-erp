@extends('layouts.master')

@section('title', 'Petty Cash Audit Trail')

@section('content')
<div class="container-fluid">
    <h1 class="h3 mb-3">Petty Cash Audit Trail</h1>

    <div class="card shadow">
        <div class="card-body table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Action</th>
                        <th>Description</th>
                        <th>Account ID</th>
                        <th>Transaction ID</th>
                        <th>Reconciliation ID</th>
                        <th>User ID</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                    <tr>
                        <td>{{ optional($log->created_at)->format('Y-m-d H:i:s') }}</td>
                        <td>{{ $log->action }}</td>
                        <td>{{ $log->description }}</td>
                        <td>{{ $log->petty_cash_account_id }}</td>
                        <td>{{ $log->petty_cash_transaction_id }}</td>
                        <td>{{ $log->reconciliation_id }}</td>
                        <td>{{ $log->performed_by }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $logs->links() }}
        </div>
    </div>
</div>
@endsection