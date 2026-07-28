@extends('layouts.master')

@section('title', 'AR Ageing')

@section('content')
<div class="container-fluid">

    <h1 class="h3 mb-4 text-primary">Accounts Receivable Ageing</h1>

    <div class="card shadow">
        <div class="card-body table-responsive">

            <table class="table table-bordered table-striped">
                <thead class="table-light">
                    <tr>
                        <th>Customer</th>
                        <th>0–30</th>
                        <th>31–60</th>
                        <th>61–90</th>
                        <th>90+</th>
                        <th>Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        <tr>
                            <td>{{ $row->customer_name }}</td>
                            <td>{{ number_format($row->bucket_0_30,2) }}</td>
                            <td>{{ number_format($row->bucket_31_60,2) }}</td>
                            <td>{{ number_format($row->bucket_61_90,2) }}</td>
                            <td>{{ number_format($row->bucket_90_plus,2) }}</td>
                            <td class="fw-bold">
                                {{ number_format($row->total_outstanding,2) }}
                            </td>
                            <td>
                                <a href="{{ route('admin.finance.customer_statements.index', ['customer_id' => $row->customer_id]) }}"
                                   class="btn btn-sm btn-primary">
                                   Statement
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

        </div>
    </div>

</div>
@endsection
