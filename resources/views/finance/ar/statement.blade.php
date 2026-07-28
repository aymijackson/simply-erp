@extends('layouts.master')

@section('title', 'Customer Statement')

@section('content')
<div class="container-fluid">

    <h1 class="h3 mb-4 text-primary">Customer Statement</h1>

    <div class="card shadow">
        <div class="card-body table-responsive">

            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Reference</th>
                        <th>Debit</th>
                        <th>Credit</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transactions as $t)
                        <tr>
                            <td>{{ $t->txn_date }}</td>
                            <td>{{ $t->reference }}</td>
                            <td>{{ number_format($t->debit,2) }}</td>
                            <td>{{ number_format($t->credit,2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

        </div>
    </div>

</div>
@endsection
