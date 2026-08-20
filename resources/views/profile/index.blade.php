@extends('layouts.master')

@section('title', 'Profile')

@section('content')
<div class="container-fluid">

    <div class="mb-3">
        <h1 class="h3 mb-0">My Profile</h1>
        <p class="text-muted mb-0">Account details and appearance preferences</p>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3">Account</h6>
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted">Name</span><b>{{ $user->name }}</b></div>
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted">Email</span><b>{{ $user->email }}</b></div>
                    <div class="d-flex justify-content-between"><span class="text-muted">Role(s)</span><b>{{ $user->getRoleNames()->implode(', ') ?: '-' }}</b></div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h6 class="fw-semibold mb-1">Appearance</h6>
                    <p class="text-muted small mb-3">Changes apply instantly and are saved automatically.</p>

                    @include('partials.theme-picker', [
                        'mode'     => $user->theme_mode,
                        'accent'   => $user->theme_accent ?? $user->company?->theme_accent ?? 'indigo',
                        'sidebar'  => $user->theme_sidebar ?? $user->company?->theme_sidebar ?? 'dark',
                        'idSuffix' => 'profile',
                        'autosave' => true,
                        'saveUrl'  => route('admin.profile.theme'),
                    ])
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
