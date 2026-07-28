<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login | {{ config('app.name') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Bootstrap 5 CSS --}}
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous"
    >
</head>
<body class="bg-light">

<div class="container">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-md-5 col-lg-4">

            <div class="card shadow-sm">
                <div class="card-body p-4">

                    <h4 class="text-center mb-4">
                        {{ config('app.name') }} Login
                    </h4>

                    {{-- Status Message --}}
                    @if (session('status'))
                        <div class="alert alert-success">
                            {{ session('status') }}
                        </div>
                    @endif

                    {{-- Login Form --}}
                    <form method="POST" action="{{ route('login.store') }}">
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
                            <label for="password" class="form-label">Password</label>
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
                        <div class="mb-3 form-check">
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
                            <button type="submit" class="btn btn-primary">
                                Log in
                            </button>
                        </div>
                    </form>

                    {{-- Forgot password --}}
                    @if (Route::has('password.request'))
                        <div class="text-center mt-3">
                            <a href="{{ route('password.request') }}" class="text-decoration-none">
                                Forgot your password?
                            </a>
                        </div>
                    @endif

                </div>
            </div>

        </div>
    </div>
</div>

{{-- Bootstrap JS (optional, but safe) --}}
<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
    crossorigin="anonymous"
></script>

</body>
</html>
