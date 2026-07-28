@extends('layouts.master')
@section('title','Driver Deliveries')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">Driver Deliveries</h1>
            <p class="text-muted mb-0">Confirm actual delivered quantities</p>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-header bg-white">
            <h6 class="mb-0 text-primary"><i class="fas fa-truck mr-1"></i> Delivery Notes</h6>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="bg-light">
                    <tr>
                        <th>#</th>
                        <th>Customer</th>
                        <th>Ship Date</th>
                        <th>Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($deliveries as $d)
                    <tr>
                        <td>{{ $d->id }}</td>
                        <td>{{ $d->customer->name ?? '—' }}</td>
                        <td>{{ optional($d->ship_date)->format('d M Y') }}</td>
                        <td><span class="badge badge-info text-uppercase">{{ $d->status ?? 'draft' }}</span></td>
                        <td class="text-center">
                            <a class="btn btn-sm btn-primary" href="{{ route('admin.sales.deliveries.driver.show', $d->id) }}">
                                Confirm Delivery
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted">No deliveries found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
