@extends('layouts.master')

@section('title', 'Petty Cash Transaction')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between mb-3">
        <h1 class="h3">Petty Cash Transaction Details</h1>
        @can('finance.petty_cash.print')
        <a href="{{ route('admin.finance.petty_cash.voucher', $row->id) }}" target="_blank" class="btn btn-secondary">
            <i class="fas fa-print me-1"></i> Print Voucher
        </a>
        @endcan
    </div>

    <div class="card shadow">
        <div class="card-body">
            <table class="table table-bordered">
                <tr><th>Transaction No</th><td>{{ $row->transaction_no }}</td></tr>
                <tr><th>Voucher No</th><td>{{ $row->voucher_no }}</td></tr>
                <tr><th>Date</th><td>{{ optional($row->transaction_date)->format('Y-m-d') }}</td></tr>
                <tr><th>Account</th><td>{{ $row->account->name ?? '-' }}</td></tr>
                <tr><th>Type</th><td>{{ ucfirst($row->type) }}</td></tr>
                <tr><th>Payee Type</th><td>{{ ucfirst($row->payee_type ?? 'other') }}</td></tr>
                <tr><th>Payee</th><td>{{ $row->payee_display ?? '-' }}</td></tr>
                <tr><th>Reference No</th><td>{{ $row->reference_no ?? '-' }}</td></tr>
                <tr><th>Description</th><td>{{ $row->description ?? '-' }}</td></tr>
                <tr><th>Amount</th><td>{{ number_format($row->amount, 2) }}</td></tr>
                <tr><th>Status</th><td>{{ ucfirst($row->status) }}</td></tr>
                <tr><th>Workflow Status</th><td>{{ $row->workflow_status ?? '-' }}</td></tr>
                <tr><th>Expense Account</th><td>{{ $row->expenseAccount->name ?? '-' }}</td></tr>
                <tr><th>Journal Entry ID</th><td>{{ $row->finance_journal_entry_id ?? '-' }}</td></tr>
                <tr><th>Submitted By</th><td>{{ optional($row->submittedBy)->name ?? '-' }}</td></tr>
                <tr><th>Submitted At</th><td>{{ optional($row->submitted_at)->format('Y-m-d H:i') ?? '-' }}</td></tr>
                <tr><th>Approved By</th><td>{{ optional($row->approvedBy)->name ?? '-' }}</td></tr>
                <tr><th>Approved At</th><td>{{ optional($row->approved_at)->format('Y-m-d H:i') ?? '-' }}</td></tr>
                <tr><th>Posted By</th><td>{{ optional($row->postedBy)->name ?? '-' }}</td></tr>
                <tr><th>Posted At</th><td>{{ optional($row->posted_at)->format('Y-m-d H:i') ?? '-' }}</td></tr>
                <tr><th>Approval Notes</th><td>{{ $row->approval_notes ?? '-' }}</td></tr>
                <tr>
                    <th>Documents</th>
                    <td>
                        @php
                            $links = $row->documentLinks ?? collect();
                        @endphp
                
                        @forelse($links as $link)
                            @if($link->document)
                                <div class="mb-1">
                                    <a href="{{ route('admin.documents.show', $link->document->id) }}" target="_blank">
                                        {{ $link->document->title ?: $link->document->original_file_name }}
                                    </a>
                                </div>
                            @endif
                        @empty
                            -
                        @endforelse
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>
@endsection