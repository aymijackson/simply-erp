@extends('layouts.guest')

@section('title', 'Sign in')

@section('content')

    <h5 class="text-center mb-1 fw-bold">Welcome back</h5>
    <p class="text-center text-muted mb-4" style="font-size: var(--fs-small);">Sign in to {{ config('app.name') }}</p>

    {{-- Status Message --}}
    @if (session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif

    {{-- Login Form --}}
    <form method="POST" action="{{ route('login.store') }}" autocomplete="on" novalidate>
        @csrf

        {{-- Email --}}
        <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <input
                type="email"
                id="email"
                name="email"
                value="{{ old('email') }}"
                class="form-control @error('email') is-invalid @enderror"
                required
                autofocus
            >
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- Password --}}
        <div class="mb-3">
            <div class="d-flex align-items-center justify-content-between">
                <label for="password" class="form-label mb-0">Password</label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-decoration-none" style="font-size: var(--fs-small);">
                        Forgot password?
                    </a>
                @endif
            </div>
            <input
                type="password"
                id="password"
                name="password"
                class="form-control @error('password') is-invalid @enderror"
                required
            >
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- Platform --}}
        <div class="mb-3">
            <label for="platform" class="form-label">Login to</label>
            <select
                id="platform"
                name="platform"
                class="form-select @error('platform') is-invalid @enderror"
                required
            >
                <option value="erp" {{ old('platform', 'erp') === 'erp' ? 'selected' : '' }}>
                    ERP
                </option>
                <option value="admin" {{ old('platform') === 'admin' ? 'selected' : '' }}>
                    Admin Platform
                </option>
            </select>
            @error('platform')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- Remember Me --}}
        <div class="mb-4 form-check">
            <input
                type="checkbox"
                class="form-check-input"
                id="remember"
                name="remember"
                {{ old('remember') ? 'checked' : '' }}
            >
            <label class="form-check-label" for="remember">
                Remember me
            </label>
        </div>

        {{-- Submit --}}
        <div class="d-grid">
            <button type="submit" class="btn btn-primary py-2">
                Sign in
            </button>
        </div>
    </form>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Prevent double-submit on slow networks
    const __loginForm = document.querySelector('form[method="POST"]');
    if (__loginForm) {
        __loginForm.addEventListener('submit', () => {
            const btn = __loginForm.querySelector('button[type="submit"]');
            if (btn) { btn.disabled = true; btn.classList.add('disabled'); }
        });
    }
</script>
@endpush
