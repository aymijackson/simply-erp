@extends('layouts.master')

@section('title', 'Trial Balance')

@section('content')
<div class="container-fluid">

    <h1 class="h3 mb-4 text-primary">Trial Balance</h1>

    <div class="card shadow">
        <div class="card-body table-responsive">

            <table class="table table-bordered table-striped">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Account</th>
                        <th>Debit</th>
                        <th>Credit</th>
                        <th>Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalDebit = 0;
                        $totalCredit = 0;
                    @endphp

                    @foreach($rows as $row)
                        @php
                            $totalDebit += $row->total_debit;
                            $totalCredit += $row->total_credit;
                        @endphp
                        <tr>
                            <td>{{ $row->code }}</td>
                            <td>{{ $row->name }}</td>
                            <td>{{ number_format($row->total_debit,2) }}</td>
                            <td>{{ number_format($row->total_credit,2) }}</td>
                            <td>{{ number_format($row->balance,2) }}</td>
                        </tr>
                    @endforeach

                    <tr class="fw-bold">
                        <td colspan="2">TOTAL</td>
                        <td>{{ number_format($totalDebit,2) }}</td>
                        <td>{{ number_format($totalCredit,2) }}</td>
                        <td></td>
                    </tr>

                </tbody>
            </table>

        </div>
    </div>

</div>
@endsection
