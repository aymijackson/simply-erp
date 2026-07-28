@extends('layouts.master')

@section('title','Management Control Center')

@section('content')

<div class="container-fluid">

<h1 class="h3 mb-4 text-primary">
Enterprise Management Control Center
</h1>

<div class="row g-4">

{{-- FINANCE --}}
<div class="col-lg-4">
<div class="card shadow-sm">
<div class="card-header fw-bold">Financial Health</div>
<div class="card-body">

<div class="d-flex justify-content-between mb-2">
<span>Cash Balance</span>
<strong>{{ number_format($finance['cash'],2) }}</strong>
</div>

<div class="d-flex justify-content-between mb-2">
<span>Receivables</span>
<strong>{{ number_format($finance['receivables'],2) }}</strong>
</div>

<div class="d-flex justify-content-between">
<span>Payables</span>
<strong>{{ number_format($finance['payables'],2) }}</strong>
</div>

</div>
</div>
</div>

{{-- SALES --}}
<div class="col-lg-4">
<div class="card shadow-sm">
<div class="card-header fw-bold">Sales Pipeline</div>
<div class="card-body">

<div class="d-flex justify-content-between mb-2">
<span>Leads</span>
<strong>{{ $sales['leads'] }}</strong>
</div>

<div class="d-flex justify-content-between">
<span>Opportunities</span>
<strong>{{ $sales['opportunities'] }}</strong>
</div>

</div>
</div>
</div>

{{-- PROJECTS --}}
<div class="col-lg-4">
<div class="card shadow-sm">
<div class="card-header fw-bold">Projects</div>
<div class="card-body">

<div class="d-flex justify-content-between mb-2">
<span>Active Projects</span>
<strong>{{ $projects['active'] }}</strong>
</div>

<div class="d-flex justify-content-between">
<span>Late Milestones</span>
<strong class="text-danger">
{{ $projects['late_milestones'] }}
</strong>
</div>

</div>
</div>
</div>

{{-- OPERATIONS --}}
<div class="col-lg-4">
<div class="card shadow-sm">
<div class="card-header fw-bold">Operations</div>
<div class="card-body">

<div class="d-flex justify-content-between mb-2">
<span>Open Work Orders</span>
<strong>{{ $operations['open_work_orders'] }}</strong>
</div>

<div class="d-flex justify-content-between">
<span>Low Stock</span>
<strong class="text-warning">
{{ $operations['low_stock'] }}
</strong>
</div>

</div>
</div>
</div>

{{-- SUPPORT --}}
<div class="col-lg-4">
<div class="card shadow-sm">
<div class="card-header fw-bold">Support</div>
<div class="card-body">

<div class="d-flex justify-content-between">
<span>Open Tickets</span>
<strong>{{ $support['open_tickets'] }}</strong>
</div>

</div>
</div>
</div>

{{-- ALERTS --}}
<div class="col-lg-4">
<div class="card shadow-sm">
<div class="card-header fw-bold text-danger">
Operational Alerts
</div>

<div class="card-body">

<div class="d-flex justify-content-between mb-2">
<span>Overdue Invoices</span>
<strong class="text-danger">
{{ $alerts['overdue_invoices'] }}
</strong>
</div>

<div class="d-flex justify-content-between">
<span>Overdue Bills</span>
<strong class="text-danger">
{{ $alerts['overdue_bills'] }}
</strong>
</div>

</div>
</div>
</div>

</div>

</div>

@endsection