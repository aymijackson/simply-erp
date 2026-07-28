@extends('layouts.master')

@section('title', 'Balance Sheet')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 text-primary mb-0">Balance Sheet</h1>
    </div>

    {{-- Date Filters --}}
    <div class="card shadow mb-3">
        <div class="card-body">
            <form class="row g-2 align-items-end" method="GET" action="">
                <div class="col-md-3">
                    <label class="form-label mb-1">From</label>
                    <input type="date" name="from" class="form-control" value="{{ $from ?? '' }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-1">To</label>
                    <input type="date" name="to" class="form-control" value="{{ $to ?? '' }}">
                </div>

                <div class="col-md-3">
                    <button class="btn btn-primary">
                        <i class="fas fa-filter me-1"></i> Apply
                    </button>

                    <a class="btn btn-outline-secondary ms-2"
                       href="{{ url()->current() }}">
                        Reset
                    </a>
                </div>

                <div class="col-md-3 text-md-end text-muted small">
                    <div>
                        Period:
                        <strong>{{ $from ?? '—' }}</strong>
                        to
                        <strong>{{ $to ?? '—' }}</strong>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Report --}}
    <div class="card shadow">
        <div class="card-body table-responsive">

            @php
                $assets = 0.0;
                $liabilities = 0.0;
                $equity = 0.0;
                $profitVal = (float)($profit ?? 0);

                // Optional: group rows by category for nicer layout
                $assetRows = [];
                $liabilityRows = [];
                $equityRows = [];

                foreach ($rows as $r) {
                    if ($r->category === 'asset') $assetRows[] = $r;
                    elseif ($r->category === 'liability') $liabilityRows[] = $r;
                    elseif ($r->category === 'equity') $equityRows[] = $r;
                }
            @endphp

            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width:160px;">Code</th>
                        <th>Account</th>
                        <th style="width:220px;" class="text-end">Balance</th>
                    </tr>
                </thead>

                <tbody>
                    {{-- ASSETS --}}
                    <tr class="table-secondary fw-bold">
                        <td colspan="3">ASSETS</td>
                    </tr>
                    @forelse($assetRows as $row)
                        @php $assets += (float)$row->balance; @endphp
                        <tr>
                            <td>{{ $row->code }}</td>
                            <td>{{ $row->name }}</td>
                            <td class="text-end">{{ number_format((float)$row->balance, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-muted">No asset accounts found.</td>
                        </tr>
                    @endforelse
                    <tr class="fw-bold">
                        <td colspan="2">Total Assets</td>
                        <td class="text-end">{{ number_format($assets, 2) }}</td>
                    </tr>

                    {{-- LIABILITIES --}}
                    <tr class="table-secondary fw-bold">
                        <td colspan="3">LIABILITIES</td>
                    </tr>
                    @forelse($liabilityRows as $row)
                        @php $liabilities += (float)$row->balance; @endphp
                        <tr>
                            <td>{{ $row->code }}</td>
                            <td>{{ $row->name }}</td>
                            <td class="text-end">{{ number_format((float)$row->balance, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-muted">No liability accounts found.</td>
                        </tr>
                    @endforelse
                    <tr class="fw-bold">
                        <td colspan="2">Total Liabilities</td>
                        <td class="text-end">{{ number_format($liabilities, 2) }}</td>
                    </tr>

                    {{-- EQUITY --}}
                    <tr class="table-secondary fw-bold">
                        <td colspan="3">EQUITY</td>
                    </tr>
                    @forelse($equityRows as $row)
                        @php $equity += (float)$row->balance; @endphp
                        <tr>
                            <td>{{ $row->code }}</td>
                            <td>{{ $row->name }}</td>
                            <td class="text-end">{{ number_format((float)$row->balance, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-muted">No equity accounts found.</td>
                        </tr>
                    @endforelse

                    {{-- Current Period Profit --}}
                    <tr class="fw-bold">
                        <td colspan="2">Current Period Profit</td>
                        <td class="text-end">{{ number_format($profitVal, 2) }}</td>
                    </tr>

                    @php
                        $equityWithProfit = $equity + $profitVal;
                    @endphp

                    <tr class="fw-bold">
                        <td colspan="2">Total Equity (incl. Profit)</td>
                        <td class="text-end">{{ number_format($equityWithProfit, 2) }}</td>
                    </tr>

                    {{-- CHECK --}}
                    @php
                        $check = $assets - ($liabilities + $equityWithProfit);
                    @endphp
                    <tr class="fw-bold bg-light">
                        <td colspan="2">Check (Assets = Liabilities + Equity)</td>
                        <td class="text-end">{{ number_format($check, 2) }}</td>
                    </tr>
                </tbody>
            </table>

            <div class="small text-muted mt-2">
                Note: Check should be 0.00 when the books are balanced for the selected period.
            </div>

        </div>
    </div>

</div>
@endsection
