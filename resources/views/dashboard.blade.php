@extends('layouts.master')

@section('title', 'Inventory Dashboard')

@section('content')
<div id="content">
    @include('partials.topbar') <!-- Consider extracting Topbar into a partial -->

    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
            <a href="#" class="btn btn-sm btn-primary shadow-sm"><i class="fas fa-download fa-sm text-white-50"></i> Generate Report</a>
        </div>

        @include('partials.dashboard.cards-summary') <!-- Extract card blocks into partials -->
        @include('partials.dashboard.charts') <!-- Extract charts into partials -->
        @include('partials.dashboard.projects') <!-- Extract project section into partials -->
    </div>
</div>
@endsection
