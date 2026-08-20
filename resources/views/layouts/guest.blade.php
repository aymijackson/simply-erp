<!DOCTYPE html>
<html lang="en" data-accent="indigo" data-sidebar="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sign in') | {{ config('app.name') }}</title>

    {{-- Prevent browsers from re-submitting cached POST pages (helps with "page expired" on back nav) --}}
    <meta http-equiv="Cache-Control" content="no-store">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito:400,600,700,800">
    <link rel="stylesheet" href="{{ asset('assets/vendor/fontawesome-free/css/all.min.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/theme.css') }}">

    <style>
        body.guest-body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--surface-page);
            padding: 1.5rem;
        }
        .guest-card {
            width: 100%;
            max-width: 26rem;
        }
        .guest-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .6rem;
            margin-bottom: 1.75rem;
        }
        .guest-brand .sidebar-brand-badge {
            width: 2.5rem;
            height: 2.5rem;
            font-size: 1.05rem;
        }
        .guest-brand-text {
            font-weight: 800;
            font-size: 1.15rem;
            color: var(--text-primary);
        }
    </style>

    @stack('styles')
</head>
<body class="guest-body">

<div class="guest-card">
    @php
        $__appName = config('app.name', 'Simply-ERP');
        $__appInitials = strtoupper(collect(explode(' ', trim(str_replace(['-', '_'], ' ', $__appName))))
            ->filter()->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode(''));
    @endphp
    <div class="guest-brand">
        <div class="sidebar-brand-badge">{{ $__appInitials ?: 'E' }}</div>
        <div class="guest-brand-text">{{ $__appName }}</div>
    </div>

    <div class="card shadow-lg">
        <div class="card-body p-4 p-md-5">
            @yield('content')
        </div>
    </div>

    <p class="text-center text-muted mt-4" style="font-size: var(--fs-small);">
        &copy; {{ date('Y') }} {{ $__appName }}
    </p>
</div>

@stack('scripts')
</body>
</html>
